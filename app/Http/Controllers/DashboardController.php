<?php

namespace App\Http\Controllers;

use App\Models\Memo;
use App\Models\MemoTemplate;
use App\Models\Notification;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user->isKC()) {
            return $this->kcDashboard($user);
        } elseif ($user->isAM()) {
            return $this->amDashboard($user);
        } else {
            return $this->adminDashboard($user);
        }
    }

    private function kcDashboard($user)
    {
        $memos = Memo::with(['template', 'branch', 'areaManager', 'latestApproval'])
            ->where('created_by', $user->id)
            ->orderBy('updated_at', 'desc')
            ->get();

        $stats = [
            'total' => $memos->count(),
            'draft' => $memos->where('status', 'draft')->count(),
            'submitted' => $memos->where('status', 'submitted')->count(),
            'approved' => $memos->where('status', 'approved')->count(),
            'rejected' => $memos->where('status', 'rejected')->count(),
        ];

        return Inertia::render('Dashboard/KC', [
            'memos' => $memos,
            'stats' => $stats,
        ]);
    }

    private function amDashboard($user)
    {
        $pendingMemos = Memo::with(['template', 'branch', 'creator', 'attachments'])
            ->where('area_manager_id', $user->id)
            ->where('status', 'submitted')
            ->orderBy('submitted_at', 'desc')
            ->get();

        $recentActions = Memo::with(['template', 'branch', 'creator', 'latestApproval'])
            ->where('area_manager_id', $user->id)
            ->whereIn('status', ['approved', 'rejected'])
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        $stats = [
            'pending' => $pendingMemos->count(),
            'approved' => Memo::where('area_manager_id', $user->id)->where('status', 'approved')->count(),
            'rejected' => Memo::where('area_manager_id', $user->id)->where('status', 'rejected')->count(),
        ];

        return Inertia::render('Dashboard/AM', [
            'pendingMemos' => $pendingMemos,
            'recentActions' => $recentActions,
            'stats' => $stats,
        ]);
    }

    private function adminDashboard($user)
    {
        $memos = Memo::with(['template', 'branch.area', 'creator', 'areaManager'])
            ->orderBy('created_at', 'desc')
            ->get();

        $stats = [
            'total_memos' => $memos->count(),
            'approved_memos' => $memos->where('status', 'approved')->count(),
            'pending_approvals' => $memos->where('status', 'submitted')->count(),
            'rejected_memos' => $memos->where('status', 'rejected')->count(),
            'draft_memos' => $memos->where('status', 'draft')->count(),
            'total_templates' => MemoTemplate::count(),
            'total_users' => \App\Models\User::count(),
            'total_areas' => \App\Models\Area::count(),
            'total_branches' => \App\Models\Branch::count(),
        ];

        $recentMemos = $memos->take(15)->values();

        return Inertia::render('Dashboard/Admin', [
            'stats' => $stats,
            'recentMemos' => $recentMemos,
        ]);
    }
}
