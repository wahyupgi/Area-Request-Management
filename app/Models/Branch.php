<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'area_id', 'kc_user_id'];

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function kcUser()
    {
        return $this->belongsTo(User::class, 'kc_user_id');
    }

    public function memos()
    {
        return $this->hasMany(Memo::class);
    }
}
