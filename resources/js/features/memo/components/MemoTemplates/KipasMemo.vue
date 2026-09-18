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
    const creatorSig = {
        displayName: props.memo.creator?.name || '',
        displayRole: 'Kepala Cabang',
        signature: props.memo.creator?.digital_signature?.signature_image,
        label: 'Tanda tangan KC',
    };

    const isApproved = props.memo.status === 'approved';
    const amSig = {
        displayName: props.memo.area_manager?.name || '',
        displayRole: 'Area Manager',
        // Only show AM signature after memo is approved
        signature: isApproved
            ? (getApprovedSignature()?.signature_image || props.memo.area_manager?.digital_signature?.signature_image)
            : null,
        label: 'Tanda tangan AM',
    };

    const slot1 = props.documentSignatures[0] ? {
        displayName: props.documentSignatures[0].name || creatorSig.displayName,
        displayRole: props.documentSignatures[0].role || creatorSig.displayRole,
        signature: props.documentSignatures[0].signature || creatorSig.signature,
        label: props.documentSignatures[0].label || 'Dibuat oleh',
    } : creatorSig;

    const slot2 = props.documentSignatures[1] ? {
        displayName: props.documentSignatures[1].name || amSig.displayName,
        displayRole: props.documentSignatures[1].role || amSig.displayRole,
        signature: props.documentSignatures[1].signature || amSig.signature,
        label: props.documentSignatures[1].label || 'Disetujui oleh',
    } : amSig;

    const primary = [slot1, slot2].filter((slot) => {
        if (!slot) return false;
        return slot.displayName || slot.displayRole || slot.signature || slot.label;
    });

    const secondary = [props.documentSignatures[2], props.documentSignatures[3]]
        .filter((slot) => slot && (slot.name || slot.role || slot.user?.digital_signature?.signature_image || slot.signature))
        .map((slot) => ({
            displayName: slot.name || '',
            displayRole: slot.role || '',
            signature: slot.signature || slot.user?.digital_signature?.signature_image || '',
            label: slot.label || 'Disetujui oleh',
        }));

    return { primary, secondary };
});

// Flatten items into table rows with rowspan support for area sub-rows
const tableRows = computed(() => {
    const rows = [];
    props.items.forEach((item) => {
        const areaRows = Array.isArray(item.area_penempatan)
            ? item.area_penempatan
            : (Array.isArray(item.areas) ? item.areas : []);

        if (areaRows.length > 0) {
            areaRows.forEach((areaRow, aIndex) => {
                rows.push({
                    cabang: item.cabang || '-',
                    permintaan: item.permintaan || props.memo.title || '-',
                    tujuan: item.tujuan || '-',
                    area: areaRow.area || '-',
                    qty: areaRow.qty ?? '-',
                    keterangan: item.keterangan || '-',
                    isFirstRow: aIndex === 0,
                    rowSpan: areaRows.length,
                });
            });
        } else {
            rows.push({
                cabang: item.cabang || '-',
                permintaan: item.permintaan || props.memo.title || '-',
                tujuan: item.tujuan || '-',
                area: item.area || '-',
                qty: item.qty_kipas_ada ?? '-',
                keterangan: item.keterangan || '-',
                isFirstRow: true,
                rowSpan: 1,
            });
        }
    });
    return rows;
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
            <tr v-for="(row, index) in tableRows" :key="'kipas-row-' + index">
                <td v-if="row.isFirstRow" :rowspan="row.rowSpan" class="border border-black px-2 py-1.5 align-top">{{ row.cabang }}</td>
                <td v-if="row.isFirstRow" :rowspan="row.rowSpan" class="border border-black px-2 py-1.5 align-top">{{ row.permintaan }}</td>
                <td v-if="row.isFirstRow" :rowspan="row.rowSpan" class="border border-black px-2 py-1.5 align-top">{{ row.tujuan }}</td>
                <td class="border border-black px-2 py-1.5 align-top">{{ row.area }}</td>
                <td class="border border-black px-2 py-1.5 text-center align-top">{{ row.qty }}</td>
                <td v-if="row.isFirstRow" :rowspan="row.rowSpan" class="border border-black px-2 py-1.5 align-top whitespace-pre-wrap">{{ row.keterangan }}</td>
            </tr>
        </tbody>
    </table>

    <div class="mb-4">
        <div class="grid grid-cols-2 gap-6 items-start text-xs w-full">
            <div v-for="(slot, index) in signatures.primary" :key="'kipas-signature-' + index" class="flex min-w-0 flex-col items-center text-center">
                <p class="mb-1">{{ slot.label }}</p>
                <div class="h-16 w-full flex items-end justify-center relative">
                    <img v-if="slot.signature" :src="'/storage/' + slot.signature" :alt="slot.label || slot.displayName" @error="handleImgError" class="max-w-full h-14 object-contain absolute bottom-0" />
                </div>
                <p v-if="slot.displayName" class="w-full whitespace-normal break-words [overflow-wrap:anywhere] mt-1 leading-tight">{{ slot.displayName }}</p>
                <p v-if="slot.displayRole" class="w-full whitespace-normal break-words [overflow-wrap:anywhere] font-bold text-gray-800 leading-tight">{{ slot.displayRole }}</p>
            </div>
        </div>

        <div v-if="signatures.secondary.length" class="mt-4 grid grid-cols-2 gap-3 items-start text-xs w-full">
            <div v-for="(slot, index) in signatures.secondary" :key="'kipas-secondary-signature-' + index" class="flex min-w-0 flex-col items-center text-center">
                <p class="mb-1">{{ slot.label }}</p>
                <div class="h-16 w-full flex items-end justify-center relative">
                    <img v-if="slot.signature" :src="'/storage/' + slot.signature" :alt="slot.label || slot.displayName" @error="handleImgError" class="max-w-full h-14 object-contain absolute bottom-0" />
                </div>
                <p v-if="slot.displayName" class="w-full whitespace-normal break-words [overflow-wrap:anywhere] mt-1 leading-tight">{{ slot.displayName }}</p>
                <p v-if="slot.displayRole" class="w-full whitespace-normal break-words [overflow-wrap:anywhere] font-bold text-gray-800 leading-tight">{{ slot.displayRole }}</p>
            </div>
        </div>
    </div>
</template>
