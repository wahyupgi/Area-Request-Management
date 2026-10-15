<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Area;
use App\Models\BeritaAcara;
use App\Models\Branch;
use App\Models\FormPengajuan;
use App\Models\Memo;
use App\Models\MemoTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KcDashboardMonthlyStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_kc_dashboard_shows_selected_month_stats_and_all_documents(): void
    {
        $area = Area::create(['name' => 'Area Dashboard Bulanan']);
        $kc = User::factory()->create(['role' => 'KC', 'area_id' => $area->id]);
        $branch = Branch::create(['name' => 'Cabang Dashboard Bulanan', 'area_id' => $area->id]);
        $template = MemoTemplate::create([
            'name' => 'Memo Dashboard Bulanan',
            'category' => 'Umum',
            'field_schema' => [],
            'created_by' => $kc->id,
        ]);

        foreach (range(1, 9) as $number) {
            $memo = Memo::create([
                'template_id' => $template->id,
                'title' => "Memo Maret {$number}",
                'branch_id' => $branch->id,
                'created_by' => $kc->id,
                'status' => $number === 1 ? Memo::STATUS_DRAFT : Memo::STATUS_SUBMITTED,
            ]);
            $this->setCreatedAt($memo->getTable(), $memo->id, '2025-03-15');
        }

        $previousMonthMemo = Memo::create([
            'template_id' => $template->id,
            'title' => 'Memo Februari',
            'branch_id' => $branch->id,
            'created_by' => $kc->id,
            'status' => Memo::STATUS_SUBMITTED,
        ]);
        $this->setCreatedAt($previousMonthMemo->getTable(), $previousMonthMemo->id, '2025-02-15');

        $ba = BeritaAcara::create([
            'title' => 'BA Maret',
            'branch_id' => $branch->id,
            'created_by' => $kc->id,
            'status' => BeritaAcara::STATUS_APPROVED,
        ]);
        $this->setCreatedAt($ba->getTable(), $ba->id, '2025-03-18');

        $form = FormPengajuan::create([
            'template' => 'form_permohonan_pinjaman',
            'title' => 'Form Maret',
            'data' => [],
            'branch_id' => $branch->id,
            'created_by' => $kc->id,
            'status' => FormPengajuan::STATUS_SUBMITTED,
        ]);
        $this->setCreatedAt($form->getTable(), $form->id, '2025-03-20');

        $headers = [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => app(HandleInertiaRequests::class)->version(request()),
        ];

        $this->actingAs($kc)
            ->withHeaders($headers)
            ->get(route('dashboard', ['month' => '2025-03']))
            ->assertOk()
            ->assertJsonPath('props.month', '2025-03')
            ->assertJsonPath('props.submissionStats.total', 11)
            ->assertJsonPath('props.submissionStats.received', 10)
            ->assertJsonPath('props.submissionStats.draft', 1)
            ->assertJsonCount(9, 'props.memos')
            ->assertJsonCount(1, 'props.recentBA')
            ->assertJsonCount(1, 'props.recentFormPengajuans');

        $this->actingAs($kc)
            ->withHeaders($headers)
            ->get(route('dashboard', ['month' => '2025-02']))
            ->assertOk()
            ->assertJsonPath('props.month', '2025-02')
            ->assertJsonPath('props.submissionStats.received', 1)
            ->assertJsonCount(1, 'props.memos');
    }

    private function setCreatedAt(string $table, int $id, string $date): void
    {
        DB::table($table)->where('id', $id)->update([
            'created_at' => "{$date} 12:00:00",
            'updated_at' => "{$date} 12:00:00",
        ]);
    }
}
