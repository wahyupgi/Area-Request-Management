<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormPengajuan extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const TEMPLATES = [
        'form_permohonan_pinjaman',
        'form_ijin_tidak_masuk_kerja',
    ];

    protected $fillable = [
        'code',
        'template',
        'title',
        'data',
        'attachment_path',
        'attachment_name',
        'branch_id',
        'created_by',
        'area_manager_id',
        'approved_by',
        'rejection_notes',
        'status',
        'submitted_at',
    ];

    protected $casts = [
        'data' => 'array',
        'submitted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (FormPengajuan $form): void {
            if ($form->code) return;

            $month = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][now()->month - 1];
            $prefix = 'FP/RBO/BRL/PGI/';
            $suffix = '/' . $month . '/' . now()->year;
            $sequence = static::query()
                ->where('code', 'like', $prefix . '%' . $suffix)
                ->pluck('code')
                ->map(fn (string $code) => preg_match('#^' . preg_quote($prefix, '#') . '(\d+)' . preg_quote($suffix, '#') . '$#', $code, $matches)
                    ? (int) $matches[1]
                    : 0)
                ->max() + 1;

            do {
                $form->code = $prefix . str_pad((string) $sequence++, 3, '0', STR_PAD_LEFT) . $suffix;
            } while (static::query()->where('code', $form->code)->exists());
        });
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function areaManager()
    {
        return $this->belongsTo(User::class, 'area_manager_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
