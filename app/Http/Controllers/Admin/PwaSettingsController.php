<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\PwaSetting;

class PwaSettingsController extends Controller
{
    public function edit()
    {
        $settings = PwaSetting::firstOrCreate([], [
            'app_name' => 'Loyalty App',
            'primary_color' => '#4f46e5',
            'background_color' => '#f3f4f6',
            'text_color' => '#111827',
        ]);

        return Inertia::render('Admin/PWA/Editor', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $settings = PwaSetting::firstOrCreate([]);

        $validated = $request->validate([
            'app_name' => 'required|string|max:255',
            'primary_color' => 'required|string|max:20',
            'background_color' => 'required|string|max:20',
            'text_color' => 'required|string|max:20',
            'logo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            if ($settings->logo_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($settings->logo_path);
            }
            $path = $request->file('logo')->store('pwa', 'public');
            $settings->logo_path = $path;
        }

        $settings->fill([
            'app_name' => $validated['app_name'],
            'primary_color' => $validated['primary_color'],
            'background_color' => $validated['background_color'],
            'text_color' => $validated['text_color'],
        ]);
        
        $settings->save();

        return redirect()->back()->with('success', 'Impostazioni PWA aggiornate.');
    }
}
