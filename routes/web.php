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
Route::post('/onboarding/venues', [\App\Http\Controllers\VenueRegistrationController::class, 'store']);
