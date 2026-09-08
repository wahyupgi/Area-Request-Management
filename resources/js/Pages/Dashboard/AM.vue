<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    pendingMemos: Array,
    recentActions: Array,
    stats: Object,
});
</script>

<template>
    <Head title="Dashboard AM" />
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-bold text-white">Dashboard Area Manager</h1>
        </template>

        <!-- Stats -->
        <div class="grid grid-cols-3 gap-4 mb-8">
            <div class="bg-amber-500/5 border border-amber-500/10 rounded-2xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-amber-400/70">Pending Approval</p>
                        <p class="text-4xl font-bold text-amber-400 mt-1">{{ stats.pending }}</p>
                    </div>
                    <div class="w-14 h-14 rounded-2xl bg-amber-500/10 flex items-center justify-center">
                        <svg class="w-7 h-7 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
            </div>
            <div class="bg-emerald-500/5 border border-emerald-500/10 rounded-2xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-emerald-400/70">Approved</p>
                        <p class="text-4xl font-bold text-emerald-400 mt-1">{{ stats.approved }}</p>
                    </div>
                    <div class="w-14 h-14 rounded-2xl bg-emerald-500/10 flex items-center justify-center">
                        <svg class="w-7 h-7 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
            </div>
            <div class="bg-red-500/5 border border-red-500/10 rounded-2xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-red-400/70">Rejected</p>
                        <p class="text-4xl font-bold text-red-400 mt-1">{{ stats.rejected }}</p>
                    </div>
                    <div class="w-14 h-14 rounded-2xl bg-red-500/10 flex items-center justify-center">
                        <svg class="w-7 h-7 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Memos -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-white">Memo Menunggu Persetujuan</h2>
                <Link :href="route('approvals.pending')" class="text-sm text-indigo-400 hover:text-indigo-300">Lihat Semua →</Link>
            </div>
            <div class="space-y-3">
                <div v-if="pendingMemos.length === 0" class="bg-slate-800/50 border border-white/5 rounded-2xl px-6 py-12 text-center">
                    <p class="text-slate-500">Tidak ada memo yang menunggu persetujuan.</p>
                </div>
                <Link
                    v-for="memo in pendingMemos"
                    :key="memo.id"
                    :href="route('approvals.review', memo.id)"
                    class="block bg-slate-800/50 border border-white/5 rounded-2xl p-5 hover:bg-slate-800/80 hover:border-indigo-500/20 transition-all duration-200 group"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-1">
                                <span class="text-xs font-mono text-slate-500">{{ memo.code }}</span>
                                <span class="text-xs bg-amber-500/20 text-amber-400 px-2 py-0.5 rounded-full font-medium">Pending</span>
                            </div>
                            <h3 class="text-white font-medium group-hover:text-indigo-400 transition-colors">{{ memo.title }}</h3>
                            <p class="text-sm text-slate-500 mt-1">{{ memo.creator?.name }} · {{ memo.branch?.name }} · {{ memo.template?.name }}</p>
                        </div>
                        <div class="flex items-center gap-2 text-slate-500 group-hover:text-indigo-400 transition-colors">
                            <span class="text-sm">Review</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </div>
                    </div>
                </Link>
            </div>
        </div>

        <!-- Recent Actions -->
        <div>
            <h2 class="text-lg font-semibold text-white mb-4">Riwayat Terbaru</h2>
            <div class="bg-slate-800/50 border border-white/5 rounded-2xl overflow-hidden">
                <div v-if="recentActions.length === 0" class="px-6 py-12 text-center">
                    <p class="text-slate-500">Belum ada riwayat approval.</p>
                </div>
                <div v-for="memo in recentActions" :key="memo.id" class="flex items-center justify-between px-6 py-4 border-b border-white/5 last:border-0">
                    <div>
                        <p class="text-sm text-white font-medium">{{ memo.title }}</p>
                        <p class="text-xs text-slate-500">{{ memo.creator?.name }} · {{ memo.branch?.name }}</p>
                    </div>
                    <span :class="[memo.status === 'approved' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400', 'text-xs font-semibold px-3 py-1 rounded-full']">
                        {{ memo.status === 'approved' ? 'Approved' : 'Rejected' }}
                    </span>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
