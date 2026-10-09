<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Branch;
use App\Models\MemoTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MemoTemplateFilteringTest extends TestCase
{
    use RefreshDatabase;

    public function test_memo_create_page_only_lists_memo_templates(): void
    {
        $area = Area::create(['name' => 'Area Test']);
        $branch = Branch::create(['name' => 'Cabang Test', 'area_id' => $area->id]);
        $user = User::factory()->create(['role' => 'KC', 'branch_id' => $branch->id]);

        MemoTemplate::create([
            'name' => 'Template Memo',
            'type' => 'memo',
            'category' => 'Umum',
            'field_schema' => [],
            'is_active' => true,
        ]);
        MemoTemplate::create([
            'name' => 'Template Berita Acara',
            'type' => 'ba',
            'category' => 'Umum',
            'field_schema' => [],
            'is_active' => true,
        ]);
        MemoTemplate::create([
            'name' => 'Template Form Pengajuan',
            'type' => 'form',
            'category' => 'HRD',
            'field_schema' => [],
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('memos.create'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Memo/Create')
                ->has('templates', 1)
                ->where('templates.0.name', 'Template Memo'));
    }

    public function test_memo_cannot_be_created_with_a_non_memo_template(): void
    {
        $area = Area::create(['name' => 'Area Test']);
        $branch = Branch::create(['name' => 'Cabang Test', 'area_id' => $area->id]);
        $user = User::factory()->create(['role' => 'KC', 'branch_id' => $branch->id]);
        $template = MemoTemplate::create([
            'name' => 'Template Berita Acara',
            'type' => 'ba',
            'category' => 'Umum',
            'field_schema' => [],
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('memos.store'), [
                'branch_id' => $branch->id,
                'template_id' => $template->id,
                'title' => 'Memo Test',
            ])
            ->assertSessionHasErrors('template_id');
    }
}
