<?php
namespace App\Services;

use App\Contracts\OtpProviderInterface;
use Illuminate\Support\Facades\Log;

class LogOtpProvider implements OtpProviderInterface {
    public function send(string $phoneNumber, string $code): bool {
        Log::info("OTP for {$phoneNumber} is {$code}");
        return true;
    }
}
