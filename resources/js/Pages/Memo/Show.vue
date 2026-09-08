<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MemoDocument from '@/Components/MemoDocument.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

const props = defineProps({ memo: Object });
const user = usePage().props.auth.user;

const statusConfig = {
    draft: { label: 'Draft', class: 'bg-slate-500/20 text-slate-400 border-slate-500/30' },
    submitted: { label: 'Menunggu Persetujuan', class: 'bg-amber-500/20 text-amber-400 border-amber-500/30' },
    approved: { label: 'Disetujui', class: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30' },
    rejected: { label: 'Ditolak', class: 'bg-red-500/20 text-red-400 border-red-500/30' },
};
</script>

<template>
    <Head :title="memo.title" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-bold text-white">Detail Memo</h1>
                <div class="flex items-center gap-4">
                    <span :class="[statusConfig[memo.status]?.class, 'text-xs font-semibold px-3 py-1 rounded-full border']">{{ statusConfig[memo.status]?.label }}</span>
                    <button onclick="window.print()" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white text-sm font-medium rounded-xl transition-colors flex items-center gap-2 print:hidden">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        Print
                    </button>
                    <div v-if="user.role === 'KC' && ['draft','rejected'].includes(memo.status)">
                        <Link :href="route('memos.edit', memo.id)" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-xl transition-colors">Edit</Link>
                    </div>
                </div>
            </div>
        </template>

        <div class="max-w-4xl mx-auto space-y-6 print:space-y-0">
            <!-- Memo Document (print-ready) -->
            <MemoDocument :memo="memo" :show-am-signature="memo.status === 'approved'" />

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

            <!-- Approval History (hidden from print) -->
            <div v-if="memo.approvals?.length > 0" class="bg-slate-800/50 border border-white/5 rounded-2xl p-6 print:hidden">
                <h3 class="text-lg font-semibold text-white mb-4">Riwayat Approval</h3>
                <div class="space-y-3">
                    <div v-for="approval in memo.approvals" :key="approval.id" :class="[approval.action === 'approved' ? 'border-l-emerald-500 bg-emerald-500/5' : 'border-l-red-500 bg-red-500/5', 'border-l-2 rounded-r-xl px-5 py-4']">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span :class="[approval.action === 'approved' ? 'text-emerald-400' : 'text-red-400', 'text-sm font-semibold uppercase']">{{ approval.action }}</span>
                                <span class="text-sm text-slate-400">oleh {{ approval.approver?.name }}</span>
                            </div>
                            <span class="text-xs text-slate-500">{{ approval.signed_at ? new Date(approval.signed_at).toLocaleString('id-ID') : new Date(approval.created_at).toLocaleString('id-ID') }}</span>
                        </div>
                        <p v-if="approval.notes" class="text-sm text-slate-300 mt-2">{{ approval.notes }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
