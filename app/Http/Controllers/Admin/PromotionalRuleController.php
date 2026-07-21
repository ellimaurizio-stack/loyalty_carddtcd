<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\LoyaltyProgram;
use App\Models\PromotionalRule;

class PromotionalRuleController extends Controller
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
            $brandId = \App\Models\Brand::first()->id ?? null;
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

        $programQuery = LoyaltyProgram::withoutGlobalScopes()->where('brand_id', $brandId);
        if ($storeId) {
            $programQuery->where('store_id', $storeId);
        } else {
            $programQuery->whereNull('store_id');
        }
        
        $program = $programQuery->first();
        
        if (!$program && $brandId) {
            $program = LoyaltyProgram::create([
                'brand_id' => $brandId,
                'store_id' => $storeId,
                'name' => 'Programma Fedeltà',
                'conversion_rate' => 1,
                'points_per_currency' => 1,
                'validity_months' => 12
            ]);
        }

        $rules = $program ? $program->rules()->orderBy('priority', 'desc')->get() : collect([]);

        $products = \App\Models\Product::orderBy('name')->get(['id', 'ean_code', 'name', 'price']);
        
        $brands = auth()->user()->role === 'super_admin' ? \App\Models\Brand::all(['id', 'name']) : [];
        $stores = $brandId ? \App\Models\Store::where('brand_id', $brandId)->get(['id', 'name']) : [];

        return Inertia::render('Admin/Rules/Index', [
            'rules' => $rules->values(),
            'program' => $program,
            'products' => $products,
            'brands' => $brands,
            'stores' => $stores,
            'currentBrandId' => $brandId,
            'currentStoreId' => $storeId,
        ]);
    }

    public function store(Request $request)
    {
        $context = $this->resolveContext($request);
        $brandId = $context['brand_id'];
        $storeId = $context['store_id'];

        $programQuery = LoyaltyProgram::withoutGlobalScopes()->where('brand_id', $brandId);
        if ($storeId) {
            $programQuery->where('store_id', $storeId);
        } else {
            $programQuery->whereNull('store_id');
        }
        $program = $programQuery->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'is_active' => 'boolean',
            'priority' => 'integer',
            'is_stackable' => 'boolean',
            'parameters' => 'nullable|array',
            'conditions' => 'nullable|array',
        ]);

        $validated['parameters'] = $validated['parameters'] ?? [];
        $validated['conditions'] = $validated['conditions'] ?? ['trigger_type' => 'always'];
        $validated['priority'] = $validated['priority'] ?? 0;
        $validated['is_stackable'] = $validated['is_stackable'] ?? true;

        // Se l'utente è uno Store Manager, auto-associa la regola al suo negozio
        if ($user->role === 'store_manager' && $user->store_id) {
            $validated['conditions']['store_id'] = $user->store_id;
        }

        $program->rules()->create($validated);

        return redirect()->back()->with('success', 'Rule created successfully.');
    }

    public function update(Request $request, PromotionalRule $promotional_rule)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'is_active' => 'boolean',
            'priority' => 'integer',
            'is_stackable' => 'boolean',
            'parameters' => 'nullable|array',
            'conditions' => 'nullable|array',
        ]);

        $validated['parameters'] = $validated['parameters'] ?? [];
        $validated['conditions'] = $validated['conditions'] ?? ['trigger_type' => 'always'];

        $user = auth()->user();
        if ($user->role === 'store_manager' && $user->store_id) {
            $validated['conditions']['store_id'] = $user->store_id;
        }

        $promotional_rule->update($validated);

        return redirect()->back()->with('success', 'Rule updated successfully.');
    }

    public function destroy(PromotionalRule $promotional_rule)
    {
        $promotional_rule->delete();
        return redirect()->back()->with('success', 'Rule deleted successfully.');
    }
}
