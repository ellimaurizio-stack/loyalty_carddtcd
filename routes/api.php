<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\LoyaltyController;

Route::post('/purchases', [PurchaseController::class, 'store']);
Route::post('/products', [App\Http\Controllers\Api\ProductController::class, 'store']);
Route::get('/product-scan', [App\Http\Controllers\Api\ProductController::class, 'findByEan']);
Route::get('/products/{ean}', [App\Http\Controllers\Api\ProductController::class, 'findByEanOld'])->where('ean', '.*');
Route::post('/loyalty/enroll', [LoyaltyController::class, 'enroll']);
Route::post('/loyalty/verify', [LoyaltyController::class, 'verify']);

use App\Http\Controllers\Api\SettingsController;
Route::get('/settings', [SettingsController::class, 'index']);

Route::get('/app-settings', function () {
    return \App\Models\AppSetting::first() ?? new \App\Models\AppSetting();
});
