<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

use App\Http\Controllers\DashboardController;

use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\PromotionalRuleController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::put('/dashboard/program/{program}', [DashboardController::class, 'updateProgram'])->name('program.update');

    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');

    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');

    Route::resource('promotional-rules', PromotionalRuleController::class)->except(['create', 'show', 'edit']);
    
    // Products
    Route::get('products/csv-template', [App\Http\Controllers\Admin\ProductController::class, 'downloadTemplate'])->name('products.csv-template');
    Route::post('products/import', [App\Http\Controllers\Admin\ProductController::class, 'importCsv'])->name('products.import');
    Route::post('products/import-zip', [App\Http\Controllers\Admin\ProductController::class, 'importZip'])->name('products.import-zip');
    Route::patch('products/{product}/stock', [App\Http\Controllers\Admin\ProductController::class, 'updateStock'])->name('products.update-stock');
    Route::resource('products', App\Http\Controllers\Admin\ProductController::class)->except(['create', 'show', 'edit']);

    // App POS Settings
    Route::get('/app-settings', [App\Http\Controllers\Admin\AppSettingController::class, 'edit'])->name('app-settings.edit');
    Route::post('/app-settings', [App\Http\Controllers\Admin\AppSettingController::class, 'update'])->name('app-settings.update');

    // PWA Settings
    Route::get('/pwa-settings', [App\Http\Controllers\Admin\PwaSettingsController::class, 'edit'])->name('pwa-settings.edit');
    Route::post('/pwa-settings', [App\Http\Controllers\Admin\PwaSettingsController::class, 'update'])->name('admin.pwa.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/analytics', [App\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('admin.analytics');
    Route::post('/analytics/generate', [App\Http\Controllers\Admin\AnalyticsController::class, 'generate'])->name('admin.analytics.generate');
    Route::get('/analytics/template', [App\Http\Controllers\Admin\AnalyticsController::class, 'downloadTemplate'])->name('admin.analytics.template');
    Route::post('/analytics/import', [App\Http\Controllers\Admin\AnalyticsController::class, 'importCsv'])->name('admin.analytics.import');
});

require __DIR__.'/auth.php';

// PWA Frontend Routes
Route::get('/pwa/login', [\App\Http\Controllers\Pwa\CustomerPortalController::class, 'showLogin'])->name('pwa.login');
Route::post('/pwa/login', [\App\Http\Controllers\Pwa\CustomerPortalController::class, 'login'])->name('pwa.login.post');
Route::get('/pwa/register', [\App\Http\Controllers\Pwa\CustomerPortalController::class, 'showRegister'])->name('pwa.register');
Route::post('/pwa/register', [\App\Http\Controllers\Pwa\CustomerPortalController::class, 'register'])->name('pwa.register.post');

Route::middleware('auth:customer')->group(function () {
    Route::get('/pwa/dashboard', [\App\Http\Controllers\Pwa\CustomerPortalController::class, 'dashboard'])->name('pwa.dashboard');
    Route::post('/pwa/logout', [\App\Http\Controllers\Pwa\CustomerPortalController::class, 'logout'])->name('pwa.logout');
});

Route::get('/manifest.json', function () {
    $settings = \App\Models\PwaSetting::firstOrCreate([], [
        'app_name' => 'Loyalty App',
        'primary_color' => '#4f46e5',
        'background_color' => '#f3f4f6',
    ]);
    
    $logoUrl = $settings->logo_path ? asset('storage/' . $settings->logo_path) : '/pwa-icon.svg';

    return response()->json([
        'name' => $settings->app_name,
        'short_name' => $settings->app_name,
        'start_url' => '/pwa/dashboard',
        'display' => 'standalone',
        'background_color' => $settings->background_color,
        'theme_color' => $settings->primary_color,
        'icons' => [
            [
                'src' => $logoUrl,
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'any maskable'
            ],
            [
                'src' => $logoUrl,
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any maskable'
            ]
        ]
    ]);
});

Route::get('/debug/delete-customer/{query}', function ($query) {
    $deleted = \App\Models\Customer::where('card_identifier', 'like', "%$query%")
        ->orWhere('email', 'like', "%$query%")
        ->delete();
    return "Deleted $deleted customer(s).";
});
