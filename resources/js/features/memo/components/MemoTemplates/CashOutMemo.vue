<script setup>
const props = defineProps({
    memo: { type: Object, required: true },
    values: { type: Object, required: true },
});

const handleImgError = (event) => {
    event.target.style.display = 'none';
};
</script>

<template>
    <table class="w-full border-collapse border border-black text-xs mb-5">
        <thead>
            <tr class="bg-[#b4c7e7] text-black">
                <th class="border border-black px-3 py-2 text-left font-bold w-1/2">Uraian</th>
                <th class="border border-black px-3 py-2 text-left font-bold">Nominal</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="border border-black px-3 py-2 font-bold">Nominal kas keluar</td>
                <td class="border border-black px-3 py-2">Rp. {{ Number(values.nominal_kas_keluar || 0).toLocaleString('id-ID') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="grid grid-cols-[0.8fr_0.8fr_1.4fr] gap-1 items-start text-xs w-full max-w-3xl mx-auto px-1 mb-4">
        <div class="flex min-w-0 flex-col items-center text-center">
            <p class="mb-1">Dibuat oleh,</p>
            <div class="h-16 w-full flex items-end justify-center relative">
                <img v-if="memo.creator?.digital_signature?.signature_image" :src="'/storage/' + memo.creator.digital_signature.signature_image" alt="Tanda tangan pembuat" @error="handleImgError" class="h-14 object-contain absolute bottom-0" />
            </div>
            <p class="w-full whitespace-normal break-words mt-1 text-black leading-tight">{{ memo.creator?.name }}</p>
            <p class="w-full whitespace-normal break-words [overflow-wrap:anywhere] font-bold text-gray-800 leading-tight">Kepala Cabang</p>
        </div>
        <div class="flex min-w-0 flex-col items-center text-center">
            <p class="mb-1 font-semibold">Disetujui oleh,</p>
            <div class="h-16 w-full flex items-end justify-center relative">
                <img v-if="memo.status === 'approved' && memo.area_manager?.digital_signature?.signature_image" :src="'/storage/' + memo.area_manager.digital_signature.signature_image" alt="Tanda tangan AM" @error="handleImgError" class="h-14 object-contain absolute bottom-0" />
            </div>
            <p class="w-full whitespace-normal break-words mt-1 text-black leading-tight">{{ memo.area_manager?.name || 'Bpk. Fathurrahman M' }}</p>
            <p class="w-full whitespace-normal break-words [overflow-wrap:anywhere] font-bold text-gray-800 leading-tight">Manager</p>
        </div>
        <div class="flex min-w-0 flex-col items-center text-center">
            <p class="mb-1 font-semibold">Disetujui oleh,</p>
            <div class="h-16 w-full"></div>
            <p class="w-full whitespace-nowrap mt-1 text-[8px] text-black leading-tight">{{ values.penyetuju_akhir || 'Bpk. Nugroho Samudra Sujatmiko, Ko' }}</p>
            <p class="w-full whitespace-nowrap font-bold text-[7px] text-gray-800 leading-tight">Senior Executive Vice President Bisnis dan Operasional</p>
        </div>
    </div>
</template>
