<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['status' => 'online']);
});

Route::middleware('tenant')->get('/menu', [\App\Http\Controllers\PublicMenuController::class, 'show']);
