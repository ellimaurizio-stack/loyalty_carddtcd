<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Purchase;
use Carbon\Carbon;

class AnalyticsDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create 20 distinct products (if they don't exist) with realistic prices and categories
        $productsData = [
            ['name' => 'Pane Bianco', 'category' => 'Panetteria', 'ean_code' => '801001', 'price' => 1.50],
            ['name' => 'Latte Intero', 'category' => 'Latticini', 'ean_code' => '801002', 'price' => 1.20],
            ['name' => 'Caffè Lavazza', 'category' => 'Colazione', 'ean_code' => '801003', 'price' => 4.50],
            ['name' => 'Biscotti Mulino Bianco', 'category' => 'Colazione', 'ean_code' => '801004', 'price' => 2.80],
            ['name' => 'Pasta Barilla 500g', 'category' => 'Pasta', 'ean_code' => '801005', 'price' => 0.90],
            ['name' => 'Passata di Pomodoro Mutti', 'category' => 'Conserve', 'ean_code' => '801006', 'price' => 1.30],
            ['name' => 'Olio Extra Vergine 1L', 'category' => 'Condimenti', 'ean_code' => '801007', 'price' => 8.50],
            ['name' => 'Vino Rosso Chianti', 'category' => 'Alcolici', 'ean_code' => '801008', 'price' => 6.90],
            ['name' => 'Birra Moretti 66cl', 'category' => 'Alcolici', 'ean_code' => '801009', 'price' => 1.10],
            ['name' => 'Patatine San Carlo', 'category' => 'Snack', 'ean_code' => '801010', 'price' => 1.80],
            ['name' => 'Coca Cola 1.5L', 'category' => 'Bibite', 'ean_code' => '801011', 'price' => 1.90],
            ['name' => 'Prosciutto Crudo 100g', 'category' => 'Salumi', 'ean_code' => '801012', 'price' => 3.50],
            ['name' => 'Parmigiano Reggiano 200g', 'category' => 'Latticini', 'ean_code' => '801013', 'price' => 4.80],
            ['name' => 'Mele Golden 1kg', 'category' => 'Ortofrutta', 'ean_code' => '801014', 'price' => 2.20],
            ['name' => 'Banane Chiquita 1kg', 'category' => 'Ortofrutta', 'ean_code' => '801015', 'price' => 2.50],
            ['name' => 'Detersivo Piatti', 'category' => 'Casa', 'ean_code' => '801016', 'price' => 1.60],
            ['name' => 'Carta Igienica 4 Rotoli', 'category' => 'Casa', 'ean_code' => '801017', 'price' => 3.00],
            ['name' => 'Bagnoschiuma Nivea', 'category' => 'Cura Persona', 'ean_code' => '801018', 'price' => 2.70],
            ['name' => 'Dentifricio Mentadent', 'category' => 'Cura Persona', 'ean_code' => '801019', 'price' => 2.10],
            ['name' => 'Tonno Rio Mare 3x80g', 'category' => 'Conserve', 'ean_code' => '801020', 'price' => 4.20],
        ];

        foreach ($productsData as $pData) {
            Product::firstOrCreate(
                ['ean_code' => $pData['ean_code']],
                ['name' => $pData['name'], 'category' => $pData['category'], 'price' => $pData['price']]
            );
        }

        $allProducts = Product::all();

        // 2. Create 100 Customers (Segmented into VIP, Regular, Occasional)
        $customers = [];
        for ($i = 1; $i <= 100; $i++) {
            $customer = Customer::firstOrCreate([
                'card_identifier' => 'CARD_MOCK_' . str_pad($i, 4, '0', STR_PAD_LEFT)
            ], [
                'name' => 'Customer ' . $i,
                'email' => "customer{$i}@example.com",
                'phone' => '333' . rand(1000000, 9999999)
            ]);
            $customers[] = $customer;
        }

        // 3. Generate 3000 purchases over the last 12 months
        // Assign some customers to be "VIP" (frequent buyers), "Regular", and "Occasional"
        $startDate = Carbon::now()->subMonths(12);
        
        foreach ($customers as $index => $customer) {
            if ($index < 15) {
                // VIP: 40-60 purchases
                $numPurchases = rand(40, 60);
            } elseif ($index < 60) {
                // Regular: 10-30 purchases
                $numPurchases = rand(10, 30);
            } else {
                // Occasional: 1-5 purchases
                $numPurchases = rand(1, 5);
            }

            for ($p = 0; $p < $numPurchases; $p++) {
                // Generate a random date
                $purchaseDate = clone $startDate;
                $purchaseDate->addDays(rand(0, 365))->setTime(rand(8, 20), rand(0, 59));

                // Generate a basket
                // Embed some logic: Colazione items bought together (Caffè + Biscotti + Latte)
                // Pasta + Passata + Parmigiano
                // Birra + Patatine
                $basketPattern = rand(1, 4);
                $basketProducts = [];
                
                if ($basketPattern === 1) { // Colazione
                    $basketProducts = $allProducts->whereIn('ean_code', ['801002', '801003', '801004'])->random(rand(1, 3));
                } elseif ($basketPattern === 2) { // Pasta meal
                    $basketProducts = $allProducts->whereIn('ean_code', ['801005', '801006', '801013', '801008'])->random(rand(2, 4));
                } elseif ($basketPattern === 3) { // Aperitivo
                    $basketProducts = $allProducts->whereIn('ean_code', ['801009', '801010'])->random(2);
                } else { // Random mixed basket
                    $basketProducts = $allProducts->random(rand(1, 6));
                }

                $totalAmount = 0;
                $productsArray = [];
                
                foreach ($basketProducts as $prod) {
                    $qty = rand(1, 3); // random quantity
                    $totalAmount += ($prod->price * $qty);
                    // Just storing flat list for simplicity, but simulating quantity by repeating or adding price
                    for($q=0; $q<$qty; $q++) {
                        $productsArray[] = [
                            'id' => $prod->id,
                            'name' => $prod->name,
                            'ean_code' => $prod->ean_code,
                            'price' => $prod->price,
                            'category' => $prod->category,
                        ];
                    }
                }

                if ($totalAmount > 0) {
                    $purchase = Purchase::create([
                        'customer_id' => $customer->id,
                        'amount' => $totalAmount,
                        'products' => $productsArray,
                        'created_at' => $purchaseDate,
                        'updated_at' => $purchaseDate,
                    ]);
                    
                    $customer->loyalty_points += floor($totalAmount);
                    $customer->cashback_balance += ($totalAmount * 0.05); // 5% mock cashback
                }
            }
            $customer->save();
        }
    }
}
