<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\LoyaltyController;
use App\Http\Controllers\Api\AuthController;

Route::post('/login', [AuthController::class, 'login']);

Route::prefix('/{store:slug}')->middleware('auth:sanctum')->group(function () {
    Route::post('/checkout/calculate', [PurchaseController::class, 'calculate']);
    Route::post('/purchases', [PurchaseController::class, 'store']);
    Route::post('/coupons/burn', [App\Http\Controllers\Api\LoyaltyController::class, 'burnCoupon']);
    Route::post('/products', [App\Http\Controllers\Api\ProductController::class, 'store']);
    Route::get('/product-scan', [App\Http\Controllers\Api\ProductController::class, 'findByEan']);
    Route::get('/products/{ean}', [App\Http\Controllers\Api\ProductController::class, 'findByEanOld'])->where('ean', '.*');
    
    Route::post('/loyalty/enroll', [LoyaltyController::class, 'enroll']);
    Route::post('/loyalty/verify', [LoyaltyController::class, 'verify']);

    Route::get('/settings', [App\Http\Controllers\Api\SettingsController::class, 'index']);

    Route::get('/app-settings', function (\App\Models\Store $store) {
        return \App\Models\AppSetting::withoutGlobalScopes()->where('brand_id', $store->brand_id)->first() ?? new \App\Models\AppSetting();
    });
});
