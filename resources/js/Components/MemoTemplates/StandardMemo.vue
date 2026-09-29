<script setup>
import { computed } from 'vue';

const props = defineProps({
    memo: { type: Object, required: true },
    items: { type: Array, required: true },
    isItemBased: { type: Boolean, default: false },
    documentSignatures: { type: Array, default: () => [] },
    showAmSignature: { type: Boolean, default: false },
});

const isKeringananJasa = computed(() => props.memo.template?.category === 'Keringanan Jasa');
const isPhoneDeathReliefMemo = computed(() => props.memo.template?.name === 'Pengajuan Keringanan Pelunasan Nasabah Handphone Meninggal Dunia');

const formatFieldValue = (field, value) => {
    if (value === undefined || value === null || value === '') return '-';
    if (field.type === 'date') {
        return new Date(`${value}T00:00:00`).toLocaleDateString('id-ID', {
            day: '2-digit', month: 'short', year: 'numeric',
        });
    }
    if (field.key.includes('persentase')) return `${value}%`;
    const isCurrency = /nominal|biaya|anggaran|harga|pinjaman|pelunasan|pokok|rupiah|\brp\b/i
        .test(`${field.key || ''} ${field.label || ''}`);
    if ((field.type === 'number' || !isNaN(value)) && isCurrency) {
        return 'Rp ' + Number(value).toLocaleString('id-ID');
    }
    return String(value);
};

const formatValue = (value) => {
    if (value === undefined || value === null || value === '') return '-';
    if (!isNaN(value) && String(value).length >= 4 && !isNaN(Number(value))) {
        return 'Rp ' + Number(value).toLocaleString('id-ID');
    }
    return String(value);
};

const getApprovedSignature = () => {
    const approval = props.memo.approvals?.find((item) => item.action === 'approved' && item.signature);
    return approval?.signature ?? null;
};

const handleImgError = (event) => {
    event.target.style.display = 'none';
};

const roleLines = (role) => {
    const value = String(role || '').trim();
    const suffix = 'Bisnis dan Operasional';
    const suffixIndex = value.toLowerCase().indexOf(suffix.toLowerCase());

    if (suffixIndex > 0) {
        return [value.slice(0, suffixIndex).trim(), value.slice(suffixIndex).trim()];
    }

    return [value];
};

const signatureGridStyle = (count) => ({
    gridTemplateColumns: count > 3
        ? '17% 21% 21% 41%'
        : `repeat(${Math.max(count, 1)}, minmax(0, 1fr))`,
});
</script>

