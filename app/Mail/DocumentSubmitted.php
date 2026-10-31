<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email ke Area Manager untuk dokumen (Berita Acara / Form Pengajuan) yang baru dikirim.
 */
class DocumentSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $documentLabel,
        public string $title,
        public ?string $code,
        public ?string $branchName,
        public User $creator,
        public User $areaManager,
        public string $reviewUrl,
        public $submittedAt = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pengajuan ' . $this->documentLabel . ' baru ' . ($this->code ?? '') . ' menunggu persetujuan',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.document-submitted',
        );
    }
}

