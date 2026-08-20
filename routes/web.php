<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['status' => 'online']);
});

Route::middleware('tenant')->get('/menu', [\App\Http\Controllers\PublicMenuController::class, 'show']);
Route::middleware('tenant')->post('/tables/{table}/orders', [\App\Http\Controllers\TableOrderController::class, 'store']);
Route::middleware('tenant')->post('/tabs/{tab}/payments', [\App\Http\Controllers\CashierController::class, 'pay']);
Route::middleware('tenant')->get('/kds/orders', [\App\Http\Controllers\KitchenDisplayController::class, 'index']);
Route::middleware('tenant')->patch('/kds/items/{item}', [\App\Http\Controllers\KitchenDisplayController::class, 'update']);
Route::middleware('tenant')->get('/operacao', [\App\Http\Controllers\OperationsDashboardController::class, 'index']);
Route::middleware('tenant')->post('/catalog/products', [\App\Http\Controllers\CatalogController::class, 'store']);
Route::middleware('tenant')->post('/tables', [\App\Http\Controllers\DiningTableController::class, 'store']);
Route::post('/onboarding/venues', [\App\Http\Controllers\VenueRegistrationController::class, 'store']);
