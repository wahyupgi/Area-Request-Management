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
        // Create Areas
        $area1 = Area::create(['name' => 'Area Jawa Tengah']);
        $area2 = Area::create(['name' => 'Area Jawa Timur']);
        $area3 = Area::create(['name' => 'Area Jawa Barat']);

        // Create Admin
        $admin = User::create([
            'name' => 'Admin System',
            'email' => 'admin@arm.test',
            'password' => 'password',
            'role' => 'ADMIN',
        ]);

        // Create Area Manager
        $am1 = User::create([
            'name' => 'Fathurrahman (Area Manager)',
            'email' => 'am.fathurrahman@arm.test',
            'password' => 'password',
            'role' => 'AM',
            'area_id' => $area1->id,
        ]);

        // Create Branches & KC Users
        $kc1 = User::create([
            'name' => 'Gibran (KC Surakarta)',
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

        // Create Memo Templates (GA, FIN, HRD)
        MemoTemplate::create([
            'name' => 'Permintaan Inventaris Kantor',
            'category' => 'GA',
            'field_schema' => [
                ['key' => 'nama_barang', 'label' => 'Nama Barang / Permintaan', 'type' => 'text', 'required' => true],
                ['key' => 'jumlah', 'label' => 'Jumlah / Qty', 'type' => 'text', 'required' => true],
                ['key' => 'keterangan', 'label' => 'Keterangan / Keperluan', 'type' => 'textarea', 'required' => true],
            ],
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        MemoTemplate::create([
            'name' => 'Pengajuan Renovasi & Pemeliharaan Cabang',
            'category' => 'GA',
            'field_schema' => [
                ['key' => 'lokasi', 'label' => 'Lokasi Renovasi / Perbaikan', 'type' => 'text', 'required' => true],
                ['key' => 'deskripsi_pekerjaan', 'label' => 'Deskripsi Pekerjaan', 'type' => 'textarea', 'required' => true],
                ['key' => 'estimasi_biaya', 'label' => 'Estimasi Biaya (Rp)', 'type' => 'number', 'required' => true],
                ['key' => 'vendor', 'label' => 'Nama Vendor / Kontraktor', 'type' => 'text', 'required' => false],
                ['key' => 'jadwal_mulai', 'label' => 'Jadwal Mulai', 'type' => 'date', 'required' => false],
                ['key' => 'jadwal_selesai', 'label' => 'Jadwal Selesai', 'type' => 'date', 'required' => false],
            ],
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        MemoTemplate::create([
            'name' => 'Pembayaran Biaya Operasional / Iuran',
            'category' => 'FIN',
            'field_schema' => [
                ['key' => 'nama_barang', 'label' => 'Nama Keperluan / Pembayaran', 'type' => 'text', 'required' => true],
                ['key' => 'jumlah', 'label' => 'Nominal Pembayaran (Rp)', 'type' => 'number', 'required' => true],
                ['key' => 'keterangan', 'label' => 'Keterangan / Periode', 'type' => 'textarea', 'required' => true],
            ],
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        MemoTemplate::create([
            'name' => 'Permohonan Penambahan Karyawan',
            'category' => 'HRD',
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
