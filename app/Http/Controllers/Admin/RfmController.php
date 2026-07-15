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

        $trends = (clone $query)->whereNotNull('rfm_previous_segment')
                                ->whereColumn('rfm_previous_segment', '!=', 'rfm_segment')
                                ->select('rfm_previous_segment', 'rfm_segment', \DB::raw('count(*) as total'))
                                ->groupBy('rfm_previous_segment', 'rfm_segment')
                                ->orderByDesc('total')
                                ->limit(5)
                                ->get();

        $brands = $user->role === 'super_admin' ? Brand::all() : [];
        $stores = in_array($user->role, ['super_admin', 'brand_manager']) 
                    ? Store::when($request->brand_id, fn($q) => $q->where('brand_id', $request->brand_id))->get() 
                    : [];

        return Inertia::render('Admin/Analytics/Rfm', [
            'total_customers' => $totalCustomers,
            'segments' => $segmentsCount,
            'trends' => $trends,
            'brands' => $brands,
            'stores' => $stores,
            'filters' => $request->only(['brand_id', 'store_id'])
        ]);
    }

    public function exportCsv(Request $request)
    {
        $segment = $request->input('segment');
        $user = auth()->user();
        
        $query = Customer::where('rfm_segment', $segment);

        if ($user->role === 'super_admin' && $request->brand_id) {
            $query->where('brand_id', $request->brand_id);
        }
        if (in_array($user->role, ['super_admin', 'brand_manager']) && $request->store_id) {
            $query->where('store_id', $request->store_id);
        }

        $customers = $query->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=rfm_segment_{$segment}.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['ID', 'Email', 'Nome', 'Cognome', 'Punti Attuali', 'Cashback', 'Segmento', 'Recency Score', 'Frequency Score', 'Monetary Score'];

        $callback = function() use($customers, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            
            foreach ($customers as $c) {
                fputcsv($file, [
                    $c->id, 
                    $c->email, 
                    $c->name, 
                    $c->surname, 
                    $c->loyalty_points, 
                    $c->cashback_balance, 
                    $c->rfm_segment,
                    $c->recency_score,
                    $c->frequency_score,
                    $c->monetary_score
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
