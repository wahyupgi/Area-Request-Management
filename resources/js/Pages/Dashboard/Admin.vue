<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    stats: Object,
    recentMemos: Array,
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
    const name = user.value?.name || 'Administrator';
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

const searchQuery = ref('');
const statusFilter = ref('all');

const statusConfig = {
    draft: { 
        label: 'Draft', 
        badgeClass: 'bg-slate-500/15 text-slate-300 border-slate-500/30',
    },
    submitted: { 
        label: 'Menunggu AM', 
        badgeClass: 'bg-slate-500/15 text-slate-300 border-slate-500/30',
    },
    approved: { 
        label: 'Disetujui', 
        badgeClass: 'bg-slate-500/15 text-slate-300 border-slate-500/30',
    },
    rejected: { 
        label: 'Ditolak', 
        badgeClass: 'bg-slate-500/15 text-slate-300 border-slate-500/30',
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

const formatTime = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit'
    });
};


const filteredMemos = computed(() => {
    let list = props.recentMemos || [];
    
    if (statusFilter.value !== 'all') {
        list = list.filter(m => m.status === statusFilter.value);
    }
    
    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase().trim();
        list = list.filter(m => 
            (m.code && m.code.toLowerCase().includes(q)) ||
            (m.title && m.title.toLowerCase().includes(q)) ||
            (m.creator?.name && m.creator.name.toLowerCase().includes(q)) ||
            (m.branch?.name && m.branch.name.toLowerCase().includes(q)) ||
            (m.template?.name && m.template.name.toLowerCase().includes(q))
        );
    }
    
    return list;
});

const totalMemosCount = computed(() => props.stats?.total_memos || (props.recentMemos?.length || 0));
const approvedCount = computed(() => props.stats?.approved_memos ?? props.recentMemos?.filter(m => m.status === 'approved').length ?? 0);
const pendingCount = computed(() => props.stats?.pending_approvals ?? props.recentMemos?.filter(m => m.status === 'submitted').length ?? 0);
const draftCount = computed(() => props.stats?.draft_memos ?? props.recentMemos?.filter(m => m.status === 'draft').length ?? 0);
const rejectedCount = computed(() => props.stats?.rejected_memos ?? props.recentMemos?.filter(m => m.status === 'rejected').length ?? 0);

const approvedPercent = computed(() => totalMemosCount.value > 0 ? Math.round((approvedCount.value / totalMemosCount.value) * 100) : 0);
const pendingPercent = computed(() => totalMemosCount.value > 0 ? Math.round((pendingCount.value / totalMemosCount.value) * 100) : 0);
const draftPercent = computed(() => totalMemosCount.value > 0 ? Math.round((draftCount.value / totalMemosCount.value) * 100) : 0);
const rejectedPercent = computed(() => totalMemosCount.value > 0 ? Math.round((rejectedCount.value / totalMemosCount.value) * 100) : 0);
</script>

