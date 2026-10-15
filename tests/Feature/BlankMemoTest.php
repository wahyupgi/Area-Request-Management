<?php

namespace Tests\Feature;

use App\Features\Memo\Services\MemoService;
use App\Models\Area;
use App\Models\Branch;
use App\Models\Memo;
use App\Models\MemoTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlankMemoTest extends TestCase
{
    use RefreshDatabase;

    public function test_kc_can_create_a_draft_without_a_template(): void
    {
        $area = Area::create(['name' => 'Area Test']);
        $branch = Branch::create(['name' => 'Cabang Test', 'area_id' => $area->id]);
        $user = User::factory()->create(['role' => 'KC', 'branch_id' => $branch->id]);

        $response = $this->actingAs($user)->post(route('memos.store'), [
            'branch_id' => $branch->id,
            'template_id' => null,
            'title' => 'Memo Bebas',
            'field_values' => [
                'pengantar' => 'Pengantar memo',
                'body' => 'Isi memo bebas',
                'items' => [[]],
                'meta' => [],
            ],
        ]);

        $response->assertRedirect();
        $memo = Memo::where('title', 'Memo Bebas')->firstOrFail();

        $this->assertNull($memo->template_id);
        $this->assertSame($branch->id, $memo->branch_id);
        $this->assertSame([], app(MemoService::class)->validateRequiredFields($memo));
    }

    public function test_other_memo_validates_its_custom_columns_instead_of_template_defaults(): void
    {
        $area = Area::create(['name' => 'Area Custom Memo']);
        $branch = Branch::create(['name' => 'Cabang Custom Memo', 'area_id' => $area->id]);
        $user = User::factory()->create(['role' => 'KC', 'branch_id' => $branch->id]);
        $template = MemoTemplate::create([
            'name' => 'Lainnya',
            'type' => 'memo',
            'category' => 'Lainnya',
            'field_schema' => [
                ['key' => 'uraian', 'label' => 'Uraian', 'type' => 'text', 'required' => true],
                ['key' => 'keterangan', 'label' => 'Keterangan', 'type' => 'text', 'required' => false],
            ],
            'is_active' => true,
            'created_by' => $user->id,
        ]);
        $memo = Memo::create([
            'template_id' => $template->id,
            'title' => 'Memo dengan rincian khusus',
            'field_values' => [
                'table_columns' => [
                    ['key' => 'tujuan', 'label' => 'Tujuan Pengeluaran'],
                    ['key' => 'nominal', 'label' => 'Nominal'],
                ],
                'items' => [['tujuan' => 'Pembelian perlengkapan', 'nominal' => '100000']],
            ],
            'branch_id' => $branch->id,
            'created_by' => $user->id,
        ]);

        $this->assertSame([], app(MemoService::class)->validateRequiredFields($memo));

        $memo->field_values = ['table_columns' => [], 'items' => [['tujuan' => 'Pembelian perlengkapan']]];
        $this->assertArrayHasKey('field_values.table_columns', app(MemoService::class)->validateRequiredFields($memo));

        $memo->field_values = [
            'table_columns' => [['key' => 'tujuan', 'label' => 'Tujuan']],
            'items' => [['tujuan' => '']],
        ];
        $this->assertArrayHasKey('field_values.items.0', app(MemoService::class)->validateRequiredFields($memo));
    }
}