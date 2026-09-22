<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('memo_templates')
            ->where('name', 'FIN - Pemberitahuan Kas Keluar')
            ->update([
                'field_schema' => json_encode([
                    ['key' => 'nominal_kas_keluar', 'label' => 'Nominal Kas Keluar (Rp)', 'type' => 'number', 'required' => true],
                    ['key' => 'cabang', 'label' => 'Kode / Nama Cabang', 'type' => 'text', 'required' => true],
                    ['key' => 'alasan', 'label' => 'Alasan Pengeluaran Kas', 'type' => 'textarea', 'required' => true],
                ]),
            ]);
    }

    public function down(): void
    {
        // The previous migration owns the original schema.
    }
};