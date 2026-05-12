<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TranslationController;

Route::get('/', [TranslationController::class, 'checkForm']);
Route::get('/check-translations', [TranslationController::class, 'check'])->name('translations.check');
Route::get('/export-translations', [TranslationController::class, 'export'])->name('translations.export');