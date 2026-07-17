<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\AppSetting;
use App\Models\Brand;

class AppSettingController extends Controller
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

        $settings = AppSetting::withoutGlobalScopes()->firstOrCreate(
            ['brand_id' => $brandId],
            [
                'bg_color' => '#FFFFFF',
                'header_color' => '#3F51B5',
                'header_text' => 'Cassa Rapida',
                'header_text_color' => '#FFFFFF',
                'pay_btn_color' => '#4CAF50',
                'pay_btn_text' => 'Paga con NFC',
                'pay_btn_text_color' => '#FFFFFF',
                'cart_icon_color' => '#42A5F5',
            ]
        );

        $brands = auth()->user()->role === 'super_admin' ? Brand::all(['id', 'name']) : [];

        return Inertia::render('Admin/AppSettings/Edit', [
            'settings' => $settings,
            'brands' => $brands,
            'currentBrandId' => $brandId,
        ]);
    }

    public function update(Request $request)
    {
        $brandId = $this->resolveBrandId($request);

        $validated = $request->validate([
            'bg_color' => 'required|string',
            'header_color' => 'required|string',
            'header_text' => 'required|string|max:255',
            'header_text_color' => 'required|string',
            'pay_btn_color' => 'required|string',
            'pay_btn_text' => 'required|string|max:255',
            'pay_btn_text_color' => 'required|string',
            'cart_icon_color' => 'required|string',
            'logo' => 'nullable|image|max:2048',
            'background_image' => 'nullable|image|max:4096',
        ]);

        $settings = AppSetting::withoutGlobalScopes()->firstOrCreate(['brand_id' => $brandId]);

        $settings->fill([
            'bg_color' => $validated['bg_color'],
            'header_color' => $validated['header_color'],
            'header_text' => $validated['header_text'],
            'header_text_color' => $validated['header_text_color'],
            'pay_btn_color' => $validated['pay_btn_color'],
            'pay_btn_text' => $validated['pay_btn_text'],
            'pay_btn_text_color' => $validated['pay_btn_text_color'],
            'cart_icon_color' => $validated['cart_icon_color'],
        ]);

        if ($request->hasFile('logo')) {
            if ($settings->logo_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($settings->logo_path);
            }
            $settings->logo_path = $request->file('logo')->store('logos', 'public');
        }

        if ($request->hasFile('background_image')) {
            if ($settings->background_image_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($settings->background_image_path);
            }
            $settings->background_image_path = $request->file('background_image')->store('backgrounds', 'public');
        }

        $settings->save();

        return redirect()->back()->with('success', 'Impostazioni aggiornate con successo.');
    }
}
