<?php

use App\Http\Controllers\Admin\PlatformController;
use App\Http\Controllers\CashierController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\DiningTableController;
use App\Http\Controllers\KitchenDisplayController;
use App\Http\Controllers\OperationsDashboardController;
use App\Http\Controllers\OwnerCatalogController;
use App\Http\Controllers\OwnerDashboardController;
use App\Http\Controllers\OwnerTableController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicMenuController;
use App\Http\Controllers\QrMenuController;
use App\Http\Controllers\TableOrderController;
use App\Http\Controllers\VenueRegistrationController;
use Illuminate\Support\Facades\Route;

Route::domain('{subdomain}.cardapiodigitalbh.com.br')->middleware('tenant')->group(function () {
    Route::get('/', [PublicMenuController::class, 'show'])->name('public.menu');
    Route::get('/menu', fn () => redirect('/'))->name('public.menu.legacy');
});

Route::get('/', function () {
    return view('marketing.home');
});

Route::get('/cardapio-digital-em-belo-horizonte', function () {
    return view('marketing.home');
});
Route::get('/m/{token}', [QrMenuController::class, 'show'])->name('public.qr-menu');
Route::post('/m/{token}/orders', [TableOrderController::class, 'storeByToken'])->middleware('throttle:30,1')->name('public.qr-order');

Route::get('/dashboard', OwnerDashboardController::class)
    ->middleware(['auth'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::middleware('platform.owner')->group(function () {
        Route::get('/admin', [PlatformController::class, 'index'])->name('admin.platform');
        Route::post('/admin/estabelecimentos/{tenant}/acessar', [PlatformController::class, 'impersonate'])->name('admin.impersonate');
        Route::patch('/admin/estabelecimentos/{tenant}/plano', [PlatformController::class, 'updatePlan'])->name('admin.plan.update');
    });
    Route::post('/admin/retornar', [PlatformController::class, 'leave'])->name('admin.leave');
    Route::get('/painel/cardapio', [OwnerCatalogController::class, 'index'])->name('owner.catalog');
    Route::post('/painel/categorias', [OwnerCatalogController::class, 'storeCategory'])->name('owner.categories.store');
    Route::patch('/painel/categorias/{category}', [OwnerCatalogController::class, 'updateCategory'])->name('owner.categories.update');
    Route::delete('/painel/categorias/{category}', [OwnerCatalogController::class, 'destroyCategory'])->name('owner.categories.destroy');
    Route::post('/painel/produtos', [OwnerCatalogController::class, 'storeProduct'])->name('owner.products.store');
    Route::patch('/painel/produtos/{product}', [OwnerCatalogController::class, 'updateProduct'])->name('owner.products.update');
    Route::delete('/painel/produtos/{product}', [OwnerCatalogController::class, 'destroyProduct'])->name('owner.products.destroy');
    Route::get('/painel/mesas', [OwnerTableController::class, 'index'])->name('owner.tables');
    Route::post('/painel/mesas', [OwnerTableController::class, 'store'])->name('owner.tables.store');
    Route::get('/painel/mesas/{table}/qr.svg', [OwnerTableController::class, 'qr'])->name('owner.tables.qr');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::middleware('tenant')->post('/tables/{table}/orders', [TableOrderController::class, 'store']);
Route::middleware(['auth', 'tenant', 'venue.access'])->group(function () {
    Route::get('/caixa', [CashierController::class, 'index'])->name('cashier.index');
    Route::post('/tabs/{tab}/payments', [CashierController::class, 'pay'])->name('cashier.pay');
    Route::get('/cozinha', [KitchenDisplayController::class, 'index'])->name('kds.index');
    Route::patch('/cozinha/itens/{item}', [KitchenDisplayController::class, 'update'])->name('kds.items.update');
    Route::get('/operacao', [OperationsDashboardController::class, 'index'])->name('operations.index');
    Route::post('/catalog/products', [CatalogController::class, 'store']);
    Route::post('/tables', [DiningTableController::class, 'store']);
});
Route::post('/onboarding/venues', [VenueRegistrationController::class, 'store']);
