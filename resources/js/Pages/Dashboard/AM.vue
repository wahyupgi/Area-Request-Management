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
                <a-card title="Memo Menunggu Persetujuan" :bordered="false" class="am-section-card">
                    <template #extra>
                        <Link v-if="pendingMemos.length > 0" :href="route('approvals.pending')">
                            <a-button type="link">Lihat Semua</a-button>
                        </Link>
                    </template>
                    
                    <a-empty
                        v-if="pendingMemos.length === 0"
                        description="Semua Bersih! Tidak ada memo yang menunggu persetujuan."
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

<style>
/* ─── GA Report Filters ─────────────────────────────────────── */
.ga-filter-card {
    background: rgba(30, 32, 53, 0.5);
    border: 1px solid rgba(255, 255, 255, 0.06);
    border-radius: 12px;
    margin-bottom: 20px;
}
.ga-filter-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
}
@media (max-width: 768px) {
    .ga-filter-grid {
        grid-template-columns: 1fr;
    }
}
.ga-filter-item {
    display: flex;
    flex-direction: column;
}
.ga-filter-label {
    font-size: 12px;
    font-weight: 600;
    color: #94a3b8;
    margin-bottom: 6px;
    display: block;
}

html:not(.theme-light) .ga-filter-item .ant-picker,
html:not(.theme-light) .ga-filter-item .ant-picker-input,
html:not(.theme-light) .ga-filter-item .ant-picker-suffix,
html:not(.theme-light) .ga-filter-item .ant-picker-range-separator,
html:not(.theme-light) .ga-filter-item .ant-picker-clear,
html:not(.theme-light) .ga-filter-item .ant-picker-input > input {
    background: #1e293b !important;
    border-color: #334155 !important;
    color: #e2e8f0 !important;
}

html:not(.theme-light) .ga-filter-item .ant-picker {
    box-shadow: none !important;
}

html:not(.theme-light) .ga-filter-item .ant-picker-input > input {
    background: transparent !important;
    color: #e2e8f0 !important;
    -webkit-text-fill-color: #e2e8f0 !important;
    caret-color: #f8fafc !important;
}

html:not(.theme-light) .ga-filter-item .ant-picker-input > input::placeholder {
    color: #94a3b8 !important;
    opacity: 1 !important;
}

html:not(.theme-light) .ga-filter-item .ant-picker-range-separator,
html:not(.theme-light) .ga-filter-item .ant-picker-suffix {
    color: #cbd5e1 !important;
}

html:not(.theme-light) .ga-filter-item .ant-picker-clear {
    color: #cbd5e1 !important;
}

html:not(.theme-light) .ant-picker-dropdown,
html:not(.theme-light) .ant-picker-panel-container,
html:not(.theme-light) .ant-picker-panel,
html:not(.theme-light) .ant-picker-date-panel,
html:not(.theme-light) .ant-picker-header,
html:not(.theme-light) .ant-picker-body,
html:not(.theme-light) .ant-picker-content th,
html:not(.theme-light) .ant-picker-content td,
html:not(.theme-light) .ant-picker-cell {
    background: #1e293b !important;
    color: #e2e8f0 !important;
    border-color: #334155 !important;
}

html:not(.theme-light) .ant-picker-cell-in-view {
    color: #e2e8f0 !important;
}

html:not(.theme-light) .ant-picker-cell-disabled {
    color: #64748b !important;
}

html:not(.theme-light) .ant-picker-cell-in-view.ant-picker-cell-selected .ant-picker-cell-inner,
html:not(.theme-light) .ant-picker-cell-in-view.ant-picker-cell-range-start .ant-picker-cell-inner,
html:not(.theme-light) .ant-picker-cell-in-view.ant-picker-cell-range-end .ant-picker-cell-inner {
    background: #2563eb !important;
    color: #f8fafc !important;
}

html:not(.theme-light) .ant-picker-cell-in-view.ant-picker-cell-today .ant-picker-cell-inner::before {
    border-color: #60a5fa !important;
}

