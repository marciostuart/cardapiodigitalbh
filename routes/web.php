<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::middleware('tenant')->get('/menu', [\App\Http\Controllers\PublicMenuController::class, 'show']);
Route::middleware('tenant')->post('/tables/{table}/orders', [\App\Http\Controllers\TableOrderController::class, 'store']);
Route::middleware('tenant')->post('/tabs/{tab}/payments', [\App\Http\Controllers\CashierController::class, 'pay']);
Route::middleware('tenant')->get('/kds/orders', [\App\Http\Controllers\KitchenDisplayController::class, 'index']);
Route::middleware('tenant')->patch('/kds/items/{item}', [\App\Http\Controllers\KitchenDisplayController::class, 'update']);
Route::middleware('tenant')->get('/operacao', [\App\Http\Controllers\OperationsDashboardController::class, 'index']);
Route::middleware('tenant')->post('/catalog/products', [\App\Http\Controllers\CatalogController::class, 'store']);
Route::middleware('tenant')->post('/tables', [\App\Http\Controllers\DiningTableController::class, 'store']);
Route::post('/onboarding/venues', [\App\Http\Controllers\VenueRegistrationController::class, 'store']);
