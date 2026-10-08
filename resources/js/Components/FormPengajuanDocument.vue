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
    sakit: 'Sakit (Melampirkan Surat Dokter)',
    cuti_khusus: 'Cuti Khusus (Menikah/Kematian/Hari Raya)',
    lainnya: 'Lainnya',
};
const formatLongDate = (value) => {
    if (!value) return '-';
    const parts = String(value).match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (!parts) return value;

    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return `${Number(parts[3])} ${months[Number(parts[2]) - 1]} ${parts[1]}`;
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

const areaManagerName = computed(() => (props.beritaAcara.areaManager?.name || props.beritaAcara.area_manager?.name || '').trim());
const areaName = computed(() => props.beritaAcara.creator?.area?.name
    || props.beritaAcara.branch?.area?.name
    || props.beritaAcara.areaManager?.area?.name
    || props.beritaAcara.area_manager?.area?.name
    || '');
const areaManagerSignatureName = computed(() => {
    const name = areaManagerName.value.replace(/^Bpk\.\s*/i, '').split(/\s+/)[0];
    return name ? `Bpk. ${name}` : '';
});
const fitmkAreaManagerName = computed(() => {
    const name = areaManagerName.value.replace(/^Bpk\.?\s*/i, '').trim();
    if (!name) return '';

    const parts = name.split(/\s+/);
    const abbreviatedName = parts.length > 1 ? `${parts[0]} ${parts[parts.length - 1][0]}` : parts[0];
    return `Bpk. ${abbreviatedName}`;
});

const creatorSignature = computed(() => props.beritaAcara.creator?.digital_signature?.signature_image);
const managerSignature = computed(() => props.beritaAcara.areaManager?.digital_signature?.signature_image
    || props.beritaAcara.area_manager?.digital_signature?.signature_image);
</script>

<template>
    <div id="printable-ba" class="request-form-document" :class="{ 'fitmk-page': !isLoan }">
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
            <div class="request-approval">
                <div class="request-band">Kolom Persetujuan</div>
                <div class="request-date">{{ areaName }}{{ areaName ? ', ' : '' }}{{ formatDate(beritaAcara.created_at) }}</div>
                <div class="request-signatures">
                    <div class="request-signature">
                        <div class="request-signature-label">PEMOHON</div>
                        <div class="request-signature-image"><img v-if="creatorSignature" :src="`/storage/${creatorSignature}`" alt="Tanda tangan pemohon" /></div>
                        <div class="request-signature-name">{{ data.full_name || beritaAcara.creator?.name || '-' }}</div>
                        <div class="request-signature-role">{{ data.position || 'Kepala Cabang' }}</div>
                        <div class="request-signature-date">Tanggal: {{ formatDate(beritaAcara.created_at) }}</div>
                    </div>
                    <div class="request-signature">
                        <div class="request-signature-label">MENGETAHUI<br />ATASAN PEMOHON</div>
                        <div class="request-signature-image"><img v-if="beritaAcara.status === 'approved' && managerSignature" :src="`/storage/${managerSignature}`" alt="Tanda tangan atasan" /></div>
                        <div class="request-signature-name">{{ areaManagerSignatureName }}</div>
                        <div class="request-signature-role">Manager</div>
                        <div class="request-signature-date">Tanggal:</div>
                    </div>
                    <div class="request-signature">
                        <div class="request-signature-label">DIPERIKSA<br />HRD</div>
                        <div class="request-signature-image"></div>
                        <div class="request-signature-name">NAMA</div>
                        <div class="request-signature-role">JABATAN</div>
                        <div class="request-signature-date">Tanggal:</div>
                    </div>
                    <div class="request-signature">
                        <div class="request-signature-label">MENYETUJUI</div>
                        <div class="request-signature-image"></div>
                        <div class="request-signature-name">Nama Penyetuju</div>
                        <div class="request-signature-role">Jabatan</div>
                        <div class="request-signature-date">Tanggal:</div>
                    </div>
                </div>
            </div>
        </template>

        <template v-else>
            <div class="fitmk-form">
                <table class="fitmk-header">
                    <tbody><tr>
                        <td class="fitmk-logo"><img src="/PGI-Primary Logo Flat.png" alt="Logo PGI" /></td>
                        <td class="fitmk-company">PT PUSAT GADAI INDONESIA</td>
                        <td class="fitmk-code">FORM 03-HRD-LEAVE/UNPAID<br />2019/2020</td>
                    </tr></tbody>
                </table>
                <div class="request-band fitmk-title">FORM IJIN TIDAK MASUK KERJA<br />(FITMK)</div>
                <section class="fitmk-details-section">
                    <table class="fitmk-details">
                        <tbody>
                            <tr><th>1.</th><td>Nama</td><td>:</td><td class="fitmk-applicant">{{ data.full_name || beritaAcara.creator?.name || '-' }}</td></tr>
                            <tr><th>2.</th><td>Detail ijin tidak masuk kerja</td><td>:</td><td>{{ leaveTypeLabels[data.leave_type] || '-' }}</td></tr>
                            <tr><th></th><td colspan="3" class="fitmk-options">
                                <span v-for="(label, value) in leaveTypeLabels" :key="value" class="fitmk-option">
                                    <span class="fitmk-option-mark">{{ data.leave_type === value ? '✓' : '◯' }}</span>{{ label }}
                                </span>
                            </td></tr>
                            <tr><th></th><td>Tanggal ijin tidak masuk kerja</td><td>:</td><td>{{ formatLongDate(data.start_date) }} / {{ data.duration || '-' }} Hari</td></tr>
                            <tr><th></th><td>Alasan ijin tidak masuk kerja</td><td>:</td><td>{{ data.reason || '-' }}</td></tr>
                            <tr><th></th><td>Pekerjaan selama tidak masuk dilimpahkan ke</td><td>:</td><td>{{ data.handover || '-' }}</td></tr>
                        </tbody>
                    </table>
                </section>
                <section class="fitmk-numbered-section">
                    <div class="fitmk-section-title"><span>3.</span><span>Kontak selama tidak masuk kerja :</span></div>
                    <div class="fitmk-contact-detail"><span>Tlp / Hp</span><span>{{ data.phone || '-' }}</span></div>
                </section>
                <section class="fitmk-numbered-section fitmk-approval-section">
                    <div class="fitmk-section-title"><span>4.</span><span>Pengesahan</span></div>
                    <table class="fitmk-approval">
                    <tbody>
                        <tr class="fitmk-approval-head"><th>PEMOHON</th><th>PENGGANTI</th><th>MENYETUJUI<br />ATASAN PEMOHON</th><th>MENGETAHUI<br />HRD</th></tr>
                        <tr class="fitmk-signature-row">
                            <td><div class="fitmk-signature-line"><img v-if="creatorSignature" :src="`/storage/${creatorSignature}`" alt="Tanda tangan pemohon" /></div></td>
                            <td><div class="fitmk-signature-line"></div></td>
                            <td><div class="fitmk-signature-line"><img v-if="beritaAcara.status === 'approved' && managerSignature" :src="`/storage/${managerSignature}`" alt="Tanda tangan atasan" /></div></td>
                            <td><div class="fitmk-signature-line"></div></td>
                        </tr>
                        <tr class="fitmk-signature-names">
                            <td><strong>{{ data.full_name || beritaAcara.creator?.name || '-' }}</strong><br />Kepala Cabang</td>
                            <td><strong>{{ data.substitute || '-' }}</strong><br />{{ data.substitute_position || 'Pengganti' }}</td>
                            <td><strong>{{ fitmkAreaManagerName || '-' }}</strong><br />Manager</td>
                            <td><strong>HRD</strong></td>
                        </tr>
                        <tr class="fitmk-date-row"><td>Tanggal: {{ formatDate(beritaAcara.created_at) }}</td><td>Tanggal:</td><td>Tanggal:</td><td>Tanggal:</td></tr>
                    </tbody>
                    </table>
                </section>
                <div class="fitmk-bottom-space"></div>
            </div>
        </template>
    </div>
</template>

<style scoped>
.request-form-document {
    box-sizing: border-box;
    width: 100%;
    max-width: 210mm;
    margin: 0 auto;
    min-height: 297mm;
    padding: 12mm;
    background: #fff;
    color: #111;
    border: 1px solid #d1d5db;
    font: 11px Tahoma, sans-serif;
}
.request-title { padding: 18mm 0 8mm; text-align: center; font-size: 19px; font-weight: 700; }
.request-band {
    padding: 4px;
    border: 1px solid #000;
    background: #1f497d;
    color: #fff;
    text-align: center;
    font-weight: 700;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
.request-table, .fitmk-header, .fitmk-details, .fitmk-contact, .fitmk-approval { width: 100%; border-collapse: collapse; }
.request-table th, .request-table td, .fitmk-header td, .fitmk-contact td, .fitmk-contact th { border: 1px solid #222; padding: 3px 5px; text-align: left; }
.request-table th { width: 36%; }
.request-table td { font-weight: 600; }
.request-choice { display: flex; padding: 0 !important; }
.request-choice span { flex: 1; padding: 3px; text-align: center; }
.request-choice span + span { border-left: 1px solid #222; }
.request-large-row th, .request-large-row td { height: 85px; }
.request-large-row td { vertical-align: middle; white-space: pre-wrap; }
.request-response { height: 35mm; border: 1px solid #222; }
.request-approval { border: 1px solid #222; }
.request-approval .request-band { margin: -1px -1px 0; }
.request-date { padding: 14px 10px 8px; text-align: left; font-weight: 700; }
.request-signatures { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); text-align: center; }
.request-signature { min-width: 0; padding: 5px 3px; }
.request-signature-label { min-height: 38px; font-weight: 700; }
.request-signature-image { height: 58px; display: flex; align-items: flex-end; justify-content: center; }
.request-signature-image img { display: block; max-width: 90%; max-height: 52px; object-fit: contain; }
.request-signature-name { display: flex; align-items: flex-end; justify-content: center; width: 80%; min-height: 19px; margin: 0 auto 2px; padding-bottom: 1px; border-bottom: 1px solid #8bb2f5; font-weight: 700; line-height: 1; }
.request-signature-role { font-weight: 400; }
.request-signature-date { padding-top: 20px; text-align: left; }
.fitmk-header { table-layout: fixed; margin-top: 12mm; }
.fitmk-header td { height: 19mm; text-align: center; }
.fitmk-page { padding: 6mm; }
.fitmk-form { margin-top: 20mm; border: 1px solid #222; }
.fitmk-header { margin-top: 0; }
.fitmk-header td { border: 0; }
.fitmk-header td + td { border-left: 1px solid #222; }
.fitmk-logo { width: 25%; }
.fitmk-logo img { width: 75px; height: 55px; object-fit: contain; }
.fitmk-company { width: 48%; font-size: 19px; font-weight: 700; }
.fitmk-code { width: 27%; font-size: 10px; }
.fitmk-title {
    border-right: 0;
    border-left: 0;
    background: #c6d9f1;
    color: #111;
    font-size: 14px;
}
.fitmk-details-section { padding: 5mm 7mm; border-bottom: 1px solid #222; }
.fitmk-details { margin: 0; table-layout: fixed; }
.fitmk-details th { width: 5%; font-weight: 400; vertical-align: top; }
.fitmk-details td { padding: 2px; vertical-align: top; }
.fitmk-details td:nth-child(2) { width: 32%; }
.fitmk-details td:nth-child(3) { width: 2%; }
.fitmk-details .fitmk-applicant { font-weight: 700; }
.fitmk-options { display: grid; grid-template-columns: 1fr 1fr; gap: 3px 12px; padding: 2px 0 7px 14px !important; }
.fitmk-option { white-space: nowrap; }
.fitmk-option-mark { display: inline-block; width: 14px; font-size: 13px; line-height: 1; }
.fitmk-numbered-section { padding: 2mm 7mm; border-bottom: 1px solid #222; }
.fitmk-section-title { display: grid; grid-template-columns: 5% 1fr; }
.fitmk-contact-detail { display: grid; grid-template-columns: 37% 1fr; padding-left: 5%; }
.fitmk-approval-section { padding-top: 2mm; padding-bottom: 12mm; border-bottom: 0; }
.fitmk-approval { margin: 0; table-layout: fixed; text-align: center; }
.fitmk-approval th, .fitmk-approval td { width: 25%; padding: 4px 5px; }
.fitmk-approval .fitmk-approval-head th { height: 34px; }
.fitmk-approval .fitmk-signature-row td { padding: 0 5px; }
.fitmk-signature-line { display: flex; width: 84%; height: 54px; margin: 0 auto; align-items: flex-end; justify-content: center; }
.fitmk-signature-line img { display: block; max-width: 90%; max-height: 52px; object-fit: contain; }
.fitmk-approval .fitmk-signature-names td { padding: 3px 5px; }
.fitmk-signature-names strong { display: inline-block; max-width: 100%; padding-bottom: 2px; border-bottom: 1px solid #8bb2f5; overflow-wrap: anywhere; }
.fitmk-approval .fitmk-date-row td { padding-top: 16px; text-align: left; }
.fitmk-bottom-space { height: 17mm; border-top: 1px solid #222; }
@media print {
    @page { size: A4 portrait; margin: 0; }
    :global(body *) { visibility: hidden !important; }
    :global(#printable-ba), :global(#printable-ba *) { visibility: visible !important; }
    :global(#printable-ba) { position: fixed !important; inset: 0 auto auto 0 !important; width: 210mm !important; min-height: 297mm !important; padding: 12mm !important; border: 0 !important; }
    :global(#printable-ba.fitmk-page) { padding: 6mm !important; }
}
</style>
