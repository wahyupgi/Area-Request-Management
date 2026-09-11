<script setup>
import { computed } from 'vue';
import KipasMemo from '@/Components/MemoTemplates/KipasMemo.vue';
import CashOutMemo from '@/Components/MemoTemplates/CashOutMemo.vue';
import StandardMemo from '@/Components/MemoTemplates/StandardMemo.vue';

const props = defineProps({
    memo: { type: Object, required: true },
    showAmSignature: { type: Boolean, default: false },
});

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return `${days[date.getDay()]}, ${String(date.getDate()).padStart(2, '0')} ${months[date.getMonth()]} ${date.getFullYear()}`;
};

const memoItems = computed(() => {
    const values = props.memo.field_values || {};
    return Array.isArray(values.items) ? values.items : [values];
});

const isItemBased = computed(() => {
    const keys = props.memo.template?.field_schema?.map((field) => field.key) || [];
    return (keys.includes('nama_barang') || keys.includes('permintaan')) && (keys.includes('jumlah') || keys.includes('nominal'));
});

const isKipas = computed(() => props.memo.template?.name === 'Pengajuan Inventori/Kipas');
const isCashOut = computed(() => props.memo.template?.name === 'FIN - Pemberitahuan Kas Keluar');
const cashOutValues = computed(() => memoItems.value[0] || {});
const introText = computed(() => props.memo.field_values?.pengantar || `Sehubungan dengan pengajuan ${props.memo.title || 'memo ini'}, saya ingin mengajukan permintaan dengan rincian sebagai berikut:`);

const configuredSignatures = computed(() => (props.memo.template?.signature_schema || [])
    .map((slot) => ({
        ...slot,
        name: slot.name || slot.label || slot.user?.name || 'Penandatangan',
        user: slot.user || props.memo.template?.signature_people?.[slot.user_id],
    }))
    .filter((slot) => slot.name && slot.role));

const documentSignatures = computed(() => configuredSignatures.value.filter((slot) => slot.location === 'document'));
const parafSignatures = computed(() => configuredSignatures.value.filter((slot) => slot.location === 'bottom_right'));

const handleImgError = (event) => {
    event.target.style.display = 'none';
};
</script>

<template>
    <div id="printable-memo" class="memo-document bg-white text-black pt-[3cm] px-[2.54cm] pb-[15mm] print:pt-[3cm] print:px-[2.54cm] print:pb-[15mm] rounded-xl print:rounded-none shadow-xl print:shadow-none font-sans w-full max-w-[210mm] min-h-[297mm] mx-auto print:max-w-full print:min-h-[297mm] flex flex-col text-xs leading-normal border border-slate-200 print:border-none transition-all">
        <div class="flex items-start justify-between mb-3">
            <div class="flex items-center gap-3">
                <img src="/logo-pgi.jpg" alt="Logo PGI" class="w-14 h-14 object-contain" />
            </div>
            <div class="border border-black px-3 py-1 text-xs font-bold tracking-tight bg-white">
                {{ memo.creator?.name }}
            </div>
        </div>

        <div class="text-center mb-5">
            <h1 class="text-lg font-extrabold uppercase underline tracking-widest mb-0.5">INTERNAL MEMO</h1>
            <p class="text-[11px] font-semibold tracking-wider text-gray-800">{{ memo.code }}</p>
        </div>

        <div class="text-right mb-4 text-xs font-semibold text-gray-900">
            {{ formatDate(memo.submitted_at || memo.created_at) }}
        </div>

        <div class="grid grid-cols-[120px_12px_1fr] text-xs gap-y-1 mb-3">
            <div class="font-bold text-gray-900">Direktorat</div><div>:</div><div>{{ isCashOut ? (cashOutValues.direktorat || 'Regional Branch Office') : 'Operasional' }}</div>
            <div class="font-bold text-gray-900">Divisi</div><div>:</div><div>{{ isCashOut ? (cashOutValues.divisi || 'Branch Leader') : (memo.template?.category || 'Support') }}</div>
            <div class="font-bold text-gray-900">Perihal</div><div>:</div><div class="font-bold text-black">{{ memo.title }}</div>
            <div class="font-bold text-gray-900">Lampiran</div><div>:</div><div>{{ isCashOut ? (cashOutValues.lampiran || '-') : (memo.attachments?.length ? memo.attachments.length + ' Berkas' : '-') }}</div>
        </div>

        <hr class="border-t-2 border-black my-3" />

        <div class="text-xs mb-4 leading-normal">
            <p class="mb-0.5">Kepada Yth :</p>
            <p class="font-bold text-xs">{{ isCashOut ? (cashOutValues.penerima || 'Bpk. / Ibu.') : (memo.area_manager?.name || 'Bpk. / Ibu.') }}</p>
            <p class="font-medium">{{ isCashOut ? (cashOutValues.penerima_jabatan || 'Senior Executive Vice President Bisnis dan Operasional') : 'Area Manager' }}</p>
            <p class="font-medium">Di tempat,</p>
        </div>

        <div class="text-xs mb-3 leading-normal text-justify">
            <p>{{ introText }}</p>
            <p v-if="isCashOut" class="mt-2">Dengan data sebagai berikut :</p>
        </div>

        <KipasMemo v-if="isKipas" :memo="memo" :items="memoItems" :document-signatures="documentSignatures" />
        <CashOutMemo v-else-if="isCashOut" :memo="memo" :values="cashOutValues" />
        <StandardMemo v-else :memo="memo" :items="memoItems" :is-item-based="isItemBased" :document-signatures="documentSignatures" :show-am-signature="showAmSignature" />

        <div class="flex justify-end gap-2 mt-8 pt-4 mb-2">
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

        <div class="flex justify-between text-[10px] font-medium pt-1.5 text-gray-800">
            <span>{{ memo.code }}</span>
            <span>PT. PUSAT GADAI INDONESIA</span>
        </div>
    </div>
</template>
