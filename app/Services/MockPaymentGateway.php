<?php
namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Log;

class MockPaymentGateway implements PaymentGatewayInterface {
    public function processPayment(string $cardIdentifier, float $amount): bool {
        Log::info("Mock payment of {$amount} processed for card: {$cardIdentifier}");
        return true;
    }
}
