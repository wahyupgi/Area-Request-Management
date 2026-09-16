<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmail extends Command
{
    protected $signature = 'mail:test {email? : Alamat email tujuan}';
    protected $description = 'Kirim email test untuk verifikasi koneksi SMTP';

    public function handle(): void
    {
        $target = $this->argument('email') ?? config('mail.from.address');

        $this->info("Mengirim email test ke: {$target}");
        $this->line('MAIL_MAILER  : ' . config('mail.default'));
        $this->line('MAIL_HOST    : ' . config('mail.mailers.smtp.host'));
        $this->line('MAIL_PORT    : ' . config('mail.mailers.smtp.port'));
        $this->line('MAIL_FROM    : ' . config('mail.from.address'));
        $this->newLine();

        try {
            Mail::raw(
                "Halo!\n\nIni adalah email test dari sistem Area Request Management (ARM).\n\nKoneksi SMTP berhasil berjalan via Brevo.\n\nWaktu: " . now()->format('d/m/Y H:i:s'),
                function ($message) use ($target) {
                    $message->to($target)
                            ->subject('[TEST] Koneksi SMTP ARM Berhasil — ' . now()->format('H:i:s'));
                }
            );
            $this->info('✅ Email berhasil dikirim!');
        } catch (\Throwable $e) {
            $this->error('❌ Gagal kirim email: ' . $e->getMessage());
        }
    }
}
