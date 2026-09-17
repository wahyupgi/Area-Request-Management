<?php

namespace App\Features\Dashboard\Services;

use App\Models\Area;
use App\Models\Branch;
use App\Models\Memo;
use App\Models\MemoTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class DashboardStatsService
{
    public function forKcUser(User $user): array
    {
        $memos = Memo::with([
            'template:id,name',
            'branch:id,name',
            'areaManager:id,name',
            'latestApproval:id,memo_id,action,signed_at',
        ])
            ->where('created_by', $user->id)
            ->orderByDesc('updated_at')
            ->limit(8)
            ->get();

        return [
            'memos' => $memos,
            'stats' => [
                'total' => $memos->count(),
                'draft' => $memos->where('status', Memo::STATUS_DRAFT)->count(),
                'submitted' => $memos->where('status', Memo::STATUS_SUBMITTED)->count(),
                'approved' => $memos->where('status', Memo::STATUS_APPROVED)->count(),
                'rejected' => $memos->where('status', Memo::STATUS_REJECTED)->count(),
            ],
        ];
    }

    public function forAmUser(User $user): array
    {
        $pendingMemos = Memo::with([
            'template:id,name',
            'branch:id,name',
            'creator:id,name',
            'attachments:id,memo_id',
        ])
            ->where('area_manager_id', $user->id)
            ->where('status', Memo::STATUS_SUBMITTED)
            ->orderByDesc('submitted_at')
            ->limit(6)
            ->get();

        $recentActions = Memo::with([
            'template:id,name',
            'branch:id,name',
            'creator:id,name',
            'latestApproval:id,memo_id,action,created_at',
        ])
            ->where('area_manager_id', $user->id)
            ->whereIn('status', [Memo::STATUS_APPROVED, Memo::STATUS_REJECTED])
            ->orderByDesc('updated_at')
            ->limit(6)
            ->get();

        $actionStats = Memo::where('area_manager_id', $user->id)
            ->selectRaw(
                "SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved, ".
                "SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected"
            )
            ->first();

        return [
            'pendingMemos' => $pendingMemos,
            'recentActions' => $recentActions,
            'stats' => [
                'pending' => $pendingMemos->count(),
                'approved' => (int) ($actionStats->approved ?? 0),
                'rejected' => (int) ($actionStats->rejected ?? 0),
            ],
        ];
    }

    public function forAdmin(): array
    {
        $memoStats = Memo::selectRaw(
            "COUNT(*) as total, ".
            "SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved, ".
            "SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as submitted, ".
            "SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected, ".
            "SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft"
        )->first();

        $recentMemos = Memo::with(['template:id,name', 'branch:id,name', 'creator:id,name', 'areaManager:id,name'])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        return [
            'stats' => [
                'total_memos' => (int) ($memoStats->total ?? 0),
                'approved_memos' => (int) ($memoStats->approved ?? 0),
                'pending_approvals' => (int) ($memoStats->submitted ?? 0),
                'rejected_memos' => (int) ($memoStats->rejected ?? 0),
                'draft_memos' => (int) ($memoStats->draft ?? 0),
                'total_templates' => MemoTemplate::count(),
                'total_users' => User::count(),
                'total_areas' => Area::count(),
                'total_branches' => Branch::count(),
            ],
            'recentMemos' => $recentMemos,
        ];
    }
}
