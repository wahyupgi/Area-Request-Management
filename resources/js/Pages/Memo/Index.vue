<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    memos: Array,
    filters: Object,
});

const statusConfig = {
    draft: { label: 'Draft', class: 'bg-slate-500/15 text-slate-300 border border-slate-500/30' },
    submitted: { label: 'Submitted', class: 'bg-slate-500/15 text-slate-300 border border-slate-500/30' },
    approved: { label: 'Approved', class: 'bg-slate-500/15 text-slate-300 border border-slate-500/30' },
    rejected: { label: 'Rejected', class: 'bg-slate-500/15 text-slate-300 border border-slate-500/30' },
};
</script>

<template>
    <Head title="Daftar Memo" />
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-bold text-white">Daftar Memo</h1>
        </template>

        <div class="flex items-center justify-between mb-6">
            <div class="flex gap-2">
                <Link :href="route('memos.index')" :class="[!filters?.status ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-400 hover:text-white', 'px-4 py-2 rounded-xl text-sm font-medium transition-colors']">Semua</Link>
                <Link v-for="s in ['draft','submitted','approved','rejected']" :key="s" :href="route('memos.index', {status: s})" :class="[filters?.status === s ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-400 hover:text-white', 'px-4 py-2 rounded-xl text-sm font-medium transition-colors capitalize']">{{ s }}</Link>
            </div>
            <Link :href="route('memos.create')" class="flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-xl transition-colors shadow-lg shadow-indigo-500/25">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Buat Memo
            </Link>
        </div>

        <div class="bg-slate-800/50 border border-white/5 rounded-2xl overflow-hidden">
            <div v-if="memos.length === 0" class="px-6 py-16 text-center">
                <p class="text-slate-500">Tidak ada memo ditemukan.</p>
            </div>
            <table v-else class="w-full table-head-pgi">
                <thead>
                    <tr class="border-b border-white/5">
                        <th class="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-6 py-4">Kode</th>
                        <th class="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-6 py-4">Judul</th>
                        <th class="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-6 py-4">Template</th>
                        <th class="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-6 py-4">Status</th>
                        <th class="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-6 py-4">Tanggal</th>
                        <th class="text-center text-xs font-medium text-slate-400 uppercase tracking-wider px-6 py-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="memo in memos" :key="memo.id" class="border-b border-white/5 hover:bg-white/[0.02] transition-colors">
                        <td class="px-6 py-4 text-sm text-slate-300 font-mono">{{ memo.code }}</td>
                        <td class="px-6 py-4 text-sm text-white font-medium">{{ memo.title }}</td>
                        <td class="px-6 py-4 text-sm text-slate-400">{{ memo.template?.name }}</td>
                        <td class="px-6 py-4">
                            <span :class="[statusConfig[memo.status]?.class, 'inline-flex items-center text-xs font-semibold px-3 py-1 rounded-full whitespace-nowrap']">{{ statusConfig[memo.status]?.label }}</span>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-400">{{ new Date(memo.created_at).toLocaleDateString('id-ID') }}</td>
                        <td class="px-6 py-4 text-center space-x-3">
                            <Link v-if="['draft','rejected'].includes(memo.status)" :href="route('memos.edit', memo.id)" class="text-indigo-400 hover:text-indigo-300 text-sm">Edit</Link>
                            <Link :href="route('memos.show', memo.id)" class="text-slate-400 hover:text-white text-sm">Detail</Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AuthenticatedLayout>
</template>
