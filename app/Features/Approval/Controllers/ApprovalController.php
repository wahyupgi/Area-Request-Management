<?php

namespace App\Features\Approval\Controllers;

use App\Features\Approval\Requests\ApproveMemoRequest;
use App\Features\Approval\Requests\RejectMemoRequest;
use App\Features\Approval\Requests\UpdateMemoMetaRequest;
use App\Features\Approval\Requests\UpdateSignersRequest;
use App\Http\Controllers\Controller;
use App\Features\Approval\Services\ApprovalService;
use App\Features\Memo\Repositories\MemoRepository;
use App\Models\BeritaAcara;
use App\Models\Memo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ApprovalController extends Controller
{
    public function __construct(
        protected ApprovalService $service,
        protected MemoRepository $memoRepository,
    ) {}

    /**
     * List pending memos for AM (also includes processed memos for history tab).
     */
    public function pending(Request $request)
    {
        $user = auth()->user();

        $allMemos = $this->memoRepository->listForApprovalInbox(
            $user,
            $request->input('per_page', 12),
        );

        // Ambil Berita Acara yang masuk ke AM ini
        $pendingBA = BeritaAcara::with(['branch:id,name', 'creator:id,name'])
            ->where('area_manager_id', $user->id)
            ->orderByDesc('updated_at')
            ->get();

        return Inertia::render('Approval/Pending', [
            'allMemos'   => $allMemos,
            'defaultTab' => $request->query('tab', 'masuk'),
            'pendingBA'  => $pendingBA,
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
    public function approve(ApproveMemoRequest $request, Memo $memo)
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
    public function reject(RejectMemoRequest $request, Memo $memo)
    {
        $user = auth()->user();

        if ($memo->area_manager_id !== $user->id) {
            abort(403);
        }

        if ($memo->status !== 'submitted') {
            return back()->withErrors(['status' => 'Memo ini tidak dalam status submitted.']);
        }

        $this->service->rejectMemo($memo, $user, $request->notes);

        return redirect()->route('approvals.pending')
            ->with('success', 'Memo berhasil ditolak.');
    }

    /**
     * Update custom signers for a memo by AM.
     */
    public function updateSigners(UpdateSignersRequest $request, Memo $memo)
    {
        $user = auth()->user();

        if ($memo->area_manager_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validated();

        $fieldValues = $memo->field_values ?? [];
        $fieldValues['custom_signers'] = $validated['signers'] ?? [];
        $fieldValues['footer_box_count'] = $validated['footer_box_count'] ?? 2;
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

        $memo->load(['template', 'branch.area', 'creator.digitalSignature', 'areaManager.digitalSignature', 'attachments', 'approvals.approver', 'approvals.signature']);
        $this->service->loadSignaturePeople($memo);

        return Inertia::render('Approval/History', [
            'memo' => $memo,
        ]);
    }

    /**
     * Update editable header meta fields.
     */
    public function updateMeta(UpdateMemoMetaRequest $request, Memo $memo)
    {
        $user = auth()->user();

        if ($memo->created_by !== $user->id && $memo->area_manager_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validated();

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
