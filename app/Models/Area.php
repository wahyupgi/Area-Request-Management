<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'parent_id'];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function cities()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function areaManager()
    {
        return $this->hasOne(User::class, 'area_id')->where('role', 'AM');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function kcUsers()
    {
        return $this->hasMany(User::class)->where('role', 'KC');
    }
}
