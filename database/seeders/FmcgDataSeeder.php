<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Customer;
use Carbon\Carbon;

class FmcgDataSeeder extends Seeder
{
    public function run()
    {
        // 1. Create or ensure we have a test customer for generic imports
        $customer = Customer::firstOrCreate(
            ['email' => 'fmcg.analytics@local.test'],
            ['name' => 'FMCG Generic Buyer', 'card_identifier' => 'FMCG_001']
        );

        // 2. Define Products
        $catalog = [
            // Pasta (Elastica) - we will simulate discounts on Barilla
            ['name' => 'Pasta Barilla 500g', 'category' => 'Pasta', 'ean' => '80011111', 'base_price' => 1.20, 'discount_price' => 0.89, 'elasticity' => 'high'],
            ['name' => 'Pasta De Cecco 500g', 'category' => 'Pasta', 'ean' => '80022222', 'base_price' => 1.50, 'discount_price' => 1.50, 'elasticity' => 'low'],
            
            // Sugo (Complementary to Pasta)
            ['name' => 'Sugo Mutti 400g', 'category' => 'Salse', 'ean' => '80033333', 'base_price' => 1.80, 'discount_price' => 1.80, 'elasticity' => 'low'],
            ['name' => 'Pesto Tigullio', 'category' => 'Salse', 'ean' => '80044444', 'base_price' => 2.50, 'discount_price' => 2.50, 'elasticity' => 'low'],

            // Birra & Patatine (Bundle/Complementary)
            ['name' => 'Birra Moretti 66cl', 'category' => 'Bevande Alcoliche', 'ean' => '80055555', 'base_price' => 1.30, 'discount_price' => 1.00, 'elasticity' => 'medium'],
            ['name' => 'Patatine San Carlo 150g', 'category' => 'Snack', 'ean' => '80066666', 'base_price' => 2.00, 'discount_price' => 2.00, 'elasticity' => 'low'],

            // Shampoo (Substitutes)
            ['name' => 'Shampoo Pantene 250ml', 'category' => 'Igiene Personale', 'ean' => '80077777', 'base_price' => 3.50, 'discount_price' => 3.50, 'elasticity' => 'low'],
            ['name' => 'Shampoo Fructis 250ml', 'category' => 'Igiene Personale', 'ean' => '80088888', 'base_price' => 3.20, 'discount_price' => 3.20, 'elasticity' => 'low'],

            // Latte (Inelastico)
            ['name' => 'Latte Granarolo Intero 1L', 'category' => 'Latticini', 'ean' => '80099999', 'base_price' => 1.70, 'discount_price' => 1.50, 'elasticity' => 'low'],
        ];

        // Clear existing test purchases to have a clean dataset
        Purchase::where('customer_id', $customer->id)->delete();

        // Create missing products in DB
        $dbProducts = [];
        foreach ($catalog as $p) {
            $dbProduct = Product::firstOrCreate(
                ['name' => $p['name']],
                [
                    'price' => $p['base_price'],
                    'stock' => 1000,
                    'ean_code' => $p['ean'],
                    'category' => $p['category']
                ]
            );
            // Save the array config for our simulation
            $dbProducts[$p['name']] = $p;
        }

        $purchasesToInsert = [];
        $startDate = Carbon::now()->subMonths(3);

        // Generate 1000 transactions
        for ($i = 0; $i < 1000; $i++) {
            $date = clone $startDate;
            $date->addHours(rand(1, 2160)); // Random hour in the last 3 months

            $basket = [];
            $basketAmount = 0;
            $isDiscountPeriod = rand(1, 10) <= 3; // 30% of the time, there's a discount on Barilla and Moretti

            // 1. RULE: Pasta & Sugo (Complementary)
            // 40% probability of buying Pasta
            if (rand(1, 100) <= 40) {
                // If discount period, buy A LOT of Barilla (Elasticity simulation)
                $qtyBarilla = 0;
                $qtyDeCecco = 0;

                if ($isDiscountPeriod) {
                    $qtyBarilla = rand(3, 6); // Buy more because it's cheap
                    $priceBarilla = $dbProducts['Pasta Barilla 500g']['discount_price'];
                } else {
                    // Standard period, choose between Barilla and De Cecco
                    if (rand(1, 100) <= 60) {
                        $qtyBarilla = rand(1, 2);
                        $priceBarilla = $dbProducts['Pasta Barilla 500g']['base_price'];
                    } else {
                        $qtyDeCecco = rand(1, 2);
                        $priceDeCecco = $dbProducts['Pasta De Cecco 500g']['base_price'];
                    }
                }

                if ($qtyBarilla > 0) {
                    $basket[] = ['name' => 'Pasta Barilla 500g', 'price' => $priceBarilla, 'quantity' => $qtyBarilla];
                    $basketAmount += ($priceBarilla * $qtyBarilla);
                }
                if ($qtyDeCecco > 0) {
                    $basket[] = ['name' => 'Pasta De Cecco 500g', 'price' => $priceDeCecco, 'quantity' => $qtyDeCecco];
                    $basketAmount += ($priceDeCecco * $qtyDeCecco);
                }

                // If bought Pasta, high probability (80%) of buying Sugo/Pesto (Complementary)
                if (rand(1, 100) <= 80) {
                    $sugoName = rand(1, 100) <= 70 ? 'Sugo Mutti 400g' : 'Pesto Tigullio';
                    $qtySugo = rand(1, 2);
                    $priceSugo = $dbProducts[$sugoName]['base_price'];
                    $basket[] = ['name' => $sugoName, 'price' => $priceSugo, 'quantity' => $qtySugo];
                    $basketAmount += ($priceSugo * $qtySugo);
                }
            }

            // 2. RULE: Birra & Patatine (Bundle)
            if (rand(1, 100) <= 30) {
                $qtyBirra = $isDiscountPeriod ? rand(4, 8) : rand(1, 3);
                $priceBirra = $isDiscountPeriod ? $dbProducts['Birra Moretti 66cl']['discount_price'] : $dbProducts['Birra Moretti 66cl']['base_price'];
                
                $basket[] = ['name' => 'Birra Moretti 66cl', 'price' => $priceBirra, 'quantity' => $qtyBirra];
                $basketAmount += ($priceBirra * $qtyBirra);

                // High chance (70%) of buying Patatine with Birra
                if (rand(1, 100) <= 70) {
                    $qtyPatatine = rand(1, 2);
                    $pricePatatine = $dbProducts['Patatine San Carlo 150g']['base_price'];
                    $basket[] = ['name' => 'Patatine San Carlo 150g', 'price' => $pricePatatine, 'quantity' => $qtyPatatine];
                    $basketAmount += ($pricePatatine * $qtyPatatine);
                }
            }

            // 3. RULE: Shampoo (Substitutes)
            // 20% chance of buying Shampoo
            if (rand(1, 100) <= 20) {
                // They either buy Pantene OR Fructis, NEVER both
                $shampooName = rand(1, 100) <= 60 ? 'Shampoo Pantene 250ml' : 'Shampoo Fructis 250ml';
                $priceShampoo = $dbProducts[$shampooName]['base_price'];
                $basket[] = ['name' => $shampooName, 'price' => $priceShampoo, 'quantity' => 1];
                $basketAmount += $priceShampoo;
            }

            // 4. RULE: Latte (Inelastic)
            // 50% chance of buying Milk regardless of discount. Discount doesn't change volume much.
            if (rand(1, 100) <= 50) {
                $priceLatte = $isDiscountPeriod ? $dbProducts['Latte Granarolo Intero 1L']['discount_price'] : $dbProducts['Latte Granarolo Intero 1L']['base_price'];
                $qtyLatte = rand(1, 2); // Qty doesn't spike like Pasta during discount
                
                $basket[] = ['name' => 'Latte Granarolo Intero 1L', 'price' => $priceLatte, 'quantity' => $qtyLatte];
                $basketAmount += ($priceLatte * $qtyLatte);
            }

            if (!empty($basket)) {
                $purchasesToInsert[] = [
                    'customer_id' => $customer->id,
                    'amount' => $basketAmount,
                    'products' => json_encode($basket),
                    'created_at' => $date,
                    'updated_at' => $date,
                ];
            }
        }

        // Insert in chunks to avoid memory issues
        $chunks = array_chunk($purchasesToInsert, 200);
        foreach ($chunks as $chunk) {
            Purchase::insert($chunk);
        }

        $this->command->info(count($purchasesToInsert) . ' transazioni FMCG generate con successo.');
    }
}
