<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdvancedAnalyticsService
{
    public function generateReport($startDate, $endDate, $minLift, $minSupport)
    {
        $query = Purchase::query();
        
        if ($startDate) {
            $query->where('created_at', '>=', Carbon::parse($startDate));
        }
        if ($endDate) {
            $query->where('created_at', '<=', Carbon::parse($endDate)->endOfDay());
        }

        $purchases = $query->get();

        return [
            'elasticity' => $this->calculateElasticity($purchases),
            'basket' => $this->calculateMarketBasket($purchases, $minLift, $minSupport)
        ];
    }

    private function calculateElasticity($purchases)
    {
        // 1. Raggruppa le transazioni per prodotto e per prezzo
        $productStats = [];

        foreach ($purchases as $purchase) {
            $items = is_array($purchase->products) ? $purchase->products : json_decode($purchase->products, true);
            foreach ($items as $item) {
                $pid = $item['id'] ?? $item['name'];
                $price = (float) ($item['price'] ?? 0);
                $qty = (int) ($item['quantity'] ?? 1);
                
                if (!isset($productStats[$pid])) {
                    $productStats[$pid] = [
                        'name' => $item['name'],
                        'prices' => []
                    ];
                }
                
                $priceKey = (string)$price;
                if (!isset($productStats[$pid]['prices'][$priceKey])) {
                    $productStats[$pid]['prices'][$priceKey] = [
                        'price' => $price,
                        'volume' => 0,
                        'transactions' => 0
                    ];
                }
                $productStats[$pid]['prices'][$priceKey]['volume'] += $qty;
                $productStats[$pid]['prices'][$priceKey]['transactions'] += 1;
            }
        }

        $results = [];

        foreach ($productStats as $pid => $data) {
            $prices = array_values($data['prices']);
            
            if (count($prices) < 2) {
                // Not enough price points
                $results[] = [
                    'name' => $data['name'],
                    'status' => 'insufficient_data',
                    'reasoning' => "[Ragionamento Statistico]\nPer calcolare l'Elasticità servono almeno 2 prezzi storici diversi. Il prodotto è stato venduto sempre a €" . number_format($prices[0]['price'], 2) . ". In produzione, l'algoritmo richiederà un minimo di 2 variazioni di prezzo e >30 transazioni per punto prezzo per un'analisi affidabile."
                ];
                continue;
            }

            // Sort by transactions (we pick the two most frequent price points)
            usort($prices, function($a, $b) {
                return $b['transactions'] <=> $a['transactions'];
            });

            $p1 = $prices[0]; // Historical Price 1 (most frequent)
            $p2 = $prices[1]; // Historical Price 2 (second most frequent)

            // Warning se i dati sono pochi
            $dataWarning = "";
            if ($p1['transactions'] < 30 || $p2['transactions'] < 30) {
                $dataWarning = "\n⚠️ Nota Produzione: I punti prezzo attuali hanno meno di 30 transazioni. I dati potrebbero non essere statisticamente rilevanti.";
            }

            // Formula Elasticità: E = ((Q2 - Q1) / Q1) / ((P2 - P1) / P1)
            $pctChangeQty = (($p2['volume'] - $p1['volume']) / $p1['volume']);
            $pctChangePrice = (($p2['price'] - $p1['price']) / $p1['price']);
            
            if ($pctChangePrice == 0) continue; // safety

            $elasticity = round($pctChangeQty / $pctChangePrice, 2);
            $absE = abs($elasticity);

            if ($absE > 1) {
                $type = 'Elastic (Da Scontare)';
                $suggestion = 'Consigliamo un Bundling con sconto: i volumi aumentano più che proporzionalmente al calo del prezzo.';
            } else {
                $type = 'Inelastic (Rigido)';
                $suggestion = 'Consigliamo Prezzo Pieno: il prodotto è un bene di necessità, scontrarlo abbatterebbe solo il margine senza aumentare proporzionalmente i volumi.';
            }

            $reasoning = "[Ragionamento Statistico]\nPassando dal prezzo €{$p1['price']} (Vol: {$p1['volume']} pz) a €{$p2['price']} (Vol: {$p2['volume']} pz):\nLa variazione di prezzo è del " . round($pctChangePrice * 100, 1) . "%, la variazione dei volumi è del " . round($pctChangeQty * 100, 1) . "%.\nL'elasticità è pari a $elasticity.\n$suggestion $dataWarning";

            $results[] = [
                'name' => $data['name'],
                'status' => 'calculated',
                'type' => $type,
                'elasticity' => $elasticity,
                'reasoning' => $reasoning
            ];
        }

        return $results;
    }

    private function calculateMarketBasket($purchases, $minLift = 1.0, $minSupport = 0.01)
    {
        $totalTransactions = count($purchases);
        if ($totalTransactions == 0) return [];

        $itemCounts = [];
        $pairCounts = [];
        $productCategories = []; // Name => Category mapping

        // Pre-fetch all products categories to memory for fast lookup
        $allProducts = Product::select('name', 'category')->get();
        foreach ($allProducts as $p) {
            $productCategories[$p->name] = $p->category ?? 'Senza Categoria';
        }

        foreach ($purchases as $purchase) {
            $items = is_array($purchase->products) ? $purchase->products : json_decode($purchase->products, true);
            $pNames = array_unique(array_column($items, 'name'));
            sort($pNames); // Assicura ordine coerente per le coppie
            
            // Count single items
            foreach ($pNames as $name) {
                $itemCounts[$name] = ($itemCounts[$name] ?? 0) + 1;
            }

            // Count pairs
            for ($i = 0; $i < count($pNames); $i++) {
                for ($j = $i + 1; $j < count($pNames); $j++) {
                    $pair = $pNames[$i] . '||' . $pNames[$j];
                    $pairCounts[$pair] = ($pairCounts[$pair] ?? 0) + 1;
                }
            }
        }

        $rules = [];

        foreach ($pairCounts as $pair => $count) {
            $supportPair = $count / $totalTransactions;
            
            // We ignore minSupport here and return all valid pairs to let frontend filter them dynamically
            if ($supportPair < 0.001) continue; 

            [$itemA, $itemB] = explode('||', $pair);

            // Regola A -> B
            $supportA = $itemCounts[$itemA] / $totalTransactions;
            $supportB = $itemCounts[$itemB] / $totalTransactions;
            
            $confidenceAB = $supportPair / $supportA;
            $liftAB = $confidenceAB / $supportB;

            // Return rules with a basic minimum, frontend will handle the strict filtering
            if ($liftAB >= 1.0) {
                $type = 'Emergenti';
                if ($supportPair > 0.05 && $liftAB > 1.5) {
                    $type = 'Bundle Sicuri';
                }

                $reasoning = "[Ragionamento Statistico]\n$itemA compare nel " . round($supportA*100, 1) . "% degli scontrini.\nQuando un cliente compra $itemA, c'è il " . round($confidenceAB*100, 1) . "% di probabilità (Confidenza) che compri anche $itemB.\nIl Lift di " . round($liftAB, 2) . " indica che l'acquisto di $itemB è " . round($liftAB, 2) . " volte più probabile in presenza di $itemA rispetto al normale acquisto casuale.";

                $rules[] = [
                    'antecedent' => $itemA,
                    'antecedent_category' => $productCategories[$itemA] ?? 'Senza Categoria',
                    'consequent' => $itemB,
                    'consequent_category' => $productCategories[$itemB] ?? 'Senza Categoria',
                    'support' => round($supportPair, 3),
                    'confidence' => round($confidenceAB, 3),
                    'lift' => round($liftAB, 3),
                    'type' => $type,
                    'reasoning' => $reasoning
                ];
            }
        }

        usort($rules, fn($a, $b) => $b['lift'] <=> $a['lift']);

        return $rules;
    }
}
