<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TranslationController;

Route::get('/', [TranslationController::class, 'checkForm']);
Route::get('/check-translations', [TranslationController::class, 'check'])->name('translations.check');
Route::get('/export-translations', [TranslationController::class, 'export'])->name('translations.export');
Route::get('/translation-history',[TranslationController::class,'history'])->name('translations.history');
Route::post('/translation-clear-cache',[TranslationController::class,'clearCache'])->name('translations.clear-cache');