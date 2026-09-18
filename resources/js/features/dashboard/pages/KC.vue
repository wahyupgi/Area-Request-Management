<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    memos: Array,
    stats: Object,
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const searchQuery = ref('');
const statusFilter = ref('all');

// Realtime Clock & Timer
const now = ref(new Date());
let clockInterval = null;

onMounted(() => {
    clockInterval = setInterval(() => {
        now.value = new Date();
    }, 1000);
});

onUnmounted(() => {
    if (clockInterval) clearInterval(clockInterval);
});

// Dynamic Greeting based on hour
const greeting = computed(() => {
    const hour = now.value.getHours();
    if (hour >= 4 && hour < 11) {
        return { text: 'Selamat Pagi'}; 
    } else if (hour >= 11 && hour < 15) {
        return { text: 'Selamat Siang'}; 
    } else if (hour >= 15 && hour < 18) {
        return { text: 'Selamat Sore'}; 
    } else {
        return { text: 'Selamat Malam'}; 
    }
});

// Clean user display name
const userDisplayName = computed(() => {
    const name = user.value?.name || 'Kepala Cabang';
    return name.split('(')[0].trim();
});

const formattedDate = computed(() => {
    return new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    }).format(now.value);
});

const formattedTime = computed(() => {
    return now.value.toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false
    }) + ' WIB';
});

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
    let list = props.memos || [];
    
    if (statusFilter.value !== 'all') {
        list = list.filter(m => m.status === statusFilter.value);
    }
    
    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase().trim();
        list = list.filter(m => 
            (m.code && m.code.toLowerCase().includes(q)) ||
            (m.title && m.title.toLowerCase().includes(q)) ||
            (m.template?.name && m.template.name.toLowerCase().includes(q))
        );
    }
    
    return list;
});
</script>

