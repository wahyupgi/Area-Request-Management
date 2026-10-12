<?php

namespace Tests\Feature;

use App\Models\MemoTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MemoTemplateIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_required_memo_templates_are_restored_if_missing(): void
    {
        $admin = User::create([
            'name' => 'SysAdmin',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'ADMIN',
        ]);

        $requiredTemplates = [
            'Pengajuan Inventaris Cabang',
            'Pengajuan Inventori/Kipas',
            'FIN - Pemberitahuan Kas Keluar',
            'HRD - Permohonan Permintaan SDM',
        ];

        foreach ($requiredTemplates as $name) {
            MemoTemplate::create([
                'name' => $name,
                'category' => 'GA',
                'field_schema' => [],
                'is_active' => true,
                'created_by' => $admin->id,
            ]);
        }

        foreach ($requiredTemplates as $name) {
            MemoTemplate::where('name', $name)->delete();
        }

        MemoTemplate::ensureRequiredDefaults();

        foreach ($requiredTemplates as $name) {
            $this->assertDatabaseHas('memo_templates', [
                'name' => $name,
                'is_active' => true,
            ]);
        }
    }

    public function test_other_memo_template_is_listed_and_keeps_admin_changes(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);

        $this->actingAs($admin)
            ->get(route('admin.templates.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Templates/Index')
                ->where('templates.0.name', 'Lainnya'));

        $template = MemoTemplate::where('name', 'Lainnya')
            ->where('type', 'memo')
            ->firstOrFail();

        $template->update([
            'is_active' => false,
            'field_schema' => [
                ['key' => 'keperluan', 'label' => 'Keperluan', 'type' => 'textarea', 'required' => true],
            ],
        ]);

        $this->get(route('admin.templates.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Templates/Index')
                ->where('templates.0.name', 'Lainnya')
                ->where('templates.0.is_active', false)
                ->where('templates.0.field_schema.0.label', 'Keperluan'));
    }
}
