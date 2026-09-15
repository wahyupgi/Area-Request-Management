<?php

use App\Features\Admin\MasterData\Controllers\MasterDataController;
use App\Features\Admin\Templates\Controllers\TemplateController;
use Illuminate\Support\Facades\Route;

Route::middleware('role:ADMIN')->prefix('admin')->name('admin.')->group(function () {

    // ─── Templates ─────────────────────────────────────────────────
    Route::get('/templates', [TemplateController::class, 'index'])->name('templates.index');
    Route::get('/templates/create', [TemplateController::class, 'create'])->name('templates.create');
    Route::post('/templates', [TemplateController::class, 'store'])->name('templates.store');
    Route::get('/templates/{template}/edit', [TemplateController::class, 'edit'])->name('templates.edit');
    Route::put('/templates/{template}', [TemplateController::class, 'update'])->name('templates.update');
    Route::post('/templates/{template}/toggle', [TemplateController::class, 'toggleActive'])->name('templates.toggle');

    // ─── Areas ─────────────────────────────────────────────────────
    Route::get('/areas', [MasterDataController::class, 'areas'])->name('areas.index');
    Route::post('/areas', [MasterDataController::class, 'storeArea'])->name('areas.store');
    Route::put('/areas/{area}', [MasterDataController::class, 'updateArea'])->name('areas.update');
    Route::delete('/areas/{area}', [MasterDataController::class, 'deleteArea'])->name('areas.destroy');

    // ─── Branches ──────────────────────────────────────────────────
    Route::get('/branches', [MasterDataController::class, 'branches'])->name('branches.index');
    Route::post('/branches', [MasterDataController::class, 'storeBranch'])->name('branches.store');

    // ─── Users ─────────────────────────────────────────────────────
    Route::get('/users', [MasterDataController::class, 'users'])->name('users.index');
    Route::post('/users', [MasterDataController::class, 'storeUser'])->name('users.store');
    Route::put('/users/{user}', [MasterDataController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{user}', [MasterDataController::class, 'deleteUser'])->name('users.destroy');
});
