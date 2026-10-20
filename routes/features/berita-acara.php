<?php

use Illuminate\Support\Facades\Route;
use App\Features\BeritaAcara\Controllers\BeritaAcaraController;
use App\Features\FormPengajuan\Controllers\FormPengajuanController;

Route::prefix('berita-acara')->name('berita-acara.')->group(function () {
    Route::get('/',                                          [BeritaAcaraController::class, 'index'])->name('index');
    Route::get('/create',                                    [BeritaAcaraController::class, 'create'])->name('create');
    Route::post('/',                                         [BeritaAcaraController::class, 'store'])->name('store');
    Route::get('/{beritaAcara}',                             [BeritaAcaraController::class, 'show'])->name('show');
    Route::get('/{beritaAcara}/edit',                        [BeritaAcaraController::class, 'edit'])->name('edit');
    Route::put('/{beritaAcara}',                             [BeritaAcaraController::class, 'update'])->name('update');
    Route::post('/{beritaAcara}/submit',                     [BeritaAcaraController::class, 'submit'])->name('submit');
    Route::post('/{beritaAcara}/attachments',                [BeritaAcaraController::class, 'uploadAttachment'])->name('attachments.upload');
    Route::delete('/{beritaAcara}/attachments/{attachment}', [BeritaAcaraController::class, 'deleteAttachment'])->name('attachments.destroy');
});

Route::middleware('role:KC')->prefix('form-pengajuan')->name('form-pengajuan.')->group(function () {
    Route::get('/',                                             [FormPengajuanController::class, 'index'])->name('index');
    Route::get('/create',                                       [FormPengajuanController::class, 'create'])->name('create');
    Route::post('/',                                            [FormPengajuanController::class, 'store'])->name('store');
    Route::get('/{formPengajuan}',                              [FormPengajuanController::class, 'show'])->name('show');
    Route::get('/{formPengajuan}/edit',                         [FormPengajuanController::class, 'edit'])->name('edit');
    Route::put('/{formPengajuan}',                              [FormPengajuanController::class, 'update'])->name('update');
    Route::post('/{formPengajuan}/submit',                      [FormPengajuanController::class, 'submit'])->name('submit');
    Route::delete('/{formPengajuan}/attachments/{attachment}',  [FormPengajuanController::class, 'deleteAttachment'])->name('attachments.destroy');
});
