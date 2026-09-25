<?php

use App\Features\Dashboard\Controllers\DashboardController;
use App\Features\Dashboard\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// AM Report – GA memos
Route::middleware('role:AM')->group(function () {
    Route::get('/reports/ga', [ReportController::class, 'gaReport'])->name('reports.ga');
    Route::get('/reports/ga/export', [ReportController::class, 'exportGaReport'])->name('reports.ga.export');
});
