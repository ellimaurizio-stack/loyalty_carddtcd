<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\PwaSetting;
use App\Models\Brand;
use Illuminate\Support\Facades\Storage;

class PwaSettingsController extends Controller
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

        $brandId = $user->role === 'brand_manager' ? $user->brand_id : ($request->query('brand_id') ?? $request->input('brand_id'));
        if (!$brandId && $user->role === 'super_admin') {
            $brandId = Brand::first()->id ?? null;
        }

        $storeId = $request->query('store_id') ?? $request->input('store_id');

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

        $query = PwaSetting::withoutGlobalScopes()->where('brand_id', $brandId);
        
        if ($storeId) {
            $query->where('store_id', $storeId);
        } else {
            $query->whereNull('store_id');
        }

        $settings = $query->firstOrCreate(
            ['brand_id' => $brandId, 'store_id' => $storeId],
            [
                'app_name' => 'Fidelity App',
                'primary_color' => '#3F51B5',
                'background_color' => '#F3F4F6',
                'text_color' => '#1F2937',
                'card_color' => '#FFFFFF',
                'card_text_color' => '#1F2937',
                'registration_fields' => ['name', 'email', 'phone'],
                'privacy_policy' => 'Accetto i termini e le condizioni d\'uso.',
            ]
        );

        $brands = auth()->user()->role === 'super_admin' ? Brand::all(['id', 'name']) : [];
        $stores = $brandId ? \App\Models\Store::where('brand_id', $brandId)->get(['id', 'name']) : [];

        return Inertia::render('Admin/PWA/Editor', [
            'settings' => $settings,
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

        $validated = $request->validate([
            'app_name' => 'required|string|max:255',
            'primary_color' => 'required|string',
            'background_color' => 'required|string',
            'text_color' => 'required|string',
            'card_color' => 'required|string',
            'card_text_color' => 'required|string',
            'registration_fields' => 'required|array',
            'privacy_policy' => 'nullable|string',
            'logo' => 'nullable|image|max:2048',
            'background_image' => 'nullable|image|max:4096',
        ]);

        $query = PwaSetting::withoutGlobalScopes()->where('brand_id', $brandId);
        if ($storeId) {
            $query->where('store_id', $storeId);
        } else {
            $query->whereNull('store_id');
        }

        $settings = $query->firstOrCreate(['brand_id' => $brandId, 'store_id' => $storeId]);

        $settings->fill([
            'app_name' => $validated['app_name'],
            'primary_color' => $validated['primary_color'],
            'background_color' => $validated['background_color'],
            'text_color' => $validated['text_color'],
            'card_color' => $validated['card_color'] ?? '#4f46e5',
            'card_text_color' => $validated['card_text_color'] ?? '#ffffff',
            'registration_fields' => $validated['registration_fields'],
            'privacy_policy' => $validated['privacy_policy'],
        ]);

        if ($request->hasFile('logo')) {
            if ($settings->logo_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($settings->logo_path);
            }
            $settings->logo_path = $request->file('logo')->store('pwa/logos', 'public');
        }

        if ($request->hasFile('background_image')) {
            if ($settings->background_image_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($settings->background_image_path);
            }
            $settings->background_image_path = $request->file('background_image')->store('pwa/backgrounds', 'public');
        }

        $settings->save();

        return redirect()->back()->with('success', 'Impostazioni aggiornate con successo.');
    }
}
