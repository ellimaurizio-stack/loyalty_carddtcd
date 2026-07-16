<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'error' => 'Le credenziali fornite non sono corrette.'
            ], 401);
        }

        // We require users logging into the POS app to have a store_id assigned
        // so that the app knows which store to operate on.
        if (! $user->store_id && $user->role !== 'super_admin') {
            return response()->json([
                'error' => 'Nessun negozio assegnato a questo utente.'
            ], 403);
        }

        $storeSlug = 'default-store';
        if ($user->store) {
            $storeSlug = $user->store->slug;
        } elseif ($user->role === 'super_admin') {
            // Give super_admin the first store for testing/default
            $storeSlug = \App\Models\Store::first()->slug ?? 'default-store';
        }

        $token = $user->createToken('pos-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'store_slug' => $storeSlug,
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ]
        ]);
    }
}
