<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Memo extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SUBMITTED,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
    ];

    protected $fillable = [
        'code', 'template_id', 'title', 'field_values',
        'branch_id', 'created_by', 'area_manager_id',
        'status', 'submitted_at',
    ];

    protected $casts = [
        'field_values' => 'array',
        'submitted_at' => 'datetime',
    ];

    /**
     * Auto-generate memo code on creation.
     */
    protected static function booted(): void
    {
        static::creating(function (Memo $memo) {
            $year = now()->year;
            $month = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][now()->month - 1];
            $sequence = static::where('code', 'like', 'INT/RBO/BRL/PGI/%/' . $year)
                ->pluck('code')
                ->map(function (string $code) {
                    return preg_match('#^INT/RBO/BRL/PGI/(\d+)/[IVXLCDM]+/\d{4}$#', $code, $matches)
                        ? (int) $matches[1]
                        : 0;
                })
                ->max() + 1;

            $memo->code = 'INT/RBO/BRL/PGI/' . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT) . '/' . $month . '/' . $year;

            while (static::where('code', $memo->code)->exists()) {
                $sequence++;
                $memo->code = 'INT/RBO/BRL/PGI/' . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT) . '/' . $month . '/' . $year;
            }
        });
    }

    public function template()
    {
        return $this->belongsTo(MemoTemplate::class, 'template_id');
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

    public function attachments()
    {
        return $this->hasMany(MemoAttachment::class);
    }

    public function approvals()
    {
        return $this->hasMany(MemoApproval::class)->orderBy('created_at', 'desc');
    }

    public function latestApproval()
    {
        return $this->hasOne(MemoApproval::class)->latestOfMany();
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function scopeForUser($query, User $user)
    {
        if ($user->isKC()) {
            return $query->where('created_by', $user->id);
        }

        if ($user->isAM()) {
            return $query->where('area_manager_id', $user->id);
        }

        return $query;
    }

    public function scopeFilterByStatus($query, ?string $status)
    {
        if ($status === null || $status === '') {
            return $query;
        }

        if (! in_array($status, self::STATUSES, true)) {
            return $query;
        }

        return $query->where('status', $status);
    }
}
