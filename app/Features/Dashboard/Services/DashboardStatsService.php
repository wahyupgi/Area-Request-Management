<?php

namespace App\Features\Dashboard\Services;

use App\Models\Area;
use App\Models\Branch;
use App\Models\BeritaAcara;
use App\Models\Memo;
use App\Models\MemoTemplate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DashboardStatsService
{
    public function forKcUser(User $user): array
    {
        $baScope = BeritaAcara::query()->where('created_by', $user->id);
        $memos = Memo::with([
            'template:id,name',
            'branch:id,name',
            'areaManager:id,name',
            'latestApproval:id,memo_approvals.memo_id,action,signed_at',
        ])
            ->where('created_by', $user->id)
            ->orderByDesc('updated_at')
            ->limit(8)
            ->get();
        $memoStats = Memo::where('created_by', $user->id)
            ->selectRaw(
                "COUNT(*) as total, ".
                "SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft, ".
                "SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as submitted, ".
                "SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved, ".
                "SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected"
            )
            ->first();
        $baStats = $this->beritaAcaraStats(clone $baScope);

        return [
            'memos' => $memos,
            'submissionStats' => $this->combineSubmissionStats($memoStats, $baStats),
            'baStats' => $baStats,
            'recentBA' => (clone $baScope)
                ->with('branch:id,name')
                ->orderByDesc('updated_at')
                ->limit(6)
                ->get(),
            'stats' => $this->memoStats($memoStats),
        ];
    }

    public function forAmUser(User $user): array
    {
        $baScope = BeritaAcara::query()->where('area_manager_id', $user->id);
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

        $pendingBA = (clone $baScope)
            ->with(['branch:id,name', 'creator:id,name'])
            ->where('status', BeritaAcara::STATUS_SUBMITTED)
            ->orderByDesc('submitted_at')
            ->limit(6)
            ->get();

        $recentActions = Memo::with([
            'template:id,name',
            'branch:id,name',
            'creator:id,name',
            'latestApproval:id,memo_approvals.memo_id,action,created_at',
        ])
            ->where('area_manager_id', $user->id)
            ->whereIn('status', [Memo::STATUS_APPROVED, Memo::STATUS_REJECTED])
            ->orderByDesc('updated_at')
            ->limit(6)
            ->get();

        $actionStats = Memo::where('area_manager_id', $user->id)
            ->selectRaw(
                "COUNT(*) as total, ".
                "SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft, ".
                "SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as submitted, ".
                "SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved, ".
                "SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected"
            )
            ->first();
        $baStats = $this->beritaAcaraStats(clone $baScope);

        return [
            'pendingMemos' => $pendingMemos,
            'pendingBA' => $pendingBA,
            'recentActions' => $recentActions,
            'submissionStats' => $this->combineSubmissionStats($actionStats, $baStats),
            'baStats' => $baStats,
            'recentBA' => (clone $baScope)
                ->with(['branch:id,name', 'creator:id,name'])
                ->orderByDesc('updated_at')
                ->limit(6)
                ->get(),
            'stats' => [
                'pending' => (int) ($actionStats->submitted ?? 0),
                'approved' => (int) ($actionStats->approved ?? 0),
                'rejected' => (int) ($actionStats->rejected ?? 0),
            ],
        ];
    }

    public function forAdmin(): array
    {
        $baScope = BeritaAcara::query();
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

        $activityCounts = Memo::query()
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw('DATE(created_at) as activity_date, COUNT(*) as total')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('activity_date')
            ->get()
            ->keyBy('activity_date');

        $activity = collect(range(6, 0))->map(function (int $daysAgo) use ($activityCounts) {
            $date = now()->subDays($daysAgo);
            $dateKey = $date->toDateString();

            return [
                'date' => $dateKey,
                'label' => $date->locale('id')->isoFormat('ddd'),
                'count' => (int) ($activityCounts->get($dateKey)->total ?? 0),
            ];
        })->values();

        $chartPeriodStart = now()->startOfYear();
        $chartDocuments = Memo::query()
            ->where('created_at', '>=', $chartPeriodStart)
            ->get(['status', 'created_at'])
            ->map(fn (Memo $memo) => [
                'status' => $memo->status,
                'created_at' => $memo->created_at,
                'type' => 'memo',
            ])
            ->concat(BeritaAcara::query()
                ->where('created_at', '>=', $chartPeriodStart)
                ->get(['status', 'created_at'])
                ->map(fn (BeritaAcara $ba) => [
                    'status' => $ba->status,
                    'created_at' => $ba->created_at,
                    'type' => 'ba',
                ]));

        $chartData = [
            'week' => $this->buildChartPeriod($chartDocuments, 'week'),
            'month' => $this->buildChartPeriod($chartDocuments, 'month'),
            'year' => $this->buildChartPeriod($chartDocuments, 'year'),
        ];

        $baStats = $this->beritaAcaraStats(clone $baScope);

        return [
            'submissionStats' => $this->combineSubmissionStats($memoStats, $baStats),
            'baStats' => $baStats,
            'recentBA' => (clone $baScope)
                ->with(['branch:id,name', 'creator:id,name'])
                ->orderByDesc('updated_at')
                ->limit(6)
                ->get(),
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
            'activity' => $activity,
            'chartData' => $chartData,
        ];
    }

    private function beritaAcaraStats(Builder $query): array
    {
        $stats = $query->selectRaw(
            "COUNT(*) as total, ".
            "SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft, ".
            "SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as submitted, ".
            "SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved, ".
            "SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected"
        )->first();

        return [
            'total' => (int) ($stats->total ?? 0),
            'draft' => (int) ($stats->draft ?? 0),
            'submitted' => (int) ($stats->submitted ?? 0),
            'approved' => (int) ($stats->approved ?? 0),
            'rejected' => (int) ($stats->rejected ?? 0),
        ];
    }

    private function memoStats(object $stats): array
    {
        return [
            'total' => (int) ($stats->total ?? 0),
            'draft' => (int) ($stats->draft ?? 0),
            'submitted' => (int) ($stats->submitted ?? 0),
            'approved' => (int) ($stats->approved ?? 0),
            'rejected' => (int) ($stats->rejected ?? 0),
        ];
    }

    private function combineSubmissionStats(object $memoStats, array $baStats): array
    {
        $stats = $this->memoStats($memoStats);
        foreach (['total', 'draft', 'submitted', 'approved', 'rejected'] as $key) {
            $stats[$key] += $baStats[$key];
        }
        $stats['received'] = $stats['total'] - $stats['draft'];

        return $stats;
    }

    private function buildChartPeriod(Collection $documents, string $period): array
    {
        if ($period === 'week') {
            $periods = collect(range(6, 0))->map(function (int $daysAgo) {
                $date = now()->subDays($daysAgo);

                return [
                    'key' => $date->toDateString(),
                    'label' => $date->locale('id')->isoFormat('ddd'),
                ];
            });
        } elseif ($period === 'month') {
            $periods = collect(range(4, 0))->map(function (int $weeksAgo) {
                $date = now()->subWeeks($weeksAgo)->startOfWeek();

                return [
                    'key' => $date->format('o-W'),
                    'label' => 'M' . (5 - $weeksAgo),
                ];
            });
        } else {
            $periods = collect(range(1, 12))->map(function (int $month) {
                $date = Carbon::create(now()->year, $month, 1);

                return [
                    'key' => $date->format('Y-m'),
                    'label' => $date->locale('id')->isoFormat('MMM'),
                ];
            });
        }

        $grouped = $documents->groupBy(function (array $document) use ($period) {
            $date = $document['created_at'];

            return match ($period) {
                'week' => $date->toDateString(),
                'month' => $date->format('o-W'),
                default => $date->format('Y-m'),
            };
        });

        return $periods->map(function (array $periodItem) use ($grouped) {
            $byType = $grouped->get($periodItem['key'], collect())->groupBy('type');

            return [
                'label' => $periodItem['label'],
                'memo' => $this->chartStatusCounts($byType->get('memo', collect())),
                'ba' => $this->chartStatusCounts($byType->get('ba', collect())),
            ];
        })->values()->all();
    }

    private function chartStatusCounts(Collection $documents): array
    {
        $counts = $documents->countBy('status');

        return [
            'approved' => (int) $counts->get('approved', 0),
            'submitted' => (int) $counts->get('submitted', 0),
            'rejected' => (int) $counts->get('rejected', 0),
        ];
    }
}
