<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('marketing.home');
});

Route::get('/cardapio-digital-em-belo-horizonte', function () {
    return view('marketing.home');
});
Route::get('/m/{token}', [\App\Http\Controllers\QrMenuController::class, 'show'])->name('public.qr-menu');

Route::get('/dashboard', \App\Http\Controllers\OwnerDashboardController::class)
    ->middleware(['auth'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::middleware('platform.owner')->group(function () {
        Route::get('/admin', [\App\Http\Controllers\Admin\PlatformController::class, 'index'])->name('admin.platform');
        Route::post('/admin/estabelecimentos/{tenant}/acessar', [\App\Http\Controllers\Admin\PlatformController::class, 'impersonate'])->name('admin.impersonate');
        Route::patch('/admin/estabelecimentos/{tenant}/plano', [\App\Http\Controllers\Admin\PlatformController::class, 'updatePlan'])->name('admin.plan.update');
    });
    Route::post('/admin/retornar', [\App\Http\Controllers\Admin\PlatformController::class, 'leave'])->name('admin.leave');
    Route::get('/painel/cardapio', [\App\Http\Controllers\OwnerCatalogController::class, 'index'])->name('owner.catalog');
    Route::post('/painel/categorias', [\App\Http\Controllers\OwnerCatalogController::class, 'storeCategory'])->name('owner.categories.store');
    Route::post('/painel/produtos', [\App\Http\Controllers\OwnerCatalogController::class, 'storeProduct'])->name('owner.products.store');
    Route::get('/painel/mesas', [\App\Http\Controllers\OwnerTableController::class, 'index'])->name('owner.tables');
    Route::post('/painel/mesas', [\App\Http\Controllers\OwnerTableController::class, 'store'])->name('owner.tables.store');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::middleware('tenant')->get('/menu', [\App\Http\Controllers\PublicMenuController::class, 'show']);
Route::middleware('tenant')->post('/tables/{table}/orders', [\App\Http\Controllers\TableOrderController::class, 'store']);
Route::middleware(['auth', 'tenant', 'venue.access'])->group(function () {
    Route::post('/tabs/{tab}/payments', [\App\Http\Controllers\CashierController::class, 'pay']);
    Route::get('/kds/orders', [\App\Http\Controllers\KitchenDisplayController::class, 'index']);
    Route::patch('/kds/items/{item}', [\App\Http\Controllers\KitchenDisplayController::class, 'update']);
    Route::get('/operacao', [\App\Http\Controllers\OperationsDashboardController::class, 'index']);
    Route::post('/catalog/products', [\App\Http\Controllers\CatalogController::class, 'store']);
    Route::post('/tables', [\App\Http\Controllers\DiningTableController::class, 'store']);
});
Route::post('/onboarding/venues', [\App\Http\Controllers\VenueRegistrationController::class, 'store']);