<template>
    <Head title="Dashboard Administrator" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-base font-bold text-white tracking-tight">Dashboard Administrator</h2>
        </template>

        <!-- Page Context Banner -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-white tracking-tight flex items-center gap-2.5 flex-wrap">
                    <span>{{ greeting.icon }} {{ greeting.text }}, <span class="text-indigo-400">{{ userDisplayName }}</span>!</span>
                </h1>
                <p class="text-base font-medium text-slate-400 tracking-tight">Monitoring pergerakan memo antar cabang dan tata kelola master data sistem.</p>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <!-- Date & Realtime Clock Pill -->
                <div class="dashboard-clock hidden sm:flex items-center gap-2.5 px-4 py-2 rounded-xl bg-slate-800/60 border border-white/5 text-xs text-slate-300 shadow-sm">
                    <div class="flex items-center gap-1.5 font-medium">
                        <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>{{ formattedDate }}</span>
                    </div>
                    <div class="flex items-center gap-1.5 font-mono text-indigo-300 font-semibold">
                        <svg class="w-3.5 h-3.5 text-indigo-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>{{ formattedTime }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
            <!-- Memo Masuk -->
            <Link href="#" class="dashboard-kpi-card card-hover-rise bg-slate-800/50 border border-white/5 rounded-2xl p-5 hover:border-indigo-500/20 shadow-sm flex flex-col justify-between block transition-all">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Memo Masuk</span>
                        <div class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-3xl font-bold text-white tracking-tight">{{ stats.pending_approvals ?? 0 }}</span>
                        <span class="text-xs font-medium text-slate-400">Baru</span>
                    </div>
                </div>
            </Link>

            <!-- Card 1: Total Memo -->
            <Link href="#" class="dashboard-kpi-card card-hover-rise bg-slate-800/50 border border-white/5 rounded-2xl p-5 hover:border-indigo-500/20 shadow-sm flex flex-col justify-between block transition-all">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Memo</span>
                        <div class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-3xl font-bold text-white tracking-tight">{{ stats.total_memos ?? 0 }}</span>
                        <span class="text-xs font-medium text-slate-400">Pengajuan</span>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-white/5 flex items-center justify-between text-xs text-slate-400">
                    <span>{{ stats.approved_memos ?? 0 }} Disetujui</span>
                    <span>{{ stats.draft_memos ?? 0 }} Draft</span>
                </div>
            </Link>

            <!-- Card 2: Menunggu Persetujuan AM -->
            <Link href="#" class="dashboard-kpi-card card-hover-rise bg-slate-800/50 border border-white/5 rounded-2xl p-5 hover:border-amber-500/20 shadow-sm flex flex-col justify-between block transition-all">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-amber-400/90 uppercase tracking-wider">Menunggu AM</span>
                        <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-3xl font-bold text-amber-300 tracking-tight">{{ stats.pending_approvals ?? 0 }}</span>
                        <span class="text-xs font-medium text-amber-400/80">Perlu Review AM</span>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-white/5 flex items-center text-xs text-amber-400/75 gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                    <span>Menunggu Verifikasi</span>
                </div>
            </Link>

            <!-- Card 3: Template Memo -->
            <Link :href="route('admin.templates.index')" class="dashboard-kpi-card card-hover-rise bg-slate-800/50 border border-white/5 rounded-2xl p-5 hover:border-emerald-500/20 shadow-sm flex flex-col justify-between block transition-all">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Template Form</span>
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm0 8a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zm10 0a1 1 0 011-1h4a1 1 0 011 1v6a1 1 0 01-1 1h-4a1 1 0 01-1-1v-6z"/></svg>
                        </div>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-3xl font-bold text-white tracking-tight">{{ stats.total_templates ?? 0 }}</span>
                        <span class="text-xs font-medium text-slate-400">Skema Aktif</span>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-white/5 flex items-center justify-between text-xs text-slate-400">
                    <span class="text-emerald-400">Format standar cabang</span>
                </div>
            </Link>

            <!-- Card 4: Pengguna & Jaringan Cabang -->
            <Link :href="route('admin.users.index')" class="dashboard-kpi-card card-hover-rise bg-slate-800/50 border border-white/5 rounded-2xl p-5 hover:border-cyan-500/20 shadow-sm flex flex-col justify-between block transition-all">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pengguna</span>
                        <div class="w-10 h-10 rounded-xl bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center text-cyan-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-3xl font-bold text-white tracking-tight">{{ stats.total_users ?? 0 }}</span>
                        <span class="text-xs font-medium text-slate-400">Akun Terdaftar</span>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-white/5 flex items-center justify-between text-xs text-slate-400">
                    <span>{{ stats.total_branches ?? 0 }} Cabang</span>
                    <span>{{ stats.total_areas ?? 0 }} Area</span>
                </div>
            </Link>
        </div>

        <!-- 2-Column Main Workspace -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left 8 Columns: Recent Memos Table with Live Search & Tabs -->
            <div class="lg:col-span-8 flex flex-col gap-4">
                <div class="bg-slate-800/50 border border-white/5 rounded-2xl p-6 shadow-sm">
                    <!-- Table Header & Controls -->
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-5 border-b border-white/5">
                        <div>
                            <h2 class="text-lg font-bold text-white tracking-tight">Pengajuan Memo Terkini</h2>
                            <p class="text-xs text-slate-400 mt-0.5">Monitoring pergerakan dan status persetujuan memo dari seluruh cabang.</p>
                        </div>

                        <!-- Live Search Input -->
                        <div class="relative w-full md:w-64">
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Cari kode, judul, cabang..."
                                class="w-full bg-slate-900/60 border border-white/10 rounded-xl pl-9 pr-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all"
                            />
                            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                    </div>

                    <!-- Filter Status Tabs -->
                    <div class="flex items-center gap-1.5 py-3 overflow-x-auto text-xs border-b border-white/5 scrollbar-none">
                        <button
                            @click="statusFilter = 'all'"
                            :class="[
                                statusFilter === 'all'
                                    ? 'bg-indigo-600 text-white font-medium shadow-sm'
                                    : 'text-slate-400 hover:text-white hover:bg-white/5',
                                'px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap'
                            ]"
                        >
                            Semua ({{ recentMemos.length }})
                        </button>
                        <button
                            @click="statusFilter = 'submitted'"
                            :class="[
                                statusFilter === 'submitted'
                                    ? 'bg-amber-500/20 text-amber-300 font-medium border border-amber-500/30'
                                    : 'text-slate-400 hover:text-white hover:bg-white/5',
                                'px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap'
                            ]"
                        >
                            Menunggu Review ({{ recentMemos.filter(m => m.status === 'submitted').length }})
                        </button>
                        <button
                            @click="statusFilter = 'approved'"
                            :class="[
                                statusFilter === 'approved'
                                    ? 'bg-emerald-500/20 text-emerald-300 font-medium border border-emerald-500/30'
                                    : 'text-slate-400 hover:text-white hover:bg-white/5',
                                'px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap'
                            ]"
                        >
                            Disetujui ({{ recentMemos.filter(m => m.status === 'approved').length }})
                        </button>
                        <button
                            @click="statusFilter = 'draft'"
                            :class="[
                                statusFilter === 'draft'
                                    ? 'bg-slate-700 text-white font-medium'
                                    : 'text-slate-400 hover:text-white hover:bg-white/5',
                                'px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap'
                            ]"
                        >
                            Draft ({{ recentMemos.filter(m => m.status === 'draft').length }})
                        </button>
                        <button
                            @click="statusFilter = 'rejected'"
                            :class="[
                                statusFilter === 'rejected'
                                    ? 'bg-rose-500/20 text-rose-300 font-medium border border-rose-500/30'
                                    : 'text-slate-400 hover:text-white hover:bg-white/5',
                                'px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap'
                            ]"
                        >
                            Ditolak ({{ recentMemos.filter(m => m.status === 'rejected').length }})
                        </button>
                    </div>

                    <!-- Enterprise Table -->
                    <div class="overflow-x-auto -mx-6">
                        <table class="w-full text-left table-head-pgi">
                            <thead>
                                <tr class="text-[11px] font-semibold uppercase tracking-wider">
                                    <th class="px-6 py-3.5">Kode &amp; Tanggal</th>
                                    <th class="px-6 py-3.5">Perihal Memo</th>
                                    <th class="px-6 py-3.5">Cabang &amp; Pembuat</th>
                                    <th class="px-6 py-3.5">Status</th>
                                    <th class="px-6 py-3.5 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <tr
                                    v-for="memo in filteredMemos"
                                    :key="memo.id"
                                    class="hover:bg-white/[0.02] transition-colors group animate-in fade-in duration-500"
                                >
                                    <!-- Kode & Tanggal -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-col">
                                            <span class="font-mono text-xs font-semibold text-indigo-400 group-hover:text-indigo-300 transition-colors">
                                                {{ memo.code }}
                                            </span>
                                            <span class="text-[11px] text-slate-500 mt-0.5">
                                                {{ formatDate(memo.created_at) }} <span class="text-slate-600">•</span> {{ formatTime(memo.created_at) }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Perihal Memo -->
                                    <td class="px-6 py-4 max-w-xs">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-medium text-white line-clamp-1 group-hover:text-indigo-200 transition-colors">
                                                {{ memo.title }}
                                            </span>
                                            <span v-if="memo.template" class="text-[11px] text-slate-400 mt-0.5 line-clamp-1">
                                                {{ memo.template.name }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Cabang & Pembuat -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-7 h-7 rounded-lg bg-slate-800 border border-white/10 flex items-center justify-center text-xs font-semibold text-slate-300">
                                                {{ memo.creator?.name?.charAt(0) || 'U' }}
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="text-xs font-medium text-slate-200">{{ memo.creator?.name || '-' }}</span>
                                                <span class="text-[11px] text-slate-400">
                                                    {{ memo.branch?.name || 'Kantor Cabang' }}
                                                    <span v-if="memo.branch?.area?.name" class="text-slate-500">({{ memo.branch.area.name }})</span>
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border whitespace-nowrap" :class="statusConfig[memo.status]?.badgeClass || 'bg-slate-500/15 text-slate-300 border-slate-500/30'">
                                            <span>{{ statusConfig[memo.status]?.label || memo.status }}</span>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <Link
                                            :href="route('memos.show', memo.id)"
                                            class="btn-secondary-anim inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-indigo-600 text-slate-300 hover:text-white text-xs font-medium border border-white/5 hover:border-transparent shadow-sm"
                                        >
                                            <span>Lihat Memo</span>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </Link>
                                    </td>
                                </tr>

                                <tr v-if="filteredMemos.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="w-12 h-12 rounded-2xl bg-slate-800/80 border border-white/10 flex items-center justify-center text-slate-500 mb-3">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            </div>
                                            <p class="text-sm font-medium text-slate-300">Tidak ada memo yang sesuai</p>
                                            <p class="text-xs text-slate-500 mt-1 max-w-sm">Coba sesuaikan kata kunci pencarian atau ganti filter status untuk melihat data memo lainnya.</p>
                                            <button
                                                v-if="searchQuery || statusFilter !== 'all'"
                                                @click="searchQuery = ''; statusFilter = 'all'"
                                                class="mt-3 text-xs text-indigo-400 hover:text-indigo-300 underline font-medium"
                                            >
                                                Reset Filter & Pencarian
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="pt-4 mt-2 border-t border-white/5 flex items-center justify-between text-xs text-slate-500">
                        <span>Menampilkan {{ filteredMemos.length }} dari {{ recentMemos.length }} memo terkini</span>
                        <span class="hidden sm:inline">Data diperbarui secara otomatis</span>
                    </div>
                </div>
            </div>

            <!-- Right 4 Columns: Widgets (Breakdown, Shortcuts, SOP Info) -->
            <div class="lg:col-span-4 flex flex-col gap-6">
                <!-- Widget 1: Status Distribution -->
                <div class="bg-slate-800/50 border border-white/5 rounded-2xl p-5 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-bold text-white tracking-tight">Distribusi Status Memo</h3>
                        <span class="text-xs font-semibold text-slate-400">{{ totalMemosCount }} total</span>
                    </div>

                    <!-- Progress Stack Bar -->
                    <div class="h-2.5 w-full bg-slate-900/60 rounded-full overflow-hidden flex mb-4 border border-white/5">
                        <div
                            :style="{ width: `${approvedPercent}%` }"
                            title="Disetujui"
                            class="bg-emerald-500 transition-all duration-500"
                        ></div>
                        <div
                            :style="{ width: `${pendingPercent}%` }"
                            title="Menunggu Persetujuan"
                            class="bg-amber-400 transition-all duration-500"
                        ></div>
                        <div
                            :style="{ width: `${draftPercent}%` }"
                            title="Draft"
                            class="bg-slate-600 transition-all duration-500"
                        ></div>
                        <div
                            :style="{ width: `${rejectedPercent}%` }"
                            title="Ditolak"
                            class="bg-rose-500 transition-all duration-500"
                        ></div>
                    </div>

                   
                    <div class="space-y-2 text-xs">
                        <div class="flex items-center justify-between py-1 border-b border-white/5">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                                <span class="text-slate-300">Disetujui (Approved)</span>
                            </div>
                            <div class="flex items-center gap-1.5 font-medium">
                                <span class="text-white">{{ approvedCount }}</span>
                                <span class="text-slate-500">({{ approvedPercent }}%)</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between py-1 border-b border-white/5">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                                <span class="text-slate-300">Menunggu Review AM</span>
                            </div>
                            <div class="flex items-center gap-1.5 font-medium">
                                <span class="text-amber-300">{{ pendingCount }}</span>
                                <span class="text-slate-500">({{ pendingPercent }}%)</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between py-1 border-b border-white/5">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-slate-500"></span>
                                <span class="text-slate-300">Draft Kantor Cabang</span>
                            </div>
                            <div class="flex items-center gap-1.5 font-medium">
                                <span class="text-white">{{ draftCount }}</span>
                                <span class="text-slate-500">({{ draftPercent }}%)</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between py-1">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                                <span class="text-slate-300">Ditolak / Perlu Revisi</span>
                            </div>
                            <div class="flex items-center gap-1.5 font-medium">
                                <span class="text-white">{{ rejectedCount }}</span>
                                <span class="text-slate-500">({{ rejectedPercent }}%)</span>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="bg-slate-800/50 border border-white/5 rounded-2xl p-5 shadow-sm">
                    <h3 class="text-sm font-bold text-white tracking-tight mb-3">Pusat Akses Master Data</h3>
                    <div class="space-y-2">
                        <Link
                            :href="route('admin.templates.index')"
                            class="flex items-center justify-between p-3 rounded-xl bg-slate-900/40 hover:bg-slate-900/80 border border-white/5 hover:border-indigo-500/30 transition-all group"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 group-hover:scale-105 transition-transform">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm0 8a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6z"/></svg>
                                </div>
                                <div>
                                    <h4 class="text-xs font-semibold text-white group-hover:text-indigo-400 transition-colors">Kelola Template Memo</h4>
                                    <p class="text-[11px] text-slate-400">Atur skema form & field dinamis</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 text-xs text-slate-400 group-hover:text-indigo-400 transition-colors">
                                <span class="px-2 py-0.5 rounded-md bg-white/5 border border-white/5 font-semibold text-[11px]">{{ stats.total_templates ?? 0 }}</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </div>
                        </Link>

                        <!-- Kelola Cabang -->
                        <Link
                            :href="route('admin.branches.index')"
                            class="flex items-center justify-between p-3 rounded-xl bg-slate-900/40 hover:bg-slate-900/80 border border-white/5 hover:border-emerald-500/30 transition-all group"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 group-hover:scale-105 transition-transform">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                </div>
                                <div>
                                    <h4 class="text-xs font-semibold text-white group-hover:text-emerald-400 transition-colors">Kelola Kantor Cabang</h4>
                                    <p class="text-[11px] text-slate-400">Daftar cabang & alokasi area</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 text-xs text-slate-400 group-hover:text-emerald-400 transition-colors">
                                <span class="px-2 py-0.5 rounded-md bg-white/5 border border-white/5 font-semibold text-[11px]">{{ stats.total_branches ?? 0 }}</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </div>
                        </Link>

                        <!-- Kelola Area -->
                        <Link
                            :href="route('admin.areas.index')"
                            class="flex items-center justify-between p-3 rounded-xl bg-slate-900/40 hover:bg-slate-900/80 border border-white/5 hover:border-cyan-500/30 transition-all group"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center text-cyan-400 group-hover:scale-105 transition-transform">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </div>
                                <div>
                                    <h4 class="text-xs font-semibold text-white group-hover:text-cyan-400 transition-colors">Kelola Wilayah Area</h4>
                                    <p class="text-[11px] text-slate-400">Pembagian area operasional</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 text-xs text-slate-400 group-hover:text-cyan-400 transition-colors">
                                <span class="px-2 py-0.5 rounded-md bg-white/5 border border-white/5 font-semibold text-[11px]">{{ stats.total_areas ?? 0 }}</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </div>
                        </Link>

                        <!-- Kelola User -->
                        <Link
                            :href="route('admin.users.index')"
                            class="flex items-center justify-between p-3 rounded-xl bg-slate-900/40 hover:bg-slate-900/80 border border-white/5 hover:border-purple-500/30 transition-all group"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-purple-400 group-hover:scale-105 transition-transform">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                </div>
                                <div>
                                    <h4 class="text-xs font-semibold text-white group-hover:text-purple-400 transition-colors">Kelola Data User</h4>
                                    <p class="text-[11px] text-slate-400">Akun KC, AM, & Administrator</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 text-xs text-slate-400 group-hover:text-purple-400 transition-colors">
                                <span class="px-2 py-0.5 rounded-md bg-white/5 border border-white/5 font-semibold text-[11px]">{{ stats.total_users ?? 0 }}</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
