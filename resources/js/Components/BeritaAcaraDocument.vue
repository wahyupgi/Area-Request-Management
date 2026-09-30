<script setup>
import { computed } from 'vue';

const props = defineProps({
    beritaAcara: { type: Object, required: true },
});

const template = computed(() => props.beritaAcara.meta?.template || 'standard');
const isCostRequest = computed(() => template.value === 'permohonan_biaya_kost');
const isAttendanceRevision = computed(() => template.value === 'revisi_absensi');
const isSeizedGoods = computed(() => template.value === 'penghapusan_barang_sitaan');
const isOtherTemplate = computed(() => template.value === 'lainnya');
const templateRows = computed(() => props.beritaAcara.meta?.data?.rows || []);
const signatureSlots = computed(() => {
    const beritaAcara = props.beritaAcara;
    const creator = beritaAcara.creator;
    const manager = beritaAcara.area_manager || beritaAcara.areaManager;
    const executive = beritaAcara.approvals?.[1]?.approver;
    const hr = beritaAcara.approvals?.[2]?.approver;
    const finalApprover = beritaAcara.meta?.penyetuju_akhir;
    const slots = [
        { name: creator?.name, role: 'Kepala Cabang', user: creator },
        { name: manager?.name || 'Bpk. Fathurrahman M', role: manager?.jabatan || 'Manager', user: manager },
    ];

    if (isSeizedGoods.value) {
        slots.push({ name: 'Bpk. Yudha', role: 'Legal', user: null });
        slots.push({ name: executive?.name || finalApprover || 'Bpk. Nugroho Samudra Sujatmiko, Ko', role: 'Senior Executive Vice President Bisnis dan Operasional', user: executive });
    } else if (isCostRequest.value) {
        slots.push({ name: executive?.name || finalApprover || 'Bpk. Nugroho Samudra Sujatmiko, Ko', role: 'Senior Executive Vice President Bisnis dan Operasional', user: executive });
    } else {
        slots.push({ name: executive?.name || finalApprover || 'Bpk. Nugroho Samudra Sujatmiko, Ko', role: 'Senior Executive Vice President Bisnis dan Operasional', user: executive });
        slots.push({ name: hr?.name || 'Ibu Ella Safitri', role: hr?.jabatan || 'SPV HC Payroll', user: hr });
    }

    return slots;
});
const signatureWidths = computed(() => isSeizedGoods.value
    ? ['19%', '19%', '19%', '43%']
    : signatureSlots.value.map(() => `${100 / signatureSlots.value.length}%`));

const handleImgError = (event) => {
    event.target.style.display = 'none';
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return `${days[date.getDay()]}, ${String(date.getDate()).padStart(2, '0')} ${months[date.getMonth()]} ${date.getFullYear()}`;
};

const formatCurrency = (value) => {
    if (value === undefined || value === null || value === '') return '-';
    const digits = String(value).replace(/\D/g, '');
    if (!digits) return String(value);
    return `Rp ${Number(digits).toLocaleString('id-ID')}`;
};
</script>

