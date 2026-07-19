<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LoyaltyProgram;
use App\Models\Brand;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    private function resolveContext(Request $request)
    {
        $user = auth()->user();
        
        if ($user->role === 'store_manager') {
            return [
                'brand_id' => $user->brand_id,
                'store_id' => $user->store_id,
            ];
        }

        $brandId = $user->role === 'brand_manager' ? $user->brand_id : $request->input('brand_id');
        if (!$brandId && $user->role === 'super_admin') {
            $brandId = Brand::first()->id ?? null;
        }

        $storeId = $request->input('store_id');
        if ($storeId === 'null' || $storeId === '') {
            $storeId = null;
        }

        return [
            'brand_id' => $brandId,
            'store_id' => $storeId,
        ];
    }

    public function edit(Request $request)
    {
        $context = $this->resolveContext($request);
        $brandId = $context['brand_id'];
        $storeId = $context['store_id'];

        $query = LoyaltyProgram::withoutGlobalScopes()->where('brand_id', $brandId);
        if ($storeId) {
            $query->where('store_id', $storeId);
        } else {
            $query->whereNull('store_id');
        }

        $program = $query->with('disclaimers')->firstOrCreate(
            ['brand_id' => $brandId, 'store_id' => $storeId, 'is_active' => true],
            [
                'name' => 'Default Program',
                'purchases_threshold' => 2,
                'form_fields' => [],
                'otp_channel' => 'phone',
                'otp_channel_label' => 'Phone Number',
                'text_color' => '#000000',
                'translations' => [
                    'page_title' => 'Join our Loyalty Program!',
                    'intro_text' => 'We noticed you shop here often. Join our loyalty program to earn rewards!',
                    'button_text' => 'Send OTP Code'
                ]
            ]
        );

        $brands = auth()->user()->role === 'super_admin' ? Brand::all(['id', 'name']) : [];
        $stores = $brandId ? \App\Models\Store::where('brand_id', $brandId)->get(['id', 'name']) : [];

        return Inertia::render('Admin/Settings/Edit', [
            'program' => $program,
            'brands' => $brands,
            'stores' => $stores,
            'currentBrandId' => $brandId,
            'currentStoreId' => $storeId,
        ]);
    }

    public function update(Request $request)
    {
        $context = $this->resolveContext($request);
        $brandId = $context['brand_id'];
        $storeId = $context['store_id'];

        $query = LoyaltyProgram::withoutGlobalScopes()->where('brand_id', $brandId);
        if ($storeId) {
            $query->where('store_id', $storeId);
        } else {
            $query->whereNull('store_id');
        }

        $program = $query->firstOrCreate(
            ['brand_id' => $brandId, 'store_id' => $storeId, 'is_active' => true],
            ['name' => 'Default Program']
        );

        // form_fields is a JSON string from FormData
        $formFields = json_decode($request->input('form_fields', '[]'), true) ?? [];

        // Update basic program settings
        $program->update([
            'background_color' => $request->input('background_color', '#ffffff'),
            'primary_color' => $request->input('primary_color', '#3f51b5'),
            'form_fields' => $formFields,
            'otp_channel' => $request->input('otp_channel', 'phone'),
            'otp_channel_label' => $request->input('otp_channel_label', 'Phone Number'),
            'text_color' => $request->input('text_color', '#000000'),
            'translations' => json_decode($request->input('translations', '{}'), true) ?? []
        ]);

        // Handle Logo
        if ($request->hasFile('logo')) {
            if ($program->logo_path) {
                Storage::disk('public')->delete($program->logo_path);
            }
            $program->logo_path = $request->file('logo')->store('logos', 'public');
            $program->save();
        }

        // Handle Background Image
        if ($request->hasFile('background_image')) {
            if ($program->background_image_path) {
                Storage::disk('public')->delete($program->background_image_path);
            }
            $program->background_image_path = $request->file('background_image')->store('backgrounds', 'public');
            $program->save();
        }

        // Handle Disclaimers
        // We will receive disclaimers as JSON to know which to keep/delete/create
        $disclaimersData = json_decode($request->input('disclaimers', '[]'), true) ?? [];
        
        $existingDisclaimerIds = $program->disclaimers->pluck('id')->toArray();
        $receivedDisclaimerIds = collect($disclaimersData)->pluck('id')->filter()->toArray();

        // Delete removed disclaimers
        $toDelete = array_diff($existingDisclaimerIds, $receivedDisclaimerIds);
        foreach ($program->disclaimers->whereIn('id', $toDelete) as $disclaimer) {
            if ($disclaimer->pdf_path) {
                Storage::disk('public')->delete($disclaimer->pdf_path);
            }
            $disclaimer->delete();
        }

        // Update or create disclaimers
        foreach ($disclaimersData as $index => $disclaimerData) {
            $disclaimer = $program->disclaimers()->updateOrCreate(
                ['id' => $disclaimerData['id'] ?? null],
                [
                    'text' => $disclaimerData['text'] ?? '',
                    'is_mandatory' => filter_var($disclaimerData['is_mandatory'] ?? true, FILTER_VALIDATE_BOOLEAN)
                ]
            );

            // Handle PDF upload for this disclaimer
            $pdfFileKey = "disclaimer_pdf_{$index}";
            if ($request->hasFile($pdfFileKey)) {
                if ($disclaimer->pdf_path) {
                    Storage::disk('public')->delete($disclaimer->pdf_path);
                }
                $disclaimer->pdf_path = $request->file($pdfFileKey)->store('disclaimers', 'public');
                $disclaimer->save();
            }
        }

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }
}
