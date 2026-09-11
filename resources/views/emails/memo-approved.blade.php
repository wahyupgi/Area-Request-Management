<h2>Memo telah disetujui</h2>

<p>Halo {{ $memo->creator->name }},</p>

<p>Memo berikut telah disetujui dan ditandatangani oleh Area Manager:</p>

<ul>
    <li><strong>Kode:</strong> {{ $memo->code }}</li>
    <li><strong>Judul:</strong> {{ $memo->title }}</li>
    <li><strong>Area Manager:</strong> {{ $memo->areaManager->name }}</li>
</ul>

<p>Silakan masuk ke sistem untuk melihat detail memo.</p>