<template>
    <table class="w-full text-xs border-collapse border border-black mb-5">
        <thead>
            <tr class="bg-[#0284c7] text-white">
                <th v-if="!isKeringananJasa" class="border border-black px-2.5 py-1.5 w-10 text-center font-bold">No.</th>
                <th class="border border-black px-3 py-1.5 text-left font-bold">
                    {{ isKeringananJasa ? 'Informasi Nasabah' : (isItemBased ? (memo.field_values?.items?.[0]?.nama_barang || memo.field_values?.nama_barang ? 'Nama Barang' : 'Permintaan') : 'Permintaan') }}
                </th>
                <th v-if="isItemBased" class="border border-black px-2.5 py-1.5 text-center font-bold w-32 min-w-[8rem] whitespace-nowrap">Jumlah</th>
                <th class="border border-black px-3 py-1.5 text-left font-bold">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <template v-if="isItemBased">
                <tr v-for="(item, index) in items" :key="'item-' + index">
                    <td v-if="!isKeringananJasa" class="border border-black px-2.5 py-1.5 text-center font-medium">{{ index + 1 }}.</td>
                    <td class="border border-black px-3 py-1.5 font-medium">{{ item?.nama_barang || item?.permintaan || memo.title }}</td>
                    <td class="border border-black px-2.5 py-1.5 text-center font-medium w-32 min-w-[8rem] whitespace-nowrap">{{ formatValue(item?.jumlah || item?.nominal) }}</td>
                    <td class="border border-black px-3 py-1.5 whitespace-pre-wrap">{{ item?.keterangan || item?.deskripsi || '-' }}</td>
                </tr>
            </template>
            <template v-else-if="!memo.template?.field_schema?.some((field) => field.type === 'table')">
                <template v-for="(item, itemIndex) in items" :key="itemIndex">
                    <tr>
                        <td v-if="!isKeringananJasa" class="border border-black px-2.5 py-1.5 text-center font-medium" :rowspan="memo.template?.field_schema?.length">{{ itemIndex + 1 }}.</td>
                        <td class="border border-black px-3 py-1.5 font-bold">{{ memo.template?.field_schema?.[0]?.label }}</td>
                        <td class="border border-black px-3 py-1.5 whitespace-pre-wrap">{{ formatFieldValue(memo.template?.field_schema?.[0], item?.[memo.template?.field_schema?.[0]?.key]) }}</td>
                    </tr>
                    <tr v-for="field in memo.template?.field_schema?.slice(1)" :key="itemIndex + '-' + field.key">
                        <td class="border border-black px-3 py-1.5 font-bold">{{ field.label }}</td>
                        <td class="border border-black px-3 py-1.5 whitespace-pre-wrap">{{ formatFieldValue(field, item?.[field.key]) }}</td>
                    </tr>
                </template>
            </template>
            <template v-else>
                <template v-for="(item, itemIndex) in items" :key="'structured-' + itemIndex">
                    <template v-for="(field, fieldIndex) in memo.template?.field_schema" :key="field.key">
                        <tr v-if="field.display === 'paragraph'">
                            <td :colspan="isKeringananJasa ? 2 : 3" class="border-0 px-1 py-2 whitespace-pre-wrap text-justify">{{ formatFieldValue(field, item?.[field.key]) }}</td>
                        </tr>
                        <tr v-else-if="field.type !== 'table'">
                            <td v-if="!isKeringananJasa" class="border border-black px-2.5 py-1.5 text-center font-medium">{{ fieldIndex === 0 ? `${itemIndex + 1}.` : '' }}</td>
                            <td class="border border-black px-3 py-1.5 font-bold">{{ field.label }}</td>
                            <td class="border border-black px-3 py-1.5 whitespace-pre-wrap">{{ formatFieldValue(field, item?.[field.key]) }}</td>
                        </tr>
                        <tr v-else>
                            <td v-if="!isKeringananJasa" class="border border-black px-2.5 py-1.5 text-center font-medium">{{ fieldIndex === 0 ? `${itemIndex + 1}.` : '' }}</td>
                            <td colspan="2" class="border border-black p-0">
                                <div class="px-3 py-1.5 font-bold">{{ field.label }}</div>
                                <table class="w-full border-collapse">
                                    <thead>
                                        <tr class="bg-[#1f497d] text-white">
                                            <th v-for="column in field.columns" :key="column.key" class="border border-black px-2 py-1.5 text-center font-bold">
                                                {{ column.label }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(row, rowIndex) in item?.[field.key] || []" :key="field.key + '-' + rowIndex">
                                            <td v-for="column in field.columns" :key="column.key" class="border border-black px-2 py-1.5 text-center whitespace-pre-wrap">
                                                {{ formatFieldValue(column, row?.[column.key]) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    </template>
                </template>
            </template>
        </tbody>
    </table>

    <div class="mt-2 text-[11px] leading-[1.5] text-black" style="font-family: Tahoma, sans-serif;">
        <p v-if="memo.field_values?.penutup" class="m-0 whitespace-pre-wrap">{{ memo.field_values.penutup }}</p>
        <template v-else>
            <p class="m-0">Demikianlah Internal Memo ini dibuat agar dapat dipergunakan dengan sebagaimana mestinya.</p>
            <p class="mt-1 m-0">Terima kasih atas perhatiannya.</p>
        </template>
    </div>

    <div v-if="documentSignatures.length" class="relative -left-1 mt-4 grid justify-start gap-6 items-start text-xs w-full max-w-none mx-0 px-0 mb-4 [break-inside:avoid]" :style="signatureGridStyle(documentSignatures.length)">
        <div
            v-for="(slot, index) in documentSignatures"
            :key="slot.name + slot.role"
            class="flex min-w-0 flex-col items-center text-center"
            :style="isPhoneDeathReliefMemo && documentSignatures.length === 3 && index > 0 ? { transform: `translateX(-${index * 8}px)` } : undefined"
        >
            <p class="mb-1">{{ slot.label || (slot.location === 'bottom_right' ? 'Paraf' : 'Disetujui Oleh,') }}</p>
            <div class="h-16 w-full flex items-end justify-center relative">
                <img v-if="slot.user?.digital_signature?.signature_image" :src="'/storage/' + slot.user.digital_signature.signature_image" :alt="slot.label" @error="handleImgError" class="h-14 object-contain absolute bottom-0" />
            </div>
            <p class="relative top-2 w-full whitespace-nowrap text-[10px] mt-2 text-black leading-none">{{ slot.name || slot.user?.name || memo.area_manager?.name || 'Bpk. Fathurrahman M' }}</p>
            <p class="relative -top-1 w-full font-bold text-gray-800 leading-tight">
                <span v-for="(line, roleIndex) in roleLines(slot.role === 'Area Manager' ? 'Manager' : slot.role)" :key="roleIndex" class="block whitespace-nowrap">{{ line }}</span>
            </p>
        </div>
    </div>

    <div v-else class="grid grid-cols-2 gap-6 items-start text-xs w-full max-w-lg mx-auto px-4 mb-4">
        <div class="flex min-w-0 flex-col items-center text-center">
            <p class="mb-1">Dibuat Oleh,</p>
            <div class="h-16 w-full flex items-end justify-center relative">
                <img v-if="memo.creator?.digital_signature?.signature_image" :src="'/storage/' + memo.creator.digital_signature.signature_image" alt="Tanda Tangan KC" @error="handleImgError" class="h-14 object-contain absolute bottom-0" />
            </div>
            <p class="relative top-2 w-full whitespace-normal break-words mt-2 text-black leading-none">{{ memo.creator?.name }}</p>
            <p class="relative -top-1 w-full whitespace-normal break-words [overflow-wrap:anywhere] font-bold text-gray-800 leading-tight">Kepala Cabang</p>
        </div>
        <div class="flex min-w-0 flex-col items-center text-center">
            <p class="mb-1">Diketahui Oleh,</p>
            <div class="h-16 w-full flex items-end justify-center relative">
                <template v-if="showAmSignature">
                    <img v-if="getApprovedSignature()" :src="'/storage/' + getApprovedSignature().signature_image" alt="Tanda Tangan AM" @error="handleImgError" class="h-14 object-contain absolute bottom-0" />
                    <img v-else-if="memo.area_manager?.digital_signature?.signature_image" :src="'/storage/' + memo.area_manager.digital_signature.signature_image" alt="Tanda Tangan AM" @error="handleImgError" class="h-14 object-contain absolute bottom-0" />
                </template>
            </div>
            <p class="relative top-2 w-full whitespace-normal break-words mt-2 text-black leading-none">{{ memo.area_manager?.name || 'Manager' }}</p>
            <p class="relative -top-1 w-full whitespace-normal break-words [overflow-wrap:anywhere] font-bold text-gray-800 leading-tight">Manager</p>
        </div>
    </div>
</template>
