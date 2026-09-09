<script setup>
import { computed } from 'vue';

/**
 * MemoDocument.vue
 * Reusable component that renders a memo in the formal "INTERNAL MEMO" paper format.
 * Used by both Show.vue (KC/AM view) and Review.vue (AM approval).
 *
 * Props:
 *   - memo: Object (the memo with template, branch, creator, areaManager, approvals, attachments loaded)
 *   - showAmSignature: Boolean — whether to show the AM's digital signature in the document
 */
const props = defineProps({
    memo: { type: Object, required: true },
    showAmSignature: { type: Boolean, default: false },
});

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const months = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];
    return `${days[date.getDay()]}, ${String(date.getDate()).padStart(2, '0')} ${months[date.getMonth()]} ${date.getFullYear()}`;
};

/**
 * Find the latest 'approved' approval that has a signature attached.
 */
const getApprovedSignature = (memo) => {
    if (!memo.approvals?.length) return null;
    const approved = memo.approvals.find(a => a.action === 'approved' && a.signature);
    return approved?.signature ?? null;
};

/**
 * Check if the template uses item-based fields (nama_barang/permintaan + jumlah/nominal)
 */
const isItemBased = computed(() => {
    const keys = props.memo.template?.field_schema?.map(f => f.key) || [];
    return (keys.includes('nama_barang') || keys.includes('permintaan')) && (keys.includes('jumlah') || keys.includes('nominal'));
});

const memoItems = computed(() => {
    const values = props.memo.field_values || {};
    return Array.isArray(values.items) ? values.items : [values];
});

const isKipas = computed(() => props.memo.template?.name === 'Pengajuan Inventori/Kipas');
const isCashOut = computed(() => props.memo.template?.name === 'FIN - Pemberitahuan Kas Keluar');
const cashOutValues = computed(() => memoItems.value[0] || {});

const configuredSignatures = computed(() => (props.memo.template?.signature_schema || [])
    .map((slot) => ({
        ...slot,
        name: slot.name || slot.label || slot.user?.name || 'Penandatangan',
        user: slot.user || props.memo.template?.signature_people?.[slot.user_id],
    }))
    .filter((slot) => slot.name && slot.role));

const documentSignatures = computed(() => configuredSignatures.value.filter((slot) => slot.location === 'document'));
const parafSignatures = computed(() => configuredSignatures.value.filter((slot) => slot.location === 'bottom_right'));

const formatFieldValue = (field, val) => {
    if (val === undefined || val === null || val === '') return '-';
    if ((field.type === 'number' || !isNaN(val)) && (field.key.includes('biaya') || field.key.includes('anggaran') || field.key.includes('nominal'))) {
        return 'Rp ' + Number(val).toLocaleString('id-ID');
    }
    return String(val);
};

const formatValue = (val) => {
    if (val === undefined || val === null || val === '') return '-';
    if (!isNaN(val) && String(val).length >= 4 && !isNaN(Number(val))) {
        return 'Rp ' + Number(val).toLocaleString('id-ID');
    }
    return String(val);
};

const handleImgError = (e) => {
    e.target.style.display = 'none';
};
</script>

