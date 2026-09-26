<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, h, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import {
    DownloadOutlined,
    FilterOutlined,
    ReloadOutlined,
    FileTextOutlined,
    EyeOutlined,
} from '@ant-design/icons-vue';

const props = defineProps({
    pendingMemos: { type: Array,  default: () => [] },
    recentActions: { type: Array,  default: () => [] },
    stats:         { type: Object, default: () => ({ pending: 0, approved: 0, rejected: 0 }) },
    // Report mode
    reportMode:  { type: Boolean, default: false },
    gaReport:    { type: Object,  default: null },
    gaTemplates: { type: Array,   default: () => [] },
    branches:    { type: Array,   default: () => [] },
    filters:     { type: Object,  default: () => ({}) },
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const greeting = computed(() => {
    const h = new Date().getHours();
    if (h >= 4 && h < 11) return 'Selamat Pagi';
    if (h >= 11 && h < 15) return 'Selamat Siang';
    if (h >= 15 && h < 18) return 'Selamat Sore';
    return 'Selamat Malam';
});

const userDisplayName = computed(() => (user.value?.name || 'Area Manager').split('(')[0].trim());

const currentTime = ref(new Date());

let timeInterval = null;

onMounted(() => {
    timeInterval = setInterval(() => {
        currentTime.value = new Date();
    }, 1000);
});

onBeforeUnmount(() => {
    if (timeInterval) {
        clearInterval(timeInterval);
    }
});

const formattedDate = computed(() => new Intl.DateTimeFormat('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
}).format(currentTime.value));

const currentClock = computed(() => new Intl.DateTimeFormat('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: false,
}).format(currentTime.value));

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

// ─── GA Report ────────────────────────────────────────────────────────────
const showReport = ref(props.reportMode);

// Local filter state (initialised from server-side filters prop)
const f = reactive({
    status:      props.filters?.status      ?? undefined,
    template_id: props.filters?.template_id ?? undefined,
    branch_id:   props.filters?.branch_id   ?? undefined,
    date_from:   props.filters?.date_from   ?? '',
    date_to:     props.filters?.date_to     ?? '',
});

// DateRangePicker value [dayjs, dayjs] | null

const dateRange = ref(
    props.filters?.date_from && props.filters?.date_to ? [
        props.filters.date_from,
        props.filters.date_to,
    ] : null
);

function onDateRangeChange(_, dateStrings) {
    f.date_from = dateStrings?.[0] ?? '';
    f.date_to   = dateStrings?.[1] ?? '';
}

function applyFilters() {
    router.get(route('reports.ga'), cleanFilters(), { preserveScroll: true });
}

function resetFilters() {
    f.template_id = undefined; f.branch_id = undefined;
    f.date_from = ''; f.date_to = '';
    dateRange.value = null;
    router.get(route('reports.ga'), {}, { preserveScroll: true });
}

function cleanFilters() {
    return Object.fromEntries(
        Object.entries(f).filter(([, v]) => v !== '' && v !== undefined && v !== null)
    );
}

// Build export URL preserving current filters
const exportUrl = computed(() => {
    const params = new URLSearchParams(cleanFilters()).toString();
    return route('reports.ga.export') + (params ? '?' + params : '');
});

// ─── Ant Design Table columns ────────────────────────────────────────────
/**
 * Struktur field_values:
 * {
 *   meta: { perihal, kepada, ... },
 *   pengantar: '...',
 *   items: [ { nama_barang, jumlah, keterangan, ... }, ... ]
 * }
 *
 * Fungsi ini mengambil nilai dari SEMUA items dan menggabungkannya.
 * Jika ada lebih dari 1 item, hasil dipisah dengan " | ".
 */
const fieldVal = (fv, ...keys) => {
    if (!fv || typeof fv !== 'object') return '-';
    const items = Array.isArray(fv.items) && fv.items.length > 0 ? fv.items : [fv];
    const results = [];
    for (const item of items) {
        if (!item || typeof item !== 'object') continue;
        let found = false;
        for (const k of keys) {
            const val = item[k];
            if (val === undefined || val === null || val === '') continue;
            if (Array.isArray(val)) {
                const text = val.map(row => typeof row === 'object' && row !== null ? Object.values(row).filter(Boolean).join(', ') : String(row)).filter(Boolean).join('; ');
                if (text) results.push(text);
            } else {
                results.push(String(val));
            }
            found = true;
            break;
        }
        if (!found) {
            for (const key in item) {
                const val = item[key];
                if (Array.isArray(val)) {
                    const tableVals = [];
                    for (const row of val) {
                        if (row && typeof row === 'object') {
                            for (const k of keys) {
                                if (row[k] !== undefined && row[k] !== null && row[k] !== '') {
                                    tableVals.push(String(row[k]));
                                    break;
                                }
                            }
                        }
                    }
                    if (tableVals.length > 0) {
                        results.push(tableVals.join(', '));
                        found = true;
                        break;
                    }
                }
            }
        }
    }
    return results.length > 0 ? results.join(' \u2022 ') : '-';
};

