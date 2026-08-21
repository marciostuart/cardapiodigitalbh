<?php

use App\Http\Controllers\PublicMediaController;
use Illuminate\Support\Facades\Route;

Route::domain('{subdomain}.cardapiodigitalbh.com.br')->middleware('tenant')->group(function () {
    Route::get('/media/{category}/{filename}', PublicMediaController::class)->name('public.media');
});
