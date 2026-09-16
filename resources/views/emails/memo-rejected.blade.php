<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Memo Ditolak</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f0f4f8; color: #334155; }
        .wrapper { max-width: 600px; margin: 32px auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%); padding: 36px 40px; text-align: center; }
        .header h1 { color: #ffffff; font-size: 20px; font-weight: 700; letter-spacing: -0.5px; }
        .header p { color: rgba(255,255,255,0.8); font-size: 13px; margin-top: 4px; }
        .body { padding: 40px; }
        .greeting { font-size: 15px; color: #334155; margin-bottom: 16px; }
        .greeting strong { color: #1e293b; }
        .intro { font-size: 14px; color: #64748b; line-height: 1.6; margin-bottom: 24px; }
        .memo-card { background: #fff5f5; border: 1px solid #fecaca; border-radius: 12px; padding: 24px; margin-bottom: 20px; }
        .memo-card .badge { display: inline-block; background: #fee2e2; color: #dc2626; border: 1px solid #fca5a5; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; }
        .memo-card .title { font-size: 17px; font-weight: 700; color: #1e293b; margin-bottom: 16px; }
        .detail-row { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 10px; }
        .detail-row:last-child { margin-bottom: 0; }
        .detail-label { font-size: 12px; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; min-width: 80px; padding-top: 2px; }
        .detail-value { font-size: 14px; color: #334155; font-weight: 500; }
        .reason-box { background: #fef2f2; border-left: 4px solid #ef4444; border-radius: 0 8px 8px 0; padding: 16px 20px; margin-bottom: 28px; }
        .reason-box .reason-label { font-size: 11px; font-weight: 700; color: #dc2626; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
        .reason-box .reason-text { font-size: 14px; color: #7f1d1d; line-height: 1.6; }
        .cta { text-align: center; margin-bottom: 32px; }
        .cta a { background: linear-gradient(135deg, #dc2626, #ef4444); color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 10px; font-size: 14px; font-weight: 700; display: inline-block; }
        .divider { border: none; border-top: 1px solid #e2e8f0; margin: 0 40px 28px; }
        .footer { padding: 0 40px 36px; text-align: center; }
        .footer p { font-size: 12px; color: #94a3b8; line-height: 1.7; }
        .footer strong { color: #64748b; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <div style="font-size: 32px; margin-bottom: 16px;">❌</div>
            <h1>Memo Ditolak — Perlu Revisi</h1>
            <p>Notifikasi Sistem Area Request Management</p>
        </div>
        <div class="body">
            <p class="greeting">Halo, <strong>{{ $memo->creator->name }}</strong></p>
            <p class="intro">
                Mohon maaf, memo Anda telah <strong>ditolak</strong> oleh Area Manager
                <strong>{{ $memo->areaManager->name }}</strong>. Silakan periksa catatan di bawah dan lakukan revisi yang diperlukan.
            </p>

            <div class="memo-card">
                <span class="badge">✗ Ditolak</span>
                <div class="title">{{ $memo->title }}</div>
                <div class="detail-row">
                    <span class="detail-label">Kode</span>
                    <span class="detail-value">{{ $memo->code ?? '-' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Cabang</span>
                    <span class="detail-value">{{ $memo->branch->name ?? '-' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Area Manager</span>
                    <span class="detail-value">{{ $memo->areaManager->name }}</span>
                </div>
            </div>

            <div class="reason-box">
                <div class="reason-label">📝 Alasan Penolakan</div>
                <div class="reason-text">{{ $notes }}</div>
            </div>

            <div class="cta">
                <a href="{{ config('app.url') }}/memos/{{ $memo->id }}/edit">Buka & Revisi Memo →</a>
            </div>
        </div>
        <hr class="divider">
        <div class="footer">
            <p>Email ini dikirim otomatis oleh sistem <strong>Area Request Management</strong>.<br>
            Kanwil Solo | Tolong jangan balas email ini.</p>
        </div>
    </div>
</body>
</html>
