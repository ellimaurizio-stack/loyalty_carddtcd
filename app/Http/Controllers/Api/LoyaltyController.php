<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Otp;
use App\Contracts\OtpProviderInterface;
use Illuminate\Support\Str;
use Carbon\Carbon;

class LoyaltyController extends Controller
{
    public function enroll(Request $request, Store $store, OtpProviderInterface $otpProvider)
    {
        $program = \App\Models\LoyaltyProgram::where('brand_id', $store->brand_id)->where('is_active', true)->first();
        $otpChannel = $program ? ($program->otp_channel ?? 'phone') : 'phone';

        // First validate standard fields
        $rules = [
            'card_identifier' => 'required|string',
        ];
        
        if ($otpChannel === 'email') {
            $rules['email'] = 'required|email|max:255';
        } else {
            $rules['phone'] = 'required|string|max:50';
        }

        $validated = $request->validate($rules);

        $customer = Customer::where('card_identifier', $validated['card_identifier'])->first();

        // If customer doesn't exist, create an empty one
        if (!$customer) {
            $customer = Customer::create([
                'card_identifier' => $validated['card_identifier'],
                'brand_id' => $store->brand_id,
                'registration_store_id' => $store->id,
            ]);
        }

        $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        Otp::create([
            'customer_id' => $customer->id,
            'code' => $code,
            'expires_at' => Carbon::now()->addMinutes(10),
        ]);

        $contactValue = $otpChannel === 'email' ? $validated['email'] : $validated['phone'];
        $otpProvider->send($contactValue, $code);

        return response()->json([
            'success' => true,
            'test_otp' => $code
        ]);
    }

    public function verify(Request $request, Store $store)
    {
        $program = \App\Models\LoyaltyProgram::where('brand_id', $store->brand_id)->where('is_active', true)->first();
        $otpChannel = $program ? ($program->otp_channel ?? 'phone') : 'phone';

        $baseRules = [
            'card_identifier' => 'required|string|exists:customers,card_identifier',
            'otp_code' => 'required|string',
        ];

        if ($otpChannel === 'email') {
            $baseRules['email'] = 'required|email|max:255';
        } else {
            $baseRules['phone'] = 'required|string|max:50';
        }

        // Dynamic rules from active program
        $dynamicRules = [];
        
        if ($program && $program->form_fields) {
            foreach ($program->form_fields as $field) {
                $rules = [];
                if ($field['required']) {
                    $rules[] = 'required';
                } else {
                    $rules[] = 'nullable';
                }
                
                if ($field['type'] === 'email') $rules[] = 'email';
                if ($field['type'] === 'number') $rules[] = 'numeric';
                if ($field['type'] === 'date') $rules[] = 'date';
                
                $dynamicRules[$field['name']] = implode('|', $rules);
            }
        }

        $validated = $request->validate(array_merge($baseRules, $dynamicRules));

        $customer = Customer::where('card_identifier', $validated['card_identifier'])->firstOrFail();

        $otp = Otp::where('customer_id', $customer->id)
                  ->where('code', $validated['otp_code'])
                  ->where('is_used', false)
                  ->where('expires_at', '>', Carbon::now())
                  ->first();

        if (!$otp) {
            return response()->json(['error' => 'Invalid or expired OTP'], 400);
        }

        $otp->update(['is_used' => true]);

        // Extract dynamic data
        $dynamicData = [];
        if ($program && $program->form_fields) {
            foreach ($program->form_fields as $field) {
                if (isset($validated[$field['name']])) {
                    $dynamicData[$field['name']] = $validated[$field['name']];
                }
            }
        }

        $updateData = [
            'customer_data' => empty($dynamicData) ? null : collect($dynamicData)->merge($customer->customer_data ?? [])->toArray(),
            'loyalty_points' => 10, // Initial bonus
        ];

        if ($otpChannel === 'email') {
            $updateData['email'] = $validated['email'];
        } else {
            $updateData['phone'] = $validated['phone'];
        }

        $customer->update($updateData);

        return response()->json(['success' => true]);
    }
}
