<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$storeId = "";
try {
    \App\Models\LoyaltyProgram::create([
        'brand_id' => 2,
        'store_id' => $storeId,
        'is_active' => true,
        'name' => 'Default Program',
        'purchases_threshold' => 2,
        'form_fields' => [],
        'otp_channel' => 'phone',
        'otp_channel_label' => 'Phone',
        'text_color' => '#000',
        'translations' => []
    ]);
    echo "Success empty string\n";
} catch (\Exception $e) {
    echo "Error empty string: " . $e->getMessage() . "\n";
}

$storeId = null;
try {
    \App\Models\LoyaltyProgram::create([
        'brand_id' => 2,
        'store_id' => $storeId,
        'is_active' => true,
        'name' => 'Default Program',
        'purchases_threshold' => 2,
        'form_fields' => [],
        'otp_channel' => 'phone',
        'otp_channel_label' => 'Phone',
        'text_color' => '#000',
        'translations' => []
    ]);
    echo "Success null\n";
} catch (\Exception $e) {
    echo "Error null: " . $e->getMessage() . "\n";
}

