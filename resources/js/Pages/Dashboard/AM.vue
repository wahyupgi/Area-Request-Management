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
onMounted(() => { clockInterval = setInterval(() => { now.value = new Date(); }, 1000); });
onUnmounted(() => { if (clockInterval) clearInterval(clockInterval); });

const greeting = computed(() => {
    const h = now.value.getHours();
    if (h >= 4 && h < 11) return 'Selamat Pagi';
    if (h >= 11 && h < 15) return 'Selamat Siang';
    if (h >= 15 && h < 18) return 'Selamat Sore';
    return 'Selamat Malam';
});

const userDisplayName = computed(() => (user.value?.name || 'Area Manager').split('(')[0].trim());

const formattedDate = computed(() =>
    new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(now.value)
);

const formattedTime = computed(() =>
    now.value.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false }) + ' WIB'
);

const formatDate = (d) => {
    if (!d) return '-';
    return new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

const formatRelative = (d) => {
    if (!d) return '';
    const diff = Math.floor((Date.now() - new Date(d)) / 60000);
    if (diff < 1) return 'Baru saja';
    if (diff < 60) return `${diff} menit lalu`;
    if (diff < 1440) return `${Math.floor(diff / 60)} jam lalu`;
    return `${Math.floor(diff / 1440)} hari lalu`;
};

// Urgency level for pending memos
const getUrgency = (submittedAt) => {
    if (!submittedAt) return 'normal';
    const hours = (Date.now() - new Date(submittedAt)) / 3600000;
    if (hours > 48) return 'critical';
    if (hours > 24) return 'high';
    return 'normal';
};
</script>

<template>
    <Head title="Dashboard Area Manager" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-base font-bold text-white tracking-tight">Dashboard Area Manager</h2>
        </template>

        <!-- ═══════════════════════════════════════════════════════
             HERO HEADER
        ════════════════════════════════════════════════════════ -->
        <div class="am-hero rounded-2xl p-6 mb-6 relative overflow-hidden">
            <!-- Decorative background -->
            <div class="am-hero-glow"></div>
            <div class="am-hero-grid"></div>

            <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <p class="text-indigo-300 text-xs font-semibold uppercase tracking-widest mb-1">Area Manager · Wilayah Anda</p>
                    <h1 class="text-2xl md:text-3xl font-bold text-white tracking-tight">
                        {{ greeting }}, <span class="text-indigo-300">{{ userDisplayName }}</span>!
                    </h1>
                    <p class="text-slate-400 text-sm mt-1">Pusat persetujuan memo operasional wilayah Anda.</p>
                </div>
                <!-- Live Clock -->
                <div class="am-clock-pill flex flex-col items-end gap-0.5 text-right">
                    <span class="text-slate-300 text-sm font-mono font-bold tracking-wider">{{ formattedTime }}</span>
                    <span class="text-slate-500 text-xs">{{ formattedDate }}</span>
                </div>
            </div>

            <!-- Quick action row -->
            <div class="relative z-10 mt-5 flex flex-wrap gap-2">
                <Link
                    :href="route('approvals.pending')"
                    class="am-quick-btn am-quick-btn--primary"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    Antrean Persetujuan
                    <span v-if="stats.pending > 0" class="am-badge-urgent">{{ stats.pending }}</span>
                </Link>
                <Link
                    :href="route('signature.index')"
                    class="am-quick-btn am-quick-btn--ghost"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                    </svg>
                    Tanda Tangan Digital
                </Link>
                <Link
                    :href="route('signature.settings')"
                    class="am-quick-btn am-quick-btn--ghost"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Pengaturan Skema TTD
                </Link>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════
             STATS ROW
        ════════════════════════════════════════════════════════ -->
        <div class="grid grid-cols-3 gap-4 mb-6">
            <!-- Pending -->
            <div class="am-stat-card am-stat-card--amber">
                <div class="am-stat-icon am-stat-icon--amber">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="am-stat-body">
                    <span class="am-stat-value text-amber-300">{{ stats.pending ?? 0 }}</span>
                    <span class="am-stat-label">Perlu Disetujui</span>
                </div>
                <div class="am-stat-pulse" v-if="(stats.pending ?? 0) > 0"></div>
            </div>

            <!-- Approved -->
            <div class="am-stat-card am-stat-card--emerald">
                <div class="am-stat-icon am-stat-icon--emerald">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="am-stat-body">
                    <span class="am-stat-value text-white">{{ stats.approved ?? 0 }}</span>
                    <span class="am-stat-label">Telah Disetujui</span>
                </div>
            </div>

            <!-- Rejected -->
            <div class="am-stat-card am-stat-card--rose">
                <div class="am-stat-icon am-stat-icon--rose">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="am-stat-body">
                    <span class="am-stat-value text-white">{{ stats.rejected ?? 0 }}</span>
                    <span class="am-stat-label">Ditolak / Revisi</span>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════
             MAIN CONTENT GRID: Pending (left) + Recent (right)
        ════════════════════════════════════════════════════════ -->
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

            <!-- ── LEFT: Pending Memo Inbox (3/5) ── -->
            <div class="lg:col-span-3">
                <!-- Section Header -->
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <h2 class="text-base font-bold text-white">Memo Menunggu Persetujuan</h2>
                        <span v-if="pendingMemos.length > 0" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                            {{ pendingMemos.length }}
                        </span>
                    </div>
                    <Link
                        v-if="pendingMemos.length > 0"
                        :href="route('approvals.pending')"
                        class="text-xs text-indigo-400 hover:text-indigo-300 font-medium flex items-center gap-1 transition-colors"
                    >
                        Lihat Semua
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </Link>
                </div>

                <!-- Empty State -->
                <div v-if="pendingMemos.length === 0" class="am-empty-state">
                    <div class="am-empty-icon am-empty-icon--emerald">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-white mt-3">Semua Bersih!</h3>
                    <p class="text-xs text-slate-400 mt-1 max-w-xs mx-auto">Tidak ada memo yang menunggu persetujuan. Semua pengajuan telah ditindaklanjuti.</p>
                </div>

                <!-- Pending List -->
                <div v-else class="space-y-3">
                    <Link
                        v-for="memo in pendingMemos"
                        :key="memo.id"
                        :href="route('approvals.review', memo.id)"
                        class="am-memo-card group"
                        :class="{
                            'am-memo-card--critical': getUrgency(memo.submitted_at) === 'critical',
                            'am-memo-card--high': getUrgency(memo.submitted_at) === 'high',
                        }"
                    >
                        <!-- Urgency indicator bar -->
                        <div class="am-urgency-bar" :class="{
                            'bg-rose-500': getUrgency(memo.submitted_at) === 'critical',
                            'bg-amber-500': getUrgency(memo.submitted_at) === 'high',
                            'bg-indigo-500': getUrgency(memo.submitted_at) === 'normal',
                        }"></div>

                        <div class="flex-1 min-w-0">
                            <!-- Top row -->
                            <div class="flex items-start justify-between gap-2 mb-2">
                                <div class="min-w-0">
                                    <span class="font-mono text-[11px] font-semibold text-slate-400">{{ memo.code || `#${memo.id}` }}</span>
                                    <h3 class="text-sm font-semibold text-white group-hover:text-indigo-300 transition-colors truncate mt-0.5">
                                        {{ memo.title }}
                                    </h3>
                                </div>
                                <!-- Urgency badge -->
                                <span v-if="getUrgency(memo.submitted_at) === 'critical'"
                                    class="flex-shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/15 text-rose-300 border border-rose-500/30">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-400 animate-ping absolute"></span>
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                                    Mendesak
                                </span>
                                <span v-else-if="getUrgency(memo.submitted_at) === 'high'"
                                    class="flex-shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/15 text-amber-300 border border-amber-500/30">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                                    Segera
                                </span>
                                <span v-else
                                    class="flex-shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-500/15 text-indigo-300 border border-indigo-500/30">
                                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
                                    Baru Masuk
                                </span>
                            </div>

                            <!-- Meta row -->
                            <div class="flex items-center gap-3 flex-wrap">
                                <!-- Template type -->
                                <span class="flex items-center gap-1 text-[11px] text-slate-400">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    {{ memo.template?.name || '-' }}
                                </span>
                                <!-- Branch -->
                                <span class="flex items-center gap-1 text-[11px] text-slate-400">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    </svg>
                                    {{ memo.branch?.name || '-' }}
                                </span>
                            </div>
                        </div>

                        <!-- Right: sender & time -->
                        <div class="flex-shrink-0 flex flex-col items-end gap-2">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-[11px] font-bold text-white">
                                    {{ memo.creator?.name?.charAt(0)?.toUpperCase() || 'U' }}
                                </div>
                                <div class="text-right hidden sm:block">
                                    <p class="text-[11px] font-medium text-slate-300">{{ memo.creator?.name?.split(' ')[0] }}</p>
                                    <p class="text-[10px] text-slate-500">{{ formatRelative(memo.submitted_at) }}</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center gap-0.5 text-[11px] font-semibold text-indigo-400 group-hover:text-indigo-300 group-hover:gap-1.5 transition-all">
                                Review
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </span>
                        </div>
                    </Link>
                </div>
            </div>

            <!-- ── RIGHT: Recent Actions (2/5) ── -->
            <div class="lg:col-span-2">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-base font-bold text-white">Riwayat Keputusan</h2>
                    <span class="text-[11px] text-slate-500">10 terbaru</span>
                </div>

                <!-- Empty -->
                <div v-if="recentActions.length === 0" class="am-empty-state">
                    <div class="am-empty-icon">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <p class="text-xs text-slate-400 mt-3">Belum ada riwayat keputusan.</p>
                </div>

                <!-- Recent Actions List -->
                <div v-else class="space-y-2">
                    <Link
                        v-for="memo in recentActions"
                        :key="memo.id"
                        :href="route('memos.show', memo.id)"
                        class="am-recent-item group"
                    >
                        <!-- Status dot -->
                        <div class="flex-shrink-0 mt-0.5">
                            <span class="w-2 h-2 rounded-full block"
                                :class="memo.status === 'approved' ? 'bg-emerald-400' : 'bg-rose-400'">
                            </span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium text-slate-200 group-hover:text-indigo-300 truncate transition-colors">{{ memo.title }}</p>
                            <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                                <span class="text-[10px] text-slate-500">{{ memo.branch?.name }}</span>
                                <span class="text-[10px] text-slate-600">·</span>
                                <span class="text-[10px]" :class="memo.status === 'approved' ? 'text-emerald-400' : 'text-rose-400'">
                                    {{ memo.status === 'approved' ? 'Disetujui' : 'Ditolak' }}
                                </span>
                            </div>
                        </div>
                        <div class="flex-shrink-0 text-right">
                            <p class="text-[10px] text-slate-500">{{ formatDate(memo.updated_at) }}</p>
                            <svg class="w-3.5 h-3.5 text-slate-600 group-hover:text-indigo-400 ml-auto mt-1 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </Link>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>

