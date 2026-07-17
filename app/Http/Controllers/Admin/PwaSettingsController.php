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
    private function resolveBrandId(Request $request)
    {
        $user = auth()->user();
        if ($user->role === 'brand_manager') {
            return $user->brand_id;
        }
        $brandId = $request->query('brand_id') ?? $request->input('brand_id');
        if (!$brandId) {
            $brandId = Brand::first()->id ?? null;
        }
        return $brandId;
    }

    public function edit(Request $request)
    {
        $brandId = $this->resolveBrandId($request);

        $settings = PwaSetting::withoutGlobalScopes()->firstOrCreate(
            ['brand_id' => $brandId],
            [
                'app_name' => 'Loyalty App',
                'primary_color' => '#4f46e5',
                'background_color' => '#f3f4f6',
                'text_color' => '#111827',
                'registration_fields' => [
                    'name' => ['enabled' => true, 'required' => true],
                    'phone' => ['enabled' => false, 'required' => false],
                ],
                'privacy_policy' => 'Accetto i termini e le condizioni d\'uso.',
            ]
        );

        $brands = auth()->user()->role === 'super_admin' ? Brand::all(['id', 'name']) : [];

        return Inertia::render('Admin/PWA/Editor', [
            'settings' => $settings,
            'brands' => $brands,
            'currentBrandId' => $brandId,
        ]);
    }

    public function update(Request $request)
    {
        $brandId = $this->resolveBrandId($request);

        $validated = $request->validate([
            'app_name' => 'required|string|max:255',
            'primary_color' => 'required|string',
            'background_color' => 'required|string',
            'text_color' => 'required|string',
            'card_color' => 'nullable|string',
            'card_text_color' => 'nullable|string',
            'logo' => 'nullable|image|max:2048',
            'background_image' => 'nullable|image|max:4096',
        ]);

        $settings = PwaSetting::withoutGlobalScopes()->firstOrCreate(['brand_id' => $brandId]);

        if ($request->hasFile('logo')) {
            if ($settings->logo_path) {
                Storage::disk('public')->delete($settings->logo_path);
            }
            $path = $request->file('logo')->store('pwa', 'public');
            $settings->logo_path = $path;
        }

        if ($request->hasFile('background_image')) {
            if ($settings->background_image_path) {
                Storage::disk('public')->delete($settings->background_image_path);
            }
            $bgPath = $request->file('background_image')->store('pwa/backgrounds', 'public');
            $settings->background_image_path = $bgPath;
        }

        $settings->update([
            'app_name' => $validated['app_name'],
            'primary_color' => $validated['primary_color'],
            'background_color' => $validated['background_color'],
            'text_color' => $validated['text_color'],
            'card_color' => $validated['card_color'] ?? '#4f46e5',
            'card_text_color' => $validated['card_text_color'] ?? '#ffffff',
        ]);

        return redirect()->back()->with('success', 'Impostazioni aggiornate con successo.');
    }
}
