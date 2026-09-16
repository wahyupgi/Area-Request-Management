<?php

namespace App\Mail;

use App\Models\Memo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MemoSubmitted extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Memo $memo)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pengajuan memo baru ' . $this->memo->code . ' menunggu persetujuan',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.memo-submitted',
        );
    }
}