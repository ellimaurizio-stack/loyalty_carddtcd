<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AppSettingController extends Controller
{
    public function edit()
    {
        $settings = AppSetting::first() ?? new AppSetting();
        return Inertia::render('Admin/AppSettings/Edit', [
            'settings' => $settings
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'bg_color' => 'required|string',
            'header_color' => 'required|string',
            'header_text' => 'required|string',
            'header_text_color' => 'required|string',
            'pay_btn_color' => 'required|string',
            'pay_btn_text' => 'required|string',
            'pay_btn_text_color' => 'required|string',
            'cart_icon_color' => 'required|string',
            'logo' => 'nullable|image|max:2048',
            'background_image' => 'nullable|image|max:4096',
        ]);

        $settings = AppSetting::first() ?? new AppSetting();
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
