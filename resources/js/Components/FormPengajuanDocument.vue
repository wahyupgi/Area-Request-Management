<script setup>
import { computed } from 'vue';

const props = defineProps({
    beritaAcara: { type: Object, required: true },
});

const data = computed(() => props.beritaAcara.meta?.request_data || {});
const isLoan = computed(() => props.beritaAcara.meta?.template === 'form_permohonan_pinjaman');
const leaveTypeLabels = {
    cuti_tahunan: 'Cuti Tahunan',
    ijin: 'Ijin (Belum Ada Hak Cuti)',
    cuti_melahirkan: 'Cuti Melahirkan',
    cuti_khusus: 'Cuti Khusus (Menikah/Kematian/Hari Raya)',
    sakit: 'Sakit (Melampirkan Surat Dokter)',
    lainnya: 'Lainnya',
};

const formatDate = (value) => {
    if (!value) return '-';
    const parts = String(value).match(/^(\d{4})-(\d{2})-(\d{2})/);
    return parts ? `${parts[3]}/${parts[2]}/${parts[1]}` : value;
};

const formatRupiahAmount = (value) => {
    if (value === null || value === undefined || value === '') return '-';
    const text = String(value).trim();
    const digits = text.replace(/^Rp\s*/i, '').replace(/[.\s,]/g, '');
    if (!/^\d+$/.test(digits)) return value;
    return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
};

const areaManagerName = computed(() => {
    const name = (props.beritaAcara.areaManager?.name || props.beritaAcara.area_manager?.name || '').trim();
    if (!name) return 'Bpk. Fathurrahman. M';
    return /^(bpk\.?|pak)\s/i.test(name) ? name : `Bpk. ${name}`;
});

const creatorSignature = computed(() => props.beritaAcara.creator?.digital_signature?.signature_image);
const managerSignature = computed(() => props.beritaAcara.areaManager?.digital_signature?.signature_image
    || props.beritaAcara.area_manager?.digital_signature?.signature_image);
</script>

