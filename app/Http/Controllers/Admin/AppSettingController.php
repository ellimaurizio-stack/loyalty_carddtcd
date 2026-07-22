<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\AppSetting;
use App\Models\Brand;

class AppSettingController extends Controller
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

        // Get the specific setting (store level) or default (brand level)
        $query = AppSetting::withoutGlobalScopes()->where('brand_id', $brandId);
        
        if ($storeId) {
            $query->where('store_id', $storeId);
        } else {
            $query->whereNull('store_id');
        }

        $settings = $query->first();

        // Se stiamo guardando uno store e non ha personalizzazioni, proviamo a prendere quelle del brand
        if (!$settings && $storeId) {
            $settings = AppSetting::withoutGlobalScopes()
                ->where('brand_id', $brandId)
                ->whereNull('store_id')
                ->first();
        }

        // Se ancora non c'è nulla, creiamo un modello in memoria con i default
        if (!$settings) {
            $settings = new AppSetting([
                'brand_id' => $brandId,
                'store_id' => $storeId,
                'bg_color' => '#FFFFFF',
                'header_color' => '#3F51B5',
                'header_text' => 'Cassa Rapida',
                'header_text_color' => '#FFFFFF',
                'pay_btn_color' => '#4CAF50',
                'pay_btn_text' => 'Paga con NFC',
                'pay_btn_text_color' => '#FFFFFF',
                'cart_icon_color' => '#42A5F5',
            ]);
        }

        $brands = auth()->user()->role === 'super_admin' ? Brand::all(['id', 'name']) : [];
        $stores = $brandId ? \App\Models\Store::where('brand_id', $brandId)->get(['id', 'name']) : [];

        return Inertia::render('Admin/AppSettings/Edit', [
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

        $query = AppSetting::withoutGlobalScopes()->where('brand_id', $brandId);
        if ($storeId) {
            $query->where('store_id', $storeId);
        } else {
            $query->whereNull('store_id');
        }

        $settings = $query->firstOrCreate(['brand_id' => $brandId, 'store_id' => $storeId]);

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
