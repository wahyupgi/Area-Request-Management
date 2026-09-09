<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $templates = DB::table('memo_templates');

        $templates->where('name', 'Permintaan Inventaris Kantor')->update([
            'name' => 'Pengajuan Inventaris Cabang',
            'category' => 'GA',
        ]);

        $templates->where('name', 'Pengajuan Renovasi & Pemeliharaan Cabang')->update([
            'name' => 'Pengajuan ATK',
            'category' => 'GA',
            'field_schema' => json_encode([
                ['key' => 'nama_barang', 'label' => 'Nama ATK', 'type' => 'text', 'required' => true],
                ['key' => 'jumlah', 'label' => 'Jumlah / Qty', 'type' => 'number', 'required' => true],
                ['key' => 'keterangan', 'label' => 'Keterangan / Keperluan', 'type' => 'textarea', 'required' => true],
            ]),
        ]);

        $templates->where('category', 'FIN')->update([
            'category' => 'Ma-Link',
            'name' => DB::raw("CASE WHEN name LIKE 'FIN - %' THEN name ELSE CONCAT('FIN - ', name) END"),
        ]);

        $templates->where('category', 'HRD')->update([
            'category' => 'Ma-Link',
            'name' => DB::raw("CASE WHEN name LIKE 'HRD - %' THEN name ELSE CONCAT('HRD - ', name) END"),
        ]);

        if (!$templates->where('name', 'Pengajuan Inventori/Kipas')->exists()) {
            $adminId = DB::table('users')->where('role', 'ADMIN')->value('id');

            if ($adminId) {
                $templates->insert([
                    'name' => 'Pengajuan Inventori/Kipas',
                    'category' => 'GA',
                    'field_schema' => json_encode([
                        ['key' => 'nama_barang', 'label' => 'Nama Inventori / Kipas', 'type' => 'text', 'required' => true],
                        ['key' => 'jumlah', 'label' => 'Jumlah / Qty', 'type' => 'number', 'required' => true],
                        ['key' => 'keterangan', 'label' => 'Keterangan / Keperluan', 'type' => 'textarea', 'required' => true],
                    ]),
                    'is_active' => true,
                    'created_by' => $adminId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('memo_templates')->where('name', 'Pengajuan Inventori/Kipas')->delete();
    }
};