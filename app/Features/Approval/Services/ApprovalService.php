<?php

namespace App\Features\Approval\Services;

use App\Models\DigitalSignature;
use App\Models\Memo;
use App\Models\MemoApproval;
use App\Models\Notification;
use App\Models\User;
use App\Mail\MemoApproved;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class ApprovalService
{
    /**
     * Mark notifications as read for a memo.
     */
    public function markNotificationsRead(Memo $memo, int $userId): void
    {
        Notification::where('user_id', $userId)
            ->where('memo_id', $memo->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    /**
     * Load signature people into memo template attribute.
     */
    public function loadSignaturePeople(Memo $memo): void
    {
        $ids = collect($memo->template?->signature_schema ?? [])->pluck('user_id')->filter()->unique();
        $memo->template?->setAttribute(
            'signature_people',
            User::with('digitalSignature')->whereIn('id', $ids)->get()->keyBy('id')
        );
    }

    /**
     * Get digital signature for a user.
     */
    public function getUserSignature(int $userId): ?DigitalSignature
    {
        return DigitalSignature::where('user_id', $userId)->first();
    }

    /**
     * Resolve or upload digital signature for AM.
     */
    public function resolveSignature(Request $request, $user): ?DigitalSignature
    {
        $signature = DigitalSignature::where('user_id', $user->id)->first();

        if (!$signature && $request->hasFile('signature_image')) {
            $path = $request->file('signature_image')->store('signatures', 'public');
            $signature = DigitalSignature::create([
                'user_id' => $user->id,
                'signature_image' => $path,
            ]);
        }

        return $signature;
    }

    /**
     * Approve a memo and notify the creator.
     */
    public function approveMemo(Memo $memo, $user, DigitalSignature $signature, ?string $notes): void
    {
        DB::transaction(function () use ($memo, $user, $signature, $notes) {
            $memo->update(['status' => 'approved']);

            MemoApproval::create([
                'memo_id' => $memo->id,
                'approver_id' => $user->id,
                'action' => 'approved',
                'notes' => $notes,
                'signature_id' => $signature->id,
                'signed_at' => now(),
            ]);

            Notification::create([
                'user_id' => $memo->created_by,
                'memo_id' => $memo->id,
                'message' => 'Memo "' . $memo->title . '" telah DISETUJUI oleh ' . $user->name . '.',
            ]);
        });

        $memo->loadMissing(['creator', 'areaManager', 'branch', 'template']);
        if ($memo->creator?->email) {
            try {
                Mail::to($memo->creator->email)->send(new MemoApproved($memo));
            } catch (\Throwable $e) {
                \Log::warning('Gagal mengirim email MemoApproved: ' . $e->getMessage());
            }
        }
    }

    /**
     * Reject a memo and notify the creator.
     */
    public function rejectMemo(Memo $memo, $user, string $notes): void
    {
        DB::transaction(function () use ($memo, $user, $notes) {
            $memo->update(['status' => 'rejected']);

            MemoApproval::create([
                'memo_id' => $memo->id,
                'approver_id' => $user->id,
                'action' => 'rejected',
                'notes' => $notes,
            ]);

            Notification::create([
                'user_id' => $memo->created_by,
                'memo_id' => $memo->id,
                'message' => 'Memo "' . $memo->title . '" telah DITOLAK oleh ' . $user->name . '. Alasan: ' . $notes,
            ]);
        });
    }
}
