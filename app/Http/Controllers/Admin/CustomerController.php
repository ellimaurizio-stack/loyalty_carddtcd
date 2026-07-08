<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::withCount('purchases')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return Inertia::render('Admin/Customers/Index', [
            'customers' => $customers
        ]);
    }

    public function show(Customer $customer)
    {
        $customer->load(['purchases' => function ($query) {
            $query->orderBy('created_at', 'desc');
        }]);

        return Inertia::render('Admin/Customers/Show', [
            'customer' => $customer
        ]);
    }
}
