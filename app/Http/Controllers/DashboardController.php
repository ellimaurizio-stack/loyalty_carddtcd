<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\LoyaltyProgram;
use App\Models\Customer;
use App\Models\Brand;

class DashboardController extends Controller
{
    private function resolveContext(Request $request)
    {
        $user = auth()->user();
        
        if ($user->role === 'store_manager') {
            return [
                'brand_id' => $user->brand_id,
                'store_id' => $user->store_id,
            ];
        }

        $brandId = $user->role === 'brand_manager' ? $user->brand_id : $request->input('brand_id');
        if (!$brandId && $user->role === 'super_admin') {
            $brandId = Brand::first()->id ?? null;
        }

        $storeId = $request->input('store_id');
        if ($storeId === 'null' || $storeId === '') {
            $storeId = null;
        }

        return [
            'brand_id' => $brandId,
            'store_id' => $storeId,
        ];
    }

    public function index(Request $request)
    {
        $context = $this->resolveContext($request);
        $brandId = $context['brand_id'];
        $storeId = $context['store_id'];

        $query = LoyaltyProgram::withoutGlobalScopes()->where('brand_id', $brandId);
        if ($storeId) {
            $query->where('store_id', $storeId);
        } else {
            $query->whereNull('store_id');
        }

        $program = $query->firstOrCreate([
            'brand_id' => $brandId,
            'store_id' => $storeId,
            'name' => 'Default Program',
            'purchases_threshold' => 2,
            'is_active' => true,
        ]);

        $customersQuery = Customer::withCount('purchases');
        if (auth()->user()->role === 'super_admin' && $brandId) {
             $customersQuery->where('brand_id', $brandId);
        }
        $customers = $customersQuery->orderBy('id', 'desc')->get();
        
        $brands = auth()->user()->role === 'super_admin' ? Brand::all(['id', 'name']) : [];
        $stores = $brandId ? \App\Models\Store::where('brand_id', $brandId)->get(['id', 'name']) : [];

        return Inertia::render('Dashboard', [
            'program' => $program,
            'customers' => $customers,
            'brands' => $brands,
            'stores' => $stores,
            'currentBrandId' => $brandId,
            'currentStoreId' => $storeId,
        ]);
    }

    public function updateProgram(Request $request, LoyaltyProgram $program)
    {
        $validated = $request->validate([
            'purchases_threshold' => 'required|integer|min:1',
            'is_active' => 'required|boolean',
        ]);

        $program->update($validated);

        return redirect()->back()->with('success', 'Program updated successfully');
    }
}
