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
        return Inertia::render('PWA/Auth/Register', [
            'pwaSettings' => $settings,
            'card_identifier' => $request->query('card_identifier'),
        ]);
    }

    public function register(Request $request)
    {
        $settings = PwaSetting::firstOrCreate([]);
        $fields = $settings->registration_fields ?? [
            'name' => ['enabled' => true, 'required' => true],
            'phone' => ['enabled' => false, 'required' => false],
            'dob' => ['enabled' => false, 'required' => false],
        ];

        $rules = [
            'email' => 'required|email',
            'password' => 'required|confirmed|min:6',
        ];

        if ($fields['name']['enabled']) {
            $rules['name'] = $fields['name']['required'] ? 'required|string' : 'nullable|string';
        }
        if ($fields['phone']['enabled']) {
            $rules['phone'] = $fields['phone']['required'] ? 'required|string' : 'nullable|string';
        }
        if ($fields['dob']['enabled']) {
            $rules['dob'] = $fields['dob']['required'] ? 'required|date' : 'nullable|date';
        }
        if (!empty($settings->privacy_policy)) {
            $rules['privacy'] = 'accepted';
        }

        $request->validate($rules);

        $customer = Customer::where('email', $request->email)->first();

        if ($customer) {
            // Already a customer but maybe without a password
            if ($customer->password) {
                return back()->withErrors([
                    'email' => 'Questa email è già registrata. Per favore, effettua l\'accesso.',
                ]);
            }
            $customer->password = Hash::make($request->password);
            
            if ($fields['name']['enabled'] && $request->filled('name')) $customer->name = $request->name;
            if ($fields['phone']['enabled'] && $request->filled('phone')) $customer->phone = $request->phone;
            if ($fields['dob']['enabled'] && $request->filled('dob')) $customer->dob = $request->dob;
            if (!empty($settings->privacy_policy)) $customer->privacy_accepted_at = now();
            
            $customer->save();
        } else {
            // If pos_mode and we received a card_identifier, we should see if a customer with that card exists
            // because the POS might have created an empty customer with just the card_identifier
            $cardIdentifier = $request->input('card_identifier');
            
            if ($cardIdentifier) {
                $customer = Customer::where('card_identifier', $cardIdentifier)->first();
            }

            if ($customer) {
                // Update the existing NFC card customer
                $customer->email = $request->email;
                $customer->password = Hash::make($request->password);
                if ($fields['name']['enabled'] && $request->filled('name')) $customer->name = $request->name;
                if ($fields['phone']['enabled'] && $request->filled('phone')) $customer->phone = $request->phone;
                if ($fields['dob']['enabled'] && $request->filled('dob')) $customer->dob = $request->dob;
                if (!empty($settings->privacy_policy)) $customer->privacy_accepted_at = now();
                $customer->save();
            } else {
                // Create brand new customer
                $cardIdentifier = $cardIdentifier ?? ('APP' . time() . rand(1000, 9999));
                
                $customer = Customer::create([
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'name' => $fields['name']['enabled'] ? $request->name : null,
                    'phone' => $fields['phone']['enabled'] ? $request->phone : null,
                    'dob' => $fields['dob']['enabled'] ? $request->dob : null,
                    'card_identifier' => $cardIdentifier,
                    'privacy_accepted_at' => $request->has('privacy') ? now() : null,
                ]);
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
