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

    public function showRegister()
    {
        $settings = PwaSetting::firstOrCreate([]);
        return Inertia::render('PWA/Auth/Register', [
            'pwaSettings' => $settings,
        ]);
    }

    public function register(Request $request)
    {
        // First check if customer exists by email (if they registered at POS)
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|confirmed|min:6'
        ]);

        $customer = Customer::where('email', $request->email)->first();

        if ($customer) {
            // Already a customer but maybe without a password
            $customer->password = Hash::make($request->password);
            $customer->save();
        } else {
            // Create brand new customer
            // Need a unique card identifier
            $cardIdentifier = 'APP' . time() . rand(1000, 9999);
            
            $customer = Customer::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'card_identifier' => $cardIdentifier,
                'name' => $request->name ?? '',
            ]);
        }

        Auth::guard('customer')->login($customer);
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
