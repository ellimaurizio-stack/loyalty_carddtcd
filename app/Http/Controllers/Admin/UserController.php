<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Brand;
use App\Models\Store;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $users = User::query();

        // If the current user is a brand_manager, BelongsToTenant trait automatically filters them.
        // We will eager load the brand and store, although we need to check if relations exist.
        // Wait, User model doesn't have brand() or store() relationships explicitly defined, we should define them or join.
        // Actually, we can just pass them as attributes or define the relationships on the fly.
        
        $users = $users->orderBy('created_at', 'desc')->get()->map(function ($u) {
            $brand = Brand::find($u->brand_id);
            $store = Store::find($u->store_id);
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role,
                'brand_name' => $brand ? $brand->name : null,
                'store_name' => $store ? $store->name : null,
            ];
        });

        // Determine accessible roles based on current user
        $currentUserRole = $request->user()->role;
        $assignableRoles = $currentUserRole === 'super_admin' 
            ? ['super_admin', 'brand_manager', 'store_manager'] 
            : ['store_manager'];

        // Get brands and stores for the dropdowns
        $brands = $currentUserRole === 'super_admin' ? Brand::all() : Brand::where('id', $request->user()->brand_id)->get();
        // Stores are already filtered by BelongsToTenant!
        $stores = Store::all();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'assignableRoles' => $assignableRoles,
            'brands' => $brands,
            'stores' => $stores,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $currentUserRole = $request->user()->role;
        $assignableRoles = $currentUserRole === 'super_admin' 
            ? ['super_admin', 'brand_manager', 'store_manager'] 
            : ['store_manager'];

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => ['required', Rule::in($assignableRoles)],
            'brand_id' => 'nullable|exists:brands,id',
            'store_id' => 'nullable|exists:stores,id',
        ]);

        if ($currentUserRole !== 'super_admin') {
            $validated['brand_id'] = $request->user()->brand_id;
        }

        // Logic check: if store_manager, ensure store_id is present and get its brand.
        if ($validated['role'] === 'store_manager' && !empty($validated['store_id'])) {
            $store = Store::findOrFail($validated['store_id']);
            $validated['brand_id'] = $store->brand_id;
        }

        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $currentUserRole = $request->user()->role;
        $assignableRoles = $currentUserRole === 'super_admin' 
            ? ['super_admin', 'brand_manager', 'store_manager'] 
            : ['store_manager'];

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
            'role' => ['required', Rule::in($assignableRoles)],
            'brand_id' => 'nullable|exists:brands,id',
            'store_id' => 'nullable|exists:stores,id',
        ]);

        if ($currentUserRole !== 'super_admin') {
            $validated['brand_id'] = $request->user()->brand_id;
        }

        if ($validated['role'] === 'store_manager' && !empty($validated['store_id'])) {
            $store = Store::findOrFail($validated['store_id']);
            $validated['brand_id'] = $store->brand_id;
        }

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return redirect()->route('users.index')->with('error', 'Cannot delete yourself.');
        }
        $user->delete();
        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }
}
