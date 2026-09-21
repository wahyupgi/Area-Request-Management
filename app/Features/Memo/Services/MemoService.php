<?php

namespace App\Features\Memo\Services;

use App\Models\DigitalSignature;
use App\Models\Memo;
use App\Models\MemoAttachment;
use App\Models\Notification;
use App\Models\User;
use App\Mail\MemoSubmitted;
use Illuminate\Mail\Mailable;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class MemoService
{
    /**
     * Create a new memo as draft.
     */
    public function createDraft(Request $request): Memo
    {
        $user = auth()->user();
        $branch = $user->branch;

        if (!$branch) {
            back()->withErrors(['branch' => 'Anda belum terdaftar di cabang manapun.'])->throwResponse();
        }

        $areaManager = User::where('role', 'AM')
            ->where('area_id', $branch->area_id)
            ->first();

        $memoData = [
            'code' => null,
            'template_id' => $request->template_id,
            'title' => $request->title,
            'field_values' => $request->field_values ?? [],
            'branch_id' => $branch->id,
            'created_by' => $user->id,
            'area_manager_id' => $areaManager?->id,
            'status' => 'draft',
        ];

        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                $memo = Memo::create($memoData);
                break;
            } catch (QueryException $exception) {
                $isUniqueViolation = in_array((string) $exception->getCode(), ['23000', '23505'], true);

                if (!$isUniqueViolation || $attempt === 2) {
                    throw $exception;
                }
            }
        }

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            MemoAttachment::create([
                'memo_id' => $memo->id,
                'file_path' => $file->store('attachments/' . $memo->id, 'public'),
                'original_name' => $file->getClientOriginalName(),
            ]);
        }

        return $memo;
    }

    /**
     * Authorize user access to a memo.
     */
    public function authorizeAccess(Memo $memo): void
    {
        $user = auth()->user();

        if ($user->isAdmin()) return;
        if ($user->isKC() && $memo->created_by === $user->id) return;
        if ($user->isAM() && $memo->area_manager_id === $user->id) return;

        abort(403);
    }

    /**
     * Mark all unread notifications for this memo as read.
     */
    public function markNotificationsRead(Memo $memo): void
    {
        Notification::where('user_id', auth()->id())
            ->where('memo_id', $memo->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    /**
     * Load signature people into the memo template attribute.
     */
    public function loadConfiguredSigners(Memo $memo): void
    {
        $ids = collect($memo->template?->signature_schema ?? [])->pluck('user_id')->filter()->unique();
        $people = User::with('digitalSignature')->whereIn('id', $ids)->get()->keyBy('id');
        $memo->template?->setAttribute('signature_people', $people);
    }

    /**
     * Validate required fields from template schema.
     */
    public function validateRequiredFields(Memo $memo): array
    {
        $template = $memo->template;
        $fieldValues = $memo->field_values ?? [];
        $items = isset($fieldValues['items']) && is_array($fieldValues['items'])
            ? $fieldValues['items']
            : [$fieldValues];
        $errors = [];

        foreach ($items as $itemIndex => $item) {
            foreach ($template->field_schema as $field) {
                if ($field['required'] && empty($item[$field['key']])) {
                    $errors['field_values.items.' . $itemIndex . '.' . $field['key']] =
                        $field['label'] . ' pada item ' . ($itemIndex + 1) . ' wajib diisi.';
                }
            }
        }

        return $errors;
    }

    /**
     * Resolve or upload digital signature for user.
     */
    public function resolveSignature(Request $request, $user): ?DigitalSignature
    {
        $signature = DigitalSignature::where('user_id', $user->id)->first();

        if ($request->hasFile('signature_image')) {
            $path = $request->file('signature_image')->store('signatures', 'public');

            if ($signature) {
                Storage::disk('public')->delete($signature->signature_image);
                $signature->update(['signature_image' => $path]);
            } else {
                $signature = DigitalSignature::create([
                    'user_id' => $user->id,
                    'signature_image' => $path,
                ]);
            }
        }

        return $signature;
    }

    /**
     * Submit memo to AM and notify.
     */
    public function submitMemo(Memo $memo): void
    {
        DB::transaction(function () use ($memo) {
            $memo->update([
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            Notification::create([
                'user_id' => $memo->area_manager_id,
                'memo_id' => $memo->id,
                'message' => 'Memo baru "' . $memo->title . '" dari ' . $memo->creator->name . ' menunggu persetujuan Anda.',
            ]);
        });

        $memo->loadMissing(['creator', 'areaManager', 'branch', 'template']);
        if ($memo->areaManager?->email) {
            $this->queueMail(
                $memo->areaManager->email,
                new MemoSubmitted($memo),
                'MemoSubmitted'
            );
        }
    }

    private function queueMail(string $email, Mailable $mailable, string $context): void
    {
        try {
            Mail::to($email)->queue($mailable);
        } catch (\Throwable $e) {
            \Log::warning("Gagal mengantrekan email {$context}: " . $e->getMessage());
        }
    }

    /**
     * Delete a memo attachment from storage and DB.
     */
    public function deleteAttachment(MemoAttachment $attachment): void
    {
        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();
    }

    /**
     * Delete a memo and all its attachments.
     */
    public function deleteMemo(Memo $memo): void
    {
        foreach ($memo->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->file_path);
        }
        $memo->delete();
    }
}
