<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class MemoTemplate extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'category', 'field_schema', 'signature_schema', 'is_active', 'created_by'];

    protected $casts = [
        'field_schema' => 'array',
        'signature_schema' => 'array',
        'is_active' => 'boolean',
    ];

    public static function ensureRequiredDefaults(): void
    {
        $admin = User::query()->where('role', 'ADMIN')->first();

        if (!$admin) {
            return;
        }

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
                    ['key' => 'direktorat', 'label' => 'Direktorat', 'type' => 'text', 'required' => true],
                    ['key' => 'divisi', 'label' => 'Divisi', 'type' => 'text', 'required' => true],
                    ['key' => 'penerima', 'label' => 'Nama Penerima', 'type' => 'text', 'required' => true],
                    ['key' => 'penerima_jabatan', 'label' => 'Jabatan Penerima', 'type' => 'text', 'required' => true],
                    ['key' => 'penyetuju_akhir', 'label' => 'Penyetuju Akhir', 'type' => 'text', 'required' => false],
                    ['key' => 'alasan', 'label' => 'Alasan Pengeluaran Kas', 'type' => 'textarea', 'required' => true],
                    ['key' => 'lampiran', 'label' => 'Lampiran Pendukung', 'type' => 'text', 'required' => false],
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
        ];

        foreach ($templates as $template) {
            $existing = static::query()->where('name', $template['name'])->first();

            if ($existing) {
                $existing->update([
                    'category' => $template['category'],
                    'field_schema' => $template['field_schema'],
                    'is_active' => true,
                ]);

                continue;
            }

            static::query()->create([
                'name' => $template['name'],
                'category' => $template['category'],
                'field_schema' => $template['field_schema'],
                'is_active' => true,
                'created_by' => $admin->id,
            ]);
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
