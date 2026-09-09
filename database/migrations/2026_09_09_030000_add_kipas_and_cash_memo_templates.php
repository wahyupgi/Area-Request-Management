<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $adminId = DB::table('users')->where('role', 'ADMIN')->value('id');

        if (!$adminId) {
            return;
        }

        $templates = DB::table('memo_templates');

        $templates->where('name', 'Pengajuan Inventori/Kipas')->update([
            'field_schema' => json_encode([
                ['key' => 'cabang', 'label' => 'Kode / Nama Cabang', 'type' => 'text', 'required' => true],
                ['key' => 'permintaan', 'label' => 'Permintaan', 'type' => 'text', 'required' => true],
                ['key' => 'tujuan', 'label' => 'Tujuan', 'type' => 'text', 'required' => true],
                ['key' => 'area', 'label' => 'Area Penempatan', 'type' => 'text', 'required' => true],
                ['key' => 'qty_kipas_ada', 'label' => 'Qty Kipas yang Ada', 'type' => 'number', 'required' => true],
                ['key' => 'keterangan', 'label' => 'Keterangan', 'type' => 'textarea', 'required' => true],
            ]),
        ]);

        if (!$templates->where('name', 'FIN - Pemberitahuan Kas Keluar')->exists()) {
            $templates->insert([
                'name' => 'FIN - Pemberitahuan Kas Keluar',
                'category' => 'Ma-Link',
                'field_schema' => json_encode([
                    ['key' => 'nominal_kas_keluar', 'label' => 'Nominal Kas Keluar (Rp)', 'type' => 'number', 'required' => true],
                    ['key' => 'cabang', 'label' => 'Kode / Nama Cabang', 'type' => 'text', 'required' => true],
                    ['key' => 'alasan', 'label' => 'Alasan Pengeluaran Kas', 'type' => 'textarea', 'required' => true],
                    ['key' => 'lampiran', 'label' => 'Lampiran Pendukung', 'type' => 'text', 'required' => false],
                ]),
                'is_active' => true,
                'created_by' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('memo_templates')->where('name', 'FIN - Pemberitahuan Kas Keluar')->delete();
    }
};