<template>
  <div id="printable-memo" class="memo-document bg-white text-black p-[15mm] print:p-[15mm] rounded-xl print:rounded-none shadow-xl print:shadow-none font-sans w-full max-w-[210mm] min-h-[297mm] mx-auto print:max-w-full print:min-h-[297mm] text-xs leading-normal border border-slate-200 print:border-none transition-all">

        <!-- Top Header Bar: Logo on Left, Name Box on Right -->
        <div class="flex items-start justify-between mb-3">
            <div class="flex items-center gap-3">
                <img src="/logo-pgi.jpg" alt="Logo PGI" class="w-14 h-14 object-contain" />
            </div>
            <div class="border border-black px-3 py-1 text-xs font-bold tracking-tight bg-white">
                {{ memo.creator?.name }}
            </div>
        </div>

        <!-- Document Title: Centered -->
        <div class="text-center mb-5">
            <h1 class="text-lg font-extrabold uppercase underline tracking-widest mb-0.5">INTERNAL MEMO</h1>
            <p class="text-[11px] font-semibold tracking-wider text-gray-800">{{ memo.code }}</p>
        </div>

        <!-- Date aligned right -->
        <div class="text-right mb-4 text-xs font-semibold text-gray-900">
            {{ formatDate(memo.submitted_at || memo.created_at) }}
        </div>

        <!-- Details Block -->
        <div class="grid grid-cols-[120px_12px_1fr] text-xs gap-y-1 mb-3">
            <div class="font-bold text-gray-900">Direktorat</div><div>:</div><div>{{ isCashOut ? (cashOutValues.direktorat || 'Regional Branch Office') : 'Operasional' }}</div>
            <div class="font-bold text-gray-900">Divisi</div><div>:</div><div>{{ isCashOut ? (cashOutValues.divisi || 'Branch Leader') : (memo.template?.category || 'Support') }}</div>
            <div class="font-bold text-gray-900">Perihal</div><div>:</div><div class="font-bold text-black">{{ memo.title }}</div>
            <div class="font-bold text-gray-900">Lampiran</div><div>:</div><div>{{ isCashOut ? (cashOutValues.lampiran || '-') : (memo.attachments?.length ? memo.attachments.length + ' Berkas' : '-') }}</div>
        </div>

        <!-- Black Horizontal Divider Line -->
        <hr class="border-t-2 border-black my-3" />

        <!-- Kepada Yth -->
        <div class="text-xs mb-4 leading-normal">
            <p class="mb-0.5">Kepada Yth :</p>
            <p class="font-bold text-xs">{{ isCashOut ? (cashOutValues.penerima || 'Bpk. / Ibu.') : (memo.area_manager?.name || 'Bpk. / Ibu.') }}</p>
            <p class="font-medium">{{ isCashOut ? (cashOutValues.penerima_jabatan || 'Senior Executive Vice President Bisnis dan Operasional') : 'Area Manager' }}</p>
            <p class="font-medium">Di tempat,</p>
        </div>

        <!-- Intro Text -->
        <div class="text-xs mb-3 leading-normal text-justify">
            <p v-if="!isCashOut">
                Sehubungan dengan <strong>{{ memo.title }}</strong> di cabang <strong>{{ memo.branch?.name }}</strong>,
                maka saya ingin mengajukan permintaan tersebut dengan rincian sebagai berikut :
            </p>
            <p v-else>
                Sehubungan dengan adanya Berita Acara Pengajuan pengeluaran kas cabang <strong>{{ cashOutValues.cabang || memo.branch?.name }}</strong>
                atas instruksi finance dikarenakan {{ cashOutValues.alasan || 'adanya kebutuhan operasional cabang' }}.
                Dengan ini maka saya selaku kepala cabang ingin mengajukan kas keluar.
            </p>
            <p v-if="isCashOut" class="mt-2">Dengan data sebagai berikut :</p>
        </div>

        <!-- Table of Fields -->
        <div v-if="isCashOut" class="text-xs mb-5">
            <div class="grid grid-cols-[150px_12px_1fr] gap-y-1">
                <div class="font-bold">Nominal kas keluar</div><div>:</div><div>Rp. {{ Number(cashOutValues.nominal_kas_keluar || 0).toLocaleString('id-ID') }}</div>
            </div>
        </div>
        <table v-else class="w-full text-xs border-collapse border border-black mb-5">
            <thead>
                <tr v-if="isKipas" class="bg-[#0284c7] text-white">
                    <th class="border border-black px-2.5 py-1.5 w-10 text-center font-bold">No.</th>
                    <th class="border border-black px-3 py-1.5 text-left font-bold">Cabang</th>
                    <th class="border border-black px-3 py-1.5 text-left font-bold">Permintaan</th>
                    <th class="border border-black px-3 py-1.5 text-left font-bold">Tujuan</th>
                    <th class="border border-black px-3 py-1.5 text-left font-bold">Area</th>
                    <th class="border border-black px-2.5 py-1.5 text-center font-bold">Qty Kipas yang Ada</th>
                    <th class="border border-black px-3 py-1.5 text-left font-bold">Keterangan</th>
                </tr>
                <tr v-else class="bg-[#0284c7] text-white">
                    <th class="border border-black px-2.5 py-1.5 w-10 text-center font-bold">No.</th>
                    <th class="border border-black px-3 py-1.5 text-left font-bold">
                        {{ isItemBased ? (memo.field_values?.items?.[0]?.nama_barang || memo.field_values?.nama_barang ? 'Nama Barang' : 'Permintaan') : 'Permintaan' }}
                    </th>
                    <th v-if="isItemBased" class="border border-black px-2.5 py-1.5 text-center font-bold w-24">Jumlah</th>
                    <th class="border border-black px-3 py-1.5 text-left font-bold">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <template v-if="isKipas">
                    <tr v-for="(item, index) in memoItems" :key="'kipas-' + index">
                        <td class="border border-black px-2.5 py-1.5 text-center font-medium">{{ index + 1 }}.</td>
                        <td class="border border-black px-3 py-1.5">{{ item?.cabang || '-' }}</td>
                        <td class="border border-black px-3 py-1.5">{{ item?.permintaan || memo.title }}</td>
                        <td class="border border-black px-3 py-1.5">{{ item?.tujuan || '-' }}</td>
                        <td class="border border-black px-3 py-1.5">{{ item?.area || '-' }}</td>
                        <td class="border border-black px-2.5 py-1.5 text-center">{{ item?.qty_kipas_ada ?? '-' }}</td>
                        <td class="border border-black px-3 py-1.5 whitespace-pre-wrap">{{ item?.keterangan || '-' }}</td>
                    </tr>
                </template>
                <!-- Item-based template (Inventaris / Pembayaran) -->
                <template v-else-if="isItemBased">
                    <tr v-for="(item, index) in memoItems" :key="'item-' + index">
                        <td class="border border-black px-2.5 py-1.5 text-center font-medium">{{ index + 1 }}.</td>
                        <td class="border border-black px-3 py-1.5 font-medium">{{ item?.nama_barang || item?.permintaan || memo.title }}</td>
                        <td class="border border-black px-2.5 py-1.5 text-center font-medium">{{ formatValue(item?.jumlah || item?.nominal) }}</td>
                        <td class="border border-black px-3 py-1.5 whitespace-pre-wrap">{{ item?.keterangan || item?.deskripsi || '-' }}</td>
                    </tr>
                </template>
                <!-- Key-value template (Renovasi / SDM dll) -->
                <template v-else>
                    <template v-for="(item, itemIndex) in memoItems" :key="itemIndex">
                    <tr>
                        <td class="border border-black px-2.5 py-1.5 text-center font-medium" :rowspan="memo.template?.field_schema?.length">{{ itemIndex + 1 }}.</td>
                        <td class="border border-black px-3 py-1.5 font-bold">{{ memo.template?.field_schema?.[0]?.label }}</td>
                        <td class="border border-black px-3 py-1.5 whitespace-pre-wrap">{{ formatFieldValue(memo.template?.field_schema?.[0], item?.[memo.template?.field_schema?.[0]?.key]) }}</td>
                    </tr>
                    <tr v-for="field in memo.template?.field_schema?.slice(1)" :key="itemIndex + '-' + field.key">
                        <td class="border border-black px-3 py-1.5 font-bold">{{ field.label }}</td>
                        <td class="border border-black px-3 py-1.5 whitespace-pre-wrap">{{ formatFieldValue(field, item?.[field.key]) }}</td>
                    </tr>
                    </template>
                </template>
            </tbody>
        </table>

        <!-- Closing Text -->
        <div class="text-xs mb-6 leading-normal text-justify">
            {{ isCashOut ? 'Demikianlah berita acara ini dibuat agar dapat dipergunakan dengan sebagaimana mestinya.' : 'Demikianlah internal memo ini dibuat agar dapat dipergunakan dengan sebagaimana-mestinya.' }}
            <br />Terima kasih atas perhatian dan kerjasamanya.
        </div>

        <!-- Configured template signatures -->
        <div v-if="documentSignatures.length" class="flex flex-wrap justify-center gap-8 items-start text-xs max-w-3xl mx-auto px-4 mb-4">
            <div v-for="slot in documentSignatures" :key="slot.name + slot.role" class="flex flex-col items-center w-44 text-center">
                <p class="mb-1 font-semibold">{{ slot.location === 'bottom_right' ? 'Paraf' : 'Disetujui Oleh,' }}</p>
                <div class="h-16 w-full flex items-end justify-center relative">
                    <img v-if="slot.user?.digital_signature?.signature_image" :src="'/storage/' + slot.user.digital_signature.signature_image" :alt="slot.label" @error="handleImgError" class="h-14 object-contain absolute bottom-0" />
                </div>
                <p class="font-bold underline mt-1 text-black">{{ slot.name }}</p>
                <p class="font-bold text-gray-800">{{ slot.role }}</p>
            </div>
        </div>

        <!-- Default signatures for templates without configuration -->
        <div v-else-if="isCashOut" class="grid grid-cols-3 gap-3 items-start text-xs max-w-3xl mx-auto px-1 mb-4">
            <div class="flex flex-col items-center min-w-0 text-center">
                <p class="mb-1 font-semibold">Dibuat oleh,</p>
                <div class="h-16 w-full flex items-end justify-center relative">
                    <img v-if="memo.creator?.digital_signature?.signature_image" :src="'/storage/' + memo.creator.digital_signature.signature_image" alt="Tanda tangan pembuat" class="h-14 object-contain absolute bottom-0" />
                </div>
                <p class="font-bold underline mt-1 text-black">{{ memo.creator?.name }}</p>
                <p class="font-bold text-gray-800">Kepala Cabang</p>
            </div>
            <div class="flex flex-col items-center min-w-0 text-center">
                <p class="mb-1 font-semibold">Disetujui oleh,</p>
                <div class="h-16 w-full flex items-end justify-center relative">
                    <img v-if="memo.area_manager?.digital_signature?.signature_image" :src="'/storage/' + memo.area_manager.digital_signature.signature_image" alt="Tanda tangan AM" class="h-14 object-contain absolute bottom-0" />
                </div>
                <p class="font-bold underline mt-1 text-black">{{ memo.area_manager?.name || 'Bpk. Fathurrahman M' }}</p>
                <p class="font-bold text-gray-800">Manager</p>
            </div>
            <div class="flex flex-col items-center min-w-0 text-center -translate-x-2">
                <p class="mb-1 font-semibold">Disetujui oleh,</p>
                <div class="h-16 w-full"></div>
                <p class="font-bold underline mt-1 text-black">{{ cashOutValues.penyetuju_akhir || 'Bpk. Nugroho Samudra Sujatmiko, Ko' }}</p>
                <p class="font-bold text-gray-800">Senior Executive Vice President Bisnis dan Operasional</p>
            </div>
        </div>
        <div v-else class="flex justify-between items-start text-xs max-w-lg mx-auto px-4 mb-4">
            <!-- KC -->
            <div class="flex flex-col items-center w-44 text-center">
                <p class="mb-1 font-semibold">Dibuat Oleh,</p>
                <div class="h-16 w-full flex items-end justify-center relative">
                    <img
                        v-if="memo.creator?.digital_signature?.signature_image"
                        :src="'/storage/' + memo.creator.digital_signature.signature_image"
                        alt="Tanda Tangan KC"
                        @error="handleImgError"
                        class="h-14 object-contain absolute bottom-0"
                    />
                </div>
                <p class="font-bold underline mt-1 text-black">{{ memo.creator?.name }}</p>
                <p class="font-bold text-gray-800">Kepala Cabang</p>
            </div>

            <!-- AM -->
            <div class="flex flex-col items-center w-44 text-center">
                <p class="mb-1 font-semibold">Diketahui Oleh,</p>
                <div class="h-16 w-full flex items-end justify-center relative">
                    <!-- Show AM digital signature if approved and available -->
                    <template v-if="showAmSignature">
                        <!-- From approval record -->
                        <img
                            v-if="getApprovedSignature(memo)"
                            :src="'/storage/' + getApprovedSignature(memo).signature_image"
                            alt="Tanda Tangan AM"
                            @error="handleImgError"
                            class="h-14 object-contain absolute bottom-0"
                        />
                        <!-- Fallback: from area_manager's digitalSignature relation -->
                        <img
                            v-else-if="memo.area_manager?.digital_signature?.signature_image"
                            :src="'/storage/' + memo.area_manager.digital_signature.signature_image"
                            alt="Tanda Tangan AM"
                            @error="handleImgError"
                            class="h-14 object-contain absolute bottom-0"
                        />
                    </template>
                </div>
                <p class="font-bold underline mt-1 text-black">{{ memo.area_manager?.name || 'Area Manager' }}</p>
                <p class="font-bold text-gray-800">Area Manager</p>
            </div>
        </div>

        <!-- Small paraf / stamp boxes on bottom right like the official document -->
        <div class="flex justify-end gap-2 mb-2">
            <template v-if="parafSignatures.length">
                <div v-for="slot in parafSignatures" :key="'paraf-' + slot.name + slot.role" class="w-10 h-10">
                    <div class="w-10 h-10 border border-black flex items-center justify-center">
                        <img v-if="slot.user?.digital_signature?.signature_image" :src="'/storage/' + slot.user.digital_signature.signature_image" :alt="slot.label" @error="handleImgError" class="max-w-full max-h-full object-contain" />
                    </div>
                </div>
            </template>
            <template v-else>
                <div class="w-7 h-7 border border-black"></div>
                <div class="w-7 h-7 border border-black"></div>
            </template>
        </div>

        <!-- Footer -->
        <div class="flex justify-between text-[10px] font-medium pt-1.5 border-t border-black text-gray-800">
            <span>{{ memo.code }}</span>
            <span>PT. PUSAT GADAI INDONESIA</span>
        </div>
    </div>
</template>
