<?php

namespace Tests\Feature;

use App\Features\Memo\Services\MemoService;
use App\Models\Area;
use App\Models\Branch;
use App\Models\Memo;
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
        $this->assertSame([], app(MemoService::class)->validateRequiredFields($memo));
    }
}