/* GA report table follows the shared memo table palette. */
.ga-report-table .ant-table-container {
    border: 1px solid #c8ced8 !important;
    border-radius: 6px;
    overflow: hidden;
}
.ga-report-table .ant-table {
    background: #ffffff !important;
}
.ga-report-table .ant-table-thead > tr > th {
    background: #344f82 !important;
    color: #ffffff !important;
    border-color: #c8ced8 !important;
    font-weight: 700 !important;
    white-space: nowrap;
}
.ga-report-table .ant-table-tbody > tr > td {
    background: #ffffff !important;
    color: #0f172a !important;
    border-color: #c8ced8 !important;
    border-right: 1px solid #c8ced8 !important;
}
.ga-report-table .ant-table-tbody > tr > td:last-child {
    border-right: 0 !important;
}
.ga-report-table .ant-table-tbody > tr:hover > td {
    background: #f3f6fb !important;
}
html.theme-light .ga-report-table .ant-table-container,
html.theme-light .ga-report-table .ant-table,
html.theme-light .ga-report-table .ant-table-content {
    border-color: #c8ced8 !important;
    background: #ffffff !important;
}

html:not(.theme-light) .ga-report-table .ant-table-container,
html:not(.theme-light) .ga-report-table .ant-table,
html:not(.theme-light) .ga-report-table .ant-table-content {
    background: #1e293b !important;
    border-color: #334155 !important;
}
html:not(.theme-light) .ga-report-table .ant-table-thead > tr > th {
    background: #123762 !important;
    color: #f8fafc !important;
    border-bottom: 0 !important;
    border-right: 0 !important;
}
html:not(.theme-light) .ga-report-table .ant-table-tbody > tr > td {
    background: #1e293b !important;
    color: #e2e8f0 !important;
    border-color: #334155 !important;
    border-right-color: #334155 !important;
}
html:not(.theme-light) .ga-report-table .ant-table-tbody > tr:hover > td {
    background: #26364d !important;
}
html:not(.theme-light) .ga-report-table .ant-empty-description {
    color: #cbd5e1 !important;
}

html.theme-light .ga-filter-card {
    background: #ffffff;
    border-color: rgba(15, 23, 42, 0.08);
}
html.theme-light .ga-filter-label {
    color: #475569;
}

