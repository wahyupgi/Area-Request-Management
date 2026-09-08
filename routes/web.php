<?php

use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MemoController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SignatureController;
use App\Http\Controllers\Admin\MasterDataController;
use App\Http\Controllers\Admin\TemplateController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return Inertia::render('Auth/Login');
})->name('home');

// Authenticated routes
Route::middleware(['auth'])->group(function () {

    // Dashboard (renders based on role)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Notifications API
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unreadCount');

    // ---- KC Routes ----
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

    // Memo show (KC & AM can both view)
    Route::get('/memos/{memo}', [MemoController::class, 'show'])->name('memos.show');

    // ---- AM Routes ----
    Route::middleware('role:AM')->group(function () {
        Route::get('/approvals/pending', [ApprovalController::class, 'pending'])->name('approvals.pending');
        Route::get('/approvals/{memo}/review', [ApprovalController::class, 'review'])->name('approvals.review');
        Route::post('/approvals/{memo}/approve', [ApprovalController::class, 'approve'])->name('approvals.approve');
        Route::post('/approvals/{memo}/reject', [ApprovalController::class, 'reject'])->name('approvals.reject');

        // Signature management
        Route::get('/signature', [SignatureController::class, 'index'])->name('signature.index');
        Route::post('/signature', [SignatureController::class, 'store'])->name('signature.store');
        Route::delete('/signature', [SignatureController::class, 'destroy'])->name('signature.destroy');
    });

    // Approval history (KC & AM)
    Route::get('/approvals/{memo}/history', [ApprovalController::class, 'history'])->name('approvals.history');

    // ---- Admin Routes ----
    Route::middleware('role:ADMIN')->prefix('admin')->name('admin.')->group(function () {
        // Templates
        Route::get('/templates', [TemplateController::class, 'index'])->name('templates.index');
        Route::get('/templates/create', [TemplateController::class, 'create'])->name('templates.create');
        Route::post('/templates', [TemplateController::class, 'store'])->name('templates.store');
        Route::get('/templates/{template}/edit', [TemplateController::class, 'edit'])->name('templates.edit');
        Route::put('/templates/{template}', [TemplateController::class, 'update'])->name('templates.update');
        Route::post('/templates/{template}/toggle', [TemplateController::class, 'toggleActive'])->name('templates.toggle');

        // Master Data
        Route::get('/areas', [MasterDataController::class, 'areas'])->name('areas.index');
        Route::post('/areas', [MasterDataController::class, 'storeArea'])->name('areas.store');
        Route::put('/areas/{area}', [MasterDataController::class, 'updateArea'])->name('areas.update');
        Route::delete('/areas/{area}', [MasterDataController::class, 'deleteArea'])->name('areas.destroy');

        Route::get('/branches', [MasterDataController::class, 'branches'])->name('branches.index');
        Route::post('/branches', [MasterDataController::class, 'storeBranch'])->name('branches.store');

        Route::get('/users', [MasterDataController::class, 'users'])->name('users.index');
        Route::post('/users', [MasterDataController::class, 'storeUser'])->name('users.store');
    });
});

require __DIR__.'/auth.php';
