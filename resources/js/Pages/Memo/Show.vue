<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MemoDocument from '@/Components/MemoDocument.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

const props = defineProps({ memo: Object });
const user = usePage().props.auth.user;

const statusConfig = {
    draft: { 
        label: 'Draft', 
        badgeClass: 'bg-slate-700/40 text-slate-300 border-slate-600/30',
        dotClass: 'bg-slate-400' 
    },
    submitted: { 
        label: 'Menunggu Persetujuan', 
        badgeClass: 'bg-amber-500/15 text-amber-300 border-amber-500/30',
        dotClass: 'bg-amber-400 animate-pulse' 
    },
    approved: { 
        label: 'Disetujui Resmi', 
        badgeClass: 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30',
        dotClass: 'bg-emerald-400' 
    },
    rejected: { 
        label: 'Ditolak / Revisi', 
        badgeClass: 'bg-rose-500/15 text-rose-300 border-rose-500/30',
        dotClass: 'bg-rose-400' 
    },
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
    <Head :title="'Detail Memo: ' + memo.code" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-3">
                    <button
                        onclick="history.back()"
                        class="p-1.5 rounded-xl text-slate-400 hover:text-white hover:bg-white/5 border border-transparent hover:border-white/10 transition-colors"
                        title="Kembali"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    </button>
                    <div>
                        <h1 class="text-base font-bold text-white tracking-tight">Detail Dokumen Memo</h1>
                        <div class="flex items-center gap-2 text-xs text-slate-400">
                            <span class="font-mono font-semibold text-indigo-400">{{ memo.code }}</span>
                            <span>•</span>
                            <span>{{ memo.branch?.name || 'Kantor Cabang' }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2.5">
                    <span :class="[statusConfig[memo.status]?.badgeClass, 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border']">
                        <span class="w-1.5 h-1.5 rounded-full" :class="statusConfig[memo.status]?.dotClass"></span>
                        <span>{{ statusConfig[memo.status]?.label }}</span>
                    </span>

                    <button
                        onclick="window.print()"
                        class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-medium border border-white/10 transition-colors shadow-sm print:hidden"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        <span>Cetak</span>
                    </button>

                    <div v-if="user.role === 'KC' && ['draft','rejected'].includes(memo.status)">
                        <Link :href="route('memos.edit', memo.id)" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded-xl transition-colors shadow-sm">
                            Edit Memo
                        </Link>
                    </div>
                </div>
            </div>
        </template>

        <!-- Main Workspace: 2-Column Desktop, Stack on Mobile -->
        <div class="max-w-7xl mx-auto py-2">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <!-- Left: Document Preview Stage (lg:col-span-8) -->
                <div class="lg:col-span-8 w-full flex justify-center">
                    <div class="w-full max-w-[210mm]">
                        <MemoDocument :memo="memo" :show-am-signature="memo.status === 'approved'" />
                    </div>
                </div>

                <!-- Right: Information & History Sidebar (lg:col-span-4) -->
                <div class="lg:col-span-4 space-y-4 lg:sticky lg:top-20 print:hidden w-full">
                    
                    <!-- Card 1: Metadata Summary -->
                    <div class="bg-slate-800/50 border border-white/10 rounded-2xl p-5 shadow-sm">
                        <div class="flex items-center justify-between mb-3 pb-3 border-b border-white/5">
                            <h3 class="text-xs font-bold text-white uppercase tracking-wider">Informasi Dokumen</h3>
                            <button
                                onclick="window.print()"
                                class="text-xs text-indigo-400 hover:text-indigo-300 font-medium flex items-center gap-1"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                <span>Cetak Memo</span>
                            </button>
                        </div>
                        
                        <div class="space-y-2.5 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400">Kode Memo</span>
                                <span class="font-mono text-indigo-400 font-semibold">{{ memo.code }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400">Status</span>
                                <span class="font-semibold text-white">{{ statusConfig[memo.status]?.label }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400">Pembuat Memo</span>
                                <span class="text-white font-medium">{{ memo.creator?.name || '-' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400">Kantor Cabang</span>
                                <span class="text-white font-medium">{{ memo.branch?.name || '-' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400">Area Manager</span>
                                <span class="text-white font-medium">{{ memo.area_manager?.name || '-' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400">Tanggal Pengajuan</span>
                                <span class="text-slate-300">{{ formatDate(memo.submitted_at || memo.created_at) }}</span>
                            </div>
                        </div>

                        <!-- Action if editable -->
                        <div v-if="user.role === 'KC' && ['draft','rejected'].includes(memo.status)" class="mt-4 pt-3 border-t border-white/5">
                            <Link :href="route('memos.edit', memo.id)" class="w-full flex items-center justify-center gap-2 py-2 px-4 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded-xl transition-all shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                <span>Edit & Perbaiki Memo</span>
                            </Link>
                        </div>
                    </div>

                    <!-- Card 2: Attachments (if any) -->
                    <div v-if="memo.attachments?.length > 0" class="bg-slate-800/50 border border-white/10 rounded-2xl p-5 shadow-sm">
                        <h3 class="text-xs font-bold text-white uppercase tracking-wider mb-3 pb-3 border-b border-white/5">
                            Lampiran Berkas ({{ memo.attachments.length }})
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

                    <!-- Card 3: Approval Timeline & Log -->
                    <div v-if="memo.approvals?.length > 0" class="bg-slate-800/50 border border-white/10 rounded-2xl p-5 shadow-sm">
                        <h3 class="text-xs font-bold text-white uppercase tracking-wider mb-3 pb-3 border-b border-white/5">Riwayat Persetujuan</h3>
                        <div class="space-y-2.5">
                            <div v-for="a in memo.approvals" :key="a.id" class="p-3 rounded-xl bg-slate-900/40 border border-white/5 text-xs">
                                <div class="flex items-center justify-between mb-1">
                                    <span :class="[a.action === 'approved' ? 'text-emerald-400' : 'text-rose-400', 'font-semibold uppercase text-[10px]']">{{ a.action === 'approved' ? 'Disetujui' : 'Ditolak' }}</span>
                                    <span class="text-[10px] text-slate-500">{{ formatDate(a.created_at) }}</span>
                                </div>
                                <p class="text-slate-400 text-[11px]">oleh {{ a.approver?.name }}</p>
                                <p v-if="a.notes" class="text-slate-300 text-[11px] mt-1 bg-white/5 p-2 rounded-lg border border-white/5">{{ a.notes }}</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
