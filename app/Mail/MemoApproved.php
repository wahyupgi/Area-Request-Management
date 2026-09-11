<?php

namespace App\Mail;

use App\Models\Memo;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MemoApproved extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Memo $memo)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Memo ' . $this->memo->code . ' telah disetujui dan ditandatangani AM',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.memo-approved',
        );
    }
}