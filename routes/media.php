<?php

use App\Http\Controllers\PublicMediaController;
use App\Http\Controllers\PrintAgentController;
use Illuminate\Support\Facades\Route;

Route::post('/api/print-agent/pair', [PrintAgentController::class, 'pair'])->middleware('throttle:5,1')->name('print-agent.pair');
Route::middleware('printer.agent')->prefix('/api/print-agent')->group(function () {
    Route::get('/jobs', [PrintAgentController::class, 'jobs'])->middleware('throttle:30,1')->name('print-agent.jobs');
    Route::post('/jobs/{job}/ack', [PrintAgentController::class, 'acknowledge'])->middleware('throttle:30,1')->name('print-agent.jobs.ack');
});

Route::domain('{subdomain}.cardapiodigitalbh.com.br')->middleware('tenant')->group(function () {
    Route::get('/media/{category}/{filename}', PublicMediaController::class)->name('public.media');
});
