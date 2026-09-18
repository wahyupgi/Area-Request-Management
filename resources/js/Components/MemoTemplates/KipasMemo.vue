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
        label: 'Dibuat oleh,',
    };

    const isApproved = props.memo.status === 'approved';
    const amSig = {
        displayName: props.memo.area_manager?.name || '',
        displayRole: 'Area Manager',
        signature: isApproved
            ? (getApprovedSignature()?.signature_image || props.memo.area_manager?.digital_signature?.signature_image)
            : null,
        label: 'Diketahui oleh,',
    };

    const slot1 = props.documentSignatures[0] ? {
        displayName: props.documentSignatures[0].name || creatorSig.displayName,
        displayRole: props.documentSignatures[0].role || creatorSig.displayRole,
        signature: props.documentSignatures[0].user?.digital_signature?.signature_image || creatorSig.signature,
        label: props.documentSignatures[0].label || 'Dibuat oleh,',
    } : creatorSig;

    const slot2 = props.documentSignatures[1] ? {
        displayName: props.documentSignatures[1].name || amSig.displayName,
        displayRole: props.documentSignatures[1].role || amSig.displayRole,
        signature: props.documentSignatures[1].user?.digital_signature?.signature_image || amSig.signature,
        label: props.documentSignatures[1].label || 'Diketahui oleh,',
    } : amSig;

    const slot3 = props.documentSignatures[2] ? {
        displayName: props.documentSignatures[2].name || '',
        displayRole: props.documentSignatures[2].role || '',
        signature: props.documentSignatures[2].user?.digital_signature?.signature_image || '',
        label: props.documentSignatures[2].label || 'Disetujui oleh',
    } : { displayName: '', displayRole: '', signature: '', label: 'Disetujui oleh,' };

    const slot4 = props.documentSignatures[3] ? {
        displayName: props.documentSignatures[3].name || '',
        displayRole: props.documentSignatures[3].role || '',
        signature: props.documentSignatures[3].user?.digital_signature?.signature_image || '',
        label: props.documentSignatures[3].label || 'Disetujui oleh,',
    } : { displayName: '', displayRole: '', signature: '', label: 'Disetujui oleh,' };

    return [slot1, slot2, slot3, slot4].filter((slot) => slot.displayName || slot.displayRole || slot.signature);
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
                <td v-if="row.isFirstRow" :rowspan="row.rowSpan" class="border border-black px-2 py-1.5 text-left align-middle">{{ row.cabang }}</td>
                <td v-if="row.isFirstRow" :rowspan="row.rowSpan" class="border border-black px-2 py-1.5 text-left align-middle">{{ row.permintaan }}</td>
                <td v-if="row.isFirstRow" :rowspan="row.rowSpan" class="border border-black px-2 py-1.5 text-left align-middle">{{ row.tujuan }}</td>
                <td class="border border-black px-2 py-1.5 text-left align-middle">{{ row.area }}</td>
                <td class="border border-black px-2 py-1.5 text-left align-middle">{{ row.qty }}</td>
                <td v-if="row.isFirstRow" :rowspan="row.rowSpan" class="border border-black px-2 py-1.5 text-left align-middle whitespace-pre-wrap">{{ row.keterangan }}</td>
            </tr>
        </tbody>
    </table>

    <div :class="['grid gap-0 items-start text-xs w-full mb-4', signatures.length === 2 ? 'grid-cols-2' : '', signatures.length === 3 ? 'grid-cols-3' : '', signatures.length >= 4 ? 'grid-cols-4' : '']">
        <div v-for="(slot, index) in signatures" :key="'kipas-signature-' + index" :class="['flex min-w-0 flex-col items-center text-center', index === 0 ? 'relative left-2' : '', index === signatures.length - 1 ? 'relative -left-2' : '']">
            <p class="mb-1">{{ slot.label }}</p>
            <div class="h-16 w-full flex items-end justify-center relative">
                <img v-if="slot.signature" :src="'/storage/' + slot.signature" :alt="slot.label || slot.displayName" @error="handleImgError" class="max-w-full h-14 object-contain absolute bottom-0" />
            </div>
            <p v-if="slot.displayName" class="w-full whitespace-normal break-words [overflow-wrap:anywhere] mt-1 leading-tight">{{ slot.displayName }}</p>
            <p v-if="slot.displayRole" class="w-full whitespace-nowrap font-bold text-[8px] text-gray-800 leading-tight">{{ slot.displayRole }}</p>
        </div>
    </div>
</template>
