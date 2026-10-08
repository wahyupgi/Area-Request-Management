<?php

namespace Database\Seeders;

use App\Models\MemoTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class DocumentTemplateSeeder extends Seeder
{
    public function run(): void
    {
        if (!User::query()->where('role', 'ADMIN')->exists()) {
            throw new RuntimeException('Buat akun ADMIN sebelum menjalankan DocumentTemplateSeeder.');
        }

        MemoTemplate::ensureRequiredDefaults();
    }
}
