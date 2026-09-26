<?php

namespace App\Features\Approval\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BeritaAcara;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BeritaAcaraApprovalController extends Controller
{
    /**
     * Show Berita Acara detail for review by AM.
     */
    public function review(BeritaAcara $beritaAcara)
    {
        $user = auth()->user();

        if ($beritaAcara->area_manager_id !== $user->id) {
            abort(403);
        }

        $beritaAcara->load(['branch', 'creator', 'areaManager.digitalSignature']);

        return Inertia::render('BeritaAcara/Review', [
            'beritaAcara' => $beritaAcara,
        ]);
    }

    /**
     * Approve Berita Acara.
     */
    public function approve(Request $request, BeritaAcara $beritaAcara)
    {
        $user = auth()->user();

        if ($beritaAcara->area_manager_id !== $user->id) {
            abort(403);
        }

        if ($beritaAcara->status !== BeritaAcara::STATUS_SUBMITTED) {
            return back()->withErrors(['status' => 'Berita Acara ini tidak dalam status submitted.']);
        }

        $beritaAcara->update([
            'status' => BeritaAcara::STATUS_APPROVED,
        ]);

        return redirect()->route('approvals.pending')
            ->with('success', 'Berita Acara berhasil disetujui.');
    }

    /**
     * Reject Berita Acara.
     */
    public function reject(Request $request, BeritaAcara $beritaAcara)
    {
        $user = auth()->user();

        if ($beritaAcara->area_manager_id !== $user->id) {
            abort(403);
        }

        if ($beritaAcara->status !== BeritaAcara::STATUS_SUBMITTED) {
            return back()->withErrors(['status' => 'Berita Acara ini tidak dalam status submitted.']);
        }

        $request->validate([
            'notes' => 'required|string|max:1000',
        ]);

        $beritaAcara->update([
            'status' => BeritaAcara::STATUS_REJECTED,
        ]);

        // We can add notes saving logic if there's a table for it, or just to memo if we add a column.
        // Since BA might not have approval notes history yet, we just update status for now.

        return redirect()->route('approvals.pending')
            ->with('success', 'Berita Acara berhasil ditolak.');
    }

    /**
     * View approval history for a Berita Acara.
     */
    public function history(BeritaAcara $beritaAcara)
    {
        $user = auth()->user();

        if ($user->isKC() && $beritaAcara->created_by !== $user->id) abort(403);
        if ($user->isAM() && $beritaAcara->area_manager_id !== $user->id) abort(403);

        $beritaAcara->load(['branch', 'creator', 'areaManager.digitalSignature']);

        return Inertia::render('BeritaAcara/History', [
            'beritaAcara' => $beritaAcara,
        ]);
    }
}
