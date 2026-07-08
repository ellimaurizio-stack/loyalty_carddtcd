<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\LoyaltyProgram;
use App\Models\PromotionalRule;

class PromotionalRuleController extends Controller
{
    public function index()
    {
        $program = LoyaltyProgram::where('is_active', true)->firstOrFail();
        $rules = $program->rules()->orderBy('priority', 'desc')->get();
        $products = \App\Models\Product::orderBy('name')->get(['id', 'ean', 'name', 'price']);

        return Inertia::render('Admin/Rules/Index', [
            'rules' => $rules,
            'program' => $program,
            'products' => $products
        ]);
    }

    public function store(Request $request)
    {
        $program = LoyaltyProgram::where('is_active', true)->firstOrFail();

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

        $promotional_rule->update($validated);

        return redirect()->back()->with('success', 'Rule updated successfully.');
    }

    public function destroy(PromotionalRule $promotional_rule)
    {
        $promotional_rule->delete();
        return redirect()->back()->with('success', 'Rule deleted successfully.');
    }
}
