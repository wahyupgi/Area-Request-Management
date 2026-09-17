<?php

namespace App\Features\Dashboard\Controllers;

use App\Features\Dashboard\Services\DashboardStatsService;
use App\Http\Controllers\Controller;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(protected DashboardStatsService $statsService) {}

    public function index()
    {
        $user = auth()->user();

        if ($user->isKC()) {
            return $this->kcDashboard($user);
        }

        if ($user->isAM()) {
            return $this->amDashboard($user);
        }

        return $this->adminDashboard();
    }

    private function kcDashboard($user)
    {
        $dashboard = $this->statsService->forKcUser($user);

        return Inertia::render('Dashboard/KC', [
            'memos' => $dashboard['memos'],
            'stats' => $dashboard['stats'],
        ]);
    }

    private function amDashboard($user)
    {
        $dashboard = $this->statsService->forAmUser($user);

        return Inertia::render('Dashboard/AM', [
            'pendingMemos' => $dashboard['pendingMemos'],
            'recentActions' => $dashboard['recentActions'],
            'stats' => $dashboard['stats'],
        ]);
    }

    private function adminDashboard()
    {
        $dashboard = $this->statsService->forAdmin();

        return Inertia::render('Dashboard/Admin', [
            'stats' => $dashboard['stats'],
            'recentMemos' => $dashboard['recentMemos'],
        ]);
    }
}
