<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import {
    AppstoreOutlined,
    BankOutlined,
    ClockCircleOutlined,
    EnvironmentOutlined,
    FileTextOutlined,
    InboxOutlined,
    TeamOutlined,
} from '@ant-design/icons-vue';

const props = defineProps({
    stats: Object,
    submissionStats: { type: Object, default: () => ({}) },
    recentMemos: { type: Array, default: () => [] },
    baStats: { type: Object, default: () => ({}) },
    recentBA: { type: Array, default: () => [] },
    formPengajuanStats: { type: Object, default: () => ({}) },
    recentFormPengajuans: { type: Array, default: () => [] },
    activity: Array,
    chartData: Object,
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

    const formattedDate = computed(() => new Intl.DateTimeFormat('id-ID', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
    }).format(now.value));

    const formattedTime = computed(() => now.value.toLocaleTimeString('id-ID', {
        hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false,
    }) + ' WIB');

    const searchQuery = ref('');
    const statusFilter = ref('all');
    const documentTypeFilter = ref('all');
    const chartPeriod = ref('year');

    const chartPeriodOptions = [
        { label: 'Week', value: 'week' },
        { label: 'Month', value: 'month' },
        { label: 'Year', value: 'year' },
    ];

    const statusConfig = {
        draft: { label: 'Draft' },
        submitted: { label: 'Menunggu AM' },
        approved: { label: 'Disetujui' },
        rejected: { label: 'Ditolak' },
    };
    const formPengajuanTemplateLabels = {
        form_permohonan_pinjaman: 'Form Permohonan Pinjaman (FPP)',
        form_ijin_tidak_masuk_kerja: 'Form Ijin Tidak Masuk Kerja (FITMK)',
    };
    const isFormPengajuan = (document) => document._documentType === 'form_pengajuan';

    const kpiCards = computed(() => [
        { title: 'Pengajuan Masuk', value: props.submissionStats?.received ?? 0, note: 'Memo + BA + Form Pengajuan', icon: InboxOutlined, color: '#818cf8', href: '#' },
        { title: 'Menunggu AM', value: props.submissionStats?.submitted ?? 0, note: 'Perlu review AM', icon: ClockCircleOutlined, color: '#fbbf24', href: '#' },
        { title: 'Template Form', value: props.stats?.total_templates ?? 0, note: 'Skema aktif', icon: AppstoreOutlined, color: '#34d399', href: route('admin.templates.index') },
        { title: 'Pengguna', value: props.stats?.total_users ?? 0, note: `${props.stats?.total_branches ?? 0} Cabang · ${props.stats?.total_areas ?? 0} Area`, icon: TeamOutlined, color: '#22d3ee', href: route('admin.users.index') },
    ]);

    const formatDate = (dateString) => {
        if (!dateString) return '-';
        return new Date(dateString).toLocaleDateString('id-ID', {
            day: '2-digit', month: 'short', year: 'numeric',
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


const documents = computed(() => [
    ...(props.recentMemos || []).map((memo) => ({ ...memo, _documentType: 'memo' })),
    ...(props.recentBA || []).map((ba) => ({ ...ba, _documentType: 'ba' })),
    ...(props.recentFormPengajuans || []).map((form) => ({ ...form, _documentType: 'form_pengajuan' })),
].sort((a, b) => new Date(b.created_at) - new Date(a.created_at)));

const documentColumns = [
    { title: 'Kode & Tanggal', key: 'code', width: 190 },
    { title: 'Perihal', key: 'subject', width: 250 },
    { title: 'Jenis', key: 'type', width: 130 },
    { title: 'Cabang & Pembuat', key: 'creator', width: 230 },
    { title: 'Status', key: 'status', width: 140 },
    { title: 'Aksi', key: 'action', align: 'center', width: 140 },
];

const filteredDocuments = computed(() => {
    let list = documents.value;

if (documentTypeFilter.value === 'form_pengajuan') {
    list = list.filter(isFormPengajuan);
} else if (documentTypeFilter.value !== 'all') {
    list = list.filter((document) => document._documentType === documentTypeFilter.value && !isFormPengajuan(document));
}
    
    if (statusFilter.value !== 'all') {
        list = list.filter((document) => document.status === statusFilter.value);
    }
    
    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase().trim();
        list = list.filter((document) =>
            (document.code && document.code.toLowerCase().includes(q)) ||
            (document.title && document.title.toLowerCase().includes(q)) ||
            (document.creator?.name && document.creator.name.toLowerCase().includes(q)) ||
            (document.branch?.name && document.branch.name.toLowerCase().includes(q)) ||
            (document.template?.name && document.template.name.toLowerCase().includes(q)) ||
            (isFormPengajuan(document) && `form pengajuan ${formPengajuanTemplateLabels[document.template] || ''}`.toLowerCase().includes(q)) ||
            (isFormPengajuan(document)
                ? false
                : (document._documentType === 'ba' ? 'berita acara ba' : 'memo').includes(q))
        );
    }
    
    return list;
});

const totalMemosCount = computed(() => props.submissionStats?.total ?? props.stats?.total_memos ?? props.recentMemos?.length ?? 0);
const approvedCount = computed(() => props.submissionStats?.approved ?? props.stats?.approved_memos ?? props.recentMemos?.filter((memo) => memo.status === 'approved').length ?? 0);
const pendingCount = computed(() => props.submissionStats?.submitted ?? props.stats?.pending_approvals ?? props.recentMemos?.filter((memo) => memo.status === 'submitted').length ?? 0);
const draftCount = computed(() => props.submissionStats?.draft ?? props.stats?.draft_memos ?? props.recentMemos?.filter((memo) => memo.status === 'draft').length ?? 0);
const rejectedCount = computed(() => props.submissionStats?.rejected ?? props.stats?.rejected_memos ?? props.recentMemos?.filter((memo) => memo.status === 'rejected').length ?? 0);

const approvedPercent = computed(() => totalMemosCount.value > 0 ? Math.round((approvedCount.value / totalMemosCount.value) * 100) : 0);
const pendingPercent = computed(() => totalMemosCount.value > 0 ? Math.round((pendingCount.value / totalMemosCount.value) * 100) : 0);
const draftPercent = computed(() => totalMemosCount.value > 0 ? Math.round((draftCount.value / totalMemosCount.value) * 100) : 0);
const rejectedPercent = computed(() => totalMemosCount.value > 0 ? Math.round((rejectedCount.value / totalMemosCount.value) * 100) : 0);

const donutSegments = computed(() => {
    const radius = 42;
    const circumference = 2 * Math.PI * radius;
    const statuses = [
        { label: 'Approved', value: approvedCount.value, color: '#2563eb', strokeWidth: 13.5 },
        { label: 'Pending', value: pendingCount.value, color: '#f2b323', strokeWidth: 10.5 },
        { label: 'Rejected', value: rejectedCount.value, color: '#f97360', strokeWidth: 8 },
    ];
    let offset = 0;

    return statuses.map((status) => {
        const length = totalMemosCount.value > 0
            ? (status.value / totalMemosCount.value) * circumference
            : 0;
        const segmentGap = Math.min(4, length * 0.2);
        const visibleLength = Math.max(length - segmentGap, 0);
        const segment = {
            ...status,
            dasharray: `${visibleLength} ${circumference - visibleLength}`,
            dashoffset: -offset,
        };
        offset += length;

        return segment;
    });
});

const activityDays = computed(() => {
    return (props.activity || []).map((item) => ({
        label: item.label,
        date: item.date,
        count: Number(item.count) || 0,
    }));
});

const chartPoints = computed(() => props.chartData?.[chartPeriod.value] || []);

const chartMax = computed(() => Math.max(...chartPoints.value.flatMap((point) => [point.memo, point.ba, point.formPengajuan].map((document) =>
    (Number(document?.approved) || 0) + (Number(document?.submitted) || 0) + (Number(document?.rejected) || 0)
)), 1));

const chartTicks = computed(() => {
    const step = Math.max(Math.ceil(chartMax.value / 4), 1);
    return [step * 4, step * 3, step * 2, step, 0];
});

const createDocumentBar = (counts = {}, type, shortLabel) => {
    const approved = Number(counts.approved) || 0;
    const submitted = Number(counts.submitted) || 0;
    const rejected = Number(counts.rejected) || 0;

    return {
        type,
        shortLabel,
        total: approved + submitted + rejected,
        approved,
        submitted,
        rejected,
        approvedHeight: (approved / chartMax.value) * 100,
        submittedHeight: (submitted / chartMax.value) * 100,
        rejectedHeight: (rejected / chartMax.value) * 100,
    };
};

const chartBars = computed(() => chartPoints.value.map((point) => ({
    label: point.label,
    documents: [
        createDocumentBar(point.memo, 'Memo', 'M'),
        createDocumentBar(point.ba, 'Berita Acara', 'BA'),
        createDocumentBar(point.formPengajuan, 'Form Pengajuan', 'FP'),
    ],
})));

const statusChartData = computed(() => [
    { status: 'Disetujui', value: approvedCount.value },
    { status: 'Menunggu', value: pendingCount.value },
    { status: 'Draft', value: draftCount.value },
    { status: 'Ditolak', value: rejectedCount.value },
]);

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
                    <span>{{ greeting.icon }} {{ greeting.text }}, <span class="dashboard-greeting-name">{{ userDisplayName }}</span>!</span>
                </h1>
                <p class="text-base font-medium text-slate-400 tracking-tight">Monitoring pengajuan Memo, Berita Acara, dan Form Pengajuan serta tata kelola master data sistem.</p>
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
        <a-row :gutter="[16, 16]" class="mb-8">
            <a-col v-for="card in kpiCards" :key="card.title" flex="1 1 200px">
                <Link :href="card.href" class="block h-full">
                    <a-card :bordered="false" size="small" hoverable class="dashboard-ant-card h-full">
                        <div class="flex items-start justify-between gap-3">
                            <a-statistic :title="card.title" :value="card.value" :value-style="{ color: '#60a5fa', fontSize: '28px', fontWeight: 700 }" />
                            <component :is="card.icon" :style="{ color: card.color, fontSize: '20px' }" />
                        </div>
                        <a-typography-text type="secondary" class="mt-3 block text-xs">{{ card.note }}</a-typography-text>
                    </a-card>
                </Link>
            </a-col>
        </a-row>

        <!-- Activity Overview -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <a-card :bordered="false" class="lg:col-span-2 dashboard-ant-card">
                <div class="flex items-start justify-between gap-4 mb-5">
                    <div>
                        <h2 class="text-base font-bold text-white tracking-tight">Aktivitas Pengajuan</h2>
                        <a-space size="small" wrap>
                            <a-badge color="#4f6ee8" text="Disetujui" />
                            <a-badge color="#f2ac3d" text="Menunggu" />
                            <a-badge color="#28b886" text="Ditolak" />
                            <a-tag color="blue">M · Memo</a-tag>
                            <a-tag color="cyan">BA · Berita Acara</a-tag>
                            <a-tag color="green">FP · Form Pengajuan</a-tag>
                        </a-space>
                    </div>
                    <a-segmented v-model:value="chartPeriod" :options="chartPeriodOptions" aria-label="Periode grafik" />
                </div>

                <div class="dashboard-stacked-chart">
                    <div class="dashboard-chart-axis">
                        <span v-for="tick in chartTicks" :key="tick">{{ tick }}</span>
                    </div>
                    <div class="dashboard-chart-plot">
                        <div v-for="bar in chartBars" :key="bar.label" class="dashboard-period-group">
                            <div class="dashboard-period-bars">
                                <div
                                    v-for="documentBar in bar.documents"
                                    :key="documentBar.type"
                                    class="dashboard-document-column"
                                    :title="`${bar.label} ${documentBar.type}: ${documentBar.total} total, ${documentBar.approved} disetujui, ${documentBar.submitted} menunggu, ${documentBar.rejected} ditolak`"
                                >
                                    <div class="dashboard-stacked-track">
                                        <div class="dashboard-stacked-segment approved" :style="{ height: `${documentBar.approvedHeight}%` }"></div>
                                        <div class="dashboard-stacked-segment submitted" :style="{ height: `${documentBar.submittedHeight}%` }"></div>
                                        <div class="dashboard-stacked-segment rejected" :style="{ height: `${documentBar.rejectedHeight}%` }"></div>
                                    </div>
                                    <span class="dashboard-document-type-label">{{ documentBar.shortLabel }}</span>
                                </div>
                            </div>
                            <span class="dashboard-period-label">{{ bar.label }}</span>
                        </div>
                    </div>
                </div>
            </a-card>

            <a-card :bordered="false" class="dashboard-ant-card">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h2 class="text-base font-bold text-white tracking-tight">Approval Status</h2>
                        <p class="text-xs text-slate-400 mt-1">Ringkasan status Memo, Berita Acara, dan Form Pengajuan.</p>
                    </div>
                </div>

                <div class="dashboard-approval-summary">
                    <div class="dashboard-status-donut">
                        <svg class="dashboard-status-svg" viewBox="0 0 100 100" aria-label="Grafik status persetujuan">
                            <circle class="dashboard-status-track" cx="50" cy="50" r="42" />
                            <circle
                                v-for="segment in donutSegments"
                                :key="segment.label"
                                class="dashboard-status-segment"
                                cx="50"
                                cy="50"
                                r="42"
                                :stroke="segment.color"
                                :style="{ strokeWidth: `${segment.strokeWidth}px` }"
                                :stroke-dasharray="segment.dasharray"
                                :stroke-dashoffset="segment.dashoffset"
                            />
                        </svg>
                        <div class="dashboard-status-hole">
                            <strong>{{ approvedPercent }}%</strong>
                            <span>selesai</span>
                        </div>
                    </div>

                    <div class="dashboard-approval-legend">
                        <div class="dashboard-legend-row"><span><i class="dashboard-legend-dot dashboard-legend-approved"></i>Approved</span><strong>{{ approvedCount }}</strong></div>
                        <div class="dashboard-legend-row"><span><i class="dashboard-legend-dot dashboard-legend-pending"></i>Pending</span><strong>{{ pendingCount }}</strong></div>
                        <div class="dashboard-legend-row"><span><i class="dashboard-legend-dot dashboard-legend-rejected"></i>Rejected</span><strong>{{ rejectedCount }}</strong></div>
                    </div>
                </div>

                <div class="dashboard-approval-footer">{{ pendingCount }} require your action</div>
            </a-card>
        </div>

        <div class="w-full">
                <div class="bg-slate-800/50 border border-white/5 rounded-2xl p-6 shadow-sm">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-5 border-b border-white/5">
                        <div>
                            <h2 class="text-lg font-bold text-white tracking-tight">Pengajuan Terkini</h2>
                            <p class="text-xs text-slate-400 mt-0.5">Monitoring Memo, Berita Acara, dan Form Pengajuan dari seluruh cabang.</p>
                        </div>
                        <div class="relative w-full md:w-64">
                            <input v-model="searchQuery" type="text" placeholder="Cari kode, judul, cabang, Memo/BA..." class="w-full bg-slate-900/60 border border-white/10 rounded-xl pl-9 pr-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" />
                            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 py-3 border-b border-white/5 text-xs">
                        <div class="flex items-center gap-1.5">
                            <span class="text-slate-500">Jenis</span>
                            <a-button v-for="type in [{ value: 'all', label: 'Semua' }, { value: 'memo', label: 'Memo' }, { value: 'ba', label: 'BA' }, { value: 'form_pengajuan', label: 'Form Pengajuan' }]" :key="type.value" size="small" :type="documentTypeFilter === type.value ? 'primary' : 'default'" @click="documentTypeFilter = type.value">{{ type.label }}</a-button>
                        </div>
                        <div class="flex flex-wrap items-center gap-1.5">
                            <a-button size="small" :type="statusFilter === 'all' ? 'primary' : 'default'" @click="statusFilter = 'all'">Semua ({{ documents.length }})</a-button>
                            <a-button size="small" :type="statusFilter === 'submitted' ? 'primary' : 'default'" @click="statusFilter = 'submitted'">Menunggu Review ({{ documents.filter(document => document.status === 'submitted').length }})</a-button>
                            <a-button size="small" :type="statusFilter === 'approved' ? 'primary' : 'default'" @click="statusFilter = 'approved'">Disetujui ({{ documents.filter(document => document.status === 'approved').length }})</a-button>
                            <a-button size="small" :type="statusFilter === 'draft' ? 'primary' : 'default'" @click="statusFilter = 'draft'">Draft ({{ documents.filter(document => document.status === 'draft').length }})</a-button>
                            <a-button size="small" :type="statusFilter === 'rejected' ? 'primary' : 'default'" @click="statusFilter = 'rejected'">Ditolak ({{ documents.filter(document => document.status === 'rejected').length }})</a-button>
                        </div>
                    </div>

                    <!-- Enterprise Table -->
                    <a-table
                        class="admin-document-table"
                        :data-source="filteredDocuments"
                        :columns="documentColumns"
                        :row-key="document => `${document._documentType}-${document.id}`"
                        :pagination="{ pageSize: 10, showSizeChanger: false, hideOnSinglePage: true }"
                        :scroll="{ x: 1080 }"
                        size="small"
                    >
                        <template #bodyCell="{ column, record }">
                            <template v-if="column.key === 'code'">
                                <div class="flex flex-col whitespace-nowrap">
                                    <span class="font-mono text-xs font-semibold text-indigo-400">{{ record.code }}</span>
                                    <span class="mt-0.5 text-[11px] text-slate-500">{{ formatDate(record.created_at) }} · {{ formatTime(record.created_at) }}</span>
                                </div>
                            </template>
                            <template v-else-if="column.key === 'subject'">
                                <div class="flex min-w-36 flex-col">
                                    <span class="line-clamp-1 text-sm font-medium text-white">{{ record.title }}</span>
                                    <span v-if="record.template?.name" class="mt-0.5 line-clamp-1 text-[11px] text-slate-400">{{ record.template.name }}</span>
                                    <span v-else-if="isFormPengajuan(record)" class="mt-0.5 line-clamp-1 text-[11px] text-slate-400">{{ formPengajuanTemplateLabels[record.template] }}</span>
                                </div>
                            </template>
                            <template v-else-if="column.key === 'type'">
                                <a-tag :color="isFormPengajuan(record) ? 'green' : record._documentType === 'ba' ? 'cyan' : 'blue'">{{ isFormPengajuan(record) ? 'Form Pengajuan' : record._documentType === 'ba' ? 'Berita Acara' : 'Memo' }}</a-tag>
                            </template>
                            <template v-else-if="column.key === 'creator'">
                                <div class="flex min-w-0 flex-col">
                                    <span class="truncate text-xs font-medium text-slate-200">{{ record.creator?.name || '-' }}</span>
                                    <span class="truncate text-[11px] text-slate-400">{{ record.branch?.name || 'Kantor Cabang' }}</span>
                                </div>
                            </template>
                            <template v-else-if="column.key === 'status'">
                                <a-tag :color="{ draft: 'default', submitted: 'processing', approved: 'success', rejected: 'error' }[record.status] || 'default'">{{ statusConfig[record.status]?.label || record.status }}</a-tag>
                            </template>
                            <template v-else-if="column.key === 'action'">
                                <Link :href="route(isFormPengajuan(record) ? 'approvals.form-pengajuan.history' : record._documentType === 'ba' ? 'approvals.ba.history' : 'memos.show', record.id)">
                                    <a-button type="primary" ghost size="small">{{ isFormPengajuan(record) ? 'Lihat Form' : record._documentType === 'ba' ? 'Lihat BA' : 'Lihat Memo' }}</a-button>
                                </Link>
                            </template>
                        </template>
                        <template #emptyText>
                            <a-empty description="Tidak ada dokumen yang sesuai">
                                <a-button v-if="searchQuery || statusFilter !== 'all' || documentTypeFilter !== 'all'" @click="searchQuery = ''; statusFilter = 'all'; documentTypeFilter = 'all'">
                                    Reset Filter &amp; Pencarian
                                </a-button>
                            </a-empty>
                        </template>
                    </a-table>

                    <div class="pt-4 mt-2 border-t border-white/5 flex items-center justify-between text-xs text-slate-500">
                        <span>Menampilkan {{ filteredDocuments.length }} dari {{ documents.length }} dokumen terkini</span>
                        <span class="hidden sm:inline">Data diperbarui secara otomatis</span>
                    </div>
                </div>
        </div>
    </AuthenticatedLayout>
</template>
