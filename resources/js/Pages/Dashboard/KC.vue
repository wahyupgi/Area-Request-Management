<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    memos: Array,
    stats: Object,
});

const statusConfig = {
    draft: { label: 'Draft', class: 'bg-slate-500/20 text-slate-400' },
    submitted: { label: 'Submitted', class: 'bg-amber-500/20 text-amber-400' },
    approved: { label: 'Approved', class: 'bg-emerald-500/20 text-emerald-400' },
    rejected: { label: 'Rejected', class: 'bg-red-500/20 text-red-400' },
};
</script>

<template>
    <Head title="Dashboard KC" />
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-bold text-white">Dashboard Kepala Cabang</h1>
        </template>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
            <div class="bg-slate-800/50 border border-white/5 rounded-2xl p-5">
                <p class="text-sm text-slate-400">Total Memo</p>
                <p class="text-3xl font-bold text-white mt-1">{{ stats.total }}</p>
            </div>
            <div class="bg-slate-800/50 border border-white/5 rounded-2xl p-5">
                <p class="text-sm text-slate-400">Draft</p>
                <p class="text-3xl font-bold text-slate-400 mt-1">{{ stats.draft }}</p>
            </div>
            <div class="bg-amber-500/5 border border-amber-500/10 rounded-2xl p-5">
                <p class="text-sm text-amber-400/70">Submitted</p>
                <p class="text-3xl font-bold text-amber-400 mt-1">{{ stats.submitted }}</p>
            </div>
            <div class="bg-emerald-500/5 border border-emerald-500/10 rounded-2xl p-5">
                <p class="text-sm text-emerald-400/70">Approved</p>
                <p class="text-3xl font-bold text-emerald-400 mt-1">{{ stats.approved }}</p>
            </div>
            <div class="bg-red-500/5 border border-red-500/10 rounded-2xl p-5">
                <p class="text-sm text-red-400/70">Rejected</p>
                <p class="text-3xl font-bold text-red-400 mt-1">{{ stats.rejected }}</p>
            </div>
        </div>

        <!-- Action -->
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold text-white">Daftar Memo</h2>
            <Link :href="route('memos.create')" class="flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-xl transition-colors shadow-lg shadow-indigo-500/25">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Buat Memo Baru
            </Link>
        </div>

        <!-- Memo Table -->
        <div class="bg-slate-800/50 border border-white/5 rounded-2xl overflow-hidden">
            <div v-if="memos.length === 0" class="px-6 py-16 text-center">
                <svg class="w-16 h-16 text-slate-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <p class="text-slate-500">Belum ada memo. Klik tombol di atas untuk membuat memo baru.</p>
            </div>
            <table v-else class="w-full">
                <thead>
                    <tr class="border-b border-white/5">
                        <th class="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-6 py-4">Kode</th>
                        <th class="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-6 py-4">Judul</th>
                        <th class="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-6 py-4">Template</th>
                        <th class="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-6 py-4">Status</th>
                        <th class="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-6 py-4">Tanggal</th>
                        <th class="text-right text-xs font-medium text-slate-400 uppercase tracking-wider px-6 py-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="memo in memos" :key="memo.id" class="border-b border-white/5 hover:bg-white/[0.02] transition-colors">
                        <td class="px-6 py-4 text-sm text-slate-300 font-mono">{{ memo.code }}</td>
                        <td class="px-6 py-4 text-sm text-white font-medium">{{ memo.title }}</td>
                        <td class="px-6 py-4 text-sm text-slate-400">{{ memo.template?.name }}</td>
                        <td class="px-6 py-4">
                            <span :class="[statusConfig[memo.status]?.class, 'text-xs font-semibold px-3 py-1 rounded-full']">
                                {{ statusConfig[memo.status]?.label }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-400">{{ new Date(memo.created_at).toLocaleDateString('id-ID') }}</td>
                        <td class="px-6 py-4 text-right">
                            <Link v-if="memo.status === 'draft' || memo.status === 'rejected'" :href="route('memos.edit', memo.id)" class="text-indigo-400 hover:text-indigo-300 text-sm mr-3">Edit</Link>
                            <Link :href="route('memos.show', memo.id)" class="text-slate-400 hover:text-white text-sm">Detail</Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AuthenticatedLayout>
</template>
