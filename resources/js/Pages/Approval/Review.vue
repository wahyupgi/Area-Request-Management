<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MemoDocument from '@/Components/MemoDocument.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    memo: Object,
    signature: Object,
});

const showRejectModal = ref(false);
const showApproveConfirm = ref(false);

const approveForm = useForm({
    notes: '',
    signature_image: null,
});

const rejectForm = useForm({
    notes: '',
});

const handleApprove = () => {
    approveForm.post(route('approvals.approve', props.memo.id), {
        onSuccess: () => { showApproveConfirm.value = false; },
    });
};

const handleReject = () => {
    rejectForm.post(route('approvals.reject', props.memo.id), {
        onSuccess: () => { showRejectModal.value = false; },
    });
};
</script>

<template>
    <Head :title="'Review: ' + memo.title" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-bold text-white">Review Memo</h1>
                <div class="flex items-center gap-4">
                    <span class="text-xs bg-amber-500/20 text-amber-400 px-3 py-1 rounded-full font-semibold border border-amber-500/30">Menunggu Persetujuan</span>
                    <button onclick="window.print()" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white text-sm font-medium rounded-xl transition-colors flex items-center gap-2 print:hidden">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        Print
                    </button>
                </div>
            </div>
        </template>

        <div class="max-w-4xl mx-auto space-y-6 print:space-y-0">
            <!-- Memo Document Preview (print-ready) -->
            <MemoDocument :memo="memo" :show-am-signature="false" />

            <!-- Attachments (hidden from print) -->
            <div v-if="memo.attachments?.length > 0" class="bg-slate-800/50 border border-white/5 rounded-2xl p-6 print:hidden">
                <h3 class="text-lg font-semibold text-white mb-4">Lampiran ({{ memo.attachments.length }})</h3>
                <div class="space-y-2">
                    <a v-for="att in memo.attachments" :key="att.id" :href="'/storage/' + att.file_path" target="_blank" class="flex items-center gap-3 bg-slate-700/30 rounded-xl px-4 py-3 hover:bg-slate-700/50 transition-colors">
                        <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        <span class="text-sm text-slate-300">{{ att.original_name || att.file_path }}</span>
                    </a>
                </div>
            </div>

            <!-- Previous Approvals (hidden from print) -->
            <div v-if="memo.approvals?.length > 0" class="bg-slate-800/50 border border-white/5 rounded-2xl p-6 print:hidden">
                <h3 class="text-lg font-semibold text-white mb-4">Riwayat Sebelumnya</h3>
                <div class="space-y-3">
                    <div v-for="a in memo.approvals" :key="a.id" :class="[a.action === 'approved' ? 'border-l-emerald-500 bg-emerald-500/5' : 'border-l-red-500 bg-red-500/5', 'border-l-2 rounded-r-xl px-5 py-3']">
                        <span :class="[a.action === 'approved' ? 'text-emerald-400' : 'text-red-400', 'text-sm font-semibold uppercase']">{{ a.action }}</span>
                        <span class="text-sm text-slate-400 ml-2">oleh {{ a.approver?.name }}</span>
                        <p v-if="a.notes" class="text-sm text-slate-300 mt-1">{{ a.notes }}</p>
                    </div>
                </div>
            </div>

            <!-- Signature Info (hidden from print) -->
            <div class="bg-slate-800/50 border border-white/5 rounded-2xl p-6 print:hidden">
                <h3 class="text-lg font-semibold text-white mb-3">Tanda Tangan Digital Anda</h3>
                <div v-if="signature" class="flex items-center gap-4">
                    <img :src="'/storage/' + signature.signature_image" alt="Signature" class="h-20 object-contain rounded-lg bg-white/10 p-3" />
                    <div>
                        <p class="text-sm text-emerald-400 font-medium">✓ Tanda tangan terdaftar</p>
                        <p v-if="signature.certificate_no" class="text-xs text-slate-500 mt-1">Sertifikat: {{ signature.certificate_no }}</p>
                        <p class="text-xs text-slate-400 mt-1">Tanda tangan ini akan dibubuhkan pada dokumen memo saat Anda menyetujui.</p>
                    </div>
                </div>
                <div v-else class="text-sm text-amber-400">
                    ⚠ Anda belum mendaftarkan tanda tangan digital. Anda bisa upload tanda tangan saat approve, atau daftarkan di halaman
                    <a :href="route('signature.index')" class="underline hover:text-amber-300">Tanda Tangan</a>.
                </div>
            </div>

            <!-- Action Buttons (hidden from print) -->
            <div class="flex items-center gap-4 print:hidden">
                <button @click="showApproveConfirm = true" class="flex-1 flex items-center justify-center gap-2 px-6 py-4 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold rounded-2xl transition-all shadow-lg shadow-emerald-500/25">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Approve
                </button>
                <button @click="showRejectModal = true" class="flex-1 flex items-center justify-center gap-2 px-6 py-4 bg-red-600 hover:bg-red-500 text-white font-semibold rounded-2xl transition-all shadow-lg shadow-red-500/25">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Reject
                </button>
            </div>
        </div>

        <!-- Approve Confirm Modal -->
        <div v-if="showApproveConfirm" class="fixed inset-0 z-50 flex items-center justify-center print:hidden">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showApproveConfirm = false"></div>
            <div class="relative bg-slate-800 border border-white/10 rounded-2xl p-8 max-w-md w-full mx-4 shadow-2xl">
                <h3 class="text-xl font-bold text-white mb-2">Konfirmasi Approval</h3>
                <p class="text-sm text-slate-400 mb-6">Memo ini akan disetujui dan tanda tangan digital Anda akan dibubuhkan pada dokumen.</p>

                <div v-if="!signature" class="mb-4">
                    <label class="block text-sm font-medium text-slate-300 mb-2">Upload Tanda Tangan <span class="text-red-400">*</span></label>
                    <input type="file" @change="approveForm.signature_image = $event.target.files[0]" accept="image/*" class="text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-indigo-600 file:text-white hover:file:bg-indigo-500 file:cursor-pointer" />
                    <p class="text-xs text-slate-500 mt-1">Upload gambar tanda tangan Anda (PNG/JPG, maks 2MB)</p>
                    <p v-if="approveForm.errors.signature" class="text-red-400 text-sm mt-1">{{ approveForm.errors.signature }}</p>
                </div>

                <div v-else class="mb-4 flex items-center gap-3 bg-emerald-500/10 border border-emerald-500/20 rounded-xl px-4 py-3">
                    <img :src="'/storage/' + signature.signature_image" alt="TTD Anda" class="h-12 object-contain bg-white/10 rounded-lg p-1" />
                    <p class="text-sm text-emerald-400">✓ Tanda tangan Anda akan digunakan</p>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-300 mb-2">Catatan (opsional)</label>
                    <textarea v-model="approveForm.notes" rows="3" placeholder="Tambahkan catatan..." class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 resize-none"></textarea>
                </div>

                <div class="flex gap-3">
                    <button @click="showApproveConfirm = false" class="flex-1 px-4 py-3 bg-slate-700 hover:bg-slate-600 text-white text-sm font-medium rounded-xl transition-colors">Batal</button>
                    <button @click="handleApprove" :disabled="approveForm.processing" class="flex-1 px-4 py-3 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white text-sm font-medium rounded-xl transition-colors">
                        {{ approveForm.processing ? 'Menyetujui...' : 'Ya, Setujui' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Reject Modal -->
        <div v-if="showRejectModal" class="fixed inset-0 z-50 flex items-center justify-center print:hidden">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showRejectModal = false"></div>
            <div class="relative bg-slate-800 border border-white/10 rounded-2xl p-8 max-w-md w-full mx-4 shadow-2xl">
                <h3 class="text-xl font-bold text-white mb-2">Tolak Memo</h3>
                <p class="text-sm text-slate-400 mb-6">Berikan alasan penolakan agar KC dapat melakukan revisi.</p>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-300 mb-2">Alasan Penolakan <span class="text-red-400">*</span></label>
                    <textarea v-model="rejectForm.notes" rows="4" placeholder="Tuliskan alasan penolakan..." class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:border-red-500 focus:ring-1 focus:ring-red-500 resize-none"></textarea>
                    <p v-if="rejectForm.errors.notes" class="text-red-400 text-sm mt-1">{{ rejectForm.errors.notes }}</p>
                </div>

                <div class="flex gap-3">
                    <button @click="showRejectModal = false" class="flex-1 px-4 py-3 bg-slate-700 hover:bg-slate-600 text-white text-sm font-medium rounded-xl transition-colors">Batal</button>
                    <button @click="handleReject" :disabled="rejectForm.processing" class="flex-1 px-4 py-3 bg-red-600 hover:bg-red-500 disabled:opacity-50 text-white text-sm font-medium rounded-xl transition-colors">
                        {{ rejectForm.processing ? 'Menolak...' : 'Ya, Tolak' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Print Styles -->
        <style>
        @media print {
            body * {
                visibility: hidden;
            }
            #printable-memo, #printable-memo * {
                visibility: visible;
            }
            #printable-memo {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                max-width: none;
                box-shadow: none !important;
                border-radius: 0 !important;
                padding: 40px !important;
            }
            .print\:hidden {
                display: none !important;
            }
        }
        </style>
    </AuthenticatedLayout>
</template>
