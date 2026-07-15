<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\LoyaltyProgram;
use App\Models\PromotionalRule;

class PromotionalRuleController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Determina il brand_id (Super Admin può passare request, gli altri usano il loro)
        $brandId = $user->role === 'super_admin' && $request->has('brand_id') 
            ? $request->brand_id 
            : $user->brand_id;

        // Se l'utente è un Brand Manager o Super Admin senza un brand, prendi il primo programma disponibile
        // o crea un programma di default se non esiste
        $programQuery = LoyaltyProgram::query();
        if ($brandId) {
            $programQuery->where('brand_id', $brandId);
        }
        
        $program = $programQuery->first();
        
        if (!$program && $brandId) {
            $program = LoyaltyProgram::create([
                'brand_id' => $brandId,
                'name' => 'Programma Fedeltà',
                'conversion_rate' => 1,
                'points_per_currency' => 1,
                'validity_months' => 12
            ]);
        }

        $rules = $program ? $program->rules()->orderBy('priority', 'desc')->get() : collect([]);

        // Se l'utente è uno Store Manager, filtra le regole che non gli appartengono
        if ($user->role === 'store_manager' && $user->store_id) {
            $rules = $rules->filter(function($rule) use ($user) {
                // Le regole possono essere globali (senza store_id nelle condizioni)
                // Oppure specifiche per il suo store
                return !isset($rule->conditions['store_id']) || $rule->conditions['store_id'] == $user->store_id;
            });
        }

        $products = \App\Models\Product::orderBy('name')->get(['id', 'ean_code', 'name', 'price']);

        return Inertia::render('Admin/Rules/Index', [
            'rules' => $rules->values(),
            'program' => $program,
            'products' => $products
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $brandId = $user->role === 'super_admin' && $request->has('brand_id') ? $request->brand_id : $user->brand_id;

        $program = LoyaltyProgram::when($brandId, function($q) use ($brandId) {
            $q->where('brand_id', $brandId);
        })->firstOrFail();

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
