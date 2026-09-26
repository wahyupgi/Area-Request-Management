<?php

use Illuminate\Support\Facades\Route;
use App\Features\BeritaAcara\Controllers\BeritaAcaraController;

Route::prefix('berita-acara')->name('berita-acara.')->group(function () {
    Route::get('/',        [BeritaAcaraController::class, 'index'])->name('index');
    Route::get('/create',  [BeritaAcaraController::class, 'create'])->name('create');
    Route::post('/',       [BeritaAcaraController::class, 'store'])->name('store');
});
