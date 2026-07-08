<?php
namespace App\Contracts;

interface PaymentGatewayInterface {
    public function processPayment(string $cardIdentifier, float $amount): bool;
}
