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
