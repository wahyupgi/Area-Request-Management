<?php

namespace App\Features\Dashboard\Controllers;

use App\Features\Dashboard\Services\DashboardStatsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(protected DashboardStatsService $statsService) {}

    public function index(Request $request)
    {
        $user = auth()->user();

        if ($user->isKC()) {
            return $this->kcDashboard($user, $request);
        }

        if ($user->isAM()) {
            return $this->amDashboard($user);
        }

        return $this->adminDashboard();
    }

    private function kcDashboard($user, Request $request)
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);
        $month = $validated['month'] ?? now()->format('Y-m');
        $dashboard = $this->statsService->forKcUser($user, $month);

        return Inertia::render('Dashboard/KC', [
            'month' => $month,
            'memos' => $dashboard['memos'],
            'submissionStats' => $dashboard['submissionStats'],
            'baStats' => $dashboard['baStats'],
            'recentBA' => $dashboard['recentBA'],
            'recentFormPengajuans' => $dashboard['recentFormPengajuans'],
            'formPengajuanStats' => $dashboard['formPengajuanStats'],
            'stats' => $dashboard['stats'],
        ]);
    }

    private function amDashboard($user)
    {
        $dashboard = $this->statsService->forAmUser($user);

        return Inertia::render('Dashboard/AM', [
            'pendingMemos' => $dashboard['pendingMemos'],
            'pendingBA' => $dashboard['pendingBA'],
            'pendingFormPengajuans' => $dashboard['pendingFormPengajuans'],
            'recentActions' => $dashboard['recentActions'],
            'submissionStats' => $dashboard['submissionStats'],
            'baStats' => $dashboard['baStats'],
            'recentBA' => $dashboard['recentBA'],
            'recentFormPengajuans' => $dashboard['recentFormPengajuans'],
            'formPengajuanStats' => $dashboard['formPengajuanStats'],
            'stats' => $dashboard['stats'],
        ]);
    }

    private function adminDashboard()
    {
        $dashboard = $this->statsService->forAdmin();

        return Inertia::render('Dashboard/Admin', [
            'stats' => $dashboard['stats'],
            'recentMemos' => $dashboard['recentMemos'],
            'activity' => $dashboard['activity'],
            'chartData' => $dashboard['chartData'],
            'submissionStats' => $dashboard['submissionStats'],
            'baStats' => $dashboard['baStats'],
            'recentBA' => $dashboard['recentBA'],
            'recentFormPengajuans' => $dashboard['recentFormPengajuans'],
            'formPengajuanStats' => $dashboard['formPengajuanStats'],
        ]);
    }
}
