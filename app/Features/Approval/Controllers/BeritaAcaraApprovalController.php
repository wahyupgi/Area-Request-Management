<?php

namespace App\Features\Approval\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BeritaAcara;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

        $beritaAcara->load(['branch', 'creator.digitalSignature', 'areaManager.digitalSignature']);

        return Inertia::render('BeritaAcara/Review', [
            'beritaAcara' => $beritaAcara,
        ]);
    }

    /**
    * Update BA document information while it is awaiting AM approval.
     */
    public function updateCode(Request $request, BeritaAcara $beritaAcara)
    {
        $user = auth()->user();

        if ($beritaAcara->area_manager_id !== $user->id) {
            abort(403);
        }

        if ($beritaAcara->status !== BeritaAcara::STATUS_SUBMITTED) {
            return back()->withErrors(['status' => 'Nomor BA hanya dapat diubah saat menunggu persetujuan.']);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:255', Rule::unique('berita_acaras', 'code')->ignore($beritaAcara->id)],
            'title' => 'required|string|max:500',
            'meta.direktorat' => 'nullable|string|max:255',
            'meta.divisi' => 'nullable|string|max:255',
            'meta.perihal' => 'nullable|string|max:500',
            'meta.kepada_nama' => 'nullable|string|max:255',
            'meta.kepada_jabatan' => 'nullable|string|max:255',
            'meta.penyetuju_akhir' => 'nullable|string|max:255',
            'meta.lampiran' => 'nullable|string|max:255',
        ]);

        $meta = array_replace($beritaAcara->meta ?? [], $validated['meta'] ?? []);
        $beritaAcara->update([
            'code' => $validated['code'],
            'title' => $validated['title'],
            'meta' => $meta,
        ]);

        return back()->with('success', 'Informasi dokumen BA berhasil diperbarui.');
    }

    /**
     * Update the names and roles shown in the BA signature columns.
     */
    public function updateSigners(Request $request, BeritaAcara $beritaAcara)
    {
        $user = auth()->user();

        if ($beritaAcara->area_manager_id !== $user->id) {
            abort(403);
        }

        if ($beritaAcara->status !== BeritaAcara::STATUS_SUBMITTED) {
            return back()->withErrors(['status' => 'Penandatangan BA hanya dapat diubah saat menunggu persetujuan.']);
        }

        $validated = $request->validate([
            'signers' => ['required', 'array', 'min:1', 'max:4'],
            'signers.*.name' => ['nullable', 'string', 'max:255'],
            'signers.*.role' => ['nullable', 'string', 'max:255'],
            'signers.*.enabled' => ['required', 'boolean'],
            'footer_box_count' => ['required', 'integer', 'in:1,2'],
        ]);

        $meta = $beritaAcara->meta ?? [];
        $meta['signature_signers'] = $validated['signers'];
        $meta['footer_box_count'] = $validated['footer_box_count'];
        $beritaAcara->update(['meta' => $meta]);

        return back()->with('success', 'Penandatangan BA berhasil diperbarui.');
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

        if (!$user->digitalSignature) {
            return back()->withErrors(['signature' => 'Tanda tangan digital belum terdaftar. Silakan upload terlebih dahulu.']);
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

        $beritaAcara->load([
            'branch.area.areaManager.digitalSignature',
            'creator.digitalSignature',
            'areaManager.digitalSignature',
        ]);

        if ($user->isKC() && $beritaAcara->created_by !== $user->id) abort(403);
        $branchAreaManager = $beritaAcara->branch?->area?->areaManager;
        $areaManagerId = $beritaAcara->area_manager_id ?? $branchAreaManager?->id;
        if ($user->isAM() && $areaManagerId !== $user->id) abort(403);

        if (!$beritaAcara->areaManager && $branchAreaManager) {
            $beritaAcara->setRelation('areaManager', $branchAreaManager);
        }

        return Inertia::render('BeritaAcara/History', [
            'beritaAcara' => $beritaAcara,
        ]);
    }
}
