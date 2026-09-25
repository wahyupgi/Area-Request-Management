<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return Inertia::render('Auth/Login');
})->name('home');

// ─── Authenticated Routes ─────────────────────────────────────────────
Route::middleware(['auth'])->group(function () {
    require __DIR__ . '/features/dashboard.php';
    require __DIR__ . '/features/notification.php';
    require __DIR__ . '/features/profile.php';
    require __DIR__ . '/features/memo.php';
    require __DIR__ . '/features/approval.php';
    require __DIR__ . '/features/admin.php';
});

require __DIR__ . '/auth.php';

// Public endpoint for Google Sheets CSV sync
Route::get('/public/export-memos-csv', [App\Features\Memo\Controllers\MemoController::class, 'exportCsvPublic'])
    ->name('memos.export-csv-public');