<style>
/* ─── Hero ─────────────────────────────────────────────────── */
.am-hero {
    background: linear-gradient(135deg, #1e2035 0%, #1a1d2e 50%, #1e2035 100%);
    border: 1px solid rgba(99, 102, 241, 0.2);
}
.am-hero-glow {
    position: absolute;
    top: -60px; right: -60px;
    width: 260px; height: 260px;
    background: radial-gradient(circle, rgba(99,102,241,0.15) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}
.am-hero-grid {
    position: absolute; inset: 0;
    background-image: linear-gradient(rgba(255,255,255,0.015) 1px, transparent 1px),
                      linear-gradient(90deg, rgba(255,255,255,0.015) 1px, transparent 1px);
    background-size: 32px 32px;
    border-radius: inherit;
    pointer-events: none;
}
.am-clock-pill {
    background: rgba(15, 15, 30, 0.5);
    border: 1px solid rgba(99, 102, 241, 0.15);
    padding: 10px 16px;
    border-radius: 14px;
}

/* ─── Quick Buttons ─────────────────────────────────────────── */
.am-quick-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 600;
    transition: all 0.2s;
    position: relative;
}
.am-quick-btn--primary {
    background: rgba(99, 102, 241, 0.2);
    border: 1px solid rgba(99, 102, 241, 0.4);
    color: #a5b4fc;
}
.am-quick-btn--primary:hover {
    background: rgba(99, 102, 241, 0.35);
    border-color: rgba(99, 102, 241, 0.6);
    color: #c7d2fe;
    transform: translateY(-1px);
}
.am-quick-btn--ghost {
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    color: #94a3b8;
}
.am-quick-btn--ghost:hover {
    background: rgba(255,255,255,0.07);
    color: #e2e8f0;
    transform: translateY(-1px);
}
.am-badge-urgent {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
    border-radius: 9px;
    background: #f59e0b;
    color: #1c1917;
    font-size: 10px;
    font-weight: 800;
}

/* ─── Stats Cards ───────────────────────────────────────────── */
.am-stat-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 20px;
    border-radius: 16px;
    border: 1px solid rgba(255,255,255,0.05);
    background: rgba(30, 32, 53, 0.6);
    position: relative;
    overflow: hidden;
    transition: transform 0.2s, border-color 0.2s;
}
.am-stat-card:hover { transform: translateY(-2px); }
.am-stat-card--amber:hover { border-color: rgba(245,158,11,0.2); }
.am-stat-card--emerald:hover { border-color: rgba(16,185,129,0.2); }
.am-stat-card--rose:hover { border-color: rgba(244,63,94,0.2); }

