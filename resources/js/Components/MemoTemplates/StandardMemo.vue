<script setup>
const props = defineProps({
    memo: { type: Object, required: true },
    items: { type: Array, required: true },
    isItemBased: { type: Boolean, default: false },
    documentSignatures: { type: Array, default: () => [] },
    showAmSignature: { type: Boolean, default: false },
});

const formatFieldValue = (field, value) => {
    if (value === undefined || value === null || value === '') return '-';
    if ((field.type === 'number' || !isNaN(value)) && (field.key.includes('biaya') || field.key.includes('anggaran') || field.key.includes('nominal'))) {
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
</script>

<template>
    <table class="w-full text-xs border-collapse border border-black mb-5">
        <thead>
            <tr class="bg-[#0284c7] text-white">
                <th class="border border-black px-2.5 py-1.5 w-10 text-center font-bold">No.</th>
                <th class="border border-black px-3 py-1.5 text-left font-bold">
                    {{ isItemBased ? (memo.field_values?.items?.[0]?.nama_barang || memo.field_values?.nama_barang ? 'Nama Barang' : 'Permintaan') : 'Permintaan' }}
                </th>
                <th v-if="isItemBased" class="border border-black px-2.5 py-1.5 text-center font-bold w-24">Jumlah</th>
                <th class="border border-black px-3 py-1.5 text-left font-bold">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <template v-if="isItemBased">
                <tr v-for="(item, index) in items" :key="'item-' + index">
                    <td class="border border-black px-2.5 py-1.5 text-center font-medium">{{ index + 1 }}.</td>
                    <td class="border border-black px-3 py-1.5 font-medium">{{ item?.nama_barang || item?.permintaan || memo.title }}</td>
                    <td class="border border-black px-2.5 py-1.5 text-center font-medium">{{ formatValue(item?.jumlah || item?.nominal) }}</td>
                    <td class="border border-black px-3 py-1.5 whitespace-pre-wrap">{{ item?.keterangan || item?.deskripsi || '-' }}</td>
                </tr>
            </template>
            <template v-else>
                <template v-for="(item, itemIndex) in items" :key="itemIndex">
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

    <div v-if="documentSignatures.length" class="grid grid-cols-[0.8fr_0.8fr_1.4fr] justify-center gap-1 items-start text-xs w-full max-w-3xl mx-auto px-1 mb-4">
        <div v-for="(slot, index) in documentSignatures" :key="slot.name + slot.role" class="flex min-w-0 flex-col items-center text-center">
            <p class="mb-1">{{ slot.label || (slot.location === 'bottom_right' ? 'Paraf' : 'Disetujui Oleh,') }}</p>
            <div class="h-16 w-full flex items-end justify-center relative">
                <img v-if="slot.user?.digital_signature?.signature_image" :src="'/storage/' + slot.user.digital_signature.signature_image" :alt="slot.label" @error="handleImgError" class="h-14 object-contain absolute bottom-0" />
            </div>
            <p :class="['w-full mt-1 text-black leading-tight', index === 2 ? 'whitespace-nowrap text-[8px]' : 'whitespace-normal break-words']">{{ slot.name }}</p>
            <p v-if="index === 2" class="w-full font-bold text-[7px] text-gray-800 leading-tight">
                <span class="block">Senior Executive Vice President</span>
                <span class="block">Bisnis dan Operasional</span>
            </p>
            <p v-else class="w-full whitespace-normal break-words [overflow-wrap:anywhere] font-bold text-gray-800 leading-tight">{{ slot.role }}</p>
        </div>
    </div>

    <div v-else class="grid grid-cols-2 gap-6 items-start text-xs w-full max-w-lg mx-auto px-4 mb-4">
        <div class="flex min-w-0 flex-col items-center text-center">
            <p class="mb-1">Dibuat oleh,</p>
            <div class="h-16 w-full flex items-end justify-center relative">
                <img v-if="memo.creator?.digital_signature?.signature_image" :src="'/storage/' + memo.creator.digital_signature.signature_image" alt="Tanda Tangan KC" @error="handleImgError" class="h-14 object-contain absolute bottom-0" />
            </div>
            <p class="w-full whitespace-normal break-words mt-1 text-black leading-tight">{{ memo.creator?.name }}</p>
            <p class="w-full whitespace-normal break-words [overflow-wrap:anywhere] font-bold text-gray-800 leading-tight">Kepala Cabang</p>
        </div>
        <div class="flex min-w-0 flex-col items-center text-center">
            <p class="mb-1">Diketahui Oleh,</p>
            <div class="h-16 w-full flex items-end justify-center relative">
                <template v-if="showAmSignature">
                    <img v-if="getApprovedSignature()" :src="'/storage/' + getApprovedSignature().signature_image" alt="Tanda Tangan AM" @error="handleImgError" class="h-14 object-contain absolute bottom-0" />
                    <img v-else-if="memo.area_manager?.digital_signature?.signature_image" :src="'/storage/' + memo.area_manager.digital_signature.signature_image" alt="Tanda Tangan AM" @error="handleImgError" class="h-14 object-contain absolute bottom-0" />
                </template>
            </div>
            <p class="w-full whitespace-normal break-words mt-1 text-black leading-tight">{{ memo.area_manager?.name || 'Area Manager' }}</p>
            <p class="w-full whitespace-normal break-words [overflow-wrap:anywhere] font-bold text-gray-800 leading-tight">Area Manager</p>
        </div>
    </div>
</template>
