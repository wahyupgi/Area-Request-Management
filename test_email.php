<?php

// Bootstrap Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$app->make('config'); // ensure config is loaded

use Illuminate\Support\Facades\Mail;

echo "Testing SMTP Connection...\n";
echo "MAIL_MAILER: " . env('MAIL_MAILER') . "\n";
echo "MAIL_HOST: " . env('MAIL_HOST') . "\n";
echo "MAIL_PORT: " . env('MAIL_PORT') . "\n";
echo "MAIL_FROM: " . env('MAIL_FROM_ADDRESS') . "\n\n";

try {
    Mail::raw('Test koneksi SMTP dari sistem Area Request Management (ARM). Email ini membuktikan bahwa notifikasi berjalan via Brevo SMTP.', function ($message) {
        $message->to('wahyupgi1@gmail.com')
                ->subject('[TEST] SMTP ARM - ' . date('d/m/Y H:i:s'));
    });
    echo "✅ Email berhasil dikirim!\n";
} catch (Exception $e) {
    echo "❌ Gagal kirim email: " . $e->getMessage() . "\n";
}
