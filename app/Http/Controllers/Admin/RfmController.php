<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Customer;
use App\Models\Brand;
use App\Models\Store;

class RfmController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        
        $query = Customer::query();

        // Se è super admin e filtra per brand
        if ($user->role === 'super_admin' && $request->brand_id) {
            $query->where('brand_id', $request->brand_id);
        }
        // Se è super admin o brand manager e filtra per store
        if (in_array($user->role, ['super_admin', 'brand_manager']) && $request->store_id) {
            $query->where('store_id', $request->store_id);
        }

        $totalCustomers = $query->count();
        $segmentsCount = (clone $query)->select('rfm_segment', \DB::raw('count(*) as total'))
                                       ->groupBy('rfm_segment')
                                       ->get()
                                       ->pluck('total', 'rfm_segment');

        $brands = $user->role === 'super_admin' ? Brand::all() : [];
        $stores = in_array($user->role, ['super_admin', 'brand_manager']) 
                    ? Store::when($request->brand_id, fn($q) => $q->where('brand_id', $request->brand_id))->get() 
                    : [];

        return Inertia::render('Admin/Analytics/Rfm', [
            'total_customers' => $totalCustomers,
            'segments' => $segmentsCount,
            'brands' => $brands,
            'stores' => $stores,
            'filters' => $request->only(['brand_id', 'store_id'])
        ]);
    }
}
