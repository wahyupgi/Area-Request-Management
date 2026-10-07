<script setup>
import { computed } from 'vue';
import { signatureColumnFractions } from '@/composables/signatureLayout';

const props = defineProps({
    memo: { type: Object, required: true },
    items: { type: Array, required: true },
    documentSignatures: { type: Array, default: () => [] },
});

const getApprovedSignature = () => {
    const approval = props.memo.approvals?.find((item) => item.action === 'approved' && item.signature);
    return approval?.signature ?? null;
};

const isAreaManagerRole = (role) => ['Area Manager', 'Manager'].includes(String(role || '').trim());

const signatures = computed(() => {
    const documentSlots = props.documentSignatures || [];
    const configuredKc = documentSlots.find((slot) => slot.role === 'Kepala Cabang');
    const configuredAm = documentSlots.find((slot) => isAreaManagerRole(slot.role));
    const additionalSlots = documentSlots
        .filter((slot) => slot.role !== 'Kepala Cabang' && !isAreaManagerRole(slot.role))
        .slice(0, 2);

    const creatorSig = {
        displayName: props.memo.creator?.name || '',
        displayRole: 'Kepala Cabang',
        signature: props.memo.creator?.digital_signature?.signature_image,
        label: 'Dibuat Oleh,',
    };
    const isApproved = props.memo.status === 'approved';
    const amSig = {
        displayName: props.memo.area_manager?.name || '',
        displayRole: 'Manager',
        signature: isApproved
            ? (getApprovedSignature()?.signature_image || props.memo.area_manager?.digital_signature?.signature_image)
            : null,
        label: 'Diketahui Oleh,',
    };
    const normalizeSlot = (slot, fallback) => slot ? {
        displayName: slot.name || slot.user?.name || fallback.displayName,
        displayRole: slot.role || fallback.displayRole,
        signature: slot.user?.digital_signature?.signature_image || fallback.signature,
        label: slot.label || fallback.label,
    } : fallback;

    return [
        normalizeSlot(configuredKc, creatorSig),
        { ...normalizeSlot(configuredAm, amSig), label: 'Disetujui Oleh,' },
        ...additionalSlots.map((slot) => normalizeSlot(slot, {
            displayName: '',
            displayRole: '',
            signature: '',
            label: 'Disetujui Oleh,',
        })),
    ];
});

const signatureCount = computed(() => signatures.value.length);
const signatureGridStyle = computed(() => {
    const signatureSlots = signatures.value.slice(0, signatureCount.value);
    const fractions = signatureColumnFractions(signatureSlots, (slot) => slot.displayName);
    return {
        gridTemplateColumns: fractions
            ? fractions.map((fraction) => `${fraction}fr`).join(' ')
            : signatureCount.value >= 4
                ? '17% 21% 21% 41%'
                : `repeat(${Math.min(signatureCount.value, 4)}, minmax(0, 1fr))`,
    };
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

const roleLines = (role) => {
    const value = String(role || '').trim();
    const suffix = 'Bisnis dan Operasional';
    const suffixIndex = value.toLowerCase().indexOf(suffix.toLowerCase());

    if (suffixIndex > 0) {
        return [value.slice(0, suffixIndex).trim(), value.slice(suffixIndex).trim()];
    }

    return [value];
};
</script>

<template>
    <table class="w-full text-xs border-collapse border border-black mb-5">
        <thead>
            <tr class="bg-[#1f497d] text-white">
                <th rowspan="2" class="border border-black px-2 py-1.5 text-center font-bold align-middle w-[13%] !text-white">Cabang</th>
                <th rowspan="2" class="border border-black px-2 py-1.5 text-center font-bold align-middle w-[18%] !text-white">Permintaan</th>
                <th rowspan="2" class="border border-black px-2 py-1.5 text-center font-bold align-middle w-[16%] !text-white">Tujuan</th>
                <th colspan="2" class="border border-black px-2 py-1.5 text-center font-bold !text-white">QTY KIPAS YANG ADA DI CABANG</th>
                <th rowspan="2" class="border border-black px-2 py-1.5 text-center font-bold align-middle w-[21%] !text-white">Keterangan</th>
            </tr>
            <tr class="bg-[#1f497d] text-white">
                <th class="border border-black px-2 py-1.5 text-center font-bold w-[18%] !text-white">Area</th>
                <th class="border border-black px-2 py-1.5 text-center font-bold w-[14%] !text-white">Qty<br />(yang ada)</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="(row, index) in tableRows" :key="'kipas-row-' + index">
                <td v-if="row.isFirstRow" :rowspan="row.rowSpan" class="border border-black px-2 py-1.5 text-left align-middle">{{ row.cabang }}</td>
                <td v-if="row.isFirstRow" :rowspan="row.rowSpan" class="border border-black px-2 py-1.5 text-left align-middle">{{ row.permintaan }}</td>
                <td v-if="row.isFirstRow" :rowspan="row.rowSpan" class="border border-black px-2 py-1.5 text-left align-middle">{{ row.tujuan }}</td>
                <td class="border border-black px-2 py-1.5 text-left align-middle">{{ row.area }}</td>
                <td class="border border-black px-2 py-1.5 text-center align-middle">{{ row.qty }}</td>
                <td v-if="row.isFirstRow" :rowspan="row.rowSpan" class="border border-black px-2 py-1.5 text-left align-middle whitespace-pre-wrap">{{ row.keterangan }}</td>
            </tr>
        </tbody>
    </table>

    <div class="mt-2 text-[11px] leading-[1.5] text-black" style="font-family: Tahoma, sans-serif;">
        <p v-if="memo.field_values?.penutup" class="m-0 whitespace-pre-wrap">{{ memo.field_values.penutup }}</p>
        <template v-else>
            <p class="m-0">Demikianlah Internal Memo ini dibuat agar dapat dipergunakan dengan sebagaimana mestinya.</p>
            <p class="mt-1 m-0">Terima kasih atas perhatiannya.</p>
        </template>
    </div>

    <div class="mt-4 grid gap-2 items-start text-xs w-full mb-4" :style="signatureGridStyle">
        <div v-for="(slot, index) in signatures.slice(0, signatureCount)" :key="'kipas-signature-' + index" class="flex min-w-0 flex-col items-center text-center">
            <p class="mb-1">{{ slot.label }}</p>
            <div class="h-16 w-full flex items-end justify-center relative">
                <img v-if="slot.signature" :src="'/storage/' + slot.signature" :alt="slot.label || slot.displayName" @error="handleImgError" class="max-w-full h-14 object-contain absolute bottom-0" />
            </div>
            <p v-if="slot.displayName" class="relative top-2 w-full whitespace-nowrap mt-2 leading-none">{{ slot.displayName }}</p>
            <p v-if="slot.displayRole" class="relative -top-1 w-full font-bold text-gray-800 leading-tight">
                <span v-for="(line, roleIndex) in roleLines(slot.displayRole)" :key="roleIndex" class="block whitespace-nowrap">{{ line }}</span>
            </p>
        </div>
    </div>
</template>
