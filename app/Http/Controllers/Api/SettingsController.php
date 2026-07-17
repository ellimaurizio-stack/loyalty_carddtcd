<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyProgram;

class SettingsController extends Controller
{
    public function index(\App\Models\Store $store)
    {
        $program = LoyaltyProgram::withoutGlobalScopes()
            ->where('brand_id', $store->brand_id)
            ->where('is_active', true)
            ->first();

        if (!$program) {
            return response()->json([
                'success' => false,
                'message' => 'No active loyalty program found',
            ], 404);
        }

        $program->load('disclaimers');

        return response()->json([
            'success' => true,
            'settings' => [
                'form_fields' => $program->form_fields ?? [],
                'disclaimers' => $program->disclaimers->map(function ($d) {
                    return [
                        'id' => $d->id,
                        'text' => $d->text,
                        'is_mandatory' => $d->is_mandatory,
                        'pdf_url' => $d->pdf_path ? asset('storage/' . $d->pdf_path) : null,
                    ];
                }),
                'background_color' => $program->background_color ?? '#ffffff',
                'primary_color' => $program->primary_color ?? '#3f51b5',
                'otp_channel' => $program->otp_channel ?? 'phone',
                'otp_channel_label' => $program->otp_channel_label ?? 'Phone Number',
                'text_color' => $program->text_color ?? '#000000',
                'translations' => $program->translations ?? [
                    'page_title' => 'Join our Loyalty Program!',
                    'intro_text' => 'We noticed you shop here often. Join our loyalty program to earn rewards!',
                    'button_text' => 'Send OTP Code'
                ],
                'logo_url' => $program->logo_path ? asset('storage/' . $program->logo_path) : null,
                'background_image_url' => $program->background_image_path ? asset('storage/' . $program->background_image_path) : null,
            ]
        ]);
    }
}
