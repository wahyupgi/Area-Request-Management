<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\DigitalSignature;
use App\Models\MemoTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $provinces = [];
            foreach (['Jawa Tengah', 'Jawa Timur', 'Jawa Barat'] as $provinceName) {
                $provinces[$provinceName] = Area::query()->firstOrCreate([
                    'name' => $provinceName,
                    'parent_id' => null,
                ]);
            }
        });

        $area1 = Area::query()->where('name', 'Jawa Tengah')->whereNull('parent_id')->firstOrFail();

        $admin = User::create([
            'name' => 'SysAdmin',
            'username' => 'admin',
            'email' => 'adminpgi@gmail.com',
            'password' => bcrypt('pgiadmin'),
            'role' => 'ADMIN',
        ]);

        $am1 = User::create([
            'name' => 'Bpk.Fathurrahman Masngudi',
            'username' => 'fathurrahman',
            'email' => 'wahyupgi1@gmail.com',
            'password' => bcrypt('amfathur'),
            'role' => 'AM',
            'area_id' => $area1->id,
        ]);

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
                [
                    'key' => 'area_penempatan',
                    'label' => 'Area Penempatan',
                    'type' => 'table',
                    'required' => true,
                    'columns' => [
                        ['key' => 'area', 'label' => 'Area', 'type' => 'text'],
                        ['key' => 'qty', 'label' => 'Qty (yang ada)', 'type' => 'number'],
                    ],
                ],
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
                ['key' => 'alasan', 'label' => 'Alasan Pengeluaran Kas', 'type' => 'textarea', 'required' => true],
            ],
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        MemoTemplate::create([
            'name' => 'HRD - Permohonan Permintaan SDM',
            'category' => 'Ma-Link',
            'field_schema' => [
                [
                    'key' => 'rincian_sdm',
                    'label' => 'Data Permintaan SDM',
                    'type' => 'table',
                    'required' => true,
                    'columns' => [
                        ['key' => 'cabang', 'label' => 'Cabang', 'type' => 'text'],
                        ['key' => 'sdm', 'label' => 'SDM', 'type' => 'number'],
                        ['key' => 'proposional', 'label' => 'Proposional', 'type' => 'number'],
                        ['key' => 'kebutuhan', 'label' => 'Kebutuhan', 'type' => 'number'],
                        ['key' => 'alasan', 'label' => 'Alasan', 'type' => 'text'],
                        ['key' => 'kualifikasi', 'label' => 'Kualifikasi', 'type' => 'text'],
                    ],
                ],
            ],
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        if (!User::query()->where('role', 'ADMIN')->exists()) {
            throw new RuntimeException('Buat akun ADMIN sebelum menjalankan DatabaseSeeder.');
        }

        MemoTemplate::ensureRequiredDefaults();
    }
}
