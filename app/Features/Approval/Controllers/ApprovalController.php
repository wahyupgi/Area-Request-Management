<?php

namespace App\Features\Approval\Controllers;

use App\Http\Controllers\Controller;
use App\Features\Approval\Services\ApprovalService;
use App\Models\Memo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ApprovalController extends Controller
{
    public function __construct(protected ApprovalService $service) {}

    /**
     * List pending memos for AM (also includes processed memos for history tab).
     */
    public function pending(Request $request)
    {
        $user = auth()->user();

        $allMemos = Memo::with(['template', 'branch.area', 'creator', 'attachments', 'approvals'])
            ->where('area_manager_id', $user->id)
            ->whereIn('status', ['submitted', 'approved', 'rejected'])
            ->orderBy('updated_at', 'desc')
            ->get();

        return Inertia::render('Approval/Pending', [
            'allMemos'   => $allMemos,
            'defaultTab' => $request->query('tab', 'masuk'),
        ]);
    }

    /**
     * Show memo detail for review.
     */
    public function review(Memo $memo)
    {
        $user = auth()->user();

        if ($memo->area_manager_id !== $user->id) {
            abort(403);
        }

        $this->service->markNotificationsRead($memo, $user->id);

        $memo->load(['template', 'branch.area', 'creator.digitalSignature', 'attachments', 'approvals.approver', 'approvals.signature']);
        $this->service->loadSignaturePeople($memo);

        $signature = $this->service->getUserSignature($user->id);

        return Inertia::render('Approval/Review', [
            'memo' => $memo,
            'signature' => $signature,
        ]);
    }

    /**
     * Approve memo.
     */
    public function approve(Request $request, Memo $memo)
    {
        $user = auth()->user();

        if ($memo->area_manager_id !== $user->id) {
            abort(403);
        }

        if ($memo->status !== 'submitted') {
            return back()->withErrors(['status' => 'Memo ini tidak dalam status submitted.']);
        }

        $signature = $this->service->resolveSignature($request, $user);

        if (!$signature) {
            return back()->withErrors(['signature' => 'Tanda tangan digital belum terdaftar. Silakan upload terlebih dahulu.']);
        }

        $this->service->approveMemo($memo, $user, $signature, $request->notes);

        return redirect()->route('approvals.pending')
            ->with('success', 'Memo berhasil disetujui.');
    }

    /**
     * Reject memo.
     */
    public function reject(Request $request, Memo $memo)
    {
        $user = auth()->user();

        if ($memo->area_manager_id !== $user->id) {
            abort(403);
        }

        if ($memo->status !== 'submitted') {
            return back()->withErrors(['status' => 'Memo ini tidak dalam status submitted.']);
        }

        $request->validate([
            'notes' => 'required|string|min:5',
        ], [
            'notes.required' => 'Catatan alasan penolakan wajib diisi.',
            'notes.min' => 'Catatan minimal 5 karakter.',
        ]);

        $this->service->rejectMemo($memo, $user, $request->notes);

        return redirect()->route('approvals.pending')
            ->with('success', 'Memo berhasil ditolak.');
    }

    /**
     * Update custom signers for a memo by AM.
     */
    public function updateSigners(Request $request, Memo $memo)
    {
        $user = auth()->user();

        if ($memo->area_manager_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'signers' => 'nullable|array',
            'signers.*.name' => 'nullable|string|max:255',
            'signers.*.role' => 'nullable|string|max:100',
            'signers.*.location' => 'nullable|string|in:document,bottom_right',
        ]);

        $fieldValues = $memo->field_values ?? [];
        $fieldValues['custom_signers'] = $validated['signers'] ?? [];
        $memo->update(['field_values' => $fieldValues]);

        return back()->with('success', 'Nama penandatangan berhasil diperbarui.');
    }

    /**
     * View approval history for a memo.
     */
    public function history(Memo $memo)
    {
        $user = auth()->user();

        if ($user->isKC() && $memo->created_by !== $user->id) abort(403);
        if ($user->isAM() && $memo->area_manager_id !== $user->id) abort(403);

        $memo->load(['template', 'branch', 'creator', 'areaManager', 'approvals.approver', 'approvals.signature']);

        return Inertia::render('Approval/History', [
            'memo' => $memo,
        ]);
    }

    /**
     * Update editable header meta fields.
     */
    public function updateMeta(Request $request, Memo $memo)
    {
        $user = auth()->user();

        if ($memo->created_by !== $user->id && $memo->area_manager_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'code'       => ['nullable', 'string', 'max:255'],
            'direktorat' => ['nullable', 'string', 'max:255'],
            'divisi'     => ['nullable', 'string', 'max:255'],
            'perihal'    => ['nullable', 'string', 'max:500'],
            'lampiran'   => ['nullable', 'string', 'max:255'],
        ]);

        if (isset($validated['code'])) {
            $memo->code = $validated['code'];
            unset($validated['code']);
        }

        $fieldValues = $memo->field_values ?? [];
        $fieldValues['meta'] = $validated;
        $memo->field_values = $fieldValues;
        $memo->save();

        return back()->with('success', 'Informasi dokumen berhasil diperbarui.');
    }
}
