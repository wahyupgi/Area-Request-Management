<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BeritaAcaraAttachment extends Model
{
    protected $fillable = ['berita_acara_id', 'file_path', 'original_name'];

    public function beritaAcara()
    {
        return $this->belongsTo(BeritaAcara::class);
    }
}