<template>
    <div
        id="printable-ba"
        class="bg-white text-black pt-[3cm] pl-[2.54cm] pr-[2cm] pb-[2cm] print:p-0 rounded-xl print:rounded-none shadow-xl print:shadow-none w-full print:max-w-[210mm] print:min-h-0 text-xs leading-normal border border-slate-200 print:border-none"
        style="font-family: Tahoma, sans-serif;"
    >
        <div class="flex items-start justify-between mb-3">
            <div class="flex items-center gap-3">
                <img src="/PGI-Primary Logo Flat.png" alt="Logo PGI" class="w-14 h-14 object-contain" @error="handleImgError" />
            </div>
            <div class="border border-black px-3 py-1 text-xs font-bold tracking-tight bg-white">
                {{ beritaAcara.creator?.name }}
            </div>
        </div>

        <div class="ba-document-header text-center mb-5">
            <h1 class="text-[21px] font-extrabold uppercase underline tracking-widest mb-0.5">BERITA ACARA</h1>
            <p class="text-[11px] tracking-wider text-gray-800">{{ beritaAcara.code }}</p>
        </div>

        <div class="text-right mb-4 text-xs text-gray-900">
            {{ formatDate(beritaAcara.created_at) }}
        </div>

        <div v-if="isAttendanceRevision" class="grid grid-cols-[70px_12px_1fr] text-xs gap-y-1 mb-3">
            <div>Dari</div><div>:</div><div class="font-bold">Kepala Cabang</div>
            <div>Kepada</div><div>:</div><div>{{ beritaAcara.meta?.kepada_nama || 'HRD' }}</div>
            <div>Perihal</div><div>:</div><div>{{ beritaAcara.meta?.perihal || beritaAcara.title }}</div>
            <div>Lampiran</div><div>:</div><div>{{ beritaAcara.meta?.lampiran || '-' }}</div>
        </div>
        <div v-else class="grid grid-cols-[120px_12px_1fr] text-xs gap-y-1 mb-3">
            <div class="font-bold text-gray-900">Direktorat</div><div>:</div><div class="font-bold text-gray-900">{{ beritaAcara.meta?.direktorat || (isOtherTemplate ? '' : 'Operasional') }}</div>
            <div class="text-gray-900">Divisi</div><div>:</div><div>{{ beritaAcara.meta?.divisi || (isOtherTemplate ? '' : '-') }}</div>
            <div class="text-gray-900">Perihal</div><div>:</div><div>{{ beritaAcara.meta?.perihal || (isOtherTemplate ? '' : beritaAcara.title) }}</div>
            <div class="text-gray-900">Lampiran</div><div>:</div><div>{{ beritaAcara.meta?.lampiran || (isOtherTemplate ? '' : '-') }}</div>
        </div>

        <hr class="border-t-2 border-black my-3" />

        <div class="text-xs mb-4 leading-tight">
            <p class="m-0">{{ isAttendanceRevision ? 'Kepada Yth :' : 'Kepada yth :' }}</p>
            <p class="m-0">{{ beritaAcara.meta?.kepada_nama || (isOtherTemplate ? '' : '_________________') }}</p>
            <p class="m-0 font-medium">{{ beritaAcara.meta?.kepada_jabatan || '' }}</p>
            <p class="m-0 font-medium">Di tempat,</p>
        </div>

        <div class="text-xs mb-3 leading-normal text-justify">
            <p>{{ beritaAcara.pengantar }}</p>
            <p v-if="!isSeizedGoods" class="mt-2">Dengan data sebagai berikut :</p>
        </div>

        <table v-if="isOtherTemplate" style="width:100%;table-layout:fixed;border-collapse:collapse;margin:0.75rem 0 1rem;font-family:Tahoma,sans-serif;font-size:11px;">
            <colgroup><col style="width:8%;" /><col style="width:42%;" /><col style="width:50%;" /></colgroup>
            <thead>
                <tr>
                    <th v-for="heading in ['No.', 'Uraian', 'Keterangan']" :key="heading" style="border:1px solid #000;background:#f3f4f6;padding:6px;text-align:center;">{{ heading }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(row, index) in templateRows" :key="index">
                    <td style="border:1px solid #000;padding:6px;text-align:center;">{{ index + 1 }}</td>
                    <td style="border:1px solid #000;padding:6px;">{{ row.uraian }}</td>
                    <td style="border:1px solid #000;padding:6px;">{{ row.keterangan }}</td>
                </tr>
            </tbody>
        </table>

        <table v-else-if="isAttendanceRevision" style="width:100%;table-layout:fixed;border-collapse:collapse;margin:0.75rem 0 1rem;font-family:Tahoma,sans-serif;font-size:11px;">
            <thead>
                <tr>
                    <th v-for="heading in ['No', 'Nama', 'NIK', 'Tanggal', 'Absensi IN', 'Absensi Out', 'Ket']" :key="heading" style="border:1px solid #000;background:#9dc3e6;padding:4px;text-align:center;">{{ heading }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(row, index) in templateRows" :key="index">
                    <td v-for="(value, columnIndex) in [index + 1, row.nama, row.nik, row.tanggal, row.absensi_in, row.absensi_out, row.ket]" :key="columnIndex" style="border:1px solid #000;padding:4px;text-align:center;">{{ value }}</td>
                </tr>
            </tbody>
        </table>

        <table v-else-if="isSeizedGoods" style="width:100%;table-layout:fixed;border-collapse:collapse;margin:0.75rem 0 0.25rem;font-family:Tahoma,sans-serif;font-size:11px;">
            <colgroup>
                <col style="width:6%;" />
                <col style="width:10%;" />
                <col style="width:20%;" />
                <col style="width:19%;" />
                <col style="width:28%;" />
                <col style="width:17%;" />
            </colgroup>
            <thead>
                <tr>
                    <th v-for="heading in ['No', 'Cabang', 'Nama Nasabah', 'No. Faktur', 'Barang', 'Nominal Pinjaman']" :key="heading" style="border:1px solid #000;padding:4px;text-align:center;">{{ heading }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(row, index) in templateRows" :key="index">
                    <td v-for="(value, columnIndex) in [index + 1, row.cabang, row.nama_nasabah, row.no_faktur, row.barang, row.nominal_pinjaman]" :key="columnIndex" style="border:1px solid #000;padding:4px;text-align:center;">{{ columnIndex === 5 ? formatCurrency(value) : value }}</td>
                </tr>
            </tbody>
        </table>

        <div v-if="isSeizedGoods" class="text-xs mb-4 leading-normal text-justify">
            <p class="m-0 font-bold">Kronologinya :</p>
            <p class="m-0 whitespace-pre-line">{{ beritaAcara.meta?.data?.kronologi }}</p>
        </div>

        <div v-else-if="!isAttendanceRevision && !isOtherTemplate" class="grid grid-cols-[200px_20px_1fr] text-xs gap-y-0.5 mb-4">
            <template v-for="(item, idx) in beritaAcara.rincian_data" :key="idx">
                <div>{{ item.label }}</div>
                <div class="text-center">:</div>
                <div>{{ item.value }}</div>
            </template>
        </div>

        <div v-if="beritaAcara.keterangan_tambahan && !isSeizedGoods" class="text-xs mb-3 leading-normal text-justify">
            <p class="whitespace-pre-line">{{ beritaAcara.keterangan_tambahan }}</p>
        </div>

        <div class="mt-2 text-xs leading-[1.5] text-black mb-10" style="font-family: Tahoma, sans-serif;">
            <p class="m-0">{{ beritaAcara.penutup }}</p>
        </div>

        <table style="width:100%;table-layout:fixed;border-collapse:collapse;margin-top:1rem;margin-bottom:1rem;font-family:Tahoma,sans-serif;font-size:11px;">
            <colgroup>
                <col v-for="(width, index) in signatureWidths" :key="'signature-column-' + index" :style="{ width }" />
            </colgroup>
            <tbody>
                <tr>
                    <td v-for="(slot, index) in signatureSlots" :key="'label-' + index" style="text-align:center;padding:0 4px 4px;">{{ index === 0 ? 'Dibuat oleh,' : 'Disetujui oleh,' }}</td>
                </tr>
                <tr>
                    <td v-for="(slot, index) in signatureSlots" :key="'signature-' + index" style="height:60px;text-align:center;vertical-align:bottom;padding:0 4px;">
                        <img v-if="(index === 0 || beritaAcara.status === 'approved') && slot.user?.digital_signature?.signature_image" :src="'/storage/' + slot.user.digital_signature.signature_image" :alt="'TTD ' + slot.role" @error="handleImgError" style="max-height:52px;max-width:100%;object-fit:contain;" />
                    </td>
                </tr>
                <tr>
                    <td v-for="(slot, index) in signatureSlots" :key="'name-' + index" style="text-align:center;padding:1px 4px 0;word-break:break-word;overflow-wrap:anywhere;">
                        <div :class="['ba-signatory-content', index === 2 && !isSeizedGoods ? 'ba-signatory-content-down' : 'ba-signatory-content-up']">
                            <div class="ba-signatory-name"><span>{{ slot.name }}</span></div>
                            <strong class="ba-signatory-role">
                                <template v-if="slot.role === 'Senior Executive Vice President Bisnis dan Operasional'">
                                    Senior Executive Vice President<br />
                                    Bisnis dan Operasional
                                </template>
                                <template v-else>{{ slot.role }}</template>
                            </strong>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="flex justify-end gap-2 mt-4 pt-2 mb-1">
            <div v-if="isSeizedGoods" class="w-12 h-12 border border-black"></div>
            <div v-else class="flex">
                <div class="w-7 h-7 border border-black"></div>
                <div class="w-7 h-7 border border-black border-l-0"></div>
            </div>
        </div>
        <div class="flex justify-between text-[10px] font-medium pt-1 text-gray-800">
            <span>{{ isSeizedGoods ? 'No. ' + beritaAcara.code : beritaAcara.code }}</span>
            <span>PT. PUSAT GADAI INDONESIA</span>
        </div>
    </div>
</template>

<style scoped>
.ba-document-header,
.ba-document-header * {
    font-family: Tahoma, Arial, sans-serif !important;
    font-size: 12px !important;
    line-height: 1 !important;
}

.ba-document-header h1 {
    font-size: 16px !important;
}

.ba-signatory-name {
    display: flex;
    height: 28px;
    align-items: flex-end;
    justify-content: center;
    font-size: 11px;
    line-height: 14px;
}

.ba-signatory-name span {
    display: block;
    width: 100%;
    overflow-wrap: anywhere;
}

.ba-signatory-role {
    display: block;
    margin-top: 1px;
    line-height: 1.2;
}

.ba-signatory-content-up {
    transform: translateY(-3px);
}

.ba-signatory-content-down {
    transform: translateY(4px);
}

@media print {
    @page {
        size: A4 portrait;
        margin: 0;
    }

    :global(body *) {
        visibility: hidden !important;
    }

    :global(#printable-ba),
    :global(#printable-ba *) {
        visibility: visible !important;
    }

    :global(#printable-ba) {
        position: fixed !important;
        inset: 0 auto auto 0 !important;
        width: 210mm !important;
        min-height: 297mm !important;
        padding: 30mm 20mm 20mm 25.4mm !important;
        margin: 0 !important;
        border: 0 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
    }
}
</style>