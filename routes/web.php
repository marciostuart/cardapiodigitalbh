<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('marketing.home');
});

Route::get('/cardapio-digital-em-belo-horizonte', function () {
    return view('marketing.home');
});

Route::get('/dashboard', \App\Http\Controllers\OwnerDashboardController::class)
    ->middleware(['auth'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
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
