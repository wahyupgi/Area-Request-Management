<?php

namespace App\Features\Memo\Repositories;

use App\Models\Memo;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class MemoRepository
{
    public function listForUser(User $user, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->baseQueryForUser($user)
            ->filterByStatus($status)
            ->orderByDesc('updated_at')
            ->paginate($perPage)
            ->appends(['status' => $status]);
    }

    public function listForApprovalInbox(User $user, int $perPage = 12): LengthAwarePaginator
    {
        return Memo::with([
            'template:id,name',
            'branch:id,name',
            'creator:id,name',
            'attachments:id,memo_id',
            'approvals:id,memo_id,action,notes,created_at',
        ])
            ->where('area_manager_id', $user->id)
            ->whereIn('status', [
                Memo::STATUS_SUBMITTED,
                Memo::STATUS_APPROVED,
                Memo::STATUS_REJECTED,
            ])
            ->orderByDesc('updated_at')
            ->paginate($perPage);
    }

    public function baseQueryForUser(User $user): Builder
    {
        return Memo::with([
            'template:id,name',
            'branch:id,name',
            'areaManager:id,name',
            'latestApproval:id,memo_id,action,signed_at',
            'attachments:id,memo_id',
        ])->forUser($user);
    }
}