/* ─── Daily Work Summary ───────────────────────────────────── */
.am-work-summary {
    background: linear-gradient(135deg, #12263d 0%, #1a2f47 48%, #1f3f5d 100%);
    border: 1px solid rgba(148, 163, 184, 0.18);
    border-radius: 16px;
    box-shadow: 0 14px 30px rgba(15, 23, 42, 0.2);
    padding: 24px;
}
.am-eyebrow {
    color: #d2e8ff;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}
.am-action-link {
    background: rgba(31, 105, 168, 0.18);
    border: 1px solid rgba(96, 165, 250, 0.35);
    border-radius: 6px;
    color: #eff6ff;
    font-size: 12px;
    font-weight: 600;
    padding: 8px 11px;
    transition: background-color 0.15s, border-color 0.15s, color 0.15s;
}
.am-action-link:hover {
    background: rgba(31, 105, 168, 0.28);
    border-color: rgba(96, 165, 250, 0.5);
    color: #ffffff;
}
.am-action-link--primary {
    background: #1f69a8;
    border-color: #1f69a8;
    color: #ffffff;
}
.am-action-link--primary:hover {
    background: #18598f;
    border-color: #18598f;
    color: #ffffff;
}
.am-metric {
    background: rgba(15, 23, 42, 0.42);
    border: 1px solid rgba(148, 163, 184, 0.14);
    border-radius: 12px;
    display: flex;
    flex-direction: column;
    gap: 3px;
    min-width: 0;
    padding: 14px 16px;
    transition: background-color 0.15s, transform 0.15s, border-color 0.15s;
}
.am-metric:hover { background: rgba(15, 23, 42, 0.56); border-color: rgba(148, 163, 184, 0.2); transform: translateY(-2px); }
.am-metric-value {
    color: #ffffff;
    font-size: 25px;
    font-weight: 700;
    line-height: 1.1;
}
.am-metric-label {
    color: #d6eafa;
    font-size: 11px;
    font-weight: 500;
    white-space: nowrap;
}
.am-metric-note { color: rgba(214, 234, 250, 0.78); font-size: 11px; }
.am-metric--priority { border-color: rgba(251, 191, 36, 0.55); }
.am-metric--priority .am-metric-value { color: #fde68a; }
.am-metric--approved { border-color: rgba(110, 231, 183, 0.45); }
.am-metric--approved .am-metric-value { color: #a7f3d0; }
.am-metric--rejected { border-color: rgba(253, 164, 175, 0.45); }
.am-metric--rejected .am-metric-value { color: #fecdd3; }

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
.am-stat-card--indigo:hover { border-color: rgba(99,102,241,0.2); }

.am-stat-icon {
    width: 44px; height: 44px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.am-stat-icon--amber { background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.2); color: #fbbf24; }
.am-stat-icon--emerald { background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.2); color: #34d399; }
.am-stat-icon--rose { background: rgba(244,63,94,0.1); border: 1px solid rgba(244,63,94,0.2); color: #fb7185; }
.am-stat-icon--indigo { background: rgba(99,102,241,0.1); border: 1px solid rgba(99,102,241,0.2); color: #818cf8; }

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

/* ─── Hero Card & Stat Boxes ───────────────────────────────── */
.am-hero-card {
    background: linear-gradient(135deg, #12263d 0%, #1a2f47 48%, #1f3f5d 100%);
    border-radius: 16px;
    box-shadow: 0 14px 30px rgba(15, 23, 42, 0.2);
    border: 1px solid rgba(148, 163, 184, 0.18);
}
.am-hero-date { color: #d2e8ff; font-size: 11px; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase; }
.am-hero-title { margin-top: 8px; font-size: 24px; font-weight: 600; color: #ffffff; margin-bottom: 0; }
.am-hero-desc { margin-top: 8px; font-size: 14px; color: #94a3b8; max-width: 600px; margin-bottom: 0; }

.am-stat-box {
    background: rgba(15, 23, 42, 0.42);
    border: 1px solid rgba(148, 163, 184, 0.14);
    border-radius: 12px;
    transition: all 0.3s;
}
.am-stat-box:hover {
    background: rgba(15, 23, 42, 0.56);
    border-color: rgba(148, 163, 184, 0.2);
}
.am-stat-title { color: #d6eafa; font-size: 11px; font-weight: 500; }
.am-stat-number { color: #ffffff; font-size: 25px; font-weight: 700; line-height: 1.1; margin: 4px 0; }
.am-stat-desc { color: #94a3b8; font-size: 11px; }

/* ─── Ant Design Dashboard Lists ────────────────────────────── */
.am-section-card {
    border-radius: 12px;
    background: rgba(30, 32, 53, 0.5);
    border: 1px solid rgba(255, 255, 255, 0.06);
    overflow: hidden;
}
.am-section-card .ant-card-head {
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    background: transparent;
}
.am-section-card .ant-card-head-title {
    color: #ffffff;
    font-weight: 700;
}
.am-section-card .ant-card-body {
    padding: 16px 18px;
}
.am-section-card .ant-empty {
    margin: 12px 0 8px;
}
.am-section-card .ant-empty-description {
    color: #94a3b8;
}
.am-section-card .ant-list-split .ant-list-item {
    border-bottom: none;
    padding: 8px 0;
}
.am-item-card {
    width: 100%;
    border-radius: 8px;
    background: rgba(15, 23, 42, 0.4);
    border: 1px solid rgba(148, 163, 184, 0.14);
    transition: all 0.2s;
    overflow: hidden;
}
.am-item-card .ant-card-body {
    background: transparent;
    padding: 14px 16px;
}
.am-item-card:hover {
    background: rgba(40, 43, 70, 0.7);
    border-color: rgba(99, 102, 241, 0.25);
    transform: translateX(2px);
    box-shadow: 0 0 0 1px rgba(99,102,241,0.1), 0 4px 20px rgba(0,0,0,0.2);
}
.am-item-title {
    color: #ffffff;
    transition: color 0.2s;
}
.am-item-card:hover .am-item-title {
    color: #a5b4fc;
}
.am-item-text {
    color: #94a3b8;
}

html:not(.theme-light) .am-section-card,
html:not(.theme-light) .am-section-card .ant-card-head-title,
html:not(.theme-light) .am-section-card .ant-card-body,
html:not(.theme-light) .am-section-card .ant-empty-description,
html:not(.theme-light) .am-section-card .ant-list-item,
html:not(.theme-light) .am-item-card,
html:not(.theme-light) .am-item-card .ant-card-body,
html:not(.theme-light) .am-item-card .ant-card-body *,
html:not(.theme-light) .am-item-title,
html:not(.theme-light) .am-item-text,
html:not(.theme-light) .am-item-card a,
html:not(.theme-light) .am-item-card .ant-badge-status-text,
html:not(.theme-light) .am-item-card .ant-space-item,
html:not(.theme-light) .am-item-card .ant-tag,
html:not(.theme-light) .am-item-card .ant-tag * {
    color: #e2e8f0 !important;
}

html:not(.theme-light) .am-item-card a {
    text-decoration: none;
}

html:not(.theme-light) .am-item-card .ant-tag {
    color: #f8fafc !important;
    border-color: rgba(148, 163, 184, 0.25) !important;
    background: rgba(59, 130, 246, 0.14) !important;
}

html:not(.theme-light) .am-item-card .ant-tag.ant-tag-error {
    background: rgba(239, 68, 68, 0.14) !important;
}

html:not(.theme-light) .am-item-card .ant-tag.ant-tag-warning {
    background: rgba(245, 158, 11, 0.16) !important;
}

html:not(.theme-light) .am-item-card .ant-tag.ant-tag-processing {
    background: rgba(96, 165, 250, 0.16) !important;
}

html:not(.theme-light) .am-item-card .ant-badge-status-text {
    color: #e2e8f0 !important;
}

/* ════════════════════════════════════════════════════════════
   LIGHT MODE OVERRIDES
   html.theme-light ditambahkan ke <html> oleh useTheme.js
   ════════════════════════════════════════════════════════════ */

/* Hero */
html.theme-light .am-eyebrow,
html.theme-light .am-metric-label { color: #64748b; }
html.theme-light .am-work-summary {
    background: linear-gradient(135deg, #dbeeff 0%, #eaf6ff 100%);
    border-color: #b8dcf5;
    box-shadow: 0 10px 24px rgba(31, 105, 168, 0.12);
}
html.theme-light .am-work-summary .text-white { color: #123f68; }
html.theme-light .am-work-summary .text-slate-400 { color: #426581; }
html.theme-light .am-eyebrow { color: #426581; }
html.theme-light .am-action-link {
    background: rgba(255, 255, 255, 0.55);
    border-color: rgba(15, 23, 42, 0.13);
    color: #475569;
}
html.theme-light .am-action-link:hover {
    background: #f1f5f9;
    border-color: rgba(15, 23, 42, 0.2);
    color: #0f172a;
}
html.theme-light .am-metric { border-color: rgba(15, 23, 42, 0.1); }
html.theme-light .am-metric { background: rgba(255, 255, 255, 0.7); }
html.theme-light .am-metric:hover { background: #ffffff; }
html.theme-light .am-metric-value { color: #123f68; }
html.theme-light .am-metric-label { color: #426581; }
html.theme-light .am-metric-note { color: #64839c; }
html.theme-light .am-metric--priority .am-metric-value { color: #b45309; }
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
html.theme-light .am-stat-card--indigo:hover { border-color: rgba(99,102,241,0.35); }

html.theme-light .am-stat-icon--amber { background: rgba(245,158,11,0.08); border-color: rgba(245,158,11,0.25); }
html.theme-light .am-stat-icon--emerald { background: rgba(16,185,129,0.08); border-color: rgba(16,185,129,0.25); }
html.theme-light .am-stat-icon--rose { background: rgba(244,63,94,0.08); border-color: rgba(244,63,94,0.25); }
html.theme-light .am-stat-icon--indigo { background: rgba(99,102,241,0.08); border-color: rgba(99,102,241,0.25); }

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
html.theme-light .am-recent-item svg { color: #94a3b8 !important; }
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

/* Section headings outside cards */
html.theme-light .text-white:not([class*="bg-"]):not(.am-memo-card *):not(.am-stat-card *) {
    color: #0f172a;
}

/* Stat card text colors */
html.theme-light .am-stat-card .text-white { color: #0f172a !important; }
html.theme-light .am-stat-card .text-amber-300 { color: #b45309 !important; }
html.theme-light .am-stat-card .am-stat-value { color: #0f172a !important; }
html.theme-light .am-stat-card .am-stat-label { color: #64748b !important; }

/* Generic page-level section headings (h2 outside components) */
html.theme-light h2.text-white,
html.theme-light h3.text-white { color: #0f172a !important; }
html.theme-light span.text-slate-500 { color: #64748b; }

/* Separator dots in recent items */
html.theme-light .am-recent-item .text-slate-600 { color: #94a3b8 !important; }

/* Dashboard Hero & Stat Boxes */
html.theme-light .am-hero-card {
    background: linear-gradient(135deg, #f0f7ff 0%, #e6f4ff 100%);
    border-color: #91caff;
    box-shadow: 0 4px 12px rgba(22, 119, 255, 0.08);
}
html.theme-light .am-hero-date { color: #1677ff; font-weight: 700; }
html.theme-light .am-hero-title { color: #0f172a; }
html.theme-light .am-hero-desc { color: #475569; }

html.theme-light .am-stat-box { background: #ffffff; }
html.theme-light .am-stat-box-pending { border-color: #91caff; }
html.theme-light .am-stat-box-pending:hover { border-color: #69b1ff; box-shadow: 0 2px 8px rgba(22, 119, 255, 0.15); }
html.theme-light .am-stat-box-pending .am-stat-number { color: #1677ff; }

html.theme-light .am-stat-box-approved { border-color: #b7eb8f; }
html.theme-light .am-stat-box-approved:hover { border-color: #95de64; box-shadow: 0 2px 8px rgba(82, 196, 26, 0.15); }
html.theme-light .am-stat-box-approved .am-stat-number { color: #52c41a; }

html.theme-light .am-stat-box-rejected { border-color: #ffa39e; }
html.theme-light .am-stat-box-rejected:hover { border-color: #ff7875; box-shadow: 0 2px 8px rgba(255, 77, 79, 0.15); }
html.theme-light .am-stat-box-rejected .am-stat-number { color: #ff4d4f; }

html.theme-light .am-stat-title { color: #475569; }
html.theme-light .am-stat-desc { color: #94a3b8; }

/* Dashboard Lists */
html.theme-light .am-section-card {
    background: #ffffff;
    border-color: rgba(15, 23, 42, 0.08);
}
html.theme-light .am-section-card .ant-card-head {
    border-bottom-color: rgba(15, 23, 42, 0.08);
}
html.theme-light .am-section-card .ant-card-head-title {
    color: #0f172a;
}
html.theme-light .am-section-card .ant-empty-description {
    color: #64748b;
}
html.theme-light .am-item-card {
    background: #ffffff;
    border-color: rgba(15, 23, 42, 0.08);
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
html.theme-light .am-item-card:hover {
    background: #f8faff;
    border-color: rgba(99, 102, 241, 0.3);
    box-shadow: 0 0 0 1px rgba(99,102,241,0.15), 0 4px 16px rgba(99,102,241,0.08);
}
html.theme-light .am-item-title {
    color: #0f172a;
}
html.theme-light .am-item-card:hover .am-item-title {
    color: #4f46e5;
}
html.theme-light .am-item-text {
    color: #64748b;
}
html.theme-light .am-section-card .ant-card-head-title {
    color: #0f172a !important;
}
html.theme-light .am-item-card .ant-card-body,
html.theme-light .am-item-card .ant-card-body a,
html.theme-light .am-item-card .am-item-title {
    color: #0f172a !important;
}
html.theme-light .am-item-card .am-item-text,
html.theme-light .am-item-card .ant-badge-status-text {
    color: #475569 !important;
}
html.theme-light .am-item-card .ant-tag {
    color: #334155 !important;
    border-color: #cbd5e1 !important;
}
html.theme-light .am-item-card .ant-tag-blue,
html.theme-light .am-item-card .ant-tag-processing {
    color: #1d4ed8 !important;
    background: #eff6ff !important;
    border-color: #bfdbfe !important;
}
html.theme-light .am-item-card .ant-tag-error {
    color: #b91c1c !important;
    background: #fef2f2 !important;
    border-color: #fecaca !important;
}
html.theme-light .am-item-card .ant-tag-warning {
    color: #b45309 !important;
    background: #fffbeb !important;
    border-color: #fde68a !important;
}

/* ─── GA Report Section (Dark Mode) ────────────────────────── */
.ga-report-card {
    background: rgba(30, 32, 53, 0.55) !important;
    border: 1px solid rgba(255,255,255,0.07) !important;
    border-radius: 14px;
}
.ga-report-card .ant-card-head {
    border-bottom: 1px solid rgba(255,255,255,0.07) !important;
    background: transparent !important;
}
.ga-report-title {
    color: #ffffff;
    font-weight: 700;
    font-size: 15px;
}
.ga-report-subtitle {
    color: #94a3b8;
    font-size: 12px;
    margin-top: 2px;
}
.ga-report-icon-wrap {
    width: 38px; height: 38px;
    display: flex; align-items: center; justify-content: center;
    background: rgba(129, 140, 248, 0.12);
    border: 1px solid rgba(129, 140, 248, 0.25);
    border-radius: 10px;
}
.ga-filter-card {
    background: rgba(15, 23, 42, 0.35) !important;
    border: 1px solid rgba(255,255,255,0.06) !important;
    border-radius: 10px;
    margin-bottom: 16px;
}
.ga-filter-label {
    display: block;
    color: #94a3b8;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    margin-bottom: 6px;
}
.ga-report-table .ant-table {
    background: transparent !important;
    color: #e2e8f0 !important;
}
.ga-report-table .ant-table-thead > tr > th {
    background: rgba(15, 23, 42, 0.45) !important;
    color: #ffffff !important;
    border-bottom: 1px solid rgba(255,255,255,0.08) !important;
    border-right: 1px solid rgba(255,255,255,0.05) !important;
}
.ga-report-table .ant-table-tbody > tr > td {
    background: transparent !important;
    color: #e2e8f0 !important;
    border-bottom: 1px solid rgba(255,255,255,0.06) !important;
}
.ga-report-table .ant-table-tbody > tr:hover > td {
    background: rgba(255,255,255,0.04) !important;
    color: #ffffff !important;
}
.ga-report-table .ant-table-placeholder {
    background: transparent !important;
}
.ga-report-table .ant-empty-description {
    color: #64748b !important;
}
.ga-report-section .ant-pagination .ant-pagination-item a { color: #94a3b8; }
.ga-report-section .ant-pagination .ant-pagination-item-active { border-color: #6366f1; }
.ga-report-section .ant-pagination .ant-pagination-item-active a { color: #818cf8; }

html:not(.theme-light) .ga-report-card .ant-card-head-title,
html:not(.theme-light) .ga-report-card .ant-card-extra,
html:not(.theme-light) .ga-report-card .ant-card-body,
html:not(.theme-light) .ga-filter-card,
html:not(.theme-light) .ga-filter-card .ant-select-selection-placeholder,
html:not(.theme-light) .ga-filter-card .ant-select-selection-item,
html:not(.theme-light) .ga-filter-card .ant-select-arrow,
html:not(.theme-light) .ga-filter-card .ant-select-clear,
html:not(.theme-light) .ga-report-table .ant-table-cell,
html:not(.theme-light) .ga-report-table .ant-table-cell a,
html:not(.theme-light) .ga-report-table .ant-table-cell span,
html:not(.theme-light) .ga-report-section .ant-pagination,
html:not(.theme-light) .ga-report-section .ant-pagination-item a,
html:not(.theme-light) .ga-report-section .ant-pagination-prev,
html:not(.theme-light) .ga-report-section .ant-pagination-next,
html:not(.theme-light) .ga-report-section .ant-pagination-jump-next,
html:not(.theme-light) .ga-report-section .ant-pagination-jump-prev {
    color: #e2e8f0 !important;
}

html:not(.theme-light) .ga-filter-card .ant-select-selector {
    background: #1e293b !important;
    border-color: #334155 !important;
    color: #e2e8f0 !important;
}

html:not(.theme-light) .ga-filter-card .ant-select-selection-placeholder {
    color: #94a3b8 !important;
}

html:not(.theme-light) .ga-filter-card .ant-select-selection-item {
    color: #f8fafc !important;
}

html:not(.theme-light) .ga-report-section .ant-pagination-item,
html:not(.theme-light) .ga-report-section .ant-pagination-prev,
html:not(.theme-light) .ga-report-section .ant-pagination-next {
    background: #1e293b !important;
    border-color: #334155 !important;
}

html:not(.theme-light) .ga-report-section .ant-pagination-item-active {
    background: #172554 !important;
    border-color: #6366f1 !important;
}

html:not(.theme-light) .ga-report-section .ant-pagination-item-active a {
    color: #a5b4fc !important;
}

html:not(.theme-light) .ga-report-section .ant-pagination-disabled,
html:not(.theme-light) .ga-report-section .ant-pagination-disabled a {
    color: #64748b !important;
}

html:not(.theme-light) .ga-report-table .ga-status-tag,
html:not(.theme-light) .ga-full-preview-table .ga-status-tag {
    font-weight: 600;
}

html:not(.theme-light) .ga-report-table .ga-status-tag.ant-tag-gold,
html:not(.theme-light) .ga-full-preview-table .ga-status-tag.ant-tag-gold {
    color: #fcd34d !important;
    background: rgba(245, 158, 11, 0.16) !important;
    border-color: rgba(245, 158, 11, 0.4) !important;
}

html:not(.theme-light) .ga-report-table .ga-status-tag.ant-tag-green,
html:not(.theme-light) .ga-full-preview-table .ga-status-tag.ant-tag-green {
    color: #86efac !important;
    background: rgba(34, 197, 94, 0.16) !important;
    border-color: rgba(34, 197, 94, 0.4) !important;
}

html:not(.theme-light) .ga-report-table .ga-status-tag.ant-tag-red,
html:not(.theme-light) .ga-full-preview-table .ga-status-tag.ant-tag-red {
    color: #fda4af !important;
    background: rgba(239, 68, 68, 0.16) !important;
    border-color: rgba(239, 68, 68, 0.4) !important;
}

html:not(.theme-light) .ga-report-table .ga-status-tag.ant-tag-default,
html:not(.theme-light) .ga-full-preview-table .ga-status-tag.ant-tag-default {
    color: #cbd5e1 !important;
    background: rgba(148, 163, 184, 0.14) !important;
    border-color: rgba(148, 163, 184, 0.35) !important;
}

html:not(.theme-light) .ga-report-table .ga-pic-tag,
html:not(.theme-light) .ga-full-preview-table .ga-pic-tag {
    color: #bfdbfe !important;
    background: rgba(37, 99, 235, 0.2) !important;
    border-color: rgba(96, 165, 250, 0.45) !important;
    font-weight: 600;
}

/* GA Report Light Mode */
html.theme-light .ga-report-card {
    background: #ffffff !important;
    border: 1px solid rgba(15,23,42,0.08) !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
html.theme-light .ga-report-card .ant-card-head {
    border-bottom: 1px solid #f0f0f0 !important;
    background: transparent !important;
}
html.theme-light .ga-report-title { color: #0f172a; }
html.theme-light .ga-report-subtitle { color: #64748b; }
html.theme-light .ga-filter-card {
    background: #f8fafc !important;
    border: 1px solid rgba(15,23,42,0.07) !important;
}
html.theme-light .ga-filter-label { color: #475569; }
html.theme-light .ga-report-table .ant-table { background: #ffffff !important; color: #334155 !important; }
html.theme-light .ga-report-table .ant-table-thead > tr > th {
    background: #344f82 !important;
    color: #ffffff !important;
    border-bottom: 0 !important;
    border-right: 0 !important;
}
html.theme-light .ga-report-table .ant-table-tbody > tr > td {
    background: #ffffff !important;
    color: #334155 !important;
    border-bottom: 1px solid #f1f5f9 !important;
}
html.theme-light .ga-report-table .ant-table-tbody > tr:hover > td {
    background: #f8fafc !important;
    color: #0f172a !important;
}
html.theme-light .ga-report-table .ant-table-placeholder { background: #ffffff !important; }

/* --- GA Export Preview Modal (full table view) --- */
.ga-export-modal .ant-modal-content { border-radius: 12px; overflow: hidden; }
.ga-export-modal .ant-modal-header { background: #f8fafc; border-bottom: 1px solid #eaeaea; padding: 14px 20px; }
.ga-export-modal .ant-modal-body { padding: 16px 20px 12px; }
.ga-doc-header { border: 1px solid #d9d9d9; border-radius: 6px; overflow: hidden; margin-bottom: 0; }
.ga-doc-table { width: 100%; border-collapse: collapse; }
.ga-doc-table td { border: 1px solid #d9d9d9; padding: 8px 16px; font-size: 12px; color: #333; }
.ga-doc-logo-cell { width: 38%; text-align: center; background: #f5f7fa; vertical-align: middle; padding: 16px; }
.ga-doc-brand { font-size: 16px; font-weight: 800; letter-spacing: 1.5px; color: #1a3a5c; }
.ga-doc-sign-cell { font-size: 11px; color: #666; width: 31%; }
.ga-doc-sign-space { height: 56px; width: 31%; }
.ga-doc-sign-label { font-size: 11px; color: #555; text-align: center; }
.ga-preview-table-wrap { border-radius: 8px; overflow: hidden; border: 1px solid #e0e0e0; }
.ga-full-preview-table .ant-table-thead > tr > th { background: #1a3a5c !important; color: #fff !important; font-size: 11px; font-weight: 600; white-space: nowrap; text-align: center !important; padding: 9px 10px; border-right: 1px solid #2c4f7a !important; }
.ga-full-preview-table .ant-table-tbody > tr > td { font-size: 12px; padding: 6px 10px; vertical-align: top; background: #fff !important; color: #222 !important; }
.ga-full-preview-table .ant-table-tbody > tr:nth-child(even) > td { background: #f7fafd !important; }
.ga-full-preview-table .ant-table-tbody > tr:hover > td { background: #ddeeff !important; }
.ga-download-btn { background: linear-gradient(135deg, #217346, #1a5c38) !important; border-color: #217346 !important; font-weight: 600; padding-left: 24px; padding-right: 24px; }
.ga-download-btn:hover { background: linear-gradient(135deg, #1a5c38, #144d2e) !important; border-color: #1a5c38 !important; }
</style>