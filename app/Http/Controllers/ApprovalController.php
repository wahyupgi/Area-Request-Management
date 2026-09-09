<?php

namespace App\Http\Controllers;

use App\Models\DigitalSignature;
use App\Models\Memo;
use App\Models\MemoApproval;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ApprovalController extends Controller
{
    /**
     * List pending memos for AM.
     */
    public function pending()
    {
        $user = auth()->user();

        $memos = Memo::with(['template', 'branch.area', 'creator', 'attachments'])
            ->where('area_manager_id', $user->id)
            ->where('status', 'submitted')
            ->orderBy('submitted_at', 'desc')
            ->get();

        return Inertia::render('Approval/Pending', [
            'memos' => $memos,
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

        $memo->load(['template', 'branch.area', 'creator.digitalSignature', 'attachments', 'approvals.approver', 'approvals.signature']);
        $ids = collect($memo->template?->signature_schema ?? [])->pluck('user_id')->filter()->unique();
        $memo->template?->setAttribute('signature_people', User::with('digitalSignature')->whereIn('id', $ids)->get()->keyBy('id'));

        $signature = DigitalSignature::where('user_id', $user->id)->first();

        return Inertia::render('Approval/Review', [
            'memo' => $memo,
            'signature' => $signature,
        ]);
    }

    /**
     * Approve memo — done in a single DB transaction.
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

        // Check/create signature
        $signature = DigitalSignature::where('user_id', $user->id)->first();

        if (!$signature && $request->hasFile('signature_image')) {
            $path = $request->file('signature_image')->store('signatures', 'public');
            $signature = DigitalSignature::create([
                'user_id' => $user->id,
                'signature_image' => $path,
            ]);
        }

        if (!$signature) {
            return back()->withErrors(['signature' => 'Tanda tangan digital belum terdaftar. Silakan upload terlebih dahulu.']);
        }

        DB::transaction(function () use ($memo, $user, $signature, $request) {
            // Update memo status
            $memo->update(['status' => 'approved']);

            // Insert approval record
            MemoApproval::create([
                'memo_id' => $memo->id,
                'approver_id' => $user->id,
                'action' => 'approved',
                'notes' => $request->notes,
                'signature_id' => $signature->id,
                'signed_at' => now(),
            ]);

            // Notify KC
            Notification::create([
                'user_id' => $memo->created_by,
                'memo_id' => $memo->id,
                'message' => 'Memo "' . $memo->title . '" telah DISETUJUI oleh ' . $user->name . '.',
            ]);
        });

        return redirect()->route('approvals.pending')
            ->with('success', 'Memo berhasil disetujui.');
    }

    /**
     * Reject memo — done in a single DB transaction.
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

        DB::transaction(function () use ($memo, $user, $request) {
            // Update memo status
            $memo->update(['status' => 'rejected']);

            // Insert approval record (rejection)
            MemoApproval::create([
                'memo_id' => $memo->id,
                'approver_id' => $user->id,
                'action' => 'rejected',
                'notes' => $request->notes,
            ]);

            // Notify KC
            Notification::create([
                'user_id' => $memo->created_by,
                'memo_id' => $memo->id,
                'message' => 'Memo "' . $memo->title . '" telah DITOLAK oleh ' . $user->name . '. Alasan: ' . $request->notes,
            ]);
        });

        return redirect()->route('approvals.pending')
            ->with('success', 'Memo berhasil ditolak.');
    }

    /**
     * View approval history for a memo.
     */
    public function history(Memo $memo)
    {
        $user = auth()->user();

        // KC can see their own memo history, AM can see memos assigned to them
        if ($user->isKC() && $memo->created_by !== $user->id) {
            abort(403);
        }
        if ($user->isAM() && $memo->area_manager_id !== $user->id) {
            abort(403);
        }

        $memo->load(['template', 'branch', 'creator', 'areaManager', 'approvals.approver', 'approvals.signature']);

        return Inertia::render('Approval/History', [
            'memo' => $memo,
        ]);
    }
}