const sumQty = (fv, ...keys) => {
    if (!fv || typeof fv !== 'object') return '-';
    const items = Array.isArray(fv.items) && fv.items.length > 0 ? fv.items : [fv];
    let total = 0;
    let hasValue = false;
    for (const item of items) {
        if (!item || typeof item !== 'object') continue;
        let found = false;
        for (const k of keys) {
            const val = item[k];
            if (val === undefined || val === null || val === '') continue;
            const num = parseFloat(String(val).replace(/[^\d.-]/g, ''));
            if (!isNaN(num)) {
                total += num;
                hasValue = true;
            }
            found = true;
            break;
        }
        if (!found) {
            for (const key in item) {
                const val = item[key];
                if (Array.isArray(val)) {
                    for (const row of val) {
                        if (row && typeof row === 'object') {
                            for (const k of keys) {
                                if (row[k] !== undefined && row[k] !== null && row[k] !== '') {
                                    const num = parseFloat(String(row[k]).replace(/[^\d.-]/g, ''));
                                    if (!isNaN(num)) {
                                        total += num;
                                        hasValue = true;
                                    }
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    return hasValue ? String(total) : '-';
};

// ─── Export preview modal ─────────────────────────────────────────
const exportPreviewVisible = ref(false);

const activeFilterSummary = computed(() => {
    const parts = [];
    if (f.status) {
        const lbl = { submitted: 'Menunggu', approved: 'Disetujui', rejected: 'Ditolak', draft: 'Draft' }[f.status];
        parts.push(`Status: ${lbl}`);
    }
    if (f.template_id) {
        const t = props.gaTemplates.find(t => String(t.id) === String(f.template_id));
        if (t) parts.push(`Kategori: ${t.name}`);
    }
    if (f.branch_id) {
        const b = props.branches.find(b => String(b.id) === String(f.branch_id));
        if (b) parts.push(`Cabang: ${b.name}`);
    }
    if (f.date_from && f.date_to) parts.push(`Periode: ${f.date_from} s/d ${f.date_to}`);
    else if (f.date_from) parts.push(`Dari: ${f.date_from}`);
    else if (f.date_to) parts.push(`Sampai: ${f.date_to}`);
    return parts.length ? parts.join('  •  ') : 'Semua data (tanpa filter)';
});

function openExportPreview() {
    exportPreviewVisible.value = true;
}

function doExport() {
    exportPreviewVisible.value = false;
    window.location.href = exportUrl.value;
}

const statusColorMap = { submitted: 'gold', approved: 'green', rejected: 'red', draft: 'default' };
const statusLabelMap = { submitted: 'Menunggu', approved: 'Disetujui', rejected: 'Ditolak', draft: 'Draft' };

const tableColumns = [
    { title: 'PIC',               dataIndex: 'pic',       key: 'pic',       width: 60 },
    { title: 'AM',                dataIndex: 'am',        key: 'am',        width: 160 },
    { title: 'Tgl Pengajuan',     dataIndex: 'submitted', key: 'submitted', width: 120 },
    { title: 'KC (Pembuat)',      dataIndex: 'creator',   key: 'creator',   width: 140 },
    { title: 'Cabang',            dataIndex: 'branch',    key: 'branch',    width: 120 },
    { title: 'Kategori',          dataIndex: 'kategori',  key: 'kategori',  width: 140, ellipsis: true },
    { title: 'Rincian Permasalahan / Permintaan', dataIndex: 'rincian', key: 'rincian', width: 200, ellipsis: true },
    { title: 'QTY',               dataIndex: 'qty',       key: 'qty',       width: 70,  align: 'center' },
    { title: 'Keterangan',        dataIndex: 'keterangan',key: 'keterangan',width: 160, ellipsis: true },
    { title: 'Status',            dataIndex: 'status',    key: 'status',    width: 100, align: 'center' },
    { title: 'Tgl Penyerahan ke GA', dataIndex: 'serahDate', key: 'serahDate', width: 140 },
];

const tableData = computed(() => {
    if (!props.gaReport?.data) return [];
    return props.gaReport.data.map((memo) => ({
        key: memo.id,
        pic: 'GA',
        am: user.value?.name?.split('(')[0].trim() ?? '-',
        submitted: formatDate(memo.submitted_at),
        creator: memo.creator?.name ?? '-',
        branch: memo.branch?.name ?? '-',
        kategori: memo.template?.name ?? '-',
        rincian: fieldVal(memo.field_values, 'nama_barang', 'permintaan'),
        qty: sumQty(memo.field_values, 'jumlah', 'qty'),
        keterangan: fieldVal(memo.field_values, 'keterangan'),
        status: memo.status,
        serahDate: memo.status === 'approved' ? formatDate(memo.updated_at) : '-',
        _raw: memo,
    }));
});

const pagination = computed(() => {
    if (!props.gaReport) return false;
    return {
        current: props.gaReport.current_page,
        pageSize: props.gaReport.per_page,
        total: props.gaReport.total,
        showSizeChanger: false,
        showTotal: (total, range) => `${range[0]}-${range[1]} dari ${total} memo`,
        onChange: (page) => {
            router.get(route('reports.ga'), { ...cleanFilters(), page }, { preserveScroll: true });
        },
    };
});
</script>

<template>
    <Head :title="reportMode ? 'Report GA' : 'Dashboard Area Manager'" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-base font-bold text-white tracking-tight">
                {{ reportMode ? 'Laporan Pengajuan Memo GA' : 'Dashboard Area Manager' }}
            </h2>
        </template>

        <!-- Dashboard sections: hidden in report mode -->
        <template v-if="!reportMode">
        <a-card :bordered="false" class="mb-8 am-hero-card">
            <a-row type="flex" justify="space-between" align="bottom" :gutter="[24, 24]">
                <a-col :xs="24" :lg="16">
                    <h1 class="am-hero-title">{{ greeting }}, {{ userDisplayName }}.</h1>
                    <p class="am-hero-desc">Tinjau memo dari cabang, ambil keputusan, dan jaga proses operasional wilayah tetap bergerak.</p>
                </a-col>
                <a-col :xs="24" :lg="8" style="display: flex; justify-content: flex-end; align-items: flex-end;">
                    <div class="am-clock-pill" style="min-width: 220px; text-align: right;">
                        <div class="am-hero-date">{{ formattedDate }}</div>
                        <div class="mt-2 text-lg font-semibold text-white" style="letter-spacing: 0.04em;">{{ currentClock }}</div>
                    </div>
                </a-col>
            </a-row>

            <a-row :gutter="[16, 16]" style="margin-top: 24px;">
                <a-col :xs="24" :sm="8">
                    <Link :href="route('approvals.pending')">
                        <a-card :bordered="false" size="small" hoverable class="am-stat-box am-stat-box-pending">
                            <div class="am-stat-title">Perlu ditinjau</div>
                            <div class="am-stat-number">{{ stats.pending ?? 0 }}</div>
                            <div class="am-stat-desc">Antrean persetujuan</div>
                        </a-card>
                    </Link>
                </a-col>
                <a-col :xs="24" :sm="8">
                    <Link :href="route('approvals.pending', { tab: 'approved' })">
                        <a-card :bordered="false" size="small" hoverable class="am-stat-box am-stat-box-approved">
                            <div class="am-stat-title">Disetujui</div>
                            <div class="am-stat-number">{{ stats.approved ?? 0 }}</div>
                            <div class="am-stat-desc">Keputusan selesai</div>
                        </a-card>
                    </Link>
                </a-col>
                <a-col :xs="24" :sm="8">
                    <Link :href="route('approvals.pending', { tab: 'rejected' })">
                        <a-card :bordered="false" size="small" hoverable class="am-stat-box am-stat-box-rejected">
                            <div class="am-stat-title">Perlu revisi</div>
                            <div class="am-stat-number">{{ stats.rejected ?? 0 }}</div>
                            <div class="am-stat-desc">Dikembalikan ke cabang</div>
                        </a-card>
                    </Link>
                </a-col>
            </a-row>
        </a-card>

        <!-- ═══════════════════════════════════════════════════════
             MAIN CONTENT GRID: Pending (left) + Recent (right)
        ════════════════════════════════════════════════════════ -->
        <a-row :gutter="[24, 24]">

            <!-- ── LEFT: Pending Memo Inbox (3/5) ── -->
            <a-col :xs="24" :lg="15">
                <a-card title="Menunggu Persetujuan" :bordered="false" class="am-section-card">
                    <template #extra>
                        <Link v-if="pendingMemos.length > 0" :href="route('approvals.pending')">
                            <a-button type="link">Lihat Semua</a-button>
                        </Link>
                    </template>
                    
                    <a-empty
                        v-if="pendingMemos.length === 0"
                        description="Tidak ada memo yang menunggu persetujuan."
                    />
                    
                    <a-list v-else :data-source="pendingMemos" item-layout="horizontal">
                        <template #renderItem="{ item: memo }">
                            <a-list-item style="padding: 4px 0; border-bottom: none;">
                                <a-card hoverable class="am-item-card w-full" size="small">
                                    <Link :href="route('approvals.review', memo.id)" style="color: inherit; text-decoration: none;">
                                        <a-row type="flex" justify="space-between" align="middle">
                                            <a-col :span="18">
                                                <div style="margin-bottom: 8px;">
                                                    <div class="am-item-text" style="font-family: monospace; font-size: 11px;">
                                                        {{ memo.code || `#${memo.id}` }}
                                                    </div>
                                                    <div class="am-item-title" style="font-weight: 600; font-size: 14px; margin-top: 4px; margin-bottom: 4px;">
                                                        {{ memo.title }}
                                                    </div>
                                                </div>
                                                <a-space wrap>
                                                    <a-tag color="blue">{{ memo.template?.name || '-' }}</a-tag>
                                                    <a-tag>{{ memo.branch?.name || '-' }}</a-tag>
                                                    <a-tag v-if="getUrgency(memo.submitted_at) === 'critical'" color="error">Mendesak</a-tag>
                                                    <a-tag v-else-if="getUrgency(memo.submitted_at) === 'high'" color="warning">Segera</a-tag>
                                                    <a-tag v-else color="processing">Baru Masuk</a-tag>
                                                </a-space>
                                            </a-col>
                                            <a-col :span="6" style="text-align: right;">
                                                <a-avatar style="background-color: #6366f1; vertical-align: middle;">
                                                    {{ memo.creator?.name?.charAt(0)?.toUpperCase() || 'U' }}
                                                </a-avatar>
                                                <div style="margin-top: 8px;">
                                                    <span class="am-item-text" style="font-size: 11px;">
                                                        {{ formatRelative(memo.submitted_at) }}
                                                    </span>
                                                </div>
                                            </a-col>
                                        </a-row>
                                    </Link>
                                </a-card>
                            </a-list-item>
                        </template>
                    </a-list>
                </a-card>
            </a-col>

            <!-- ── RIGHT: Recent Actions (2/5) ── -->
            <a-col :xs="24" :lg="9">
                <a-card title="Riwayat Keputusan" :bordered="false" class="am-section-card">
                    <template #extra>
                        <span class="am-item-text" style="font-size: 11px;">10 terbaru</span>
                    </template>

                    <a-empty
                        v-if="recentActions.length === 0"
                        description="Belum ada riwayat keputusan."
                    />

                    <a-list v-else :data-source="recentActions" item-layout="horizontal">
                        <template #renderItem="{ item: memo }">
                            <a-list-item style="padding: 4px 0; border-bottom: none;">
                                <a-card hoverable class="am-item-card w-full" size="small">
                                    <Link :href="route('memos.show', memo.id)" style="color: inherit; text-decoration: none;">
                                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                            <div>
                                                <div class="am-item-title" style="font-weight: 600; font-size: 13px;">{{ memo.title }}</div>
                                                <div style="margin-top: 4px; font-size: 11px;">
                                                    <a-space split="·">
                                                        <span class="am-item-text">{{ memo.branch?.name }}</span>
                                                        <a-badge :status="memo.status === 'approved' ? 'success' : 'error'" :text="memo.status === 'approved' ? 'Disetujui' : 'Ditolak'" />
                                                    </a-space>
                                                </div>
                                            </div>
                                            <div style="text-align: right;">
                                                <span class="am-item-text" style="font-size: 11px;">
                                                    {{ formatDate(memo.updated_at) }}
                                                </span>
                                            </div>
                                        </div>
                                    </Link>
                                </a-card>
                            </a-list-item>
                        </template>
                    </a-list>
                </a-card>
            </a-col>
        </a-row>
        </template><!-- end v-if="!reportMode" -->

        <!-- ═══════════════════════════════════════════════════════
             GA REPORT SECTION – Ant Design Vue
        ════════════════════════════════════════════════════════ -->
        <section v-if="showReport" class="ga-report-section mt-10">

            <!-- ── Header Card ── -->
            <a-card class="ga-report-card" :bordered="false">
                <!-- Card title slot -->
                <template #title>
                    <a-space align="center" :size="12">
                        <span class="ga-report-icon-wrap">
                            <FileTextOutlined style="font-size:18px;color:#818cf8" />
                        </span>
                        <div>
                            <div class="ga-report-title">Laporan Pengajuan Memo GA</div>
                            <div class="ga-report-subtitle">Semua memo masuk divisi GA yang melalui persetujuan Anda</div>
                        </div>
                    </a-space>
                </template>
                <template #extra>
                    <a-button type="primary" :icon="h(EyeOutlined)" @click="openExportPreview">
                        Unduh CSV
                    </a-button>
                </template>

                <!-- ── Filter Panel ── -->
                <a-card class="ga-filter-card" :bordered="false">
                    <div class="ga-filter-grid grid grid-cols-1 md:grid-cols-3 gap-4">

                        <!-- Kategori -->
                        <div class="ga-filter-item">
                            <label class="ga-filter-label">Kategori</label>
                            <a-select
                                v-model:value="f.template_id"
                                placeholder="Semua Kategori"
                                allow-clear
                                style="width:100%"
                            >
                                <a-select-option v-for="t in gaTemplates" :key="t.id" :value="t.id">
                                    {{ t.name }}
                                </a-select-option>
                            </a-select>
                        </div>
                        <!-- Cabang -->
                        <div class="ga-filter-item">
                            <label class="ga-filter-label">Cabang</label>
                            <a-select
                                v-model:value="f.branch_id"
                                placeholder="Semua Cabang"
                                allow-clear
                                style="width:100%"
                            >
                                <a-select-option v-for="b in branches" :key="b.id" :value="b.id">
                                    {{ b.name }}
                                </a-select-option>
                            </a-select>
                        </div>
                        <!-- Date Range -->
                        <div class="ga-filter-item">
                            <label class="ga-filter-label">Rentang Tanggal</label>
                            <a-range-picker
                                v-model:value="dateRange"
                                format="YYYY-MM-DD"
                                value-format="YYYY-MM-DD"
                                :placeholder="['Dari Tanggal', 'Sampai Tanggal']"
                                style="width:100%"
                                @change="onDateRangeChange"
                            />
                        </div>
                    </div>
                    <!-- Actions -->
                    <a-space style="margin-top:16px">
                        <a-button type="primary" :icon="h(FilterOutlined)" @click="applyFilters">
                            Terapkan Filter
                        </a-button>
                        <a-button :icon="h(ReloadOutlined)" @click="resetFilters">
                            Reset
                        </a-button>
                    </a-space>
                </a-card>

                <!-- ── Table ── -->
                <a-table
                    :columns="tableColumns"
                    :data-source="tableData"
                    :pagination="pagination"
                    :scroll="{ x: 1200 }"
                    :locale="{ emptyText: 'Tidak ada memo GA ditemukan.' }"
                    row-key="key"
                    class="ga-report-table"
                    size="small"
                >
                    <!-- PIC slot -->
                    <template #bodyCell="{ column, record }">
                        <template v-if="column.key === 'pic'">
                            <a-tag class="ga-pic-tag" color="geekblue">GA</a-tag>
                        </template>
                        <template v-else-if="column.key === 'status'">
                            <a-tag class="ga-status-tag" :color="statusColorMap[record.status] ?? 'default'">
                                {{ statusLabelMap[record.status] ?? record.status }}
                            </a-tag>
                        </template>
                    </template>
                </a-table>
            </a-card>
        </section>

        <!-- ── Export Preview Modal ── -->
        <a-modal
            v-model:open="exportPreviewVisible"
            title=""
            :footer="null"
            width="92vw"
            :style="{ top: '20px', maxWidth: '1360px' }"
            :closable="true"
            :destroyOnClose="true"
            class="ga-export-modal"
        >
            <template #title>
                <div style="display:flex; align-items:center; gap:8px; font-size:15px; font-weight:700;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" style="color:#217346"><rect x="2" y="3" width="20" height="18" rx="2" stroke="#217346" stroke-width="1.8"/><path d="M8 7l-4 5 4 5M16 7l4 5-4 5M13 6l-2 12" stroke="#217346" stroke-width="1.8" stroke-linecap="round"/></svg>
                    Preview Rekap Memo — pastikan data sudah sesuai sebelum mengunduh
                </div>
            </template>

            <!-- ── Tanda Terima GA header ── -->
            <div class="ga-doc-header">
                <table class="ga-doc-table">
                    <tbody>
                        <tr>
                            <td rowspan="3" class="ga-doc-logo-cell">
                                <div class="ga-doc-brand">TANDA TERIMA GA</div>
                            </td>
                            <td class="ga-doc-sign-cell">Diketahui oleh,</td>
                            <td class="ga-doc-sign-cell">Diketahui oleh,</td>
                        </tr>
                        <tr>
                            <td class="ga-doc-sign-space"></td>
                            <td class="ga-doc-sign-space"></td>
                        </tr>
                        <tr>
                            <td class="ga-doc-sign-label">Manager GA</td>
                            <td class="ga-doc-sign-label">Area Manager</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- ── Summary bar ── -->
            <div style="display:flex; justify-content:space-between; align-items:center; margin: 12px 0 8px;">
                <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                    <a-tag color="blue" style="font-size:12px; padding:2px 10px;">
                        Total: {{ gaReport?.total ?? tableData.length }} baris data
                    </a-tag>
                    <a-tag v-if="f.date_from && f.date_to" color="purple" style="font-size:11px;">
                        Periode: {{ f.date_from }} s/d {{ f.date_to }}
                    </a-tag>
                    <a-tag v-if="f.template_id" color="cyan" style="font-size:11px;">
                        {{ gaTemplates.find(t => String(t.id) === String(f.template_id))?.name }}
                    </a-tag>
                </div>
                <span style="font-size:11px; color:#aaa;">Scroll ke kanan untuk melihat semua kolom</span>
            </div>

            <!-- ── Preview table ── -->
            <div class="ga-preview-table-wrap">
                <a-table
                    :columns="tableColumns"
                    :data-source="tableData"
                    :scroll="{ x: 1380, y: 420 }"
                    :pagination="{ pageSize: 50, showSizeChanger: false, showQuickJumper: true }"
                    row-key="key"
                    class="ga-full-preview-table"
                    size="small"
                    bordered
                    :locale="{ emptyText: 'Tidak ada data memo ditemukan.' }"
                >
                    <template #bodyCell="{ column, record }">
                        <template v-if="column.key === 'pic'">
                            <a-tag class="ga-pic-tag" color="geekblue">GA</a-tag>
                        </template>
                        <template v-else-if="column.key === 'status'">
                            <a-tag class="ga-status-tag" :color="statusColorMap[record.status] ?? 'default'">
                                {{ statusLabelMap[record.status] ?? record.status }}
                            </a-tag>
                        </template>
                        <template v-else-if="column.key === 'serahDate'">
                            <a-tag :color="record.serahDate === '-' ? 'default' : 'green'" style="font-size:11px;">
                                {{ record.serahDate }}
                            </a-tag>
                        </template>
                        <template v-else-if="column.key === 'submitted'">
                            <span style="font-size:11px; color:#555;">{{ record.submitted }}</span>
                        </template>
                        <template v-else-if="column.key === 'qty'">
                            <span style="font-weight:600;">{{ record.qty || '-' }}</span>
                        </template>
                    </template>
                </a-table>
            </div>

            <!-- ── Footer actions ── -->
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:16px; padding-top:14px; border-top:1px solid #f0f0f0;">
                <a-button size="large" @click="exportPreviewVisible = false">Tutup</a-button>
                <a-button
                    type="primary"
                    size="large"
                    :icon="h(DownloadOutlined)"
                    @click="doExport"
                    class="ga-download-btn"
                >
                    Unduh CSV
                </a-button>
            </div>
        </a-modal>


    </AuthenticatedLayout>
</template>

