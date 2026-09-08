<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Memo extends Model
{
    use HasFactory;

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
            if (empty($memo->code)) {
                $date = now()->format('Ymd');
                $count = static::whereDate('created_at', today())->count() + 1;
                $memo->code = 'MEMO-' . $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
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
}
