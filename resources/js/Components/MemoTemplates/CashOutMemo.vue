<script setup>
import { computed } from 'vue';
import { managerDisplayName, signatureColumnFractions } from '@/composables/signatureLayout';

const props = defineProps({
    memo: { type: Object, required: true },
    values: { type: Object, required: true },
    signatureSlots: { type: Array, default: () => [] },
    approverName: { type: String, default: '' },
});

const signatureColumns = computed(() => {
    const configured = props.signatureSlots || [];
    const schemaAdditional = props.memo.template?.signature_schema?.slice(2, 4) || [];
    const hasConfiguredAdditional = configured.length > 2 || schemaAdditional.length > 0;
    const maxDocumentSlot = Math.max(2, ...configured.map((slot, index) => (
        (slot.location || 'document') === 'document' ? Number(slot.slotNumber || index + 1) : 0
    )));
    const additionalColumns = [];

    for (let slotIndex = 2; slotIndex < maxDocumentSlot; slotIndex += 1) {
        const slot = configured[slotIndex];
        additionalColumns.push(slot && (slot.location || 'document') === 'document'
            ? {
                label: slot.label || 'Disetujui oleh,',
                name: slot.name || slot.user?.name || '',
                role: slot.role || '',
                signature: slot.user?.digital_signature?.signature_image || '',
            }
            : { label: 'Disetujui oleh,', name: '', role: '', signature: '' });
    }

    if (additionalColumns.length === 0 && !hasConfiguredAdditional) {
        additionalColumns.push({
            label: 'Disetujui oleh,',
            name: props.approverName || props.values.penyetuju_akhir || props.memo.template?.document_defaults?.penyetuju_akhir || 'Bpk. Nugroho Samudra Sujatmiko, Ko',
            role: 'Senior Executive Vice President Bisnis dan Operasional',
            signature: '',
        });
        const signatureGridStyle = computed(() => {
            const fractions = signatureColumnFractions(signatureColumns.value);
            return {
                gridTemplateColumns: fractions
                    ? fractions.map((fraction) => `${fraction}fr`).join(' ')
                    : `repeat(${signatureColumns.value.length}, minmax(0, 1fr))`,
            };
        });
    }

    const columns = [
        {
            label: 'Dibuat oleh,',
            name: props.memo.creator?.name || '',
            role: 'Kepala Cabang',
            signature: props.memo.creator?.digital_signature?.signature_image || '',
        },
        {
            label: 'Disetujui oleh,',
            name: props.memo.area_manager?.name || '',
            role: 'Manager',
            signature: props.memo.status === 'approved' ? props.memo.area_manager?.digital_signature?.signature_image || '' : '',
        },
        ...additionalColumns,
    ];

    return columns.map((slot) => (slot.role === 'Manager'
        ? { ...slot, name: managerDisplayName(slot.name, columns.length) }
        : slot));
});

const handleImgError = (event) => {
    event.target.style.display = 'none';
};
</script>

<template>
    <table class="w-full table-fixed border-collapse border border-black text-xs mb-5">
        <thead>
            <tr class="bg-[#1f497d] text-white">
                <th class="border border-black px-3 py-2 text-left font-bold w-[55%] !text-white">Uraian</th>
                <th class="border border-black px-3 py-2 text-left font-bold w-[45%] whitespace-nowrap !text-white">Nominal</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="border border-black px-3 py-2 font-bold">Nominal kas keluar</td>
                <td class="border border-black px-3 py-2 whitespace-nowrap" style="white-space: nowrap;">Rp. {{ Number(values.nominal_kas_keluar || 0).toLocaleString('id-ID') }}</td>
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

    <div class="mt-4 grid gap-3 items-start text-xs w-full max-w-3xl mx-auto px-1 mb-4" :style="signatureGridStyle">
        <div v-for="(slot, index) in signatureColumns" :key="`cash-out-signature-${index}`" class="flex min-w-0 flex-col items-center text-center">
            <p class="mb-1">{{ slot.label }}</p>
            <div class="h-16 w-full flex items-end justify-center relative">
                <img v-if="slot.signature" :src="'/storage/' + slot.signature" :alt="slot.label" @error="handleImgError" class="h-14 object-contain absolute bottom-0" />
            </div>
            <p class="relative top-1 w-full whitespace-nowrap mt-0 text-black leading-none">{{ slot.name }}</p>
            <p class="relative -top-1 w-full whitespace-normal break-words [overflow-wrap:anywhere] font-bold text-gray-800 leading-tight">{{ slot.role }}</p>
        </div>
    </div>

    
</template>
