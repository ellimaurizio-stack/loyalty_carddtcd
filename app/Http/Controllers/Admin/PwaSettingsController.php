<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\PwaSetting;
use Illuminate\Support\Facades\Storage;

class PwaSettingsController extends Controller
{
    public function edit()
    {
        $settings = PwaSetting::firstOrCreate([], [
            'app_name' => 'Loyalty App',
            'primary_color' => '#4f46e5',
            'background_color' => '#f3f4f6',
            'text_color' => '#111827',
            'registration_fields' => [
                'name' => ['enabled' => true, 'required' => true],
                'phone' => ['enabled' => false, 'required' => false],
            ],
            'privacy_policy' => 'Accetto i termini e le condizioni d\'uso.',
        ]);

        return Inertia::render('Admin/PWA/Editor', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'app_name' => 'required|string|max:255',
            'primary_color' => 'required|string',
            'background_color' => 'required|string',
            'text_color' => 'required|string',
            'logo' => 'nullable|image|max:2048',
            'registration_fields' => 'nullable|array',
            'privacy_policy' => 'nullable|string',
        ]);

        $settings = PwaSetting::first();

        if ($request->hasFile('logo')) {
            if ($settings->logo_path) {
                Storage::disk('public')->delete($settings->logo_path);
            }
            $path = $request->file('logo')->store('pwa', 'public');
            $settings->logo_path = $path;
        }

        $settings->update([
            'app_name' => $validated['app_name'],
            'primary_color' => $validated['primary_color'],
            'background_color' => $validated['background_color'],
            'text_color' => $validated['text_color'],
            'registration_fields' => $validated['registration_fields'] ?? $settings->registration_fields,
            'privacy_policy' => $validated['privacy_policy'] ?? $settings->privacy_policy,
        ]);

        return redirect()->back()->with('success', 'Impostazioni aggiornate con successo.');
    }
}
