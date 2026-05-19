<?php

use App\Http\Controllers\TranslationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/translations/check', [TranslationController::class, 'check']);
    Route::get('/translations/stats', [TranslationController::class, 'stats']);
    Route::post('/translations', [TranslationController::class, 'store']);
});