.am-stat-icon {
    width: 44px; height: 44px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.am-stat-icon--amber { background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.2); color: #fbbf24; }
.am-stat-icon--emerald { background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.2); color: #34d399; }
.am-stat-icon--rose { background: rgba(244,63,94,0.1); border: 1px solid rgba(244,63,94,0.2); color: #fb7185; }

.am-stat-body { display: flex; flex-direction: column; gap: 2px; }
.am-stat-value { font-size: 28px; font-weight: 800; letter-spacing: -0.02em; line-height: 1; }
.am-stat-label { font-size: 11px; font-weight: 500; color: #64748b; }

.am-stat-pulse {
    position: absolute;
    top: 12px; right: 12px;
    width: 8px; height: 8px;
    border-radius: 50%;
    background: #f59e0b;
    animation: pulse-ring 2s ease-in-out infinite;
}
@keyframes pulse-ring {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.4; transform: scale(1.5); }
}

/* ─── Memo Inbox Card ───────────────────────────────────────── */
.am-memo-card {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 14px 16px;
    border-radius: 14px;
    border: 1px solid rgba(255,255,255,0.06);
    background: rgba(30, 32, 53, 0.5);
    transition: all 0.2s;
    position: relative;
    overflow: hidden;
    text-decoration: none;
}
.am-memo-card:hover {
    background: rgba(40, 43, 70, 0.7);
    border-color: rgba(99, 102, 241, 0.25);
    transform: translateX(2px);
    box-shadow: 0 0 0 1px rgba(99,102,241,0.1), 0 4px 20px rgba(0,0,0,0.2);
}
.am-memo-card--critical { border-left-color: rgba(244,63,94,0.4) !important; }
.am-memo-card--high { border-left-color: rgba(245,158,11,0.3) !important; }

.am-urgency-bar {
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 3px;
    border-radius: 14px 0 0 14px;
}

/* ─── Recent Actions ────────────────────────────────────────── */
.am-recent-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 10px 14px;
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,0.05);
    background: rgba(30, 32, 53, 0.4);
    transition: all 0.2s;
    text-decoration: none;
}
.am-recent-item:hover {
    background: rgba(40,43,70,0.6);
    border-color: rgba(99,102,241,0.2);
}

