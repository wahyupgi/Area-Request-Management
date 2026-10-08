<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class MemoTemplate extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'type', 'category', 'field_schema', 'document_defaults', 'signature_schema', 'is_active', 'created_by'];

    protected $casts = [
        'field_schema' => 'array',
        'document_defaults' => 'array',
        'signature_schema' => 'array',
        'is_active' => 'boolean',
    ];

    public static function ensureRequiredDefaults(): void
    {
        $admin = User::query()->where('role', 'ADMIN')->first();

        if (!$admin) {
            return;
        }

        $keringananClosing = 'Demikianlah internal memo ini dibuat agar dapat dipergunakan dengan sebagaimana mestinya. Terima kasih atas perhatian dan kerjasamanya.';

        $templates = [
            [
                'name' => 'Pengajuan Inventaris Cabang',
                'category' => 'GA',
                'field_schema' => [
                    ['key' => 'nama_barang', 'label' => 'Nama Barang / Permintaan', 'type' => 'text', 'required' => true],
                    ['key' => 'jumlah', 'label' => 'Jumlah / Qty', 'type' => 'text', 'required' => true],
                    ['key' => 'keterangan', 'label' => 'Keterangan / Keperluan', 'type' => 'textarea', 'required' => true],
                ],
            ],
            [
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
            ],
            [
                'name' => 'FIN - Pemberitahuan Kas Keluar',
                'category' => 'Ma-Link',
                'field_schema' => [
                    ['key' => 'nominal_kas_keluar', 'label' => 'Nominal Kas Keluar (Rp)', 'type' => 'number', 'required' => true],
                    ['key' => 'cabang', 'label' => 'Kode / Nama Cabang', 'type' => 'text', 'required' => true],
                    ['key' => 'alasan', 'label' => 'Alasan Pengeluaran Kas', 'type' => 'textarea', 'required' => true],
                ],
            ],
            [
                'name' => 'HRD - Permohonan Penambahan Karyawan',
                'category' => 'Ma-Link',
                'field_schema' => [
                    ['key' => 'posisi', 'label' => 'Posisi yang Dibutuhkan', 'type' => 'text', 'required' => true],
                    ['key' => 'jumlah', 'label' => 'Jumlah Karyawan', 'type' => 'number', 'required' => true],
                    ['key' => 'justifikasi', 'label' => 'Justifikasi / Alasan', 'type' => 'textarea', 'required' => true],
                    ['key' => 'tanggal_kebutuhan', 'label' => 'Target Tanggal Bergabung', 'type' => 'date', 'required' => false],
                ],
            ],
            [
                'name' => 'Permohonan Keringanan Jasa PBKB',
                'category' => 'Keringanan Jasa',
                'field_schema' => [
                    ['key' => 'cabang', 'label' => 'Cabang', 'type' => 'text', 'required' => true],
                    ['key' => 'no_faktur', 'label' => 'No. Faktur', 'type' => 'text', 'required' => true],
                    ['key' => 'nama_nasabah', 'label' => 'Nama Nasabah', 'type' => 'text', 'required' => true],
                    ['key' => 'no_telepon', 'label' => 'No. Telepon', 'type' => 'text', 'required' => false],
                    [
                        'key' => 'rincian_jasa',
                        'label' => 'Rincian Keringanan Jasa',
                        'type' => 'table',
                        'required' => true,
                        'columns' => [
                            ['key' => 'tanggal', 'label' => 'Tanggal', 'type' => 'date'],
                            ['key' => 'keterangan', 'label' => 'Keterangan', 'type' => 'text'],
                            ['key' => 'nominal', 'label' => 'Nominal (Rp)', 'type' => 'number'],
                        ],
                    ],
                ],
                'document_defaults' => [
                    'direktorat' => 'Regional Branch Office',
                    'divisi' => 'Branch Leader',
                    'perihal' => 'Permohonan Keringanan Jasa PBKB',
                    'kepada' => 'Bpk. Nugroho Samudra Sujatmiko, Ko',
                    'kepada_jabatan' => 'Senior Executive Vice President Bisnis dan Operasional',
                    'lampiran' => '-',
                    'pengantar' => 'Sehubungan dengan adanya berita acara dari cabang terkait permohonan keringanan jasa, bersama ini kami sampaikan data nasabah dan rincian sebagai berikut:',
                    'penutup' => $keringananClosing,
                ],
                'signature_schema' => [
                    ['label' => 'Dibuat oleh,', 'role' => 'Kepala Cabang'],
                    ['label' => 'Disetujui oleh,', 'role' => 'Area Manager'],
                    ['label' => 'Disetujui oleh,', 'name' => 'Ibu. Harum', 'role' => 'COE'],
                    ['label' => 'Disetujui oleh,', 'name' => 'Bpk. Nugroho Samudra Sujatmiko, Ko', 'role' => 'Senior Executive Vice President Bisnis dan Operasional'],
                ],
            ],
            [
                'name' => 'Pengajuan Keringanan Pelunasan Nasabah Handphone Meninggal Dunia',
                'category' => 'Keringanan Jasa',
                'field_schema' => [
                    ['key' => 'nama_nasabah', 'label' => 'Nama Nasabah', 'type' => 'text', 'required' => true],
                    ['key' => 'no_anggota', 'label' => 'No. Anggota', 'type' => 'text', 'required' => true],
                    ['key' => 'cabang', 'label' => 'Cabang', 'type' => 'text', 'required' => true],
                    ['key' => 'no_faktur', 'label' => 'No. Faktur', 'type' => 'text', 'required' => true],
                    ['key' => 'jenis_barang', 'label' => 'Jenis Barang', 'type' => 'text', 'required' => true],
                    ['key' => 'no_hp', 'label' => 'No. HP', 'type' => 'text', 'required' => true],
                    ['key' => 'jatuh_tempo', 'label' => 'Tanggal Jatuh Tempo', 'type' => 'date', 'required' => true],
                    ['key' => 'nominal_pinjaman', 'label' => 'Nominal Pinjaman (Rp)', 'type' => 'number', 'required' => true],
                    ['key' => 'nominal_pelunasan', 'label' => 'Nominal Pelunasan (Rp)', 'type' => 'number', 'required' => true],
                    ['key' => 'nominal_keringanan', 'label' => 'Nominal Keringanan (Rp)', 'type' => 'number', 'required' => true],
                ],
                'document_defaults' => [
                    'direktorat' => 'Regional Branch Office',
                    'divisi' => 'Branch Leader',
                    'perihal' => 'Pengajuan Keringanan Pelunasan Nasabah Handphone yang Meninggal Dunia',
                    'lampiran' => '-',
                    'pengantar' => 'Sehubungan dengan adanya berita acara dari cabang perihal pengajuan keringanan pelunasan nasabah handphone yang meninggal dunia, bersama ini kami mengajukan permohonan berdasarkan data berikut:',
                    'penutup' => $keringananClosing,
                ],
                'signature_schema' => [
                    ['label' => 'Dibuat oleh,', 'role' => 'Kepala Cabang'],
                    ['label' => 'Disetujui oleh,', 'role' => 'Area Manager'],
                    ['label' => 'Disetujui oleh,', 'name' => 'Bpk. Nugroho Samudra Sujatmiko, Ko', 'role' => 'Senior Executive Vice President Bisnis dan Operasional'],
                ],
            ],
            [
                'name' => 'Permohonan Keringanan Denda Reclaim',
                'category' => 'Keringanan Jasa',
                'field_schema' => [
                    ['key' => 'cabang', 'label' => 'Cabang', 'type' => 'text', 'required' => true],
                    [
                        'key' => 'rincian_reclaim',
                        'label' => 'Data Nasabah dan Barang',
                        'type' => 'table',
                        'required' => true,
                        'columns' => [
                            ['key' => 'nama_nasabah', 'label' => 'Nama Nasabah', 'type' => 'text'],
                            ['key' => 'tanggal_jatuh_tempo', 'label' => 'Tgl Jatuh Tempo', 'type' => 'date'],
                            ['key' => 'tanggal_pembayaran', 'label' => 'Tgl Pembayaran', 'type' => 'date'],
                            ['key' => 'pinjaman_pokok', 'label' => 'Pinjaman Pokok (Rp)', 'type' => 'number'],
                            ['key' => 'no_faktur', 'label' => 'No. Faktur', 'type' => 'text'],
                            ['key' => 'jenis_barang', 'label' => 'Jenis Barang', 'type' => 'text'],
                            ['key' => 'keterangan', 'label' => 'Keterangan', 'type' => 'text'],
                        ],
                    ],
                    ['key' => 'nominal_seharusnya', 'label' => 'Nominal Sebelum Reclaim (Rp)', 'type' => 'number', 'required' => true],
                    ['key' => 'nominal_setelah_reclaim', 'label' => 'Nominal Setelah Reclaim (Rp)', 'type' => 'number', 'required' => true],
                    ['key' => 'persentase_denda', 'label' => 'Persentase Denda / Jasa (%)', 'type' => 'number', 'required' => true],
                    ['key' => 'permohonan', 'label' => 'Uraian Permohonan', 'type' => 'textarea', 'display' => 'paragraph', 'required' => true],
                ],
                'document_defaults' => [
                    'direktorat' => 'Operasional',
                    'divisi' => 'Support',
                    'perihal' => 'Keringanan denda reclaim',
                    'lampiran' => '-',
                    'pengantar' => 'Sehubungan dengan adanya reclaim yang menyebabkan perubahan jumlah pembayaran, bersama ini kami mengajukan permohonan keringanan atau penghapusan denda jasa reclaim berdasarkan data berikut:',
                    'penutup' => $keringananClosing,
                ],
                'signature_schema' => [
                    ['label' => 'Dibuat oleh,', 'role' => 'Kepala Cabang'],
                    ['label' => 'Disetujui oleh,', 'role' => 'Area Manager'],
                    ['label' => 'Diketahui oleh,', 'name' => 'Bpk. Nugroho Samudra Sujatmiko, Ko', 'role' => 'Senior Executive Vice President Bisnis dan Operasional'],
                ],
            ],
            // ─── Berita Acara Templates ───────────────────────────────
            [
                'name' => 'Pengembalian Dana',
                'type' => 'ba',
                'category' => 'Keuangan',
                'field_schema' => [
                    ['key' => 'cabang', 'label' => 'Cabang', 'type' => 'text', 'required' => true],
                    ['key' => 'faktur', 'label' => 'Faktur', 'type' => 'text', 'required' => true],
                    ['key' => 'tujuan', 'label' => 'Tujuan', 'type' => 'text', 'required' => true],
                    ['key' => 'nominal', 'label' => 'Nominal', 'type' => 'number', 'required' => true],
                ],
                'document_defaults' => [
                    'direktorat' => 'Operasional',
                    'divisi' => 'Support',
                    'perihal' => 'Pemberitahuan Pengembalian Dana',
                    'lampiran' => '-',
                    'pengantar' => 'Sehubungan dengan adanya Transaksi disbursement T +1 dana berhasil cair maka saya selaku kepala cabang ingin menginformasikan nasabah melakukan pengembalian dana dengan data sebagai berikut :',
                    'penutup' => $keringananClosing,
                ],
            ],
            [
                'name' => 'Permohonan Biaya Kost',
                'type' => 'ba',
                'category' => 'GA',
                'field_schema' => [
                    ['key' => 'nama', 'label' => 'Nama', 'type' => 'text', 'required' => true],
                    ['key' => 'nik', 'label' => 'NIK', 'type' => 'text', 'required' => true],
                    ['key' => 'nama_pemilik', 'label' => 'Nama Pemilik', 'type' => 'text', 'required' => true],
                    ['key' => 'nama_kost', 'label' => 'Nama Kost', 'type' => 'text', 'required' => true],
                    ['key' => 'no_tlp', 'label' => 'No. Tlp', 'type' => 'text', 'required' => false],
                    ['key' => 'alamat_kost', 'label' => 'Alamat Kost', 'type' => 'text', 'required' => true],
                    ['key' => 'biaya_kost', 'label' => 'Biaya Kost', 'type' => 'number', 'required' => true],
                ],
                'document_defaults' => [
                    'direktorat' => 'Regional Branch Office',
                    'divisi' => 'Branch Leader',
                    'perihal' => 'Permohonan Biaya Kost',
                    'kepada' => 'Bpk. Nugroho Samudra Sujatmiko, Ko',
                    'kepada_jabatan' => 'Senior Executive Vice President Bisnis dan Operasional',
                    'pengantar' => 'Sehubungan dengan kondisi yang mengharuskan saya untuk tinggal di luar kota, maka dengan ini saya mengajukan permohonan biaya kost dengan data sebagai berikut:',
                    'penutup' => 'Demikian internal memo ini dibuat agar dapat dipergunakan sebagaimana mestinya. Mohon dibantu pembayaran melalui rekening yang tertera. Terima kasih atas perhatian dan kerjasamanya.',
                ],
            ],
            [
                'name' => 'Permintaan Revisi Absensi',
                'type' => 'ba',
                'category' => 'HRD',
                'field_schema' => [
                    ['key' => 'nama', 'label' => 'Nama', 'type' => 'text', 'required' => true],
                    ['key' => 'nik', 'label' => 'NIK', 'type' => 'text', 'required' => true],
                    ['key' => 'tanggal', 'label' => 'Tanggal', 'type' => 'date', 'required' => true],
                    ['key' => 'absensi_in', 'label' => 'Absensi IN', 'type' => 'text', 'required' => true],
                    ['key' => 'absensi_out', 'label' => 'Absensi Out', 'type' => 'text', 'required' => true],
                    ['key' => 'ket', 'label' => 'Keterangan', 'type' => 'text', 'required' => false],
                ],
                'document_defaults' => [
                    'divisi' => 'HRD',
                    'kepada' => 'Kepala Cabang',
                    'kepada_jabatan' => 'HRD',
                    'pengantar' => 'Sehubungan dengan adanya kendala absensi, dengan ini kami mengajukan permohonan revisi absensi dengan data sebagai berikut:',
                    'penutup' => 'Demikian berita acara ini saya buat dengan sebenarnya. Terima kasih atas perhatian dan kerjasamanya, saya berharap dapat dibantu memakluminya.',
                ],
            ],
            [
                'name' => 'Penghapusan Barang Sitaan',
                'type' => 'ba',
                'category' => 'Operasional',
                'field_schema' => [
                    ['key' => 'cabang', 'label' => 'Cabang', 'type' => 'text', 'required' => true],
                    ['key' => 'nama_nasabah', 'label' => 'Nama Nasabah', 'type' => 'text', 'required' => true],
                    ['key' => 'no_faktur', 'label' => 'No. Faktur', 'type' => 'text', 'required' => true],
                    ['key' => 'barang', 'label' => 'Barang', 'type' => 'text', 'required' => true],
                    ['key' => 'nominal_pinjaman', 'label' => 'Nominal Pinjaman', 'type' => 'number', 'required' => true],
                ],
                'document_defaults' => [
                    'direktorat' => 'Regional Branch Office',
                    'divisi' => 'Branch Leader',
                    'kepada' => 'Bpk. Nugroho Samudra Sujatmiko, Ko',
                    'kepada_jabatan' => 'Senior Executive Vice President Bisnis dan Operasional',
                    'pengantar' => 'Sehubungan dengan adanya penyitaan barang gadai, bersama ini kami sampaikan data barang sebagai berikut:',
                    'penutup' => 'Demikian berita acara ini dibuat agar dapat dipergunakan sebagaimana mestinya. Terima kasih atas perhatian dan kerjasamanya.',
                ],
            ],
            [
                'name' => 'Berita Acara Lainnya',
                'type' => 'ba',
                'category' => 'Umum',
                'field_schema' => [
                    ['key' => 'uraian', 'label' => 'Uraian', 'type' => 'text', 'required' => true],
                    ['key' => 'keterangan', 'label' => 'Keterangan', 'type' => 'textarea', 'required' => false],
                ],
            ],

            // ─── Form Pengajuan Templates ─────────────────────────────
            [
                'name' => 'Form Permohonan Pinjaman (FPP)',
                'type' => 'form',
                'category' => 'HRD',
                'field_schema' => [
                    ['key' => 'full_name', 'label' => 'Nama Lengkap', 'type' => 'text', 'required' => true],
                    ['key' => 'position', 'label' => 'Jabatan', 'type' => 'text', 'required' => true],
                    ['key' => 'work_location', 'label' => 'Cabang / Lokasi Kerja', 'type' => 'text', 'required' => true],
                    ['key' => 'employment_date', 'label' => 'Tanggal Masuk Kerja', 'type' => 'date', 'required' => true],
                    ['key' => 'late_months', 'label' => 'Terlambat (bulan)', 'type' => 'number', 'required' => false],
                    ['key' => 'absence_months', 'label' => 'Tidak Masuk (bulan)', 'type' => 'number', 'required' => false],
                    ['key' => 'request_number', 'label' => 'Permohonan ke-', 'type' => 'number', 'required' => false],
                    ['key' => 'salary_after_approval', 'label' => 'Gaji setelah disetujui', 'type' => 'number', 'required' => false],
                    ['key' => 'minimum_salary', 'label' => 'Gaji minimal', 'type' => 'number', 'required' => false],
                    ['key' => 'loan_amount', 'label' => 'Jumlah pinjaman yang diajukan', 'type' => 'number', 'required' => true],
                    ['key' => 'repayment_months', 'label' => 'Lama pengembalian (bulan)', 'type' => 'number', 'required' => true],
                    ['key' => 'reason', 'label' => 'Alasan Pinjaman', 'type' => 'textarea', 'required' => true],
                ],
            ],
            [
                'name' => 'Form Ijin Tidak Masuk Kerja (FITMK)',
                'type' => 'form',
                'category' => 'HRD',
                'field_schema' => [
                    ['key' => 'full_name', 'label' => 'Nama', 'type' => 'text', 'required' => true],
                    ['key' => 'leave_type', 'label' => 'Detail ijin tidak masuk kerja', 'type' => 'select', 'required' => true],
                    ['key' => 'start_date', 'label' => 'Tanggal ijin tidak masuk kerja', 'type' => 'date', 'required' => true],
                    ['key' => 'duration', 'label' => 'Durasi (hari)', 'type' => 'number', 'required' => true],
                    ['key' => 'reason', 'label' => 'Alasan ijin tidak masuk kerja', 'type' => 'textarea', 'required' => true],
                    ['key' => 'handover', 'label' => 'Pekerjaan selama tidak masuk dilimpahkan ke', 'type' => 'text', 'required' => false],
                    ['key' => 'substitute', 'label' => 'Nama pengganti', 'type' => 'text', 'required' => false],
                    ['key' => 'phone', 'label' => 'Kontak selama tidak masuk kerja (Telepon / HP)', 'type' => 'text', 'required' => false],
                ],
            ],
        ];

        foreach ($templates as $template) {
            if (!isset($template['document_defaults'])) {
                $template['document_defaults'] = [];
            }
            $template['document_defaults']['kepada'] = 'Bpk Nugroho Samudra Sujatmiko,Ko';
            $template['document_defaults']['kepada_jabatan'] = 'Senior Executive Vice President Bisnis dan Operasional';

            $existing = static::query()->where('name', $template['name'])->first();

            if ($existing) {
                $updates = [
                    'type' => $template['type'] ?? 'memo',
                    'category' => $template['category'],
                    'field_schema' => $template['field_schema'],
                    'is_active' => true,
                ];
                foreach (['document_defaults', 'signature_schema'] as $optionalSchema) {
                    if (array_key_exists($optionalSchema, $template)) {
                        $updates[$optionalSchema] = $template[$optionalSchema];
                    }
                }
                $existing->update($updates);

                continue;
            }

            static::query()->create(array_merge([
                'name' => $template['name'],
                'type' => $template['type'] ?? 'memo',
                'category' => $template['category'],
                'field_schema' => $template['field_schema'],
                'is_active' => true,
                'created_by' => $admin->id,
            ], array_intersect_key($template, array_flip(['document_defaults', 'signature_schema']))));
        }

        Cache::forget('active_memo_templates');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function memos()
    {
        return $this->hasMany(Memo::class, 'template_id');
    }
}
