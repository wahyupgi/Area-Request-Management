<?php

use App\Features\Memo\Controllers\MemoController;
use Illuminate\Support\Facades\Route;

// KC-only routes
Route::middleware('role:KC')->group(function () {
    Route::get('/memos', [MemoController::class, 'index'])->name('memos.index');
    Route::get('/memos/create', [MemoController::class, 'create'])->name('memos.create');
    Route::post('/memos', [MemoController::class, 'store'])->name('memos.store');
    Route::get('/memos/{memo}/edit', [MemoController::class, 'edit'])->name('memos.edit');
    Route::put('/memos/{memo}', [MemoController::class, 'update'])->name('memos.update');
    Route::post('/memos/{memo}/submit', [MemoController::class, 'submit'])->name('memos.submit');
    Route::post('/memos/{memo}/attachments', [MemoController::class, 'uploadAttachment'])->name('memos.attachments.upload');
    Route::delete('/memos/{memo}/attachments/{attachment}', [MemoController::class, 'deleteAttachment'])->name('memos.attachments.delete');
    Route::delete('/memos/{memo}', [MemoController::class, 'destroy'])->name('memos.destroy');
});

// KC & AM can both view a memo
Route::get('/export-memos-csv', [MemoController::class, 'exportCsv'])->name('memos.export-csv');
Route::get('/memos/{memo}', [MemoController::class, 'show'])->name('memos.show');