<template>
    <div id="printable-ba" class="request-form-document">
        <template v-if="isLoan">
            <header class="request-title">FORM PERMOHONAN PINJAMAN (FPP)</header>
            <div class="request-band">Kolom Pemohon (diisi oleh pemohon)</div>
            <table class="request-table">
                <tbody>
                    <tr><th>Nama Lengkap</th><td>{{ data.full_name || '-' }}</td></tr>
                    <tr><th>Jabatan</th><td>{{ data.position || '-' }}</td></tr>
                    <tr><th>Cabang / Lokasi Kerja</th><td>{{ data.work_location || beritaAcara.branch?.name || '-' }}</td></tr>
                    <tr><th>Tanggal masuk kerja</th><td>{{ formatDate(data.employment_date) }}</td></tr>
                    <tr><th>Terlambat ({{ data.late_months ?? 0 }} bulan)</th><td>{{ data.late_months ?? '-' }}</td></tr>
                    <tr><th>Tidak Masuk ({{ data.absence_months ?? 0 }} bulan)</th><td>{{ data.absence_months ?? '-' }}</td></tr>
                    <tr><th>Permohonan keberapa</th><td>{{ data.request_number || '-' }}</td></tr>
                    <tr><th>Gaji yang diterima setelah disetujui</th><td>{{ formatRupiahAmount(data.salary_after_approval) }}</td></tr>
                    <tr><th>Gaji minimal</th><td>{{ formatRupiahAmount(data.minimum_salary) }}</td></tr>
                    <tr><th>Jumlah Pinjaman yang diajukan</th><td>{{ formatRupiahAmount(data.loan_amount) }}</td></tr>
                    <tr><th>Lamanya pengembalian</th><td>{{ data.repayment_months ? `${data.repayment_months} Bulan` : '-' }}</td></tr>
                    <tr><th>Bersedia potong gaji setiap bulan</th><td>{{ data.salary_deduction || '-' }}</td></tr>
                    <tr>
                        <th>Jenis Pinjaman (pilih salah satu)</th>
                        <td class="request-choice">
                            <span>Pinjaman Uang {{ data.loan_type === 'pinjaman_uang' ? '✓' : '' }}</span>
                            <span>Pembelian Barang {{ data.loan_type === 'pembelian_barang' ? '✓' : '' }}</span>
                        </td>
                    </tr>
                    <tr class="request-large-row">
                        <th>Alasan Pinjaman<br />(Lampirkan foto bukti atau kwitansi)</th>
                        <td>{{ data.reason || '-' }}</td>
                    </tr>
                </tbody>
            </table>
            <div class="request-band">Tanggapan MANAJEMEN</div>
            <div class="request-response"></div>
            <div class="request-band">Kolom Persetujuan</div>
            <div class="request-date">{{ formatDate(beritaAcara.created_at) }}</div>
            <table class="request-signatures">
                <thead><tr><th>PEMOHON</th><th>MENGETAHUI<br />ATASAN PEMOHON</th><th>DIPERIKSA<br />HRD</th><th>MENYETUJUI</th></tr></thead>
                <tbody>
                    <tr class="request-signature-images">
                        <td><img v-if="creatorSignature" :src="`/storage/${creatorSignature}`" alt="Tanda tangan pemohon" /></td>
                        <td><img v-if="beritaAcara.status === 'approved' && managerSignature" :src="`/storage/${managerSignature}`" alt="Tanda tangan atasan" /></td>
                        <td></td><td></td>
                    </tr>
                    <tr class="request-signature-names">
                        <td><span>{{ data.full_name || beritaAcara.creator?.name || '-' }}</span><strong>{{ data.position || 'Kepala Cabang' }}</strong></td>
                        <td><span>{{ areaManagerName }}</span><strong>Manager</strong></td>
                        <td><span>NAMA</span><strong>JABATAN</strong></td>
                        <td><span>Nama Penyetuju</span><strong>Jabatan</strong></td>
                    </tr>
                    <tr class="request-signature-dates"><td>Tanggal: {{ formatDate(beritaAcara.created_at) }}</td><td>Tanggal:</td><td>Tanggal:</td><td>Tanggal:</td></tr>
                </tbody>
            </table>
        </template>

        <template v-else>
            <table class="fitmk-header">
                <tbody><tr>
                    <td class="fitmk-logo"><img src="/PGI-Primary Logo Flat.png" alt="Logo PGI" /></td>
                    <td class="fitmk-company">PT PUSAT GADAI INDONESIA</td>
                    <td class="fitmk-code">FORM 03-HRD-LEAVE/UNPAID<br />2019/2020</td>
                </tr></tbody>
            </table>
            <div class="request-band fitmk-title">FORM IJIN TIDAK MASUK KERJA<br />(FITMK)</div>
            <table class="fitmk-details">
                <tbody>
                    <tr><th>1.</th><td>Nama</td><td>:</td><td>{{ data.full_name || beritaAcara.creator?.name || '-' }}</td></tr>
                    <tr><th>2.</th><td>Detail ijin tidak masuk kerja</td><td>:</td><td>{{ leaveTypeLabels[data.leave_type] || '-' }}</td></tr>
                    <tr><th></th><td colspan="3" class="fitmk-options">
                        <span v-for="(label, value) in leaveTypeLabels" :key="value">
                            {{ data.leave_type === value ? '☑' : '☐' }} {{ label }}
                        </span>
                    </td></tr>
                    <tr><th></th><td>Tanggal ijin tidak masuk kerja</td><td>:</td><td>{{ formatDate(data.start_date) }} / {{ data.duration || '-' }} Hari</td></tr>
                    <tr><th></th><td>Alasan ijin tidak masuk kerja</td><td>:</td><td>{{ data.reason || '-' }}</td></tr>
                    <tr><th></th><td>Pekerjaan selama tidak masuk dilimpahkan ke</td><td>:</td><td>{{ data.handover || '-' }}</td></tr>
                </tbody>
            </table>
            <table class="fitmk-contact">
                <tbody><tr><th>3.</th><td>Kontak selama tidak masuk kerja :<br />Tlp / Hp</td><td>{{ data.phone || '-' }}</td></tr></tbody>
            </table>
            <table class="fitmk-approval">
                <tbody>
                    <tr><td colspan="4">4. &nbsp; Pengesahan</td></tr>
                    <tr><th>PEMOHON</th><th>PENGGANTI</th><th>MENYETUJUI<br />ATASAN PEMOHON</th><th>MENGETAHUI<br />HRD</th></tr>
                    <tr class="request-signature-images">
                        <td><img v-if="creatorSignature" :src="`/storage/${creatorSignature}`" alt="Tanda tangan pemohon" /></td>
                        <td></td>
                        <td><img v-if="beritaAcara.status === 'approved' && managerSignature" :src="`/storage/${managerSignature}`" alt="Tanda tangan atasan" /></td>
                        <td></td>
                    </tr>
                    <tr><td>{{ data.full_name || beritaAcara.creator?.name || '-' }}<br />Kepala Cabang</td><td>{{ data.substitute || '-' }}<br />Pengganti</td><td>{{ beritaAcara.areaManager?.name || beritaAcara.area_manager?.name || '-' }}<br />Manager</td><td>HRD</td></tr>
                    <tr class="fitmk-date-row"><td>Tanggal: {{ formatDate(beritaAcara.created_at) }}</td><td>Tanggal:</td><td>Tanggal:</td><td>Tanggal:</td></tr>
                </tbody>
            </table>
        </template>
    </div>
