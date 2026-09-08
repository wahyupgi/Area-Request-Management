<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemoApproval extends Model
{
    use HasFactory;

    protected $fillable = ['memo_id', 'approver_id', 'action', 'notes', 'signature_id', 'signed_at'];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function memo()
    {
        return $this->belongsTo(Memo::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function signature()
    {
        return $this->belongsTo(DigitalSignature::class, 'signature_id');
    }
}
