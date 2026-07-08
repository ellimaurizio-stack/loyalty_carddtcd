<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\LoyaltyProgram;
use App\Models\Customer;

class DashboardController extends Controller
{
    public function index()
    {
        $program = LoyaltyProgram::first();
        if (!$program) {
            $program = LoyaltyProgram::create([
                'name' => 'Default Program',
                'purchases_threshold' => 2,
                'is_active' => true,
            ]);
        }

        $customers = Customer::withCount('purchases')->orderBy('id', 'desc')->get();

        return Inertia::render('Dashboard', [
            'program' => $program,
            'customers' => $customers,
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
