<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemoTemplate extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'category', 'field_schema', 'is_active', 'created_by'];

    protected $casts = [
        'field_schema' => 'array',
        'is_active' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function memos()
    {
        return $this->hasMany(Memo::class, 'template_id');
    }
}
