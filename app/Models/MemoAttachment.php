<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemoAttachment extends Model
{
    use HasFactory;

    protected $fillable = ['memo_id', 'file_path', 'original_name', 'uploaded_at'];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    public function memo()
    {
        return $this->belongsTo(Memo::class);
    }
}
