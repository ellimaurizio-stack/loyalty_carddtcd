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
    {
        $settings = PwaSetting::firstOrCreate([], [
            'app_name' => 'Loyalty App',
            'primary_color' => '#4f46e5',
            'background_color' => '#f3f4f6',
            'text_color' => '#111827',
        ]);

        return Inertia::render('PWA/Auth/Login', [
            'pwaSettings' => $settings,
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::guard('customer')->attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended('/pwa/dashboard');
        }

        return back()->withErrors([
            'email' => 'Le credenziali non sono corrette.',
        ]);
    }

    public function showRegister(Request $request)
    {
        $settings = PwaSetting::firstOrCreate([]);
        $program = \App\Models\LoyaltyProgram::with('disclaimers')->first();
        
        return Inertia::render('PWA/Auth/Register', [
            'pwaSettings' => $settings,
            'card_identifier' => $request->query('card_identifier'),
            'formFields' => $program ? $program->form_fields : [],
            'disclaimers' => $program ? $program->disclaimers : [],
        ]);
    }

    public function register(Request $request)
    {
        $program = \App\Models\LoyaltyProgram::with('disclaimers')->first();
        $formFields = $program ? ($program->form_fields ?? []) : [];
        $disclaimers = $program ? ($program->disclaimers ?? collect()) : collect();

        $rules = [
            'email' => 'required|email',
            'password' => 'required|confirmed|min:6',
        ];

        // Dynamic fields validation
        foreach ($formFields as $field) {
            // Note: email is already handled above, but if they redefined it, this will override or append rules
            if ($field['name'] === 'email' || $field['name'] === 'password') continue;
            
            $fieldRules = [];
            if (!empty($field['required'])) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            if ($field['type'] === 'email') $fieldRules[] = 'email';
            if ($field['type'] === 'number') $fieldRules[] = 'numeric';
            if ($field['type'] === 'date') $fieldRules[] = 'date';
            
            $rules[$field['name']] = implode('|', $fieldRules);
        }

        // Disclaimers validation
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
                $cardIdentifier = $cardIdentifier ?? ('APP' . time() . rand(1000, 9999));
                
                $customerData = array_merge([
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'card_identifier' => $cardIdentifier,
                    'extra_data' => $extraData,
                    'accepted_disclaimers' => $acceptedDisclaimers,
                    'privacy_accepted_at' => !empty($acceptedDisclaimers) ? now() : null,
                ], $staticData);
                
                $customer = Customer::create($customerData);
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
        
        return redirect()->route('pwa.dashboard');
    }

    public function dashboard(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        $settings = PwaSetting::firstOrCreate([]);
        
        $rewards = PromotionalRule::where('is_active', true)->get();

        return Inertia::render('PWA/Dashboard', [
            'customer' => $customer,
            'pwaSettings' => $settings,
            'rewards' => $rewards,
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('pwa.login');
    }
}
