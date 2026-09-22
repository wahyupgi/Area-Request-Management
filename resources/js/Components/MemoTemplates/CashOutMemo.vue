<script setup>
const props = defineProps({
    memo: { type: Object, required: true },
    values: { type: Object, required: true },
    approverName: { type: String, default: '' },
});

const handleImgError = (event) => {
    event.target.style.display = 'none';
};
</script>

<template>
    <table class="w-full table-fixed border-collapse border border-black text-xs mb-5">
        <thead>
            <tr class="bg-[#b4c7e7] text-black">
                <th class="border border-black px-3 py-2 text-left font-bold w-[55%]">Uraian</th>
                <th class="border border-black px-3 py-2 text-left font-bold w-[45%] whitespace-nowrap">Nominal</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="border border-black px-3 py-2 font-bold">Nominal kas keluar</td>
                <td class="border border-black px-3 py-2 whitespace-nowrap" style="white-space: nowrap;">Rp. {{ Number(values.nominal_kas_keluar || 0).toLocaleString('id-ID') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="grid grid-cols-3 gap-3 items-start text-xs w-full max-w-3xl mx-auto px-1 mb-4">
        <div class="flex min-w-0 flex-col items-center text-center">
            <p class="mb-1">Dibuat oleh,</p>
            <div class="h-16 w-full flex items-end justify-center relative">
                <img v-if="memo.creator?.digital_signature?.signature_image" :src="'/storage/' + memo.creator.digital_signature.signature_image" alt="Tanda tangan pembuat" @error="handleImgError" class="h-14 object-contain absolute bottom-0" />
            </div>
            <p class="relative top-1 w-full whitespace-normal break-words [overflow-wrap:anywhere] mt-0 text-black leading-none">{{ memo.creator?.name }}</p>
            <p class="relative -top-1 w-full whitespace-normal break-words [overflow-wrap:anywhere] font-bold text-gray-800 leading-tight">Kepala Cabang</p>
        </div>
        <div class="flex min-w-0 flex-col items-center text-center">
            <p class="mb-1">Disetujui oleh,</p>
            <div class="h-16 w-full flex items-end justify-center relative">
                <img v-if="memo.status === 'approved' && memo.area_manager?.digital_signature?.signature_image" :src="'/storage/' + memo.area_manager.digital_signature.signature_image" alt="Tanda tangan AM" @error="handleImgError" class="h-14 object-contain absolute bottom-0" />
            </div>
            <p class="relative top-1 w-full whitespace-normal break-words [overflow-wrap:anywhere] mt-0 text-black leading-none">{{ memo.area_manager?.name || 'Bpk. Fathurrahman M' }}</p>
            <p class="relative -top-1 w-full whitespace-normal break-words [overflow-wrap:anywhere] font-bold text-gray-800 leading-tight">Manager</p>
        </div>
        <div class="flex min-w-0 flex-col items-center text-center">
            <p class="mb-1">Disetujui oleh,</p>
            <div class="h-16 w-full"></div>
            <p class="relative top-1 w-full whitespace-nowrap text-[10px] mt-0 text-black leading-none">{{ approverName || values.penyetuju_akhir || 'Bpk. Nugroho Samudra Sujatmiko, Ko' }}</p>
            <p class="relative -top-1 w-full font-bold text-gray-800 leading-tight">
                <span class="block whitespace-nowrap">Senior Executive Vice President</span>
                <span class="block whitespace-nowrap">Bisnis dan Operasional</span>
            </p>
        </div>
    </div>
</template>
