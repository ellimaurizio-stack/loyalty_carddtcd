<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Purchase;
use App\Models\LoyaltyProgram;
use App\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function calculate(Request $request, \App\Models\Store $store)
    {
        $validated = $request->validate([
            'card_identifier' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'products' => 'nullable|array',
        ]);

        $customer = Customer::where('card_identifier', $validated['card_identifier'])->first();
        $program = LoyaltyProgram::withoutGlobalScopes()->where('store_id', $store->id)->where('is_active', true)->first()
            ?? LoyaltyProgram::withoutGlobalScopes()->where('brand_id', $store->brand_id)->whereNull('store_id')->where('is_active', true)->first();
        
        $originalAmount = (float) $validated['amount'];
        $finalAmount = $originalAmount;
        $discountsApplied = [];
        $pointsToEarn = 0;
        $couponsToIssue = [];

        if ($program) {
            $rules = $program->rules()->where('is_active', true)->orderBy('priority', 'desc')->get();
            $purchasesCount = $customer ? $customer->purchases()->count() : 0;
            $customerSegment = $customer ? $customer->rfm_segment : null;

            foreach ($rules as $rule) {
                // Check if rule targets a specific segment
                if (!empty($rule->conditions['target_segment']) && $rule->conditions['target_segment'] !== $customerSegment) {
                    continue; // Skip if user is not in the target segment
                }

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
                        $purchasedEans = [];
                        foreach ($validated['products'] as $prod) {
                            $ean = $prod['ean'] ?? null;
                            if ($ean) {
                                $purchasedEans[$ean] = ($purchasedEans[$ean] ?? 0) + ($prod['quantity'] ?? 1);
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
                        if ($allMet) $applyRule = true;
                    }
                }

                if ($applyRule) {
                    if ($rule->type === 'Discount') {
                        $dType = $rule->parameters['discount_type'] ?? 'percent';
                        $dVal = (float)($rule->parameters['discount_value'] ?? 0);
                        $discountAmt = 0;
                        
                        if ($dType === 'percent') {
                            $discountAmt = ($finalAmount * $dVal) / 100;
                        } else {
                            $discountAmt = $dVal;
                        }

                        if ($discountAmt > 0) {
                            $finalAmount -= $discountAmt;
                            $discountsApplied[] = "-€" . number_format($discountAmt, 2) . " (" . $rule->name . ")";
                        }

                    } elseif ($rule->type === 'Bundle') {
                        $appType = $rule->parameters['bundle_application_type'] ?? 'pos_direct';
                        
                        if ($appType === 'pos_direct') {
                            $bType = $rule->parameters['bundle_discount_type'] ?? 'percent';
                            $bVal = (float)($rule->parameters['bundle_discount_value'] ?? 0);
                            $discountAmt = 0;

                            // Simplified logic: apply discount on the whole cart for this prototype,
                            // or calculate product specific if prices were passed.
                            // We assume totalAmount includes the bundle products already.
                            if ($bType === 'free') {
                                // In a real scenario we'd subtract the price of the bundle product.
                                // Here we fake it by applying a 100% discount on 1 item.
                                $discountAmt = 10; // Faked 10€ for free item
                            } elseif ($bType === 'percent') {
                                $discountAmt = ($finalAmount * $bVal) / 100;
                            } else {
                                $discountAmt = $bVal;
                            }

                            if ($discountAmt > 0) {
                                $finalAmount -= $discountAmt;
                                $discountsApplied[] = "-€" . number_format($discountAmt, 2) . " (" . $rule->name . ")";
                            }
                        } elseif ($appType === 'pwa_coupon') {
                            $cType = $rule->parameters['coupon_type'] ?? 'discount';
                            if ($cType === 'physical_prize') {
                                $couponsToIssue[] = "Premio: " . ($rule->parameters['coupon_prize_name'] ?? 'Omaggio');
                            } else {
                                $couponsToIssue[] = "Coupon Sconto Generato in App!";
                            }
                        }

                    } elseif ($rule->type === 'Points' || $rule->type === 'SpecialMultiplier') {
                        $minSpend = $rule->parameters['min_spend'] ?? 0;
                        if ($finalAmount >= $minSpend) {
                            $eurosPerPoint = $rule->parameters['euros_per_point'] ?? 1;
                            $multiplier = $rule->parameters['special_multiplier'] ?? 1;
                            if ($eurosPerPoint > 0) {
                                $pointsToEarn += floor(($finalAmount / $eurosPerPoint) * $multiplier);
                            }
                        }
                    }

                    // Non scendiamo sotto zero
                    if ($finalAmount < 0) $finalAmount = 0;

                    if (!($rule->is_stackable ?? true)) {
                        break;
                    }
                }
            }
        }

        return response()->json([
            'original_amount' => $originalAmount,
            'final_amount' => round($finalAmount, 2),
            'discounts_applied' => $discountsApplied,
            'points_to_earn' => $pointsToEarn,
            'coupons_to_issue' => $couponsToIssue,
        ]);
    }

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
        $program = LoyaltyProgram::withoutGlobalScopes()->where('store_id', $store->id)->where('is_active', true)->first()
            ?? LoyaltyProgram::withoutGlobalScopes()->where('brand_id', $store->brand_id)->whereNull('store_id')->where('is_active', true)->first();

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
                // Controlla se il cliente è nel target
                $customerSegment = $customer ? $customer->rfm_segment : null;
                if (!empty($rule->conditions['target_segment']) && $rule->conditions['target_segment'] !== $customerSegment) {
                    continue; 
                }

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
                    if ($rule->type === 'Points' || $rule->type === 'SpecialMultiplier') {
                        $minSpend = $rule->parameters['min_spend'] ?? 0;
                        if ($validated['amount'] >= $minSpend) {
                            $eurosPerPoint = $rule->parameters['euros_per_point'] ?? 1;
                            $multiplier = $rule->parameters['special_multiplier'] ?? 1;
                            if ($eurosPerPoint > 0) {
                                $pointsEarned += floor(($validated['amount'] / $eurosPerPoint) * $multiplier);
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
                    } elseif ($rule->type === 'Bundle') {
                        // Se è pwa_coupon, genera il coupon qui dopo il pagamento
                        if (($rule->parameters['bundle_application_type'] ?? '') === 'pwa_coupon') {
                            \App\Models\CustomerReward::create([
                                'customer_id' => $customer->id,
                                'reward_type' => 'pwa_coupon',
                                'reward_value' => json_encode($rule->parameters),
                                'description' => $rule->name,
                                'is_redeemed' => false,
                                'brand_id' => $store->brand_id,
                                'store_id' => $store->id,
                            ]);
                            $unlockedRewards[] = [
                                'type' => 'coupon',
                                'value' => $rule->parameters['coupon_title'] ?? 'Coupon',
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
