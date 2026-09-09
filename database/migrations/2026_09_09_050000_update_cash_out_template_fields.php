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
                    ['key' => 'direktorat', 'label' => 'Direktorat', 'type' => 'text', 'required' => true],
                    ['key' => 'divisi', 'label' => 'Divisi', 'type' => 'text', 'required' => true],
                    ['key' => 'penerima', 'label' => 'Nama Penerima', 'type' => 'text', 'required' => true],
                    ['key' => 'penerima_jabatan', 'label' => 'Jabatan Penerima', 'type' => 'text', 'required' => true],
                    ['key' => 'penyetuju_akhir', 'label' => 'Penyetuju Akhir', 'type' => 'text', 'required' => false],
                    ['key' => 'alasan', 'label' => 'Alasan Pengeluaran Kas', 'type' => 'textarea', 'required' => true],
                    ['key' => 'lampiran', 'label' => 'Lampiran Pendukung', 'type' => 'text', 'required' => false],
                ]),
            ]);
    }

    public function down(): void
    {
        DB::table('memo_templates')
            ->where('name', 'FIN - Pemberitahuan Kas Keluar')
            ->update([
                'field_schema' => json_encode([
                    ['key' => 'nominal_kas_keluar', 'label' => 'Nominal Kas Keluar (Rp)', 'type' => 'number', 'required' => true],
                    ['key' => 'cabang', 'label' => 'Kode / Nama Cabang', 'type' => 'text', 'required' => true],
                    ['key' => 'direktorat', 'label' => 'Direktorat', 'type' => 'text', 'required' => true],
                    ['key' => 'divisi', 'label' => 'Divisi', 'type' => 'text', 'required' => true],
                    ['key' => 'penerima', 'label' => 'Nama Penerima', 'type' => 'text', 'required' => true],
                    ['key' => 'penerima_jabatan', 'label' => 'Jabatan Penerima', 'type' => 'text', 'required' => true],
                    ['key' => 'penyetuju_akhir', 'label' => 'Penyetuju Akhir', 'type' => 'text', 'required' => false],
                    ['key' => 'alasan', 'label' => 'Alasan Pengeluaran Kas', 'type' => 'textarea', 'required' => true],
                    ['key' => 'lampiran', 'label' => 'Lampiran Pendukung', 'type' => 'text', 'required' => false],
                ]),
            ]);
    }
};