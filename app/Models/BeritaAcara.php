<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BeritaAcara extends Model
{
    use HasFactory;

    public const STATUS_DRAFT     = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED  = 'approved';
    public const STATUS_REJECTED  = 'rejected';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SUBMITTED,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
    ];

    protected $fillable = [
        'code', 'title', 'meta', 'pengantar',
        'rincian_data', 'keterangan_tambahan', 'penutup',
        'attachment_path', 'attachment_name',
        'branch_id', 'created_by', 'area_manager_id',
        'status', 'submitted_at',
    ];

    protected $casts = [
        'meta'          => 'array',
        'rincian_data'  => 'array',
        'submitted_at'  => 'datetime',
    ];

    /**
     * Auto-generate BA code on creation (format sama seperti Memo).
     */
    protected static function booted(): void
    {
        static::creating(function (BeritaAcara $ba) {
            $year     = now()->year;
            $month    = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'][now()->month - 1];
            $prefix   = 'BA/RBO/BRL/PGI/';
            $suffix   = '/' . $month . '/' . $year;

            $sequence = static::where('code', 'like', $prefix . '%' . $suffix)
                ->pluck('code')
                ->map(function (string $code) use ($prefix, $suffix) {
                    return preg_match('#^' . preg_quote($prefix, '#') . '(\d+)' . preg_quote($suffix, '#') . '$#', $code, $m)
                        ? (int) $m[1]
                        : 0;
                })
                ->max() + 1;

            $ba->code = $prefix . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT) . $suffix;

            while (static::where('code', $ba->code)->exists()) {
                $sequence++;
                $ba->code = $prefix . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT) . $suffix;
            }
        });
    }

    // ─── Relationships ────────────────────────────────────────────

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
}
