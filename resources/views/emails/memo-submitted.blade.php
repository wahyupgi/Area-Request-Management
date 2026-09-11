<h2>Pengajuan memo baru</h2>

<p>Halo {{ $memo->areaManager->name }},</p>

<p>Pengajuan memo berikut telah dikirim oleh {{ $memo->creator->name }} dan menunggu persetujuan Anda:</p>

<ul>
    <li><strong>Kode:</strong> {{ $memo->code }}</li>
    <li><strong>Judul:</strong> {{ $memo->title }}</li>
    <li><strong>Cabang:</strong> {{ $memo->branch->name }}</li>
</ul>

<p>Silakan masuk ke sistem untuk meninjau pengajuan tersebut.</p>