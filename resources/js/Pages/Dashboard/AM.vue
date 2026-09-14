<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    pendingMemos: Array,
    recentActions: Array,
    stats: Object,
});

const page = usePage();
const user = computed(() => page.props.auth.user);

// Realtime Clock
const now = ref(new Date());
let clockInterval = null;

onMounted(() => {
    clockInterval = setInterval(() => { now.value = new Date(); }, 1000);
});

onUnmounted(() => {
    if (clockInterval) clearInterval(clockInterval);
});

// Dynamic Greeting
const greeting = computed(() => {
    const hour = now.value.getHours();
    if (hour >= 4 && hour < 11) return { text: 'Selamat Pagi'}; 
    if (hour >= 11 && hour < 15) return { text: 'Selamat Siang'}; 
    if (hour >= 15 && hour < 18) return { text: 'Selamat Sore'}; 
    return { text: 'Selamat Malam'}; 
});

const userDisplayName = computed(() => {
    const name = user.value?.name || 'Area Manager';
    return name.split('(')[0].trim();
});

const formattedDate = computed(() => {
    return new Intl.DateTimeFormat('id-ID', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
    }).format(now.value);
});

const formattedTime = computed(() => {
    return now.value.toLocaleTimeString('id-ID', {
        hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false
    }) + ' WIB';
});

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

const formatTime = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
};
</script>

