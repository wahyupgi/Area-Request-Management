<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    allMemos: Object,
    defaultTab: String,
});

const activeTab = ref(props.defaultTab || 'masuk');

const memoList = computed(() => props.allMemos?.data ?? []);
const memos = computed(() => memoList.value.filter(m => m.status === 'submitted'));
const approvedMemos = computed(() => memoList.value.filter(m => m.status === 'approved'));
const rejectedMemos = computed(() => memoList.value.filter(m => m.status === 'rejected'));
const history = computed(() => memoList.value);
const paginationLinks = computed(() => props.allMemos?.links ?? []);

const historyStatusConfig = {
    submitted: {
        label: 'Menunggu',
        badgeClass: 'text-amber-600',
        dotClass: 'bg-amber-400',
    },
    approved: {
        label: 'Disetujui',
        badgeClass: 'text-emerald-600',
        dotClass: 'bg-emerald-400',
    },
    rejected: {
        label: 'Ditolak',
        badgeClass: 'text-rose-600',
        dotClass: 'bg-red-400',
    },
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric', month: 'short', year: 'numeric',
    });
};
</script>

<template>
    <Head title="Kotak Masuk AM" />
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-bold text-white">Kotak Masuk & Riwayat Memo</h1>
        </template>

        <!-- Tab Navigation -->
        <div class="flex items-center gap-1 mb-6 p-1 bg-slate-800/60 border border-white/5 rounded-2xl w-fit overflow-x-auto max-w-full">
            <button
                @click="activeTab = 'masuk'"
                :class="[
                    activeTab === 'masuk' ? 'tab-active-pgi' : 'tab-inactive-pgi',
                    'relative flex items-center gap-2.5 px-5 py-2 rounded-xl text-sm font-semibold transition-all duration-200 whitespace-nowrap'
                ]"
            >
                Masuk
            </button>
            <button
                @click="activeTab = 'approved'"
                :class="[
                    activeTab === 'approved' ? 'tab-active-pgi' : 'tab-inactive-pgi',
                    'relative flex items-center gap-2.5 px-5 py-2 rounded-xl text-sm font-semibold transition-all duration-200 whitespace-nowrap'
                ]"
            >
                Disetujui
            </button>
            <button
                @click="activeTab = 'rejected'"
                :class="[
                    activeTab === 'rejected' ? 'tab-active-pgi' : 'tab-inactive-pgi',
                    'relative flex items-center gap-2.5 px-5 py-2 rounded-xl text-sm font-semibold transition-all duration-200 whitespace-nowrap'
                ]"
            >
                Ditolak / Revisi
            </button>
            <button
                @click="activeTab = 'riwayat'"
                :class="[
                    activeTab === 'riwayat' ? 'tab-active-pgi' : 'tab-inactive-pgi',
                    'relative flex items-center gap-2.5 px-5 py-2 rounded-xl text-sm font-semibold transition-all duration-200 whitespace-nowrap'
                ]"
            >
                Semua Riwayat
            </button>
        </div>

        <!-- TAB: MASUK -->
        <div v-show="activeTab === 'masuk'">
            <div v-if="memos.length === 0" class="bg-slate-800/50 border border-white/5 rounded-2xl px-6 py-16 text-center">
                <div class="w-16 h-16 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-slate-400 font-medium">Tidak ada memo yang menunggu persetujuan</p>
                <p class="text-slate-500 text-sm mt-1">Semua memo sudah diproses. 🎉</p>
            </div>
            <div v-else class="space-y-3">
                <Link
                    v-for="memo in memos" :key="memo.id" :href="route('approvals.review', memo.id)"
                    class="block bg-slate-800/50 border border-white/5 rounded-2xl p-5 hover:bg-slate-800/80 hover:border-indigo-500/25 transition-all duration-200 group"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center flex-wrap gap-2 mb-2">
                                <span class="text-xs font-mono text-slate-500 bg-slate-700/50 px-2 py-0.5 rounded-lg">{{ memo.code }}</span>
                                <span class="inline-flex items-center gap-1 text-xs text-amber-600 font-semibold"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Menunggu</span>
                                <span v-if="memo.template?.name" class="text-xs text-slate-500">{{ memo.template.name }}</span>
                            </div>
                            <h3 class="text-base text-white font-semibold group-hover:text-slate-700 transition-colors truncate">{{ memo.title }}</h3>
                            <div class="flex items-center flex-wrap gap-x-4 gap-y-1 mt-2 text-sm text-slate-500">
                                <span class="flex items-center gap-1">{{ memo.creator?.name }}</span>
                                <span class="flex items-center gap-1">{{ memo.branch?.name }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 text-slate-500 group-hover:text-indigo-400 transition-colors shrink-0 mt-1">
                            <span class="text-sm font-medium hidden sm:block">Review</span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </div>
                    </div>
                </Link>
            </div>
        </div>

        <!-- TAB: APPROVED -->
        <div v-show="activeTab === 'approved'">
            <div v-if="approvedMemos.length === 0" class="bg-slate-800/50 border border-white/5 rounded-2xl px-6 py-16 text-center">
                <p class="text-slate-400 font-medium">Belum ada memo yang disetujui</p>
            </div>
            <div v-else class="space-y-3">
                <Link
                    v-for="memo in approvedMemos" :key="memo.id" :href="route('approvals.history', memo.id)"
                    class="block bg-slate-800/50 border border-white/5 rounded-2xl p-5 hover:bg-slate-800/80 hover:border-emerald-500/25 transition-all duration-200 group"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center flex-wrap gap-2 mb-2">
                                <span class="text-xs font-mono text-slate-500 bg-slate-700/50 px-2 py-0.5 rounded-lg">{{ memo.code }}</span>
                                <span class="text-xs text-emerald-600 font-semibold flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>Disetujui
                                </span>
                            </div>
                            <h3 class="text-base text-white font-semibold group-hover:text-slate-700 transition-colors truncate">{{ memo.title }}</h3>
                        </div>
                        <div class="flex items-center gap-2 text-slate-500 group-hover:text-emerald-400 transition-colors shrink-0 mt-1">
                            <span class="text-sm font-medium hidden sm:block">Detail</span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </div>
                    </div>
                </Link>
            </div>
        </div>

        <!-- TAB: REJECTED -->
        <div v-show="activeTab === 'rejected'">
            <div v-if="rejectedMemos.length === 0" class="bg-slate-800/50 border border-white/5 rounded-2xl px-6 py-16 text-center">
                <p class="text-slate-400 font-medium">Belum ada memo yang ditolak / revisi</p>
            </div>
            <div v-else class="space-y-3">
                <Link
                    v-for="memo in rejectedMemos" :key="memo.id" :href="route('approvals.history', memo.id)"
                    class="block bg-slate-800/50 border border-white/5 rounded-2xl p-5 hover:bg-slate-800/80 hover:border-rose-500/25 transition-all duration-200 group"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center flex-wrap gap-2 mb-2">
                                <span class="text-xs font-mono text-slate-500 bg-slate-700/50 px-2 py-0.5 rounded-lg">{{ memo.code }}</span>
                                <span class="text-xs text-rose-600 font-semibold flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>Ditolak
                                </span>
                            </div>
                            <h3 class="text-base text-white font-semibold group-hover:text-slate-700 transition-colors truncate">{{ memo.title }}</h3>
                        </div>
                        <div class="flex items-center gap-2 text-slate-500 group-hover:text-rose-400 transition-colors shrink-0 mt-1">
                            <span class="text-sm font-medium hidden sm:block">Detail</span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </div>
                    </div>
                </Link>
            </div>
        </div>

        <!-- TAB: RIWAYAT (SEMUA) -->
        <div v-show="activeTab === 'riwayat'">
            <div v-if="history.length === 0" class="bg-slate-800/50 border border-white/5 rounded-2xl px-6 py-16 text-center">
                <div class="w-16 h-16 rounded-2xl bg-slate-700/50 border border-white/5 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <p class="text-slate-400 font-medium">Belum ada memo</p>
                <p class="text-slate-500 text-sm mt-1">Semua memo yang masuk, disetujui, dan ditolak akan muncul di sini.</p>
            </div>
            <div v-else class="space-y-3">
                <Link
                    v-for="memo in history" :key="memo.id" :href="memo.status === 'submitted' ? route('approvals.review', memo.id) : route('approvals.history', memo.id)"
                    class="block bg-slate-800/50 border border-white/5 rounded-2xl p-5 hover:bg-slate-800/80 hover:border-white/10 transition-all duration-200 group"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center flex-wrap gap-2 mb-2">
                                <span class="text-xs font-mono text-slate-500 bg-slate-700/50 px-2 py-0.5 rounded-lg">{{ memo.code }}</span>
                                <span :class="[historyStatusConfig[memo.status]?.badgeClass, 'text-xs font-semibold flex items-center gap-1']">
                                    <span :class="[historyStatusConfig[memo.status]?.dotClass, 'w-1.5 h-1.5 rounded-full']"></span>
                                    {{ historyStatusConfig[memo.status]?.label }}
                                </span>
                            </div>
                            <h3 class="text-base text-white font-semibold group-hover:text-slate-700 transition-colors truncate">{{ memo.title }}</h3>
                        </div>
                        <div class="flex items-center gap-2 text-slate-500 group-hover:text-slate-400 transition-colors shrink-0 mt-1">
                            <span class="text-sm font-medium hidden sm:block">Detail</span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </div>
                    </div>
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