/* ─── Empty State ───────────────────────────────────────────── */
.am-empty-state {
    padding: 40px 24px;
    text-align: center;
    border: 1px dashed rgba(255,255,255,0.08);
    border-radius: 16px;
    background: rgba(15, 15, 25, 0.3);
}
.am-empty-icon {
    width: 52px; height: 52px;
    border-radius: 16px;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto;
    background: rgba(100, 116, 139, 0.1);
    border: 1px solid rgba(255,255,255,0.06);
    color: #64748b;
}
.am-empty-icon--emerald {
    background: rgba(16, 185, 129, 0.1);
    border-color: rgba(16,185,129,0.2);
    color: #34d399;
}

/* ════════════════════════════════════════════════════════════
   LIGHT MODE OVERRIDES
   html.theme-light ditambahkan ke <html> oleh useTheme.js
   ════════════════════════════════════════════════════════════ */

/* Hero */
html.theme-light .am-hero {
    background: linear-gradient(135deg, #eef2ff 0%, #f0f4ff 50%, #eef2ff 100%);
    border-color: rgba(99, 102, 241, 0.25);
}
html.theme-light .am-hero-glow {
    background: radial-gradient(circle, rgba(99,102,241,0.1) 0%, transparent 70%);
}
html.theme-light .am-hero-grid {
    background-image: linear-gradient(rgba(99,102,241,0.05) 1px, transparent 1px),
                      linear-gradient(90deg, rgba(99,102,241,0.05) 1px, transparent 1px);
}
html.theme-light .am-clock-pill {
    background: rgba(255,255,255,0.85);
    border-color: rgba(99, 102, 241, 0.2);
}
html.theme-light .am-clock-pill .text-slate-300 {
    color: #1e293b !important;
}
html.theme-light .am-clock-pill .text-slate-500 {
    color: #64748b !important;
}

/* Quick Buttons */
html.theme-light .am-quick-btn--primary {
    background: rgba(99, 102, 241, 0.12);
    border-color: rgba(99, 102, 241, 0.35);
    color: #4f46e5;
}
html.theme-light .am-quick-btn--primary:hover {
    background: rgba(99, 102, 241, 0.2);
    color: #3730a3;
}
html.theme-light .am-quick-btn--ghost {
    background: rgba(15, 23, 42, 0.05);
    border-color: rgba(15, 23, 42, 0.12);
    color: #475569;
}
html.theme-light .am-quick-btn--ghost:hover {
    background: rgba(15, 23, 42, 0.09);
    color: #1e293b;
}

/* Stat Cards */
html.theme-light .am-stat-card {
    background: #ffffff;
    border-color: rgba(15, 23, 42, 0.08);
    box-shadow: 0 1px 4px rgba(0,0,0,0.06), 0 4px 12px rgba(0,0,0,0.04);
}
html.theme-light .am-stat-card--amber:hover { border-color: rgba(245,158,11,0.35); }
html.theme-light .am-stat-card--emerald:hover { border-color: rgba(16,185,129,0.35); }
html.theme-light .am-stat-card--rose:hover { border-color: rgba(244,63,94,0.35); }

html.theme-light .am-stat-icon--amber { background: rgba(245,158,11,0.08); border-color: rgba(245,158,11,0.25); }
html.theme-light .am-stat-icon--emerald { background: rgba(16,185,129,0.08); border-color: rgba(16,185,129,0.25); }
html.theme-light .am-stat-icon--rose { background: rgba(244,63,94,0.08); border-color: rgba(244,63,94,0.25); }

html.theme-light .am-stat-label { color: #94a3b8; }

/* Memo Inbox Cards */
html.theme-light .am-memo-card {
    background: #ffffff;
    border-color: rgba(15, 23, 42, 0.08);
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
html.theme-light .am-memo-card:hover {
    background: #f8faff;
    border-color: rgba(99, 102, 241, 0.3);
    box-shadow: 0 0 0 1px rgba(99,102,241,0.15), 0 4px 16px rgba(99,102,241,0.08);
}
html.theme-light .am-memo-card--critical { border-left-color: rgba(244,63,94,0.5) !important; }
html.theme-light .am-memo-card--high { border-left-color: rgba(245,158,11,0.5) !important; }

/* Text colors inside memo cards */
html.theme-light .am-memo-card .text-slate-400 { color: #64748b !important; }
html.theme-light .am-memo-card .text-slate-500 { color: #94a3b8 !important; }
html.theme-light .am-memo-card .text-white { color: #0f172a !important; }
html.theme-light .am-memo-card .text-slate-300 { color: #334155 !important; }
html.theme-light .am-memo-card .text-slate-200 { color: #1e293b !important; }

/* Recent Actions Items */
html.theme-light .am-recent-item {
    background: #ffffff;
    border-color: rgba(15, 23, 42, 0.08);
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
html.theme-light .am-recent-item:hover {
    background: #f8faff;
    border-color: rgba(99, 102, 241, 0.2);
}
html.theme-light .am-recent-item .text-slate-200 { color: #1e293b !important; }
html.theme-light .am-recent-item .text-slate-500 { color: #64748b !important; }
html.theme-light .am-recent-item .text-slate-600 { color: #94a3b8 !important; }
html.theme-light .am-recent-item .text-slate-600 { color: #cbd5e1 !important; }
html.theme-light .am-recent-item svg { color: #cbd5e1 !important; }
html.theme-light .am-recent-item:hover svg { color: #6366f1 !important; }

/* Empty State */
html.theme-light .am-empty-state {
    background: rgba(241, 245, 249, 0.7);
    border-color: rgba(15, 23, 42, 0.1);
}
html.theme-light .am-empty-icon {
    background: rgba(100, 116, 139, 0.08);
    border-color: rgba(15, 23, 42, 0.1);
    color: #94a3b8;
}
html.theme-light .am-empty-icon--emerald {
    background: rgba(16, 185, 129, 0.08);
    border-color: rgba(16,185,129,0.2);
    color: #10b981;
}

/* Section headings & labels */
html.theme-light .am-hero h1,
html.theme-light .am-hero p { color: #0f172a; }
html.theme-light .am-hero .text-indigo-300 { color: #4f46e5 !important; }
html.theme-light .am-hero .text-slate-400 { color: #64748b !important; }
html.theme-light .am-hero .text-indigo-300\/60 { color: rgba(79,70,229,0.7) !important; }
</style>
