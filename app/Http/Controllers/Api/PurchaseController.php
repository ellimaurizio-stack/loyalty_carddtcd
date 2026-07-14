<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Purchase;
use App\Models\LoyaltyProgram;
use App\Contracts\PaymentGatewayInterface;

class PurchaseController extends Controller
{
    public function store(Request $request, PaymentGatewayInterface $paymentGateway, \App\Models\Store $store)
    {
        $validated = $request->validate([
            'card_identifier' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'products' => 'nullable|array',
        ]);

        $validated['brand_id'] = $store->brand_id;
        $validated['store_id'] = $store->id;

        if (!empty($validated['products'])) {
            $outOfStock = [];
            \Illuminate\Support\Facades\DB::beginTransaction();
            try {
                foreach ($validated['products'] as $prodData) {
                    $ean = $prodData['ean'] ?? null;
                    $qty = $prodData['quantity'] ?? 1;

                    if ($ean) {
                        $product = \App\Models\Product::where('ean_code', $ean)->lockForUpdate()->first();
                        if (!$product) {
                            $outOfStock[] = "Prodotto inesistente ($ean)";
                        } elseif ($product->stock < $qty) {
                            $outOfStock[] = "{$product->name} (Disponibili: {$product->stock}, Richiesti: {$qty})";
                        } else {
                            $product->stock -= $qty;
                            $product->save();
                        }
                    }
                }

                if (!empty($outOfStock)) {
                    \Illuminate\Support\Facades\DB::rollBack();
                    return response()->json([
                        'error' => 'Prodotti esauriti o giacenza insufficiente',
                        'out_of_stock_items' => $outOfStock
                    ], 400);
                }

                \Illuminate\Support\Facades\DB::commit();
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\DB::rollBack();
                return response()->json(['error' => 'Errore durante la verifica del magazzino'], 500);
            }
        }

        // Process payment via interface
        $success = $paymentGateway->processPayment($validated['card_identifier'], $validated['amount']);

        if (!$success) {
            return response()->json(['error' => 'Payment failed'], 400);
        }

        $customer = Customer::firstOrCreate(
            ['card_identifier' => $validated['card_identifier']],
            [
                'brand_id' => $store->brand_id,
                'registration_store_id' => $store->id,
            ]
        );

        Purchase::create([
            'customer_id' => $customer->id,
            'amount' => $validated['amount'],
            'products' => $validated['products'] ?? null,
            'brand_id' => $store->brand_id,
            'store_id' => $store->id,
        ]);

        $purchasesCount = $customer->purchases()->count();
        $program = LoyaltyProgram::where('brand_id', $store->brand_id)->where('is_active', true)->first();

        $promptLoyaltySignup = false;
        $unlockedRewards = [];

        if ($program) {
            // Prompt if they hit the threshold AND they haven't enrolled yet (no phone and no email)
            if ($purchasesCount >= $program->purchases_threshold && empty($customer->phone) && empty($customer->email)) {
                $promptLoyaltySignup = true;
            }

            // --- RULE ENGINE EVALUATION ---
            $rules = $program->rules()->where('is_active', true)->orderBy('priority', 'desc')->get();
            $pointsEarned = 0;
            $cashbackEarned = 0;

            foreach ($rules as $rule) {
                $trigger = $rule->conditions['trigger_type'] ?? 'always';
                $applyRule = false;

                if ($trigger === 'always') {
                    $applyRule = true;
                } elseif ($trigger === 'nth_purchase') {
                    $count = $rule->conditions['nth_purchase_count'] ?? 1;
                    $recurrence = $rule->conditions['nth_purchase_recurrence'] ?? 'once';
                    if ($recurrence === 'once') {
                        if ($purchasesCount == $count) $applyRule = true;
                    } else {
                        if ($purchasesCount > 0 && $purchasesCount % $count == 0) $applyRule = true;
                    }
                } elseif ($trigger === 'specific_products') {
                    $triggerProducts = $rule->conditions['trigger_products'] ?? [];
                    if (!empty($triggerProducts) && !empty($validated['products'])) {
                        // Tally product EANs from payload
                        $purchasedEans = [];
                        foreach ($validated['products'] as $prod) {
                            $ean = $prod['ean'] ?? null;
                            $qty = $prod['quantity'] ?? 1;
                            if ($ean) {
                                $purchasedEans[$ean] = ($purchasedEans[$ean] ?? 0) + $qty;
                            }
                        }
                        
                        $allMet = true;
                        foreach ($triggerProducts as $tp) {
                            $reqEan = $tp['ean'] ?? '';
                            $reqQty = $tp['quantity'] ?? 1;
                            if (!isset($purchasedEans[$reqEan]) || $purchasedEans[$reqEan] < $reqQty) {
                                $allMet = false;
                                break;
                            }
                        }
                        
                        if ($allMet) {
                            $applyRule = true;
                        }
                    }
                }

                if ($applyRule) {
                    if ($rule->type === 'Points') {
                        $minSpend = $rule->parameters['min_spend'] ?? 0;
                        if ($validated['amount'] >= $minSpend) {
                            $eurosPerPoint = $rule->parameters['euros_per_point'] ?? 1;
                            if ($eurosPerPoint > 0) {
                                $pointsEarned += floor($validated['amount'] / $eurosPerPoint);
                            }
                        }
                    } elseif ($rule->type === 'Cashback') {
                        $percent = $rule->parameters['cashback_percent'] ?? 0;
                        $cashbackEarned += ($validated['amount'] * $percent) / 100;
                    } elseif ($rule->type === 'ProductReward') {
                        $rType = $rule->parameters['reward_type'] ?? 'points';
                        $rVal = $rule->parameters['reward_value'] ?? 0;
                        
                        if ($rType === 'points') {
                            $pointsEarned += (int) $rVal;
                        } elseif ($rType === 'cashback') {
                            $cashbackEarned += (float) $rVal;
                        } else {
                            \App\Models\CustomerReward::create([
                                'customer_id' => $customer->id,
                                'reward_type' => $rType,
                                'reward_value' => (string) $rVal,
                                'description' => $rule->name,
                                'is_redeemed' => false,
                                'brand_id' => $store->brand_id,
                                'store_id' => $store->id,
                            ]);
                            $unlockedRewards[] = [
                                'type' => $rType,
                                'value' => $rVal,
                                'description' => $rule->name
                            ];
                        }
                    }

                    if (!($rule->is_stackable ?? true)) {
                        break;
                    }
                }
            }

            if ($pointsEarned > 0 || $cashbackEarned > 0) {
                $customer->loyalty_points += $pointsEarned;
                $customer->cashback_balance += $cashbackEarned;
                $customer->save();
            }
        }

        return response()->json([
            'success' => true,
            'prompt_loyalty_signup' => $promptLoyaltySignup,
            'unlocked_rewards' => $unlockedRewards,
        ]);
    }
}
