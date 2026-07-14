<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FmcgSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brand = \App\Models\Brand::firstOrCreate(
            ['slug' => 'default-brand'],
            ['name' => 'Default Brand']
        );
        $store = \App\Models\Store::firstOrCreate(
            ['slug' => 'default-store'],
            ['brand_id' => $brand->id, 'name' => 'Default Store']
        );

        $fmcgProducts = [
            ['name' => 'Barilla Spaghetti n.5 500g', 'category' => 'Pasta', 'price' => 0.89, 'ean_code' => '8076809511059'],
            ['name' => 'Nutella Ferrero 400g', 'category' => 'Dolci', 'price' => 3.49, 'ean_code' => '8000500179864'],
            ['name' => 'Coca-Cola 1.5L', 'category' => 'Bevande', 'price' => 1.79, 'ean_code' => '5449000000996'],
            ['name' => 'Mulino Bianco Macine 800g', 'category' => 'Colazione', 'price' => 2.59, 'ean_code' => '8076809516641'],
            ['name' => 'Latte Granarolo P.S. 1L', 'category' => 'Latticini', 'price' => 1.25, 'ean_code' => '8004208000529'],
            ['name' => 'Passata Mutti 700g', 'category' => 'Salse', 'price' => 1.35, 'ean_code' => '8005110170425'],
            ['name' => 'Caffè Lavazza Qualità Rossa 250g', 'category' => 'Colazione', 'price' => 3.19, 'ean_code' => '8000070107871'],
            ['name' => 'Birra Moretti 66cl', 'category' => 'Bevande', 'price' => 1.15, 'ean_code' => '8001431123456'],
            ['name' => 'Tonno Rio Mare 3x80g', 'category' => 'Scatolame', 'price' => 4.49, 'ean_code' => '8004030022211'],
            ['name' => 'Olio EVO De Cecco 1L', 'category' => 'Condimenti', 'price' => 7.89, 'ean_code' => '8001250231201'],
            ['name' => 'Carta Igienica Scottex 10 Rotoli', 'category' => 'Igiene Casa', 'price' => 3.99, 'ean_code' => '8006040223315'],
            ['name' => 'Dentifricio Mentadent 75ml', 'category' => 'Igiene Persona', 'price' => 1.95, 'ean_code' => '8710908881234'],
            ['name' => 'Shampoo Pantene 250ml', 'category' => 'Igiene Persona', 'price' => 2.89, 'ean_code' => '8001090123456'],
            ['name' => 'Detersivo Dash Pods 15pz', 'category' => 'Igiene Casa', 'price' => 6.49, 'ean_code' => '8001090765432'],
        ];

        $insertedProducts = [];
        foreach ($fmcgProducts as $p) {
            $insertedProducts[] = \App\Models\Product::firstOrCreate(
                ['ean_code' => $p['ean_code']],
                array_merge($p, ['brand_id' => $brand->id, 'stock' => rand(50, 200)])
            );
        }

        // Generate 3 sample customers
        $customers = [];
        for ($i = 1; $i <= 3; $i++) {
            $customers[] = \App\Models\Customer::firstOrCreate(
                ['email' => "cliente{$i}@example.com"],
                [
                    'card_identifier' => "APP00{$i}",
                    'name' => "Cliente",
                    'surname' => "Test {$i}",
                    'password' => bcrypt('password'),
                    'brand_id' => $brand->id,
                    'registration_store_id' => $store->id,
                ]
            );
        }

        // Generate historical purchases for the last 3 months
        foreach ($customers as $customer) {
            $numPurchases = rand(3, 10);
            for ($j = 0; $j < $numPurchases; $j++) {
                // Random items
                $numItems = rand(2, 5);
                $items = [];
                $total = 0;
                
                $selectedProds = collect($insertedProducts)->random($numItems);
                
                foreach ($selectedProds as $prod) {
                    $qty = rand(1, 3);
                    $price = $prod->price;
                    $items[] = [
                        'product_id' => $prod->id,
                        'name' => $prod->name,
                        'ean' => $prod->ean_code,
                        'category' => $prod->category,
                        'quantity' => $qty,
                        'price' => $price,
                        'total' => $price * $qty
                    ];
                    $total += $price * $qty;
                }

                \App\Models\Purchase::create([
                    'customer_id' => $customer->id,
                    'brand_id' => $brand->id,
                    'store_id' => $store->id,
                    'amount' => $total,
                    'products' => $items,
                    'created_at' => now()->subDays(rand(1, 90)), // Historical date
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
