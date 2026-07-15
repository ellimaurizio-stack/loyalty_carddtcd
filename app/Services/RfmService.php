<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Customer;
use App\Models\Purchase;

class RfmService
{
    /**
     * Calculate RFM for all customers.
     * Optionally scoped to a specific brand or store.
     */
    public function calculateRfm($brandId = null, $storeId = null)
    {
        // 1. Gather raw data per customer
        $query = Customer::query();
        
        if ($brandId) {
            $query->where('brand_id', $brandId);
        }
        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        $customers = $query->with(['purchases' => function ($q) {
            $q->select('customer_id', 'amount', 'created_at');
        }])->get();

        if ($customers->isEmpty()) {
            return;
        }

        $rfmData = [];

        foreach ($customers as $customer) {
            $purchases = $customer->purchases;
            
            if ($purchases->isEmpty()) {
                // If a customer has no purchases, we still want to segment them?
                // Typically they are "New" or "Lost", but for RFM they need at least 1 purchase.
                continue;
            }

            $lastPurchase = $purchases->sortByDesc('created_at')->first();
            
            $recencyDays = $lastPurchase->created_at->diffInDays(now());
            $frequency = $purchases->count();
            $monetary = $purchases->sum('amount');

            $rfmData[$customer->id] = [
                'customer' => $customer,
                'recency' => $recencyDays, // Lower is better
                'frequency' => $frequency, // Higher is better
                'monetary' => $monetary,   // Higher is better
            ];
        }

        if (empty($rfmData)) {
            return;
        }

        // 2. Calculate quintiles
        // Recency: Sort Ascending (fewest days = rank 5)
        uasort($rfmData, fn($a, $b) => $a['recency'] <=> $b['recency']);
        $this->assignQuintiles($rfmData, 'recency_score', true); // Reverse because lower days = better score (5)

        // Frequency: Sort Ascending (lower freq = rank 1, highest = rank 5)
        uasort($rfmData, fn($a, $b) => $a['frequency'] <=> $b['frequency']);
        $this->assignQuintiles($rfmData, 'frequency_score', false);

        // Monetary: Sort Ascending
        uasort($rfmData, fn($a, $b) => $a['monetary'] <=> $b['monetary']);
        $this->assignQuintiles($rfmData, 'monetary_score', false);

        // 3. Assign Segments and Save
        foreach ($rfmData as $data) {
            $r = $data['recency_score'];
            $f = $data['frequency_score'];
            $m = $data['monetary_score'];

            $segment = $this->determineSegment($r, $f, $m);

            $data['customer']->update([
                'recency_score' => $r,
                'frequency_score' => $f,
                'monetary_score' => $m,
                'rfm_segment' => $segment,
                'rfm_updated_at' => now(),
            ]);
        }
    }

    protected function assignQuintiles(&$rfmData, $key, $reverse)
    {
        $total = count($rfmData);
        $chunkSize = ceil($total / 5);
        $rank = $reverse ? 5 : 1;
        $count = 0;

        foreach ($rfmData as &$data) {
            $data[$key] = $rank;
            $count++;
            
            if ($count >= $chunkSize) {
                $count = 0;
                $rank = $reverse ? max(1, $rank - 1) : min(5, $rank + 1);
            }
        }
    }

    protected function determineSegment($r, $f, $m)
    {
        // 11 Segments logic based on Arthur Hughes / DMA standard
        
        // 1. Campioni (Champions)
        if ($r >= 4 && $f >= 4 && $m >= 4) {
            return 'Campioni';
        }
        
        // 9. Non Perderli! (Can\'t Lose Them)
        if ($r <= 1 && $f >= 4 && $m >= 4) {
            return 'Non Perderli!';
        }
        
        // 8. A Rischio (At Risk)
        if ($r <= 2 && $f >= 2 && $m >= 2) {
            return 'A Rischio';
        }

        // 2. Clienti Fedeli (Loyal Customers)
        if ($r >= 3 && $f >= 3 && $m >= 1) {
            return 'Clienti Fedeli';
        }

        // 3. Potenziali Leali (Potential Loyalist)
        if ($r >= 3 && $f >= 1 && $f <= 3 && $m >= 1 && $m <= 3) {
            return 'Potenziali Leali';
        }

        // 4. Nuovi Clienti (New Customers)
        if ($r >= 4 && $f == 1 && $m <= 2) {
            return 'Nuovi Clienti';
        }

        // 5. Promettenti (Promising)
        if ($r >= 3 && $f == 1 && $m <= 2) {
            return 'Promettenti';
        }

        // 6. Necessitano Attenzioni (Need Attention)
        if ($r >= 2 && $r <= 3 && $f >= 2 && $f <= 3 && $m >= 2 && $m <= 3) {
            return 'Necessitano Attenzioni';
        }

        // 7. In Sonno / Quasi Persi (About To Sleep)
        if ($r >= 2 && $r <= 3 && $f <= 2 && $m <= 2) {
            return 'In Sonno';
        }

        // 10. Ibernati (Hibernating)
        if ($r <= 2 && $f <= 2 && $m <= 2 && $r > 0 && $f > 0 && $m > 0) {
            // Wait, rank is 1-5, so > 0 is always true
            if ($r != 1 || $f != 1 || $m != 1) {
                return 'Ibernati';
            }
        }

        // 11. Persi (Lost)
        if ($r == 1 && $f == 1 && $m == 1) {
            return 'Persi';
        }

        // Fallback for edge cases
        return 'Altro';
    }
}
