<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $stores = \App\Models\Store::with('brand')->get();
        $brands = \App\Models\Brand::all();
        
        return inertia('Admin/Stores/Index', [
            'stores' => $stores,
            'brands' => $brands,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'brand_id' => 'nullable|exists:brands,id',
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:stores',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $data = $request->all();
        
        // If super admin and provided brand_id, use it. Otherwise BelongsToTenant trait sets it for BrandManager.
        if (auth()->user()->role === 'super_admin' && !empty($data['brand_id'])) {
            // It will be set by create
        }

        \App\Models\Store::create($data);

        return redirect()->back()->with('success', 'Store created successfully.');
    }
}