<template>
    <Head title="Dashboard Kepala Cabang" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-base font-bold text-white tracking-tight">Dashboard Kepala Cabang</h2>
        </template>

        <!-- Page Context Banner with Realtime Greeting & Live Clock -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-white tracking-tight flex items-center gap-2.5 flex-wrap">
                    <span>{{ greeting.icon }} {{ greeting.text }}, <span class="text-indigo-400">{{ userDisplayName }}</span>!</span>
                </h1>
                <p class="text-xs md:text-sm text-slate-400 mt-1">
                    Kelola pembuatan dan pantau status persetujuan memo pengajuan unit <span class="text-slate-200 font-medium">{{ user?.branch?.name || 'kantor cabang' }}</span>.
                </p>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <!-- Date & Realtime Clock Pill -->
                <div class="dashboard-clock hidden sm:flex items-center gap-2.5 px-4 py-2 rounded-xl bg-slate-800/60 border border-white/5 text-xs text-slate-300 shadow-sm">
                    <!-- Date -->
                    <div class="flex items-center gap-1.5 font-medium">
                        <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>{{ formattedDate }}</span>
                    </div>
                    <span class="text-slate-600 font-bold">•</span>
                    <!-- Clock -->
                    <div class="flex items-center gap-1.5 font-mono text-indigo-300 font-semibold">
                        <svg class="w-3.5 h-3.5 text-indigo-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>{{ formattedTime }}</span>
                    </div>
                </div>

                <Link
                    :href="route('memos.create')"
                    class="btn-primary-anim flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded-xl shadow-md shadow-indigo-600/20"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Buat Memo Baru</span>
                </Link>
            </div>
        </div>

        <!-- Stats Cards Grid -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
            <!-- Total -->
            <div class="dashboard-kpi-card card-hover-rise bg-slate-800/50 border border-white/5 rounded-2xl p-4 hover:border-indigo-500/20 shadow-sm">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Memo</span>
                <p class="text-2xl font-bold text-white mt-1">{{ stats.total ?? 0 }}</p>
                <p class="text-[11px] text-slate-500 mt-2">Semua riwayat</p>
            </div>

            <!-- Draft -->
            <div class="dashboard-kpi-card card-hover-rise bg-slate-800/50 border border-white/5 rounded-2xl p-4 hover:border-slate-500/20 shadow-sm">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Draft Tersimpan</span>
                <p class="text-2xl font-bold text-slate-300 mt-1">{{ stats.draft ?? 0 }}</p>
                <p class="text-[11px] text-slate-500 mt-2">Belum diajukan</p>
            </div>

            <!-- Submitted / Pending -->
            <div class="dashboard-kpi-card card-hover-rise bg-slate-800/50 border border-white/5 rounded-2xl p-4 hover:border-amber-500/20 shadow-sm">
                <span class="text-xs font-semibold text-amber-400/90 uppercase tracking-wider">Menunggu AM</span>
                <p class="text-2xl font-bold text-amber-300 mt-1">{{ stats.submitted ?? 0 }}</p>
                <p class="text-[11px] text-amber-400/70 mt-2">Dalam proses verifikasi</p>
            </div>

            <!-- Approved -->
            <div class="dashboard-kpi-card card-hover-rise bg-slate-800/50 border border-white/5 rounded-2xl p-4 hover:border-emerald-500/20 shadow-sm">
                <span class="text-xs font-semibold text-emerald-400 uppercase tracking-wider">Disetujui</span>
                <p class="text-2xl font-bold text-emerald-400 mt-1">{{ stats.approved ?? 0 }}</p>
                <p class="text-[11px] text-emerald-500/70 mt-2">Selesai & resmi</p>
            </div>

            <!-- Rejected -->
            <div class="dashboard-kpi-card card-hover-rise bg-slate-800/50 border border-white/5 rounded-2xl p-4 hover:border-rose-500/20 shadow-sm">
                <span class="text-xs font-semibold text-rose-400 uppercase tracking-wider">Perlu Revisi</span>
                <p class="text-2xl font-bold text-rose-400 mt-1">{{ stats.rejected ?? 0 }}</p>
                <p class="text-[11px] text-rose-500/70 mt-2">Ditolak Area Manager</p>
            </div>
        </div>

        <!-- Memos Table Section -->
        <div class="bg-slate-800/50 border border-white/5 rounded-2xl p-6 shadow-sm">
            <!-- Header & Controls -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-5 border-b border-white/5">
                <div>
                    <h2 class="text-lg font-bold text-white tracking-tight">Daftar Memo Kantor Cabang</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Seluruh pengajuan memo yang dibuat dari akun kantor cabang Anda.</p>
                </div>

                <!-- Live Search -->
                <div class="relative w-full md:w-64">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari kode atau perihal..."
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
                    Semua ({{ memos.length }})
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
                    Menunggu AM ({{ memos.filter(m => m.status === 'submitted').length }})
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
                    Disetujui ({{ memos.filter(m => m.status === 'approved').length }})
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
                    Draft ({{ memos.filter(m => m.status === 'draft').length }})
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
                    Perlu Revisi ({{ memos.filter(m => m.status === 'rejected').length }})
                </button>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto -mx-6">
                <table class="w-full text-left table-head-pgi">
                    <thead>
                        <tr class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 border-b border-white/5">
                            <th class="px-6 py-3.5">Kode Memo</th>
                            <th class="px-6 py-3.5">Perihal & Template</th>
                            <th class="px-6 py-3.5">Tanggal Dibuat</th>
                            <th class="px-6 py-3.5">Status</th>
                            <th class="px-6 py-3.5">TTD Digital</th>
                            <th class="px-6 py-3.5 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <tr
                            v-for="memo in filteredMemos"
                            :key="memo.id"
                            class="hover:bg-white/[0.02] transition-colors group"
                        >
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-mono text-xs font-semibold text-indigo-400 group-hover:text-indigo-300 transition-colors">
                                    {{ memo.code }}
                                </span>
                            </td>
                            <td class="px-6 py-4 max-w-xs">
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium text-white line-clamp-1 group-hover:text-indigo-200 transition-colors">
                                        {{ memo.title }}
                                    </span>
                                    <span v-if="memo.template" class="text-[11px] text-slate-400 mt-0.5">
                                        {{ memo.template.name }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-400">
                                <span>{{ formatDate(memo.created_at) }}</span>
                                <span class="text-slate-600 block text-[11px]">{{ formatTime(memo.created_at) }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border whitespace-nowrap" :class="statusConfig[memo.status]?.badgeClass || 'bg-slate-500/15 text-slate-300 border-slate-500/30'">
                                    <span>{{ statusConfig[memo.status]?.label || memo.status }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div v-if="memo.latest_approval?.signature" class="flex items-center gap-2">
                                    <img
                                        :src="'/storage/' + memo.latest_approval.signature.signature_image"
                                        alt="Tanda tangan digital AM"
                                        class="h-8 w-20 object-contain rounded bg-white/10 p-1"
                                    />
                                    <span class="text-[11px] text-emerald-400">Tertanda</span>
                                </div>
                                <span v-else class="text-[11px] text-slate-500">Belum ditandatangani</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <Link
                                        v-if="memo.status === 'draft' || memo.status === 'rejected'"
                                        :href="route('memos.edit', memo.id)"
                                        class="btn-secondary-anim px-2.5 py-1 rounded-lg bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-400 text-xs font-medium border border-indigo-500/20"
                                    >
                                        Edit
                                    </Link>
                                    <Link
                                        :href="route('memos.show', memo.id)"
                                        class="btn-secondary-anim inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-medium border border-white/5"
                                    >
                                        <span>Detail</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </Link>
                                </div>
                            </td>
                        </tr>

                        <!-- Empty State -->
                        <tr v-if="filteredMemos.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-12 h-12 rounded-2xl bg-slate-800/80 border border-white/10 flex items-center justify-center text-slate-500 mb-3">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </div>
                                    <p class="text-sm font-medium text-slate-300">Belum ada memo yang sesuai</p>
                                    <p class="text-xs text-slate-500 mt-1 max-w-sm">Mulai buat pengajuan memo baru atau sesuaikan filter Anda.</p>
                                    <Link
                                        :href="route('memos.create')"
                                        class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded-xl transition-all shadow-sm"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        Buat Memo Sekarang
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Footer -->
            <div class="pt-4 mt-2 border-t border-white/5 flex items-center justify-between text-xs text-slate-500">
                <span>Menampilkan {{ filteredMemos.length }} dari {{ memos.length }} total memo</span>
                <span class="hidden sm:inline">PT Pusat Gadai Indonesia</span>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
