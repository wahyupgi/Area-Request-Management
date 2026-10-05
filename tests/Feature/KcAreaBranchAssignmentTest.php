<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Branch;
use App\Models\Memo;
use App\Models\User;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KcAreaBranchAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_assign_multiple_kc_users_to_one_area(): void
    {
        $area = Area::create(['name' => 'Surakarta']);
        $firstKc = User::factory()->create(['role' => 'KC']);
        $secondKc = User::factory()->create(['role' => 'KC']);
        $admin = User::factory()->create(['role' => 'ADMIN']);

        $this->actingAs($admin)->put(route('admin.areas.update', $area), [
            'name' => 'Surakarta',
            'kc_user_ids' => [$firstKc->id, $secondKc->id],
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $firstKc->id, 'area_id' => $area->id]);
        $this->assertDatabaseHas('users', ['id' => $secondKc->id, 'area_id' => $area->id]);
    }

    public function test_admin_area_list_shows_each_kc_assigned_branches(): void
    {
        $area = Area::create(['name' => 'Surakarta']);
        $kc = User::factory()->create(['role' => 'KC', 'area_id' => $area->id]);
        $branch = Branch::create([
            'name' => 'Cabang Kartasura',
            'area_id' => $area->id,
            'kc_user_id' => $kc->id,
        ]);
        $admin = User::factory()->create(['role' => 'ADMIN']);

        $this->actingAs($admin)
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Inertia-Version' => app(HandleInertiaRequests::class)->version(request()),
            ])
            ->get(route('admin.areas.index'))
            ->assertOk()
            ->assertJsonPath('props.areas.0.name', 'Surakarta')
            ->assertJsonPath('props.areas.0.kc_users.0.name', $kc->name)
            ->assertJsonPath('props.areas.0.kc_users.0.assigned_branches.0.name', $branch->name);
    }

    public function test_admin_can_assign_branches_to_a_kc_from_the_mapping_page(): void
    {
        $area = Area::create(['name' => 'Surakarta']);
        $kc = User::factory()->create(['role' => 'KC', 'area_id' => $area->id]);
        $otherKc = User::factory()->create(['role' => 'KC', 'area_id' => $area->id]);
        $firstBranch = Branch::create(['name' => 'SKT001', 'area_id' => $area->id]);
        $secondBranch = Branch::create(['name' => 'SKT002', 'area_id' => $area->id]);
        $reassignedBranch = Branch::create([
            'name' => 'SKT003',
            'area_id' => $area->id,
            'kc_user_id' => $otherKc->id,
        ]);
        $admin = User::factory()->create(['role' => 'ADMIN']);

        $this->actingAs($admin)->put(route('admin.branch-assignments.update', $kc), [
            'area_id' => $area->id,
            'branch_ids' => [$firstBranch->id, $secondBranch->id, $reassignedBranch->id],
        ])->assertRedirect();

        $this->assertDatabaseHas('branches', ['id' => $firstBranch->id, 'kc_user_id' => $kc->id]);
        $this->assertDatabaseHas('branches', ['id' => $secondBranch->id, 'kc_user_id' => $kc->id]);
        $this->assertDatabaseHas('branches', ['id' => $reassignedBranch->id, 'kc_user_id' => $kc->id]);
    }

    public function test_admin_can_change_kc_area_and_assign_a_branch_from_the_new_area(): void
    {
        $previousArea = Area::create(['name' => 'Klaten']);
        $targetArea = Area::create(['name' => 'Surakarta']);
        $kc = User::factory()->create(['role' => 'KC', 'area_id' => $previousArea->id]);
        $previousBranch = Branch::create([
            'name' => 'KLT001',
            'area_id' => $previousArea->id,
            'kc_user_id' => $kc->id,
        ]);
        $targetBranch = Branch::create(['name' => 'SKT001', 'area_id' => $targetArea->id]);
        $admin = User::factory()->create(['role' => 'ADMIN']);

        $this->actingAs($admin)->put(route('admin.branch-assignments.update', $kc), [
            'area_id' => $targetArea->id,
            'branch_ids' => [$targetBranch->id],
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $kc->id, 'area_id' => $targetArea->id]);
        $this->assertDatabaseHas('branches', ['id' => $previousBranch->id, 'kc_user_id' => null]);
        $this->assertDatabaseHas('branches', ['id' => $targetBranch->id, 'kc_user_id' => $kc->id]);
    }

    public function test_admin_can_register_a_branch_from_the_branch_mapping_flow(): void
    {
        $area = Area::create(['name' => 'Surakarta']);
        $admin = User::factory()->create(['role' => 'ADMIN']);

        $this->actingAs($admin)->post(route('admin.branches.store'), [
            'name' => 'SKT001',
            'area_id' => $area->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('branches', [
            'name' => 'SKT001',
            'area_id' => $area->id,
            'kc_user_id' => null,
        ]);
    }

    public function test_admin_mapping_page_shows_kc_area_and_assigned_branches(): void
    {
        $area = Area::create(['name' => 'Surakarta']);
        $kc = User::factory()->create(['role' => 'KC', 'area_id' => $area->id]);
        $branch = Branch::create([
            'name' => 'SKT001',
            'area_id' => $area->id,
            'kc_user_id' => $kc->id,
        ]);
        $admin = User::factory()->create(['role' => 'ADMIN']);

        $this->actingAs($admin)
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Inertia-Version' => app(HandleInertiaRequests::class)->version(request()),
            ])
            ->get(route('admin.branch-assignments.index'))
            ->assertOk()
            ->assertJsonPath('props.areas.0.kc_users_count', 1)
            ->assertJsonPath('props.kcUsers.0.area.name', $area->name)
            ->assertJsonPath('props.kcUsers.0.branches.0.name', $branch->name);
    }

    public function test_admin_mapping_page_counts_kc_from_branch_assignment(): void
    {
        $area = Area::create(['name' => 'Surakarta']);
        $kc = User::factory()->create(['role' => 'KC']);
        Branch::create([
            'name' => 'SKT001',
            'area_id' => $area->id,
            'kc_user_id' => $kc->id,
        ]);
        $admin = User::factory()->create(['role' => 'ADMIN']);

        $this->actingAs($admin)
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Inertia-Version' => app(HandleInertiaRequests::class)->version(request()),
            ])
            ->get(route('admin.branch-assignments.index'))
            ->assertOk()
            ->assertJsonPath('props.areas.0.kc_users_count', 1);
    }

    public function test_admin_mapping_page_counts_kc_assigned_directly_to_area(): void
    {
        $klaten = Area::create(['name' => 'Klaten']);
        $sragen = Area::create(['name' => 'Sragen']);
        User::factory()->create(['role' => 'KC', 'area_id' => $klaten->id]);
        User::factory()->create(['role' => 'KC', 'area_id' => $sragen->id]);
        $admin = User::factory()->create(['role' => 'ADMIN']);

        $this->actingAs($admin)
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Inertia-Version' => app(HandleInertiaRequests::class)->version(request()),
            ])
            ->get(route('admin.branch-assignments.index'))
            ->assertOk()
            ->assertJsonPath('props.areas.0.name', 'Klaten')
            ->assertJsonPath('props.areas.0.kc_users_count', 1)
            ->assertJsonPath('props.areas.1.name', 'Sragen')
            ->assertJsonPath('props.areas.1.kc_users_count', 1);
    }

    public function test_admin_can_create_area_together_with_its_branches(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);

        $this->actingAs($admin)->post(route('admin.areas.store'), [
            'name' => 'Sragen',
            'branch_names' => ['SGN001', 'SGN002'],
        ])->assertRedirect();

        $area = Area::where('name', 'Sragen')->firstOrFail();
        $this->assertDatabaseHas('branches', ['name' => 'SGN001', 'area_id' => $area->id]);
        $this->assertDatabaseHas('branches', ['name' => 'SGN002', 'area_id' => $area->id]);

        $this->actingAs($admin)->put(route('admin.areas.update', $area), [
            'name' => 'Sragen',
            'branch_names' => ['sgn001', 'SGN003'],
        ])->assertRedirect();

        $this->assertSame(3, $area->branches()->count());
    }

    public function test_admin_can_rename_existing_branches_when_editing_an_area(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $area = Area::create(['name' => 'Sragen']);
        $otherArea = Area::create(['name' => 'Klaten']);
        $first = Branch::create(['name' => 'SGN001', 'area_id' => $area->id]);
        $second = Branch::create(['name' => 'SGN002', 'area_id' => $area->id]);
        $foreign = Branch::create(['name' => 'KLT001', 'area_id' => $otherArea->id]);

        $this->actingAs($admin)->put(route('admin.areas.update', $area), [
            'name' => 'Sragen',
            'existing_branches' => [['id' => $first->id, 'name' => 'Cabang Sragen Kota']],
        ])->assertRedirect();

        $this->assertDatabaseHas('branches', ['id' => $first->id, 'name' => 'Cabang Sragen Kota']);

        $this->actingAs($admin)->put(route('admin.areas.update', $area), [
            'name' => 'Sragen',
            'existing_branches' => [['id' => $second->id, 'name' => 'cabang sragen kota']],
        ])->assertSessionHasErrors('existing_branches.0.name');

        $this->actingAs($admin)->put(route('admin.areas.update', $area), [
            'name' => 'Sragen',
            'existing_branches' => [['id' => $foreign->id, 'name' => 'Dibajak']],
        ])->assertSessionHasErrors('existing_branches.0.id');

        $this->assertDatabaseHas('branches', ['id' => $second->id, 'name' => 'SGN002']);
        $this->assertDatabaseHas('branches', ['id' => $foreign->id, 'name' => 'KLT001']);
    }

    public function test_kc_can_select_only_available_branches_in_their_area(): void
    {
        $area = Area::create(['name' => 'Surakarta']);
        $kc = User::factory()->create(['role' => 'KC', 'area_id' => $area->id]);
        $otherKc = User::factory()->create(['role' => 'KC', 'area_id' => $area->id]);
        $branch = Branch::create(['name' => 'SKT001', 'area_id' => $area->id]);
        $otherKcBranch = Branch::create([
            'name' => 'SKT002',
            'area_id' => $area->id,
            'kc_user_id' => $otherKc->id,
        ]);

        $this->actingAs($kc)->put(route('kc.branches.update'), [
            'branch_ids' => [$branch->id],
        ])->assertRedirect();

        $this->assertDatabaseHas('branches', ['id' => $branch->id, 'kc_user_id' => $kc->id]);

        $this->actingAs($kc)->put(route('kc.branches.update'), [
            'branch_ids' => [$otherKcBranch->id],
        ])->assertSessionHasErrors('branch_ids');

        $this->assertDatabaseHas('branches', ['id' => $otherKcBranch->id, 'kc_user_id' => $otherKc->id]);
    }

    public function test_kc_can_add_a_branch_to_their_assigned_area(): void
    {
        $area = Area::create(['name' => 'Surakarta']);
        $kc = User::factory()->create(['role' => 'KC', 'area_id' => $area->id]);

        $this->actingAs($kc)->post(route('kc.branches.store'), [
            'name' => 'Cabang Kartasura',
        ])->assertRedirect();

        $this->assertDatabaseHas('branches', [
            'name' => 'Cabang Kartasura',
            'area_id' => $area->id,
            'kc_user_id' => $kc->id,
        ]);
    }

    public function test_kc_can_create_a_memo_only_for_an_assigned_branch(): void
    {
        $area = Area::create(['name' => 'Surakarta']);
        $kc = User::factory()->create(['role' => 'KC', 'area_id' => $area->id]);
        $otherKc = User::factory()->create(['role' => 'KC', 'area_id' => $area->id]);
        $assignedBranch = Branch::create([
            'name' => 'Cabang Kartasura',
            'area_id' => $area->id,
            'kc_user_id' => $kc->id,
        ]);
        $otherBranch = Branch::create([
            'name' => 'Cabang Colomadu',
            'area_id' => $area->id,
            'kc_user_id' => $otherKc->id,
        ]);

        $this->actingAs($kc)->post(route('memos.store'), [
            'branch_id' => $assignedBranch->id,
            'template_id' => null,
            'title' => 'Memo Cabang Kartasura',
            'field_values' => [],
        ])->assertRedirect();

        $this->assertDatabaseHas('memos', [
            'title' => 'Memo Cabang Kartasura',
            'branch_id' => $assignedBranch->id,
            'created_by' => $kc->id,
        ]);

        $this->actingAs($kc)->post(route('memos.store'), [
            'branch_id' => $otherBranch->id,
            'template_id' => null,
            'title' => 'Memo tidak berwenang',
            'field_values' => [],
        ])->assertSessionHasErrors('branch_id');

        $this->assertDatabaseMissing('memos', ['title' => 'Memo tidak berwenang']);
    }

    public function test_kc_can_create_a_berita_acara_for_an_assigned_branch(): void
    {
        $area = Area::create(['name' => 'Surakarta']);
        $kc = User::factory()->create(['role' => 'KC', 'area_id' => $area->id]);
        $branch = Branch::create([
            'name' => 'Cabang Kartasura',
            'area_id' => $area->id,
            'kc_user_id' => $kc->id,
        ]);

        $this->actingAs($kc)->post(route('berita-acara.store'), [
            'branch_id' => $branch->id,
            'meta' => ['template' => 'lainnya'],
            'title' => 'BA Cabang Kartasura',
        ])->assertRedirect();

        $this->assertDatabaseHas('berita_acaras', [
            'title' => 'BA Cabang Kartasura',
            'branch_id' => $branch->id,
            'created_by' => $kc->id,
        ]);
    }
}