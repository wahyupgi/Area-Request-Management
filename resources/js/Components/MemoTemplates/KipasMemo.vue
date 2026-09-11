<script setup>
import { computed } from 'vue';

const props = defineProps({
    memo: { type: Object, required: true },
    items: { type: Array, required: true },
    documentSignatures: { type: Array, default: () => [] },
});

const getApprovedSignature = () => {
    const approval = props.memo.approvals?.find((item) => item.action === 'approved' && item.signature);
    return approval?.signature ?? null;
};

const signatures = computed(() => {
    const configured = props.documentSignatures.slice(0, 4).map((slot) => ({
        displayName: slot.name,
        displayRole: slot.role,
        signature: slot.user?.digital_signature?.signature_image,
        label: slot.label,
    }));

    if (configured.length) {
        while (configured.length < 4) {
            configured.push({ displayName: '', displayRole: '', signature: '', label: `Tanda tangan ${configured.length + 1}` });
        }
        return configured;
    }

    return [
        {
            displayName: props.memo.creator?.name || '',
            displayRole: 'Kepala Cabang',
            signature: props.memo.creator?.digital_signature?.signature_image,
            label: 'Tanda tangan KC',
        },
        {
            displayName: props.memo.area_manager?.name || '',
            displayRole: 'Area Manager',
            signature: getApprovedSignature()?.signature_image || props.memo.area_manager?.digital_signature?.signature_image,
            label: 'Tanda tangan AM',
        },
        { displayName: '', displayRole: '', signature: '', label: 'Tanda tangan 3' },
        { displayName: '', displayRole: '', signature: '', label: 'Tanda tangan 4' },
    ];
});

const handleImgError = (event) => {
    event.target.style.display = 'none';
};
</script>

<template>
    <table class="w-full text-xs border-collapse border border-black mb-5">
        <thead>
            <tr class="bg-[#b4c7e7] text-black">
                <th rowspan="2" class="border border-black px-2 py-1.5 text-center font-bold align-middle w-[13%]">Cabang</th>
                <th rowspan="2" class="border border-black px-2 py-1.5 text-center font-bold align-middle w-[18%]">Permintaan</th>
                <th rowspan="2" class="border border-black px-2 py-1.5 text-center font-bold align-middle w-[16%]">Tujuan</th>
                <th colspan="2" class="border border-black px-2 py-1.5 text-center font-bold">QTY KIPAS YANG ADA DI CABANG</th>
                <th rowspan="2" class="border border-black px-2 py-1.5 text-center font-bold align-middle w-[21%]">Keterangan</th>
            </tr>
            <tr class="bg-[#b4c7e7] text-black">
                <th class="border border-black px-2 py-1.5 text-center font-bold w-[18%]">Area</th>
                <th class="border border-black px-2 py-1.5 text-center font-bold w-[14%]">Qty<br />(yang ada)</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="(item, index) in items" :key="'kipas-' + index">
                <td class="border border-black px-2 py-1.5 align-top">{{ item?.cabang || '-' }}</td>
                <td class="border border-black px-2 py-1.5 align-top">{{ item?.permintaan || memo.title }}</td>
                <td class="border border-black px-2 py-1.5 align-top">{{ item?.tujuan || '-' }}</td>
                <td class="border border-black px-2 py-1.5 align-top">{{ item?.area || '-' }}</td>
                <td class="border border-black px-2 py-1.5 text-center align-top">{{ item?.qty_kipas_ada ?? '-' }}</td>
                <td class="border border-black px-2 py-1.5 align-top whitespace-pre-wrap">{{ item?.keterangan || '-' }}</td>
            </tr>
        </tbody>
    </table>

    <div class="grid grid-cols-4 gap-2 items-start text-xs w-full mb-4">
        <div v-for="(slot, index) in signatures" :key="'kipas-signature-' + index" class="flex min-w-0 flex-col items-center text-center">
            <p class="mb-1 font-semibold">{{ index === 0 ? 'Dibuat oleh,' : 'Disetujui oleh,' }}</p>
            <div class="h-16 w-full flex items-end justify-center relative">
                <img v-if="slot.signature" :src="'/storage/' + slot.signature" :alt="slot.label || slot.displayName" @error="handleImgError" class="max-w-full h-14 object-contain absolute bottom-0" />
            </div>
            <p v-if="slot.displayName" class="w-full whitespace-normal break-words [overflow-wrap:anywhere] font-bold underline mt-1 leading-tight">{{ slot.displayName }}</p>
            <p v-if="slot.displayRole" class="w-full whitespace-normal break-words [overflow-wrap:anywhere] font-bold text-gray-800 leading-tight">{{ slot.displayRole }}</p>
        </div>
    </div>
</template>
