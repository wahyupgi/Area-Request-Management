<?php

use App\Features\KC\Controllers\BranchController;
use Illuminate\Support\Facades\Route;

Route::middleware('role:KC')->prefix('kc')->name('kc.')->group(function () {
    Route::get('/branches', [BranchController::class, 'index'])->name('branches.index');
    Route::post('/branches', [BranchController::class, 'store'])->name('branches.store');
    Route::put('/branches', [BranchController::class, 'update'])->name('branches.update');
});
