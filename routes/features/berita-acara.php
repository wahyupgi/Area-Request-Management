<?php

use Illuminate\Support\Facades\Route;
use App\Features\BeritaAcara\Controllers\BeritaAcaraController;
use App\Features\FormPengajuan\Controllers\FormPengajuanController;

Route::prefix('berita-acara')->name('berita-acara.')->group(function () {
    Route::get('/',        [BeritaAcaraController::class, 'index'])->name('index');
    Route::get('/create',  [BeritaAcaraController::class, 'create'])->name('create');
    Route::post('/',       [BeritaAcaraController::class, 'store'])->name('store');
});

Route::middleware('role:KC')->prefix('form-pengajuan')->name('form-pengajuan.')->group(function () {
    Route::get('/', [FormPengajuanController::class, 'index'])->name('index');
    Route::get('/create', [FormPengajuanController::class, 'create'])->name('create');
    Route::post('/', [FormPengajuanController::class, 'store'])->name('store');
});
