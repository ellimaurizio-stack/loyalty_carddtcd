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

    // Brands (Only Super Admin)
    Route::middleware('role:super_admin')->group(function () {
        Route::resource('brands', \App\Http\Controllers\Admin\BrandController::class);
    });

    // Stores and Users (Super Admin and Brand Manager)
    Route::middleware('role:super_admin,brand_manager')->group(function () {
        Route::resource('stores', \App\Http\Controllers\Admin\StoreController::class);
        Route::resource('users', \App\Http\Controllers\Admin\UserController::class)->except(['create', 'show', 'edit']);
    });

    // Products
    Route::get('products/csv-template', [App\Http\Controllers\Admin\ProductController::class, 'downloadTemplate'])->name('products.csv-template');
    Route::post('products/import', [App\Http\Controllers\Admin\ProductController::class, 'importCsv'])->name('products.import');
    Route::post('products/import-zip', [App\Http\Controllers\Admin\ProductController::class, 'importZip'])->name('products.import-zip');
    Route::patch('products/{product}/stock', [App\Http\Controllers\Admin\ProductController::class, 'updateStock'])->name('products.update-stock');
    Route::resource('products', App\Http\Controllers\Admin\ProductController::class)->except(['create', 'show', 'edit']);

    // Settings (Super Admin and Brand Manager)
    Route::middleware('role:super_admin,brand_manager')->group(function () {
        Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::resource('promotional-rules', PromotionalRuleController::class)->except(['create', 'show', 'edit']);
        Route::get('/app-settings', [App\Http\Controllers\Admin\AppSettingController::class, 'edit'])->name('app-settings.edit');
        Route::post('/app-settings', [App\Http\Controllers\Admin\AppSettingController::class, 'update'])->name('app-settings.update');
        Route::get('/pwa-settings', [App\Http\Controllers\Admin\PwaSettingsController::class, 'edit'])->name('pwa-settings.edit');
        Route::post('/pwa-settings', [App\Http\Controllers\Admin\PwaSettingsController::class, 'update'])->name('admin.pwa.update');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/analytics', [App\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('admin.analytics');
    Route::get('/analytics/rfm', [App\Http\Controllers\Admin\RfmController::class, 'index'])->name('admin.analytics.rfm');
    Route::get('/analytics/rfm/export', [App\Http\Controllers\Admin\RfmController::class, 'exportCsv'])->name('admin.analytics.rfm.export');
    Route::post('/analytics/rfm/promo', [App\Http\Controllers\Admin\RfmController::class, 'createQuickPromo'])->name('admin.analytics.rfm.promo');
    Route::post('/analytics/generate', [App\Http\Controllers\Admin\AnalyticsController::class, 'generate'])->name('admin.analytics.generate');
    Route::get('/analytics/template', [App\Http\Controllers\Admin\AnalyticsController::class, 'downloadTemplate'])->name('admin.analytics.template');
    Route::post('/analytics/import', [App\Http\Controllers\Admin\AnalyticsController::class, 'importCsv'])->name('admin.analytics.import');
    Route::post('/analytics/ask', [App\Http\Controllers\Admin\AnalyticsController::class, 'askAssistant'])->name('admin.analytics.ask');
});

require __DIR__.'/auth.php';

// PWA Frontend Routes
Route::prefix('/{store:slug}/pwa')->group(function () {
    Route::get('/login', [\App\Http\Controllers\Pwa\CustomerPortalController::class, 'showLogin'])->name('pwa.login');
    Route::post('/login', [\App\Http\Controllers\Pwa\CustomerPortalController::class, 'login'])->name('pwa.login.post');
    Route::get('/register', [\App\Http\Controllers\Pwa\CustomerPortalController::class, 'showRegister'])->name('pwa.register');
    Route::post('/register', [\App\Http\Controllers\Pwa\CustomerPortalController::class, 'register'])->name('pwa.register.post');

    Route::middleware('auth:customer')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Pwa\CustomerPortalController::class, 'dashboard'])->name('pwa.dashboard');
        Route::post('/logout', [\App\Http\Controllers\Pwa\CustomerPortalController::class, 'logout'])->name('pwa.logout');
    });
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

Route::get('/debug-log', function () {
    return response(file_get_contents(storage_path('logs/laravel.log')))->header('Content-Type', 'text/plain');
});

Route::get('/debug-seed', function () {
    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'SuperAdminSeeder', '--force' => true]);
    return \Illuminate\Support\Facades\Artisan::output();
});

Route::get('/debug-db', function () {
    $admin = \App\Models\User::where('email', 'admin@loyalty.com')->first();
    return [
        'has_admin' => $admin ? true : false,
        'admin_id' => $admin->id ?? null,
        'admin_role' => $admin->role ?? null,
        'all_users' => \App\Models\User::withoutGlobalScopes()->get(),
    ];
});
