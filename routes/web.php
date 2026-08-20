<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['status' => 'online']);
});

Route::middleware('tenant')->get('/menu', [\App\Http\Controllers\PublicMenuController::class, 'show']);
Route::middleware('tenant')->post('/tables/{table}/orders', [\App\Http\Controllers\TableOrderController::class, 'store']);
Route::post('/onboarding/venues', [\App\Http\Controllers\VenueRegistrationController::class, 'store']);
