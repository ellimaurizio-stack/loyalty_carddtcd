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

    public function answerQuery($productName, $questionType)
    {
        // Otteniamo il report completo senza filtri stretti per avere tutto il contesto
        $report = $this->generateReport(null, null, 0.01, 0.001);
        $basket = $report['basket'] ?? [];
        $elasticity = $report['elasticity'] ?? [];

        $answer = "";

        if ($questionType === 'complementary') {
            // Cerchiamo le regole dove il prodotto è l'antecedente (A -> B), ordinate per confidenza
            $complements = array_filter($basket, function($rule) use ($productName) {
                return $rule['antecedent'] === $productName || $rule['consequent'] === $productName;
            });
            
            usort($complements, fn($a, $b) => $b['confidence'] <=> $a['confidence']);
            
            if (empty($complements)) {
                return "Non ci sono dati sufficienti per stabilire prodotti complementari per '$productName'. Nessuno lo ha mai comprato stabilmente insieme ad altro.";
            }

            $answer = "I prodotti maggiormente complementari a **$productName** sono:\n\n";
            $count = 0;
            foreach ($complements as $rule) {
                if ($count >= 5) break; // Top 5
                $other = $rule['antecedent'] === $productName ? $rule['consequent'] : $rule['antecedent'];
                $conf = round($rule['confidence'] * 100, 1);
                $answer .= "- **$other** (Confidenza: $conf%)\n";
                $count++;
            }
            $answer .= "\n*Spiegazione*: Questi prodotti vengono spesso comprati insieme a $productName. Ti consigliamo di posizionarli fisicamente vicini nello store o suggerirli nel carrello online.";

        } elseif ($questionType === 'substitute') {
            // Un prodotto sostituto è nella stessa categoria, ma quasi MAI comprato nello stesso scontrino.
            $product = Product::where('name', $productName)->first();
            $category = $product->category ?? null;
            
            if (!$category) {
                return "Il prodotto '$productName' non ha una categoria assegnata, non posso identificare sostituti sensati.";
            }

            // Troviamo tutti i prodotti di quella categoria
            $sameCatProducts = Product::where('category', $category)
                                      ->where('name', '!=', $productName)
                                      ->pluck('name')->toArray();

            if (empty($sameCatProducts)) {
                return "Non ci sono altri prodotti nella categoria '$category' per identificare sostituti.";
            }

            // Quali di questi appaiono in basket rules INSIEME al nostro? 
            // Quelli che NON appaiono (Supporto bassissimo) sono probabili sostituti.
            $boughtTogether = [];
            foreach ($basket as $rule) {
                if ($rule['antecedent'] === $productName) {
                    $boughtTogether[] = $rule['consequent'];
                } elseif ($rule['consequent'] === $productName) {
                    $boughtTogether[] = $rule['antecedent'];
                }
            }

            $substitutes = array_diff($sameCatProducts, $boughtTogether);

            if (empty($substitutes)) {
                return "Curiosamente, tutti i prodotti simili della categoria '$category' vengono spesso comprati INSIEME a '$productName', quindi non sembrano essere puri sostituti.";
            }

            $answer = "I prodotti che si comportano come **Sostituti** per '$productName' (stessa categoria ma quasi mai comprati insieme) sono:\n\n";
            $count = 0;
            foreach ($substitutes as $sub) {
                if ($count >= 5) break;
                $answer .= "- **$sub**\n";
                $count++;
            }
            $answer .= "\n*Spiegazione*: I clienti scelgono o l'uno o l'altro. Se '$productName' finisce le scorte, offri immediatamente uno di questi prodotti.";

        } elseif ($questionType === 'bundle') {
            // Bundle -> Alto Lift (>1.5) e Alto Supporto
            $bundles = array_filter($basket, function($rule) use ($productName) {
                return ($rule['antecedent'] === $productName || $rule['consequent'] === $productName) && $rule['lift'] > 1.2;
            });
            
            usort($bundles, fn($a, $b) => $b['lift'] <=> $a['lift']);

            if (empty($bundles)) {
                return "Al momento non esistono combinazioni con Lift sufficiente per giustificare un bundle forte con '$productName'. Gli acquisti associati sembrano del tutto casuali (Lift ~ 1).";
            }

            $answer = "Ecco i migliori candidati per creare un **Bundle** (Vendita Abbinata) con '$productName':\n\n";
            $count = 0;
            foreach ($bundles as $rule) {
                if ($count >= 3) break;
                $other = $rule['antecedent'] === $productName ? $rule['consequent'] : $rule['antecedent'];
                $answer .= "- **$productName + $other** (Forza del Bundle / Lift: {$rule['lift']})\n";
                $count++;
            }
            $answer .= "\n*Spiegazione*: Il Lift maggiore di 1 indica che la propensione all'acquisto di questi due prodotti *insieme* è superiore rispetto a comprarli separatamente. Promuovendoli in coppia (es. 'Compra X, il 2° a metà prezzo') ottimizzi il margine.";

        } elseif ($questionType === 'promo') {
            // Basato su Elasticità
            $elData = null;
            foreach ($elasticity as $item) {
                if ($item['name'] === $productName) {
                    $elData = $item;
                    break;
                }
            }

            if (!$elData || $elData['status'] !== 'calculated') {
                return "Non ho dati storici sufficienti sulle variazioni di prezzo di '$productName'. Senza almeno due punti di prezzo non posso calcolarne l'elasticità matematica. Nel dubbio, evita sconti percentuali e prova prima un **Cashback in Punti** per misurare la risposta senza bruciare margine.";
            }

            $e = $elData['elasticity'];
            if (abs($e) > 1.0) {
                $answer = "Il prodotto '$productName' è **Altamente Elastico** (Elasticità: $e).\n\n";
                $answer .= "👉 **Strategia Consigliata: Sconto % Diretto (es. -20%) o Taglio Prezzo.**\n";
                $answer .= "I dati dimostrano che abbassando il prezzo, i volumi di vendita esplodono in modo più che proporzionale. Più lo sconti, più l'incasso totale aumenta coprendo abbondantemente il margine unitario perso.";
            } else {
                $answer = "Il prodotto '$productName' è **Rigido / Inelastico** (Elasticità: $e).\n\n";
                $answer .= "👉 **Strategia Consigliata: Cashback, Punti Fedeltà o Bundle.**\n";
                $answer .= "I dati dimostrano che si tratta di un bene di necessità o ad alta fedeltà. Se lo sconti del 20%, i volumi NON aumentano del 20%. Uno sconto abbatterebbe solo il tuo guadagno senza portarti nuovi clienti.\n";
                $answer .= "Usa invece un 'Cashback a Valore' (Es. Compra a prezzo pieno, ricevi 5€ in punti) che obbliga il cliente a tornare, oppure mettilo in Bundle con un prodotto elastico.";
            }
        }

        return $answer;
    }
}
