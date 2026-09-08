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
</script>

<template>
    <div id="printable-memo" class="bg-white text-black p-8 print:p-0 rounded-2xl print:rounded-none shadow-xl print:shadow-none font-sans max-w-3xl mx-auto print:max-w-none">

        <!-- Top Row: Logo on Left, Name Box on Right -->
        <div class="flex items-start justify-between mb-2">
            <div>
                <img src="/logo-pgi.jpg" alt="Logo PGI" class="w-16 h-16 object-contain" />
            </div>
            <div class="border border-gray-400 px-3 py-1 text-xs font-semibold">
                {{ memo.creator?.name }}
            </div>
        </div>

        <!-- Document Title: Centered -->
        <div class="text-center mb-4">
            <h1 class="text-xl font-bold uppercase underline tracking-wider mb-0.5">INTERNAL MEMO</h1>
            <p class="text-xs font-medium tracking-wide text-gray-800">{{ memo.code }}</p>
        </div>

        <!-- Date aligned right -->
        <div class="text-right mb-4 text-xs font-medium">
            {{ formatDate(memo.submitted_at || memo.created_at) }}
        </div>

        <!-- Details Block -->
        <div class="grid grid-cols-[110px_10px_1fr] text-xs gap-y-1 mb-3">
            <div class="font-medium">Direktorat</div><div>:</div><div>Operasional</div>
            <div class="font-medium">Divisi</div><div>:</div><div>{{ memo.template?.category || 'Support' }}</div>
            <div class="font-medium">Perihal</div><div>:</div><div class="font-semibold">{{ memo.title }}</div>
            <div class="font-medium">Lampiran</div><div>:</div><div>{{ memo.attachments?.length ? memo.attachments.length + ' Berkas' : '-' }}</div>
        </div>

        <!-- Horizontal divider line like in official memo -->
        <hr class="border-t-2 border-black my-3" />

        <!-- Kepada Yth -->
        <div class="text-xs mb-3 leading-relaxed">
            <p class="mb-0.5">Kepada yth :</p>
            <p class="font-semibold">{{ memo.area_manager?.name || 'Bpk. / Ibu.' }}</p>
            <p>Area Manager</p>
            <p>Di tempat,</p>
        </div>

        <!-- Intro Text -->
        <div class="text-xs mb-3 leading-relaxed text-justify">
            <p>
                Sehubungan dengan <strong>{{ memo.title }}</strong> di cabang <strong>{{ memo.branch?.name }}</strong>,
                maka saya ingin mengajukan permintaan tersebut dengan rincian sebagai berikut :
            </p>
        </div>

        <!-- Table of Fields -->
        <table class="w-full text-xs border-collapse border border-black mb-4">
            <thead>
                <tr class="bg-[#00b0f0]">
                    <th class="border border-black px-2 py-1.5 w-10 text-center text-white font-bold">No.</th>
                    <th class="border border-black px-3 py-1.5 text-left text-white font-bold">
                        {{ isItemBased ? (memo.field_values?.nama_barang ? 'Nama Barang' : 'Permintaan') : 'Permintaan' }}
                    </th>
                    <th v-if="isItemBased" class="border border-black px-2 py-1.5 text-center text-white font-bold w-24">Jumlah</th>
                    <th class="border border-black px-3 py-1.5 text-left text-white font-bold">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <!-- Item-based template (Inventaris / Pembayaran) -->
                <tr v-if="isItemBased">
                    <td class="border border-black px-2 py-1.5 text-center">1.</td>
                    <td class="border border-black px-3 py-1.5 font-medium">{{ memo.field_values?.nama_barang || memo.field_values?.permintaan || memo.title }}</td>
                    <td class="border border-black px-2 py-1.5 text-center">{{ formatValue(memo.field_values?.jumlah || memo.field_values?.nominal) }}</td>
                    <td class="border border-black px-3 py-1.5 whitespace-pre-wrap">{{ memo.field_values?.keterangan || memo.field_values?.deskripsi || '-' }}</td>
                </tr>
                <!-- Key-value template (Renovasi / SDM dll) -->
                <tr v-else v-for="(field, index) in memo.template?.field_schema" :key="field.key">
                    <td class="border border-black px-2 py-1.5 text-center">{{ index + 1 }}.</td>
                    <td class="border border-black px-3 py-1.5 font-medium">{{ field.label }}</td>
                    <td class="border border-black px-3 py-1.5 whitespace-pre-wrap">{{ formatFieldValue(field, memo.field_values?.[field.key]) }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Closing Text -->
        <div class="text-xs mb-6 leading-relaxed text-justify">
            Demikianlah internal memo ini dibuat agar dapat dipergunakan dengan sebagaimana-mestinya.
            Terima kasih atas perhatian dan kerjasamanya.
        </div>

        <!-- Signatures — 2 columns: KC (Dibuat Oleh) and AM (Diketahui Oleh) -->
        <div class="grid grid-cols-2 gap-8 text-center text-xs max-w-md mx-auto mb-4">
            <!-- KC -->
            <div class="flex flex-col items-center">
                <p class="mb-1 font-medium">Dibuat Oleh,</p>
                <div class="h-20 w-full flex items-end justify-center relative">
                    <!-- KC physical signature placeholder -->
                </div>
                <p class="font-bold underline mt-1">{{ memo.creator?.name }}</p>
                <p class="font-bold">Kepala Cabang</p>
            </div>

            <!-- AM -->
            <div class="flex flex-col items-center">
                <p class="mb-1 font-medium">Diketahui Oleh,</p>
                <div class="h-20 w-full flex items-end justify-center relative">
                    <!-- Show AM digital signature if approved and available -->
                    <template v-if="showAmSignature">
                        <!-- From approval record -->
                        <img
                            v-if="getApprovedSignature(memo)"
                            :src="'/storage/' + getApprovedSignature(memo).signature_image"
                            alt="Tanda Tangan AM"
                            class="h-16 object-contain absolute bottom-0"
                        />
                        <!-- Fallback: from area_manager's digitalSignature relation -->
                        <img
                            v-else-if="memo.area_manager?.digital_signature?.signature_image"
                            :src="'/storage/' + memo.area_manager.digital_signature.signature_image"
                            alt="Tanda Tangan AM"
                            class="h-16 object-contain absolute bottom-0"
                        />
                    </template>
                </div>
                <p class="font-bold underline mt-1">{{ memo.area_manager?.name || 'Area Manager' }}</p>
                <p class="font-bold">Area Manager</p>
            </div>
        </div>

        <!-- Small paraf / stamp boxes on bottom right like the official document -->
        <div class="flex justify-end gap-1 mb-2">
            <div class="w-7 h-7 border border-black"></div>
            <div class="w-7 h-7 border border-black"></div>
        </div>

        <!-- Footer -->
        <div class="flex justify-between text-[11px] pt-2 border-t border-gray-300">
            <span>{{ memo.code }}</span>
            <span>PT. PUSAT GADAI INDONESIA</span>
        </div>
    </div>
</template>
