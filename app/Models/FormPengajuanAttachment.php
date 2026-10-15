<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormPengajuanAttachment extends Model
{
    protected $fillable = ['form_pengajuan_id', 'file_path', 'original_name'];

    public function formPengajuan()
    {
        return $this->belongsTo(FormPengajuan::class);
    }
}
