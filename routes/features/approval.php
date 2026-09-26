<?php

use App\Features\Approval\Controllers\ApprovalController;
use App\Features\Approval\Controllers\BeritaAcaraApprovalController;
use App\Features\Signature\Controllers\SignatureController;
use Illuminate\Support\Facades\Route;

// AM-only routes
Route::middleware('role:AM')->group(function () {
    // Approval
    Route::get('/approvals/pending', [ApprovalController::class, 'pending'])->name('approvals.pending');
    Route::get('/approvals/{memo}/review', [ApprovalController::class, 'review'])->name('approvals.review');
    Route::post('/approvals/{memo}/approve', [ApprovalController::class, 'approve'])->name('approvals.approve');
    Route::post('/approvals/{memo}/reject', [ApprovalController::class, 'reject'])->name('approvals.reject');
    Route::post('/approvals/{memo}/signers', [ApprovalController::class, 'updateSigners'])->name('approvals.updateSigners');

    // Berita Acara Approval for AM
    Route::get('/approvals/ba/{beritaAcara}/review', [BeritaAcaraApprovalController::class, 'review'])->name('approvals.ba.review');
    Route::post('/approvals/ba/{beritaAcara}/approve', [BeritaAcaraApprovalController::class, 'approve'])->name('approvals.ba.approve');
    Route::post('/approvals/ba/{beritaAcara}/reject', [BeritaAcaraApprovalController::class, 'reject'])->name('approvals.ba.reject');

    // Signature management (AM manages their own signature)
    Route::get('/signature', [SignatureController::class, 'index'])->name('signature.index');
    Route::post('/signature', [SignatureController::class, 'store'])->name('signature.store');
    Route::delete('/signature', [SignatureController::class, 'destroy'])->name('signature.destroy');
    Route::get('/signature/settings', [SignatureController::class, 'settings'])->name('signature.settings');
    Route::put('/signature/settings/{template}', [SignatureController::class, 'updateSettings'])->name('signature.settings.update');
});

// KC & AM can both view approval history and update meta
Route::get('/approvals/{memo}/history', [ApprovalController::class, 'history'])->name('approvals.history');
Route::post('/approvals/{memo}/update-meta', [ApprovalController::class, 'updateMeta'])->name('approvals.updateMeta');
Route::get('/approvals/ba/{beritaAcara}/history', [BeritaAcaraApprovalController::class, 'history'])->name('approvals.ba.history');
