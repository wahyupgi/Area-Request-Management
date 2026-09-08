<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    stats: Object,
    recentMemos: Array,
});

const statusConfig = {
    draft: { label: 'Draft', class: 'bg-slate-500/20 text-slate-400' },
    submitted: { label: 'Submitted', class: 'bg-amber-500/20 text-amber-400' },
    approved: { label: 'Approved', class: 'bg-emerald-500/20 text-emerald-400' },
    rejected: { label: 'Rejected', class: 'bg-red-500/20 text-red-400' },
};
</script>

<template>
    <Head title="Dashboard Admin" />
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-bold text-white">Dashboard Administrator</h1>
        </template>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-indigo-500/5 border border-indigo-500/10 rounded-2xl p-6">
                <p class="text-sm text-indigo-400/70">Total Memo</p>
                <p class="text-4xl font-bold text-indigo-400 mt-1">{{ stats.total_memos }}</p>
            </div>
            <div class="bg-purple-500/5 border border-purple-500/10 rounded-2xl p-6">
                <p class="text-sm text-purple-400/70">Templates</p>
                <p class="text-4xl font-bold text-purple-400 mt-1">{{ stats.total_templates }}</p>
            </div>
            <div class="bg-cyan-500/5 border border-cyan-500/10 rounded-2xl p-6">
                <p class="text-sm text-cyan-400/70">Total User</p>
                <p class="text-4xl font-bold text-cyan-400 mt-1">{{ stats.total_users }}</p>
            </div>
            <div class="bg-amber-500/5 border border-amber-500/10 rounded-2xl p-6">
                <p class="text-sm text-amber-400/70">Pending</p>
                <p class="text-4xl font-bold text-amber-400 mt-1">{{ stats.pending_approvals }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <Link :href="route('admin.templates.index')" class="bg-slate-800/50 border border-white/5 rounded-2xl p-6 hover:border-indigo-500/30 transition-all group">
                <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm0 8a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6z"/></svg>
                </div>
                <h3 class="text-white font-semibold group-hover:text-indigo-400 transition-colors">Kelola Template</h3>
                <p class="text-sm text-slate-500 mt-1">Atur template memo dan field schema</p>
            </Link>
            <Link :href="route('admin.areas.index')" class="bg-slate-800/50 border border-white/5 rounded-2xl p-6 hover:border-emerald-500/30 transition-all group">
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                </div>
                <h3 class="text-white font-semibold group-hover:text-emerald-400 transition-colors">Kelola Area & Cabang</h3>
                <p class="text-sm text-slate-500 mt-1">Kelola data area dan cabang</p>
            </Link>
            <Link :href="route('admin.users.index')" class="bg-slate-800/50 border border-white/5 rounded-2xl p-6 hover:border-purple-500/30 transition-all group">
                <div class="w-12 h-12 rounded-2xl bg-purple-500/10 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </div>
                <h3 class="text-white font-semibold group-hover:text-purple-400 transition-colors">Kelola User</h3>
                <p class="text-sm text-slate-500 mt-1">Kelola data pengguna sistem</p>
            </Link>
        </div>

        <h2 class="text-lg font-semibold text-white mb-4">Memo Terbaru</h2>
        <div class="bg-slate-800/50 border border-white/5 rounded-2xl overflow-hidden">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-white/5">
                        <th class="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-6 py-4">Kode</th>
                        <th class="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-6 py-4">Judul</th>
                        <th class="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-6 py-4">Pembuat</th>
                        <th class="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-6 py-4">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="memo in recentMemos" :key="memo.id" class="border-b border-white/5 last:border-0">
                        <td class="px-6 py-4 text-sm text-slate-300 font-mono">{{ memo.code }}</td>
                        <td class="px-6 py-4 text-sm text-white">{{ memo.title }}</td>
                        <td class="px-6 py-4 text-sm text-slate-400">{{ memo.creator?.name }}</td>
                        <td class="px-6 py-4">
                            <span :class="[statusConfig[memo.status]?.class, 'text-xs font-semibold px-3 py-1 rounded-full']">{{ statusConfig[memo.status]?.label }}</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AuthenticatedLayout>
</template>
