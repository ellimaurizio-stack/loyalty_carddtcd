<?php

namespace App\Http\Controllers\Pwa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use App\Models\Customer;
use App\Models\PwaSetting;
use App\Models\PromotionalRule;

class CustomerPortalController extends Controller
{
    public function showLogin()
    public function showLogin(Store $store)
    {
        return inertia('PWA/Auth/Login', [
            'store' => $store,
            'pwaSettings' => PwaSetting::where('brand_id', $store->brand_id)->first(),
        ]);
    }

    public function login(Request $request, Store $store)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $customer = Customer::where('email', $request->email)->first();

        if ($customer && Hash::check($request->password, $customer->password)) {
            Auth::guard('customer')->login($customer);
            return redirect()->route('pwa.dashboard', ['store' => $store->slug]);
        }

        return back()->withErrors([
            'email' => 'Le credenziali fornite non sono corrette.',
        ]);
    }

    public function showRegister(Store $store)
    {
        // Load loyalty program configuration for this brand
        $loyaltyProgram = LoyaltyProgram::where('brand_id', $store->brand_id)->first();
        // Disclaimers for this brand
        $disclaimers = Disclaimer::where('brand_id', $store->brand_id)->where('is_active', true)->get();

        return inertia('PWA/Auth/Register', [
            'store' => $store,
            'loyaltyProgram' => $loyaltyProgram,
            'disclaimers' => $disclaimers,
            'pwaSettings' => PwaSetting::where('brand_id', $store->brand_id)->first(),
        ]);
    }

    public function register(Request $request, Store $store)
    {
        $program = \App\Models\LoyaltyProgram::where('brand_id', $store->brand_id)->with('disclaimers')->first();
        $formFields = $program ? ($program->form_fields ?? []) : [];
        $disclaimers = $program ? ($program->disclaimers ?? []) : [];

        // Validate basic fields
        $rules = [
            'email' => ['required', 'string', 'email', 'max:255', 'unique:customers'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];

        // Add dynamic validation rules based on formFields
        foreach ($formFields as $field) {
            if ($field['name'] === 'email' || $field['name'] === 'password') continue;
            
            $fieldRules = [];
            if ($field['required']) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }
            if ($field['type'] === 'email') $fieldRules[] = 'email';
            if ($field['type'] === 'date') $fieldRules[] = 'date';
            
            $rules[$field['name']] = $fieldRules;
        }

        // Add validation for disclaimers
        foreach ($disclaimers as $disclaimer) {
            if ($disclaimer->is_mandatory) {
                $rules['disclaimer_' . $disclaimer->id] = 'accepted';
            }
        }

        $request->validate($rules);

        // Separate static columns from extra JSON data
        $staticColumns = ['name', 'surname', 'phone', 'dob'];
        $staticData = [];
        $extraData = [];

        foreach ($formFields as $field) {
            if ($field['name'] === 'email' || $field['name'] === 'password') continue;
            
            if (in_array($field['name'], $staticColumns)) {
                $staticData[$field['name']] = $request->input($field['name']);
            } else {
                $extraData[$field['name']] = $request->input($field['name']);
            }
        }

        // Collect accepted disclaimers
        $acceptedDisclaimers = [];
        foreach ($disclaimers as $disclaimer) {
            if ($request->has('disclaimer_' . $disclaimer->id)) {
                $acceptedDisclaimers[] = $disclaimer->id;
            }
        }

        $customer = Customer::where('email', $request->email)->first();

        if ($customer) {
            if ($customer->password) {
                return back()->withErrors([
                    'email' => 'Questa email è già registrata. Per favore, effettua l\'accesso.',
                ]);
            }
            $customer->password = Hash::make($request->password);
            foreach ($staticData as $key => $value) {
                if ($value !== null) $customer->{$key} = $value;
            }
            $customer->extra_data = array_merge((array)$customer->extra_data, $extraData);
            $customer->accepted_disclaimers = array_unique(array_merge((array)$customer->accepted_disclaimers, $acceptedDisclaimers));
            if (!empty($acceptedDisclaimers)) {
                $customer->privacy_accepted_at = now();
            }
            $customer->save();
        } else {
            $cardIdentifier = $request->input('card_identifier');
            
            if ($cardIdentifier) {
                $customer = Customer::where('card_identifier', $cardIdentifier)->first();
            }

            if ($customer) {
                if ($customer->password) {
                     return back()->withErrors([
                        'card_identifier' => 'Questa tessera è già associata ad un account completo.',
                    ]);
                }
                
                $customer->email = $request->email;
                $customer->password = Hash::make($request->password);
                foreach ($staticData as $key => $value) {
                    if ($value !== null) $customer->{$key} = $value;
                }
                $customer->extra_data = array_merge((array)$customer->extra_data, $extraData);
                $customer->accepted_disclaimers = array_unique(array_merge((array)$customer->accepted_disclaimers, $acceptedDisclaimers));
                if (!empty($acceptedDisclaimers)) {
                    $customer->privacy_accepted_at = now();
                }
                $customer->save();
            } else {
                $customer = Customer::create(array_merge([
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'card_identifier' => 'APP' . time() . rand(1000, 9999),
                    'extra_data' => $extraData,
                    'accepted_disclaimers' => $acceptedDisclaimers,
                    'privacy_accepted_at' => !empty($acceptedDisclaimers) ? now() : null,
                    'brand_id' => $store->brand_id,
                    'registration_store_id' => $store->id,
                ], $staticData));
            }
        }

        Auth::guard('customer')->login($customer);
        
        // If it's POS mode, return JSON so the WebView can capture it
        if ($request->has('pos_mode')) {
            return response()->json([
                'status' => 'success',
                'customer' => $customer
            ]);
        }
        
        return redirect()->route('pwa.dashboard', ['store' => $store->slug]);
    }

    public function dashboard(Store $store)
    {
        $customer = Auth::guard('customer')->user();
        
        $pwaSettings = PwaSetting::where('brand_id', $store->brand_id)->first();
        $loyaltyProgram = LoyaltyProgram::where('brand_id', $store->brand_id)->first();
        $rewards = \App\Models\PromotionalRule::where('brand_id', $store->brand_id)->where('is_active', true)->get();
        
        // Pass the loyalty values safely
        $points = $customer->loyalty_points ?? 0;
        $maxPoints = $loyaltyProgram ? $loyaltyProgram->max_points : 1000;
        
        return inertia('PWA/Dashboard', [
            'store' => $store,
            'customer' => $customer,
            'loyaltyProgram' => [
                'max_points' => $maxPoints,
                'points_value' => $loyaltyProgram ? $loyaltyProgram->points_value : null,
                'cashback_percentage' => $loyaltyProgram ? $loyaltyProgram->cashback_percentage : null,
            ],
            'pwaSettings' => $pwaSettings,
            'rewards' => $rewards,
        ]);
    }

    public function logout(Request $request, Store $store)
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('pwa.login', ['store' => $store->slug]);
    }
}
