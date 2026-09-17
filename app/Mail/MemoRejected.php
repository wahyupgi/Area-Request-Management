<?php

namespace App\Mail;

use App\Models\Memo;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MemoRejected extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Memo $memo, public string $notes)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[DITOLAK] Memo ' . $this->memo->code . ' memerlukan revisi',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.memo-rejected',
        );
    }
}
