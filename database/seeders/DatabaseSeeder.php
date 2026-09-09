<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Branch;
use App\Models\DigitalSignature;
use App\Models\MemoTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
       
        $area1 = Area::create(['name' => 'Area Jawa Tengah']);
        $area2 = Area::create(['name' => 'Area Jawa Timur']);
        $area3 = Area::create(['name' => 'Area Jawa Barat']);


        $admin = User::create([
            'name' => 'Admin System',
            'username' => 'admin',
            'email' => 'admin@arm.test',
            'password' => 'pgiadmin',
            'role' => 'ADMIN',
        ]);

        $am1 = User::create([
            'name' => 'Fathurrahman (Area Manager)',
            'username' => 'fathurrahman',
            'email' => 'am.fathurrahman@arm.test',
            'password' => 'password',
            'role' => 'AM',
            'area_id' => $area1->id,
        ]);

      
        $kc1 = User::create([
            'name' => 'Gibran (KC Surakarta)',
            'username' => 'gibran',
            'email' => 'kc.surakarta@arm.test',
            'password' => 'gibran123',
            'role' => 'KC',
        ]);
        $branch1 = Branch::create([
            'name' => 'Cabang Surakarta',
            'area_id' => $area1->id,
            'kc_user_id' => $kc1->id,
        ]);
        $kc1->update(['branch_id' => $branch1->id]);

        $kc2 = User::create([
            'name' => 'Dewi Lestari (KC Klaten)',
            'username' => 'dewi',
            'email' => 'kc.klaten@arm.test',
            'password' => 'dewi123',
            'role' => 'KC',
        ]);
        $branch2 = Branch::create([
            'name' => 'Cabang Klaten',
            'area_id' => $area1->id,
            'kc_user_id' => $kc2->id,
        ]);
        $kc2->update(['branch_id' => $branch2->id]);

        $kc3 = User::create([
            'name' => 'Budi (KC Sragen)',
            'username' => 'budi',
            'email' => 'kc.sragen@arm.test',
            'password' => 'budi123',
            'role' => 'KC',
        ]);
        $branch3 = Branch::create([
            'name' => 'Cabang Sragen',
            'area_id' => $area1->id,
            'kc_user_id' => $kc3->id,
        ]);
        $kc3->update(['branch_id' => $branch3->id]);

        // Create Digital Signature for AM
        DigitalSignature::create([
            'user_id' => $am1->id,
            'signature_image' => 'signatures/am_fathurrahman.png',
            'certificate_no' => 'CERT-FATHUR-001',
        ]);

        // Create Memo Templates (GA and Ma-Link: FIN, HRD)
        MemoTemplate::create([
            'name' => 'Pengajuan Inventaris Cabang',
            'category' => 'GA',
            'signature_schema' => [
                ['label' => 'Dibuat Oleh', 'role' => 'Kepala Cabang', 'user_id' => null, 'location' => 'document'],
                ['label' => 'Disetujui Oleh', 'role' => 'Area Manager', 'user_id' => null, 'location' => 'document'],
            ],
            'field_schema' => [
                ['key' => 'nama_barang', 'label' => 'Nama Barang / Permintaan', 'type' => 'text', 'required' => true],
                ['key' => 'jumlah', 'label' => 'Jumlah / Qty', 'type' => 'text', 'required' => true],
                ['key' => 'keterangan', 'label' => 'Keterangan / Keperluan', 'type' => 'textarea', 'required' => true],
            ],
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        MemoTemplate::create([
            'name' => 'Pengajuan ATK',
            'category' => 'GA',
            'field_schema' => [
                ['key' => 'nama_barang', 'label' => 'Nama ATK', 'type' => 'text', 'required' => true],
                ['key' => 'jumlah', 'label' => 'Jumlah / Qty', 'type' => 'number', 'required' => true],
                ['key' => 'keterangan', 'label' => 'Keterangan / Keperluan', 'type' => 'textarea', 'required' => true],
            ],
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        MemoTemplate::create([
            'name' => 'Pengajuan Inventori/Kipas',
            'category' => 'GA',
            'field_schema' => [
                ['key' => 'cabang', 'label' => 'Kode / Nama Cabang', 'type' => 'text', 'required' => true],
                ['key' => 'permintaan', 'label' => 'Permintaan', 'type' => 'text', 'required' => true],
                ['key' => 'tujuan', 'label' => 'Tujuan', 'type' => 'text', 'required' => true],
                ['key' => 'area', 'label' => 'Area Penempatan', 'type' => 'text', 'required' => true],
                ['key' => 'qty_kipas_ada', 'label' => 'Qty Kipas yang Ada', 'type' => 'number', 'required' => true],
                ['key' => 'keterangan', 'label' => 'Keterangan', 'type' => 'textarea', 'required' => true],
            ],
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        MemoTemplate::create([
            'name' => 'FIN - Pembayaran Biaya Operasional / Iuran',
            'category' => 'Ma-Link',
            'field_schema' => [
                ['key' => 'nama_barang', 'label' => 'Nama Keperluan / Pembayaran', 'type' => 'text', 'required' => true],
                ['key' => 'jumlah', 'label' => 'Nominal Pembayaran (Rp)', 'type' => 'number', 'required' => true],
                ['key' => 'keterangan', 'label' => 'Keterangan / Periode', 'type' => 'textarea', 'required' => true],
            ],
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        MemoTemplate::create([
            'name' => 'FIN - Pemberitahuan Kas Keluar',
            'category' => 'Ma-Link',
            'field_schema' => [
                ['key' => 'nominal_kas_keluar', 'label' => 'Nominal Kas Keluar (Rp)', 'type' => 'number', 'required' => true],
                ['key' => 'cabang', 'label' => 'Kode / Nama Cabang', 'type' => 'text', 'required' => true],
                ['key' => 'direktorat', 'label' => 'Direktorat', 'type' => 'text', 'required' => true],
                ['key' => 'divisi', 'label' => 'Divisi', 'type' => 'text', 'required' => true],
                ['key' => 'penerima', 'label' => 'Nama Penerima', 'type' => 'text', 'required' => true],
                ['key' => 'penerima_jabatan', 'label' => 'Jabatan Penerima', 'type' => 'text', 'required' => true],
                ['key' => 'penyetuju_akhir', 'label' => 'Penyetuju Akhir', 'type' => 'text', 'required' => false],
                ['key' => 'alasan', 'label' => 'Alasan Pengeluaran Kas', 'type' => 'textarea', 'required' => true],
                ['key' => 'lampiran', 'label' => 'Lampiran Pendukung', 'type' => 'text', 'required' => false],
            ],
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        MemoTemplate::create([
            'name' => 'HRD - Permohonan Penambahan Karyawan',
            'category' => 'Ma-Link',
            'field_schema' => [
                ['key' => 'posisi', 'label' => 'Posisi yang Dibutuhkan', 'type' => 'text', 'required' => true],
                ['key' => 'jumlah', 'label' => 'Jumlah Karyawan', 'type' => 'number', 'required' => true],
                ['key' => 'justifikasi', 'label' => 'Justifikasi / Alasan', 'type' => 'textarea', 'required' => true],
                ['key' => 'tanggal_kebutuhan', 'label' => 'Target Tanggal Bergabung', 'type' => 'date', 'required' => false],
            ],
            'is_active' => true,
            'created_by' => $admin->id,
        ]);
    }
}
