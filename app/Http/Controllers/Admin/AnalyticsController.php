<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index()
    {
        $products = \App\Models\Product::pluck('name');
        return Inertia::render('Admin/Analytics/Index', [
            'products' => $products
        ]);
    }

    public function generate(Request $request, \App\Services\AdvancedAnalyticsService $analyticsService)
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'min_lift' => 'nullable|numeric|min:0',
            'min_support' => 'nullable|numeric|min:0'
        ]);

        $report = $analyticsService->generateReport(
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null,
            $validated['min_lift'] ?? 1.0,
            $validated['min_support'] ?? 0.01
        );

        return response()->json($report);
    }

    public function askAssistant(Request $request, \App\Services\AdvancedAnalyticsService $analyticsService)
    {
        $validated = $request->validate([
            'product_name' => 'nullable|string',
            'question_type' => 'nullable|in:complementary,substitute,bundle,promo',
            'use_ai' => 'boolean',
            'free_text_question' => 'nullable|string'
        ]);

        $useAi = $validated['use_ai'] ?? false;
        
        if ($useAi) {
            $question = $validated['free_text_question'] ?? "Dimmi di più sul prodotto {$validated['product_name']}";
            $answer = $analyticsService->answerQueryWithAi($question);
        } else {
            $answer = $analyticsService->answerQuery($validated['product_name'] ?? '', $validated['question_type'] ?? 'complementary');
        }

        return response()->json(['answer' => $answer]);
    }

    public function downloadTemplate()
    {
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=analytics_import_template.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['transaction_id', 'date', 'product_name', 'price', 'quantity'];

        $callback = function() use($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            
            // Dati di esempio
            fputcsv($file, ['TXN-1001', '2026-07-08 10:30:00', 'Smartphone Alpha', '800.00', '1']);
            fputcsv($file, ['TXN-1001', '2026-07-08 10:30:00', 'Custodia Silicone', '15.00', '1']);
            fputcsv($file, ['TXN-1002', '2026-07-08 11:00:00', 'Caffè Lavazza', '5.50', '1']);
            fputcsv($file, ['TXN-1002', '2026-07-08 11:00:00', 'Latte Intero', '1.20', '2']);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importCsv(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt|max:10240'
        ]);

        $file = $request->file('file');
        $csvData = array_map('str_getcsv', file($file->getPathname()));
        $header = array_shift($csvData);

        // Required columns validation
        $required = ['transaction_id', 'date', 'product_name', 'price', 'quantity'];
        if (count(array_intersect($required, $header)) !== count($required)) {
            return redirect()->back()->with('error', 'Il CSV non rispetta il formato del template (colonne mancanti).');
        }

        $transactions = [];

        foreach ($csvData as $row) {
            if (count($row) !== count($header)) continue;
            
            $rowData = array_combine($header, $row);
            $txnId = $rowData['transaction_id'];
            
            if (!isset($transactions[$txnId])) {
                $transactions[$txnId] = [
                    'date' => \Carbon\Carbon::parse($rowData['date'] ?? now()),
                    'amount' => 0,
                    'products' => []
                ];
            }

            $price = (float) $rowData['price'];
            $qty = (int) $rowData['quantity'];
            $transactions[$txnId]['amount'] += ($price * $qty);
            $transactions[$txnId]['products'][] = [
                'name' => $rowData['product_name'],
                'price' => $price,
                'quantity' => $qty
            ];
        }

        // Generic customer for imports
        $importCustomer = \App\Models\Customer::firstOrCreate(
            ['email' => 'import@analytics.local'],
            ['name' => 'Data Lake Import']
        );

        DB::transaction(function() use ($transactions, $importCustomer) {
            foreach ($transactions as $txn) {
                \App\Models\Purchase::create([
                    'customer_id' => $importCustomer->id,
                    'amount' => $txn['amount'],
                    'products' => $txn['products'],
                    'created_at' => $txn['date'],
                    'updated_at' => $txn['date'],
                ]);
            }
        });

        return redirect()->back()->with('success', count($transactions) . ' transazioni importate con successo nel Database Interno.');
    }
}
