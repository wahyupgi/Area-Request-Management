<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'branch_id',
        'area_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // ---- Role helpers ----
    public function isKC(): bool
    {
        return $this->role === 'KC';
    }

    public function isAM(): bool
    {
        return $this->role === 'AM';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'ADMIN';
    }

    // ---- Relationships ----
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function branches()
    {
        return $this->hasMany(Branch::class, 'kc_user_id');
    }

    public function assignedBranches()
    {
        return Branch::query()->where(function ($query) {
            $query->where('kc_user_id', $this->id)
                ->orWhere(function ($legacyQuery) {
                    $legacyQuery->where('id', $this->branch_id)
                        ->where(function ($kcQuery) {
                            $kcQuery->whereNull('kc_user_id')->orWhere('kc_user_id', $this->id);
                        });
                });
        });
    }

    public function availableBranchesForDocuments()
    {
        if ($this->isKC()) {
            return $this->assignedBranches();
        }

        if ($this->isAM() && $this->area_id) {
            return Branch::query()->where('area_id', $this->area_id);
        }

        if ($this->isAdmin()) {
            return Branch::query();
        }

        return Branch::query()->whereRaw('1 = 0');
    }

    public function areaManagerForBranch(Branch $branch): ?self
    {
        return self::query()
            ->where('role', 'AM')
            ->where('area_id', $branch->area_id)
            ->first();
    }

    public function areaManagerForApproval(): ?self
    {
        $areaId = $this->area_id;

        if (!$areaId) {
            $areaIds = $this->assignedBranches()->distinct()->pluck('area_id');
            if ($areaIds->count() !== 1) {
                return null;
            }

            $areaId = $areaIds->first();
        }

        return self::query()
            ->where('role', 'AM')
            ->where('area_id', $areaId)
            ->first();
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function memos()
    {
        return $this->hasMany(Memo::class, 'created_by');
    }

    public function pendingMemos()
    {
        return $this->hasMany(Memo::class, 'area_manager_id')->where('status', 'submitted');
    }

    public function digitalSignature()
    {
        return $this->hasOne(DigitalSignature::class);
    }

    public function appNotifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function unreadNotifications()
    {
        return $this->hasMany(Notification::class)->where('is_read', false);
    }
}
