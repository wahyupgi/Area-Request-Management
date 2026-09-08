<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MemoDocument from '@/Components/MemoDocument.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
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

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};
</script>

<template>
    <Head :title="'Review Memo: ' + memo.code" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('approvals.pending')"
                        class="p-1.5 rounded-xl text-slate-400 hover:text-white hover:bg-white/5 border border-transparent hover:border-white/10 transition-colors"
                        title="Kembali ke Daftar Antrean"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    </Link>
                    <div>
                        <h1 class="text-base font-bold text-white tracking-tight">Review Memo Pengajuan</h1>
                        <div class="flex items-center gap-2 text-xs text-slate-400">
                            <span class="font-mono font-semibold text-indigo-400">{{ memo.code }}</span>
                            <span>•</span>
                            <span>{{ memo.branch?.name || 'Cabang' }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2.5">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-500/15 text-amber-300 border border-amber-500/30">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                        Menunggu Persetujuan
                    </span>
                    <button
                        onclick="window.print()"
                        class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-medium border border-white/10 transition-colors shadow-sm print:hidden"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        <span>Cetak Dokumen</span>
                    </button>
                </div>
            </div>
        </template>

        <!-- Main Workspace Grid: Responsive 2-Column Desktop, Stack on Mobile -->
        <div class="max-w-7xl mx-auto py-2">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <!-- Left: Document Preview Stage (lg:col-span-8) -->
                <div class="lg:col-span-8 w-full flex justify-center">
                    <div class="w-full max-w-[210mm] transition-all">
                        <MemoDocument :memo="memo" :show-am-signature="false" />
                    </div>
                </div>

                <!-- Right: Action & Inspector Sidebar (lg:col-span-4) -->
                <div class="lg:col-span-4 space-y-4 lg:sticky lg:top-20 print:hidden w-full">
                    
                    <!-- Card 1: Decision Action Panel -->
                    <div class="bg-slate-800/50 border border-white/10 rounded-2xl p-5 shadow-sm">
                        <div class="flex items-center justify-between mb-3 pb-3 border-b border-white/5">
                            <h3 class="text-xs font-bold text-white uppercase tracking-wider">Aksi Keputusan AM</h3>
                            <span class="text-[10px] font-semibold text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2 py-0.5 rounded-full">Perlu Tindakan</span>
                        </div>

                        <p class="text-xs text-slate-300 leading-relaxed mb-4">
                            Periksa kelayakan rincian permohonan memo. Keputusan Anda akan dibubuhkan tanda tangan digital dan tercatat pada riwayat audit sistem.
                        </p>

                        <div class="space-y-2.5">
                            <button
                                @click="showApproveConfirm = true"
                                class="w-full flex items-center justify-center gap-2 py-2.5 px-4 bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 text-white text-xs font-semibold rounded-xl transition-all shadow-md shadow-emerald-600/20"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 13l4 4L19 7"/></svg>
                                <span>Setujui & Tanda Tangani Memo</span>
                            </button>

                            <button
                                @click="showRejectModal = true"
                                class="w-full flex items-center justify-center gap-2 py-2.5 px-4 bg-rose-600/15 hover:bg-rose-600/25 border border-rose-500/30 text-rose-300 hover:text-white text-xs font-semibold rounded-xl transition-all"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                <span>Tolak / Minta Revisi Cabang</span>
                            </button>

                            <Link
                                :href="route('approvals.pending')"
                                class="w-full flex items-center justify-center gap-1.5 py-2 px-4 text-xs font-medium text-slate-400 hover:text-white transition-colors"
                            >
                                <span>&larr; Kembali ke Daftar Antrean</span>
                            </Link>
                        </div>
                    </div>

                    <!-- Card 2: Digital Signature Status -->
                    <div class="bg-slate-800/50 border border-white/10 rounded-2xl p-5 shadow-sm">
                        <div class="flex items-center justify-between mb-3 pb-3 border-b border-white/5">
                            <h3 class="text-xs font-bold text-white uppercase tracking-wider">Tanda Tangan Digital Anda</h3>
                            <Link :href="route('signature.index')" class="text-[11px] text-indigo-400 hover:text-indigo-300 font-medium">Ubah</Link>
                        </div>

                        <div v-if="signature" class="flex items-center gap-3">
                            <div class="h-14 w-24 rounded-xl bg-white p-1.5 flex items-center justify-center flex-shrink-0 shadow-inner border border-slate-200">
                                <img :src="'/storage/' + signature.signature_image" alt="TTD Anda" class="max-h-full object-contain" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1 text-emerald-400 text-xs font-semibold">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    <span>Siap Dibubuhkan</span>
                                </div>
                                <p class="text-[11px] text-slate-400 mt-0.5 truncate">Sertifikat: {{ signature.certificate_no || 'PGI-DS-AUTO' }}</p>
                            </div>
                        </div>

                        <div v-else class="text-xs text-amber-300/90 leading-relaxed bg-amber-500/10 border border-amber-500/20 rounded-xl p-3">
                            <p class="font-semibold mb-1">⚠ Tanda Tangan Belum Didaftarkan</p>
                            <p class="text-[11px] text-slate-300">Anda dapat mengunggah tanda tangan saat mengonfirmasi approval atau melalui menu Tanda Tangan.</p>
                        </div>
                    </div>

                    <!-- Card 3: Information Summary -->
                    <div class="bg-slate-800/50 border border-white/10 rounded-2xl p-5 shadow-sm">
                        <h3 class="text-xs font-bold text-white uppercase tracking-wider mb-3 pb-3 border-b border-white/5">Informasi Permohonan</h3>
                        
                        <div class="space-y-2.5 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400">Pemohon</span>
                                <span class="text-white font-medium">{{ memo.creator?.name || '-' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400">Unit Cabang</span>
                                <span class="text-white font-medium">{{ memo.branch?.name || '-' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400">Template</span>
                                <span class="text-indigo-400 font-medium truncate max-w-[180px] text-right">{{ memo.template?.name || '-' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400">Diajukan Pada</span>
                                <span class="text-slate-300">{{ formatDate(memo.submitted_at || memo.created_at) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Card 4: Attachments (if any) -->
                    <div v-if="memo.attachments?.length > 0" class="bg-slate-800/50 border border-white/10 rounded-2xl p-5 shadow-sm">
                        <h3 class="text-xs font-bold text-white uppercase tracking-wider mb-3 pb-3 border-b border-white/5">
                            Lampiran Dokumen ({{ memo.attachments.length }})
                        </h3>
                        <div class="space-y-2">
                            <a
                                v-for="att in memo.attachments"
                                :key="att.id"
                                :href="'/storage/' + att.file_path"
                                target="_blank"
                                class="flex items-center gap-2.5 p-2.5 rounded-xl bg-slate-900/40 hover:bg-slate-900/80 border border-white/5 hover:border-indigo-500/30 text-xs transition-colors group"
                            >
                                <svg class="w-4 h-4 text-indigo-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                <span class="text-slate-300 group-hover:text-white truncate flex-1">{{ att.original_name || att.file_path }}</span>
                                <svg class="w-3.5 h-3.5 text-slate-500 group-hover:text-indigo-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            </a>
                        </div>
                    </div>

                    <!-- Card 5: Previous Approvals (if any) -->
                    <div v-if="memo.approvals?.length > 0" class="bg-slate-800/50 border border-white/10 rounded-2xl p-5 shadow-sm">
                        <h3 class="text-xs font-bold text-white uppercase tracking-wider mb-3 pb-3 border-b border-white/5">Riwayat Review Sebelumnya</h3>
                        <div class="space-y-2.5">
                            <div v-for="a in memo.approvals" :key="a.id" class="p-3 rounded-xl bg-slate-900/40 border border-white/5 text-xs">
                                <div class="flex items-center justify-between mb-1">
                                    <span :class="[a.action === 'approved' ? 'text-emerald-400' : 'text-rose-400', 'font-semibold uppercase text-[10px]']">{{ a.action }}</span>
                                    <span class="text-[10px] text-slate-500">{{ formatDate(a.created_at) }}</span>
                                </div>
                                <p v-if="a.notes" class="text-slate-300 text-[11px] mt-1">{{ a.notes }}</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Approve Confirm Modal -->
        <div v-if="showApproveConfirm" class="fixed inset-0 z-50 flex items-center justify-center print:hidden">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showApproveConfirm = false"></div>
            <div class="relative bg-slate-800 border border-white/10 rounded-2xl p-6 max-w-md w-full mx-4 shadow-2xl">
                <h3 class="text-base font-bold text-white mb-1.5">Konfirmasi Persetujuan Memo</h3>
                <p class="text-xs text-slate-400 mb-4">Memo ini akan disetujui dan tanda tangan digital Anda akan dibubuhkan pada dokumen resmi.</p>

                <div v-if="!signature" class="mb-4">
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">Upload Tanda Tangan <span class="text-red-400">*</span></label>
                    <input type="file" @change="approveForm.signature_image = $event.target.files[0]" accept="image/*" class="text-xs text-slate-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-medium file:bg-indigo-600 file:text-white hover:file:bg-indigo-500 file:cursor-pointer" />
                    <p class="text-[11px] text-slate-500 mt-1">Upload gambar tanda tangan Anda (PNG/JPG, maks 2MB)</p>
                    <p v-if="approveForm.errors.signature" class="text-red-400 text-xs mt-1">{{ approveForm.errors.signature }}</p>
                </div>

                <div v-else class="mb-4 flex items-center gap-3 bg-emerald-500/10 border border-emerald-500/20 rounded-xl px-3.5 py-2.5">
                    <img :src="'/storage/' + signature.signature_image" alt="TTD Anda" class="h-10 object-contain bg-white/10 rounded-lg p-1" />
                    <p class="text-xs text-emerald-400 font-medium">✓ Tanda tangan terdaftar akan digunakan</p>
                </div>

                <div class="mb-5">
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">Catatan Persetujuan (opsional)</label>
                    <textarea v-model="approveForm.notes" rows="3" placeholder="Tambahkan catatan jika diperlukan..." class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-3.5 py-2 text-white text-xs focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 resize-none"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2.5">
                    <button @click="showApproveConfirm = false" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white text-xs font-medium rounded-xl transition-colors">Batal</button>
                    <button @click="handleApprove" :disabled="approveForm.processing" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white text-xs font-semibold rounded-xl transition-colors">
                        {{ approveForm.processing ? 'Menyetujui...' : 'Ya, Setujui Memo' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Reject Modal -->
        <div v-if="showRejectModal" class="fixed inset-0 z-50 flex items-center justify-center print:hidden">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showRejectModal = false"></div>
            <div class="relative bg-slate-800 border border-white/10 rounded-2xl p-6 max-w-md w-full mx-4 shadow-2xl">
                <h3 class="text-base font-bold text-white mb-1.5">Tolak Memo Pengajuan</h3>
                <p class="text-xs text-slate-400 mb-4">Berikan alasan penolakan agar Kepala Cabang dapat melakukan revisi permohonan.</p>

                <div class="mb-5">
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">Alasan Penolakan <span class="text-red-400">*</span></label>
                    <textarea v-model="rejectForm.notes" rows="4" placeholder="Tuliskan alasan penolakan secara jelas..." class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-3.5 py-2 text-white text-xs focus:border-rose-500 focus:ring-1 focus:ring-rose-500 resize-none"></textarea>
                    <p v-if="rejectForm.errors.notes" class="text-rose-400 text-xs mt-1">{{ rejectForm.errors.notes }}</p>
                </div>

                <div class="flex items-center justify-end gap-2.5">
                    <button @click="showRejectModal = false" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white text-xs font-medium rounded-xl transition-colors">Batal</button>
                    <button @click="handleReject" :disabled="rejectForm.processing" class="px-4 py-2 bg-rose-600 hover:bg-rose-500 disabled:opacity-50 text-white text-xs font-semibold rounded-xl transition-colors">
                        {{ rejectForm.processing ? 'Menolak...' : 'Ya, Tolak Memo' }}
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

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
