<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Customer;
use Carbon\Carbon;

class AnalyticsDummyDataSeeder extends Seeder
{
    public function run()
    {
        // 1. Create or ensure some specific products exist
        $prodA = Product::updateOrCreate(['ean_code' => 'ELASTIC123'], ['name' => 'Smartphone Alpha', 'category' => 'Electronics', 'price' => 1000, 'stock' => 1000]);
        $prodB = Product::updateOrCreate(['ean_code' => 'RIGID123'], ['name' => 'Pane Integrale', 'category' => 'Food', 'price' => 2.00, 'stock' => 10000]);
        $prodC = Product::updateOrCreate(['ean_code' => 'BASKET_C'], ['name' => 'Caffè', 'category' => 'Food', 'price' => 5.00, 'stock' => 5000]);
        $prodD = Product::updateOrCreate(['ean_code' => 'BASKET_D'], ['name' => 'Latte', 'category' => 'Food', 'price' => 1.50, 'stock' => 5000]);
        
        // 2. We need a dummy customer to attach purchases to, or we can leave customer_id null if allowed
        $customer = Customer::first() ?? Customer::create(['name' => 'Dummy Customer', 'email' => 'dummy@test.com']);

        // Month 1 (March): High price for A, Low price for B
        $dateM1 = Carbon::now()->subMonths(3);
        // Smartphone Alpha: sold 30 times at $1000
        $this->generatePurchases($prodA, 1000, 30, $dateM1, $customer);
        // Pane Integrale: sold 200 times at $2.00
        $this->generatePurchases($prodB, 2.00, 200, $dateM1, $customer);

        // Month 2 (April): Price drops for A, increases for B
        $dateM2 = Carbon::now()->subMonths(2);
        // Smartphone Alpha: sold 80 times at $800 (Elastic: 20% price drop -> 166% volume increase)
        $this->generatePurchases($prodA, 800, 80, $dateM2, $customer);
        // Pane Integrale: sold 190 times at $2.50 (Rigid: 25% price increase -> 5% volume drop)
        $this->generatePurchases($prodB, 2.50, 190, $dateM2, $customer);

        // Month 3 (May): Mix to simulate Market Basket (Caffè + Latte)
        $dateM3 = Carbon::now()->subMonth();
        for ($i=0; $i<150; $i++) {
            // 150 times Caffè and Latte are bought together
            Purchase::create([
                'customer_id' => $customer->id,
                'products' => [
                    ['id' => $prodC->id, 'name' => $prodC->name, 'price' => $prodC->price, 'quantity' => 1],
                    ['id' => $prodD->id, 'name' => $prodD->name, 'price' => $prodD->price, 'quantity' => 2]
                ],
                'amount' => $prodC->price + ($prodD->price * 2),
                'created_at' => $dateM3->copy()->addMinutes(rand(1, 40000)),
                'updated_at' => $dateM3->copy()->addMinutes(rand(1, 40000))
            ]);
        }
        
        for ($i=0; $i<50; $i++) {
            // 50 times Caffè alone
            $this->generatePurchases($prodC, $prodC->price, 1, $dateM3, $customer);
        }
    }

    private function generatePurchases($product, $price, $count, $baseDate, $customer)
    {
        for ($i=0; $i<$count; $i++) {
            $date = $baseDate->copy()->addMinutes(rand(1, 40000));
            Purchase::create([
                'customer_id' => $customer->id,
                'products' => [
                    ['id' => $product->id, 'name' => $product->name, 'price' => $price, 'quantity' => 1]
                ],
                'amount' => $price,
                'created_at' => $date,
                'updated_at' => $date
            ]);
        }
    }
}
