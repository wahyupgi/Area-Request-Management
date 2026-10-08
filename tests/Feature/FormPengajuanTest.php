<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\BeritaAcara;
use App\Models\Branch;
use App\Models\FormPengajuan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormPengajuanTest extends TestCase
{
    use RefreshDatabase;

    public function test_kc_can_submit_standalone_form_templates_for_area_manager_review(): void
    {
        $area = Area::create(['name' => 'Area Form Pengajuan']);
        $areaManager = User::factory()->create([
            'role' => 'AM',
            'area_id' => $area->id,
        ]);
        $branch = Branch::create([
            'name' => 'Cabang Form Pengajuan',
            'area_id' => $area->id,
        ]);
        $kc = User::factory()->create([
            'role' => 'KC',
            'area_id' => $area->id,
            'branch_id' => $branch->id,
        ]);

        $forms = [
            [
                'template' => 'form_permohonan_pinjaman',
                'request_data' => [
                    'full_name' => 'Pemohon Pinjaman',
                    'position' => 'Kepala Cabang',
                    'work_location' => 'Cabang Form Pengajuan',
                    'employment_date' => '2020-01-01',
                    'loan_amount' => 'Rp 5.000.000',
                    'repayment_months' => 10,
                    'salary_deduction' => 'Bersedia',
                    'loan_type' => 'pinjaman_uang',
                    'reason' => 'Keperluan keluarga',
                ],
            ],
            [
                'template' => 'form_ijin_tidak_masuk_kerja',
                'request_data' => [
                    'full_name' => 'Pemohon Ijin',
                    'leave_type' => 'cuti_tahunan',
                    'start_date' => '2026-10-07',
                    'duration' => 1,
                    'reason' => 'Keperluan keluarga',
                    'substitute' => 'Ana Fauziyah',
                    'substitute_position' => 'Kepala Unit',
                ],
            ],
        ];

        foreach ($forms as $form) {
            $this->actingAs($kc)
                ->post(route('form-pengajuan.store'), [
                    'branch_id' => $branch->id,
                    'template' => $form['template'],
                    'meta' => ['request_data' => $form['request_data']],
                    'submit' => true,
                ])
                ->assertRedirect(route('form-pengajuan.index'));

            $document = FormPengajuan::query()->latest('id')->firstOrFail();

            $this->assertSame($form['template'], $document->template);
            $this->assertSame($branch->id, $document->branch_id);
            $this->assertStringStartsWith('FP/RBO/BRL/PGI/', $document->code);
            $this->assertSame(FormPengajuan::STATUS_SUBMITTED, $document->status);
            if ($form['template'] === 'form_ijin_tidak_masuk_kerja') {
                $this->assertSame('Kepala Unit', $document->data['substitute_position']);
            }
            $this->assertNull($document->branch_id);
            $this->assertSame($areaManager->id, $document->area_manager_id);
            $this->assertSame(0, BeritaAcara::query()->count());

            $this->actingAs($areaManager)
                ->get(route('approvals.form-pengajuan.review', $document))
                ->assertOk();
        }
    }

    public function test_berita_acara_route_still_creates_only_a_berita_acara(): void
    {
        $area = Area::create(['name' => 'Area BA Terpisah']);
        $areaManager = User::factory()->create([
            'role' => 'AM',
            'area_id' => $area->id,
        ]);
        $kc = User::factory()->create([
            'role' => 'KC',
            'area_id' => $area->id,
        ]);
        $branch = Branch::create([
            'name' => 'Cabang BA Terpisah',
            'area_id' => $area->id,
            'kc_user_id' => $kc->id,
        ]);

        $this->actingAs($kc)
            ->post(route('berita-acara.store'), [
                'branch_id' => $branch->id,
                'title' => 'Berita Acara Terpisah',
                'meta' => [
                    'template' => 'lainnya',
                    'data' => ['rows' => [], 'kronologi' => ''],
                ],
                'submit_after_save' => false,
            ])
            ->assertRedirect(route('berita-acara.index'));

        $this->assertDatabaseCount('berita_acaras', 1);
        $this->assertDatabaseCount('form_pengajuans', 0);
        $this->assertDatabaseHas('berita_acaras', [
            'title' => 'Berita Acara Terpisah',
            'branch_id' => $branch->id,
            'area_manager_id' => $areaManager->id,
        ]);
    }
}
