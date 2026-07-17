<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\LoyaltyProgram;
use App\Models\Customer;
use App\Models\Brand;

class DashboardController extends Controller
{
    private function resolveBrandId(Request $request)
    {
        $user = auth()->user();
        if ($user->role === 'brand_manager') {
            return $user->brand_id;
        }
        $brandId = $request->query('brand_id') ?? $request->input('brand_id');
        if (!$brandId) {
            $brandId = Brand::first()->id ?? null;
        }
        return $brandId;
    }

    public function index(Request $request)
    {
        $brandId = $this->resolveBrandId($request);

        $program = LoyaltyProgram::withoutGlobalScopes()->where('brand_id', $brandId)->first();
        if (!$program) {
            $program = LoyaltyProgram::create([
                'brand_id' => $brandId,
                'name' => 'Default Program',
                'purchases_threshold' => 2,
                'is_active' => true,
            ]);
        }

        $customersQuery = Customer::withCount('purchases');
        if (auth()->user()->role === 'super_admin' && $brandId) {
             $customersQuery->where('brand_id', $brandId);
        }
        $customers = $customersQuery->orderBy('id', 'desc')->get();
        
        $brands = auth()->user()->role === 'super_admin' ? Brand::all(['id', 'name']) : [];

        return Inertia::render('Dashboard', [
            'program' => $program,
            'customers' => $customers,
            'brands' => $brands,
            'currentBrandId' => $brandId,
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
