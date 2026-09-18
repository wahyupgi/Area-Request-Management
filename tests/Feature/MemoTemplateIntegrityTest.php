<?php

namespace Tests\Feature;

use App\Models\MemoTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'HRD - Permohonan Penambahan Karyawan',
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
}