<template>
    <Head title="Dashboard Area Manager" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-base font-bold text-white tracking-tight">Dashboard Area Manager</h2>
        </template>

        <!-- Page Context Banner -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-white tracking-tight flex items-center gap-2.5 flex-wrap">
                    <span>{{ greeting.icon }} {{ greeting.text }}, <span class="text-indigo-400">{{ userDisplayName }}</span>!</span>
                </h1>
                <p class="text-xs md:text-sm text-slate-400 mt-1">Pusat persetujuan memo pengajuan operasional dan tanda tangan digital wilayah.</p>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <!-- Date & Realtime Clock Pill -->
                <div class="dashboard-clock hidden sm:flex items-center gap-2.5 px-4 py-2 rounded-xl bg-slate-800/60 border border-white/5 text-xs text-slate-300 shadow-sm">
                    <div class="flex items-center gap-1.5 font-medium">
                        <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>{{ formattedDate }}</span>
                    </div>
                    <span class="text-slate-600 font-bold">•</span>
                    <div class="flex items-center gap-1.5 font-mono text-indigo-300 font-semibold">
                        <svg class="w-3.5 h-3.5 text-indigo-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>{{ formattedTime }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
            <!-- Pending -->
            <div class="dashboard-kpi-card card-hover-rise bg-slate-800/50 border border-white/5 rounded-2xl p-5 hover:border-amber-500/20 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-amber-400/90 uppercase tracking-wider">Perlu Persetujuan</span>
                        <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-3xl font-bold text-amber-300 tracking-tight">{{ stats.pending ?? 0 }}</span>
                        <span class="text-xs font-medium text-amber-400/80">Memo Menunggu</span>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-white/5 flex items-center justify-between text-xs">
                    <span class="text-amber-400/80 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                        Menunggu review Anda
                    </span>
                    <Link :href="route('approvals.pending')" class="btn-card-link-anim text-indigo-400 hover:text-indigo-300 font-medium">Lihat Semua</Link>
                </div>
            </div>

            <!-- Approved -->
            <div class="dashboard-kpi-card card-hover-rise bg-slate-800/50 border border-white/5 rounded-2xl p-5 hover:border-emerald-500/20 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-emerald-400 uppercase tracking-wider">Telah Disetujui</span>
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-3xl font-bold text-white tracking-tight">{{ stats.approved ?? 0 }}</span>
                        <span class="text-xs font-medium text-slate-400">Memo Disetujui</span>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-white/5 flex items-center text-xs text-slate-400">
                    <span>Tertanda-tangani secara digital</span>
                </div>
            </div>

            <!-- Rejected -->
            <div class="dashboard-kpi-card card-hover-rise bg-slate-800/50 border border-white/5 rounded-2xl p-5 hover:border-rose-500/20 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-rose-400 uppercase tracking-wider">Ditolak / Revisi</span>
                        <div class="w-10 h-10 rounded-xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-rose-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-3xl font-bold text-white tracking-tight">{{ stats.rejected ?? 0 }}</span>
                        <span class="text-xs font-medium text-slate-400">Memo Ditolak</span>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-white/5 flex items-center text-xs text-slate-400">
                    <span>Dikembalikan ke cabang untuk revisi</span>
                </div>
            </div>
        </div>

        <!-- Pending Memos Section -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-bold text-white tracking-tight">Memo Menunggu Persetujuan Anda</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Segera review dan verifikasi permohonan dari Kepala Cabang di wilayah Anda.</p>
                </div>
                <Link
                    v-if="pendingMemos.length > 0"
                    :href="route('approvals.pending')"
                    class="text-xs text-indigo-400 hover:text-indigo-300 font-medium flex items-center gap-1"
                >
                    <span>Lihat Semua Antrean</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </Link>
            </div>

            <div v-if="pendingMemos.length === 0" class="bg-slate-800/50 border border-white/5 rounded-2xl px-6 py-12 text-center shadow-sm">
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 mx-auto mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 13l4 4L19 7"/></svg>
                </div>
                <h3 class="text-sm font-semibold text-white">Semua Memo Telah Ditinjau</h3>
                <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">Tidak ada pengajuan memo yang sedang menunggu persetujuan Anda saat ini.</p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <Link
                    v-for="memo in pendingMemos"
                    :key="memo.id"
                    :href="route('approvals.review', memo.id)"
                    class="bg-slate-800/50 hover:bg-slate-800/90 border border-white/5 hover:border-amber-500/30 rounded-2xl p-5 transition-all group shadow-sm flex flex-col justify-between"
                >
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-mono text-xs font-semibold text-amber-400/90">{{ memo.code }}</span>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-amber-500/15 text-amber-300 border border-amber-500/30">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                                Perlu Review
                            </span>
                        </div>
                        <h3 class="text-sm font-semibold text-white group-hover:text-indigo-300 transition-colors line-clamp-1 mb-1">
                            {{ memo.title }}
                        </h3>
                        <p class="text-xs text-slate-400">
                            {{ memo.template?.name }}
                        </p>
                    </div>

                    <div class="mt-4 pt-3 border-t border-white/5 flex items-center justify-between">
                        <div class="flex items-center gap-2 text-xs text-slate-300">
                            <div class="w-6 h-6 rounded-lg bg-slate-700 flex items-center justify-center text-[10px] font-bold text-white">
                                {{ memo.creator?.name?.charAt(0) || 'U' }}
                            </div>
                            <span>{{ memo.creator?.name }} · {{ memo.branch?.name }}</span>
                        </div>
                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-400 group-hover:text-indigo-300 group-hover:translate-x-0.5 transition-all">
                            Review & Tanda Tangani
                        </span>
                    </div>
                </Link>
            </div>
        </div>

        <!-- Recent Actions Table -->
        <div>
            <h2 class="text-lg font-bold text-white tracking-tight mb-4">Riwayat Keputusan Terbaru</h2>
            <div class="bg-slate-800/50 border border-white/5 rounded-2xl overflow-hidden shadow-sm">
                <div v-if="recentActions.length === 0" class="px-6 py-12 text-center">
                    <p class="text-xs text-slate-500">Belum ada riwayat persetujuan atau penolakan memo.</p>
                </div>
                <table v-else class="w-full text-left table-head-pgi">
                    <thead>
                        <tr class="text-[11px] font-semibold uppercase tracking-wider">
                            <th class="px-6 py-3.5">Kode Memo & Tanggal</th>
                            <th class="px-6 py-3.5">Perihal</th>
                            <th class="px-6 py-3.5">Asal Cabang</th>
                            <th class="px-6 py-3.5">Keputusan</th>
                            <th class="px-6 py-3.5 text-right">Detail</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <tr v-for="memo in recentActions" :key="memo.id" class="hover:bg-white/[0.02] transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-mono text-xs font-semibold text-slate-300">{{ memo.code }}</span>
                                <p class="text-[11px] text-slate-500 mt-0.5">{{ formatDate(memo.updated_at) }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm font-medium text-white">{{ memo.title }}</span>
                                <p class="text-[11px] text-slate-400 mt-0.5">{{ memo.template?.name }}</p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-300">
                                <span>{{ memo.creator?.name }}</span>
                                <p class="text-[11px] text-slate-500 mt-0.5">{{ memo.branch?.name }}</p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="[
                                    memo.status === 'approved'
                                        ? 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30'
                                        : 'bg-rose-500/15 text-rose-300 border-rose-500/30',
                                    'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium border'
                                ]">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="memo.status === 'approved' ? 'bg-emerald-400' : 'bg-rose-400'"></span>
                                    <span>{{ memo.status === 'approved' ? 'Disetujui' : 'Ditolak' }}</span>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <Link
                                    :href="route('memos.show', memo.id)"
                                    class="btn-secondary-anim inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-indigo-600 text-slate-300 hover:text-white text-xs font-medium border border-white/5"
                                >
                                    <span>Lihat Dokumen</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