</template>

<style scoped>
.request-form-document {
    box-sizing: border-box;
    width: 100%;
    min-height: 297mm;
    padding: 12mm;
    background: #fff;
    color: #111;
    border: 1px solid #d1d5db;
    font: 11px Arial, sans-serif;
}
.request-title { padding: 18mm 0 8mm; text-align: center; font-size: 19px; font-weight: 700; }
.request-band { padding: 4px; border: 1px solid #222; background: #c6d9f1; text-align: center; font-weight: 700; }
.request-table, .request-signatures, .fitmk-header, .fitmk-details, .fitmk-contact, .fitmk-approval { width: 100%; border-collapse: collapse; }
.request-table th, .request-table td, .fitmk-header td, .fitmk-contact td, .fitmk-contact th { border: 1px solid #222; padding: 3px 5px; text-align: left; }
.request-table th { width: 36%; }
.request-table td { font-weight: 600; }
.request-choice { display: flex; padding: 0 !important; }
.request-choice span { flex: 1; padding: 3px; text-align: center; }
.request-choice span + span { border-left: 1px solid #222; }
.request-large-row th, .request-large-row td { height: 85px; }
.request-large-row td { vertical-align: middle; white-space: pre-wrap; }
.request-response { height: 35mm; border: 1px solid #222; }
.request-date { padding: 14px 0 8px; }
.request-signatures { table-layout: fixed; text-align: center; }
.request-signatures th, .request-signatures td { width: 25%; padding: 5px 3px; }
.request-signatures th { height: 40px; }
.request-signature-images td { height: 58px; vertical-align: bottom; }
.request-signature-images img { display: block; max-width: 90%; max-height: 52px; margin: 0 auto; object-fit: contain; }
.request-signature-names span { display: block; width: 80%; margin: 0 auto 2px; border-bottom: 1px solid #8bb2f5; font-weight: 700; }
.request-signature-names strong { display: block; font-weight: 400; }
.request-signature-dates td { padding-top: 20px; text-align: left; }
.fitmk-header { table-layout: fixed; margin-top: 12mm; }
.fitmk-header td { height: 19mm; text-align: center; }
.fitmk-logo { width: 25%; }
.fitmk-logo img { width: 75px; height: 55px; object-fit: contain; }
.fitmk-company { width: 48%; font-size: 19px; font-weight: 700; }
.fitmk-code { width: 27%; font-size: 10px; }
.fitmk-title { font-size: 14px; }
.fitmk-details { margin: 8px 0 0; }
.fitmk-details th { width: 7%; font-weight: 400; vertical-align: top; }
.fitmk-details td { padding: 2px; vertical-align: top; }
.fitmk-details td:nth-child(2) { width: 38%; }
.fitmk-details td:nth-child(3) { width: 3%; }
.fitmk-options { display: grid; grid-template-columns: 1fr 1fr; gap: 3px 12px; padding: 6px 0 10px 14px !important; }
.fitmk-contact { margin-top: 12px; }
.fitmk-contact th { width: 7%; font-weight: 400; vertical-align: top; }
.fitmk-approval { margin-top: 12px; table-layout: fixed; text-align: center; }
.fitmk-approval th, .fitmk-approval td { width: 25%; padding: 5px; }
.fitmk-approval th { height: 45px; }
.fitmk-approval .fitmk-date-row td { padding-top: 20px; text-align: left; }
@media print {
    :global(body *) { visibility: hidden !important; }
    :global(#printable-ba), :global(#printable-ba *) { visibility: visible !important; }
    :global(#printable-ba) { position: fixed !important; inset: 0 auto auto 0 !important; width: 210mm !important; min-height: 297mm !important; padding: 12mm !important; border: 0 !important; }
}
</style>
