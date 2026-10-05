<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { 
    InboxOutlined, 
    CheckCircleOutlined, 
    CloseCircleOutlined, 
    HistoryOutlined,
    DownloadOutlined
} from '@ant-design/icons-vue';

const downloadCsv = () => {
    const rows = [
        ['Jenis Dokumen', 'Nomor Dokumen', 'Perihal', 'Pembuat', 'Cabang', 'Status', 'Tanggal'],
        ...filteredItems.value.map(item => [
            itemTypeLabel(item),
            item.code || '-',
            item.title || '-',
            item.creator?.name || '-',
            item.branch?.name || '-',
            historyStatusConfig[item.status]?.label || item.status || '-',
            formatDate(item._date),
        ]),
    ];
    const csv = '\uFEFF' + rows
        .map(row => row.map(value => `"${String(value ?? '').replace(/"/g, '""')}"`).join(','))
        .join('\r\n');
    const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8;' }));
    const link = document.createElement('a');
    link.href = url;
    link.download = `kotak-masuk-${activeTab.value}-${new Date().toISOString().slice(0, 10)}.csv`;
    link.click();
    URL.revokeObjectURL(url);
};

const props = defineProps({
    allMemos: Object,
    defaultTab: String,
    pendingBA: { type: Array, default: () => [] },
    pendingFormPengajuans: { type: Array, default: () => [] },
});

const formPengajuanTemplateLabels = {
    form_permohonan_pinjaman: 'Form Permohonan Pinjaman (FPP)',
    form_ijin_tidak_masuk_kerja: 'Form Ijin Tidak Masuk Kerja (FITMK)',
};
const isFormPengajuan = (item) => item._itemType === 'form_pengajuan';
const itemTypeLabel = (item) => isFormPengajuan(item)
    ? 'Form Pengajuan'
    : item._itemType === 'memo' ? 'Memo' : 'BA';

const activeTab = ref(props.defaultTab || 'masuk');
const searchText = ref('');
const selectedType = ref('all');
const selectedStartDate = ref(null);
const selectedEndDate = ref(null);

const combinedItems = computed(() => {
    const memoItems = (props.allMemos?.data || []).map(m => ({
        ...m,
        _itemType: 'memo',
        _date: m.submitted_at || m.created_at,
    }));
    
    const baItems = (props.pendingBA || []).map(ba => ({
        ...ba,
        _itemType: 'ba',
        _date: ba.submitted_at || ba.created_at,
    }));
    const formItems = (props.pendingFormPengajuans || []).map(form => ({
        ...form,
        _itemType: 'form_pengajuan',
        _date: form.submitted_at || form.created_at,
    }));
    
    return [...memoItems, ...baItems, ...formItems].sort((a, b) => new Date(b._date) - new Date(a._date));
});

const itemsPending = computed(() => combinedItems.value.filter(i => i.status === 'submitted'));
const itemsApproved = computed(() => combinedItems.value.filter(i => i.status === 'approved'));
const itemsRejected = computed(() => combinedItems.value.filter(i => i.status === 'rejected'));
const itemsHistory = computed(() => combinedItems.value);

const displayedItems = computed(() => {
    if (activeTab.value === 'masuk') return itemsPending.value;
    if (activeTab.value === 'approved') return itemsApproved.value;
    if (activeTab.value === 'rejected') return itemsRejected.value;
    if (activeTab.value === 'riwayat') return itemsHistory.value;
    return itemsPending.value;
});

const filteredItems = computed(() => {
    const query = searchText.value.trim().toLocaleLowerCase('id');
    const startDate = selectedStartDate.value?.startOf('day').valueOf();
    const endDate = selectedEndDate.value?.endOf('day').valueOf();

    return displayedItems.value.filter(item => {
        const matchesQuery = !query || [
            item.code,
            item.title,
            item.creator?.name,
            item.branch?.name,
        ].some(value => String(value || '').toLocaleLowerCase('id').includes(query));
        const matchesType = selectedType.value === 'all' ||
            (selectedType.value === 'form_pengajuan' ? isFormPengajuan(item) : item._itemType === selectedType.value && !isFormPengajuan(item));
        const itemDate = new Date(item._date).getTime();
        const matchesDate = (!startDate || itemDate >= startDate) && (!endDate || itemDate <= endDate);

        return matchesQuery && matchesType && matchesDate;
    });
});

const unifiedColumns = computed(() => [
    { title: 'Nomor Dokumen', dataIndex: 'code', key: 'code', width: '18%' },
    { title: 'Perihal & Tipe', dataIndex: 'title', key: 'title_type', width: '24%' },
    { title: 'Pembuat', dataIndex: 'creator', key: 'creator', width: '11%' },
    { title: 'Cabang', dataIndex: 'branch', key: 'branch', width: '15%' },
    { title: 'Status', dataIndex: 'status', key: 'status', width: '10%' },
    { title: 'Tanggal', dataIndex: '_date', key: 'date', width: '10%' },
    { title: 'Aksi', dataIndex: 'action', key: 'action', align: 'center', width: '12%' },
]);

const historyStatusConfig = {
    submitted: { label: 'Menunggu', color: 'warning' },
    approved:  { label: 'Disetujui', color: 'success' },
    rejected:  { label: 'Ditolak',   color: 'error' },
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric', month: 'short', year: 'numeric',
    });
};

const goToDetail = (record) => {
    if (record._itemType === 'memo') {
        router.visit(record.status === 'submitted' ? route('approvals.review', record.id) : route('approvals.history', record.id));
    } else if (isFormPengajuan(record)) {
        router.visit(record.status === 'submitted' ? route('approvals.form-pengajuan.review', record.id) : route('approvals.form-pengajuan.history', record.id));
    } else {
        router.visit(record.status === 'submitted' ? route('approvals.ba.review', record.id) : route('approvals.ba.history', record.id));
    }
};

const disableFutureEndDate = (date) => date && date.startOf('day').valueOf() > new Date().setHours(0, 0, 0, 0);
</script>

<template>
    <Head title="Kotak Masuk AM" />
    <AuthenticatedLayout>
        <template #header>
            <div class="w-full">
                <h1 class="am-approval-header-title text-xl font-bold mb-0">Kotak Masuk & Riwayat Dokumen</h1>
            </div>
        </template>

        <a-card :bordered="false" class="am-approval-page rounded-lg shadow-sm">
            <a-tabs v-model:activeKey="activeTab" size="large" :animated="false">
                <template #rightExtra>
                    <a-button
                        type="primary"
                        size="large"
                        style="height: 48px; background-color: #16a34a; border-color: #16a34a;"
                        title="Export/Download CSV"
                        @click="downloadCsv"
                    >
                        <template #icon><download-outlined /></template>
                        Export CSV
                    </a-button>
                </template>
                <a-tab-pane key="masuk">
                    <template #tab>
                        <span>
                            <inbox-outlined /> Pengajuan Masuk
                        </span>
                    </template>
                </a-tab-pane>
                <a-tab-pane key="approved"><template #tab><span><check-circle-outlined /> Disetujui</span></template></a-tab-pane>
                <a-tab-pane key="rejected"><template #tab><span><close-circle-outlined /> Ditolak</span></template></a-tab-pane>
                <a-tab-pane key="riwayat"><template #tab><span><history-outlined /> Semua Riwayat</span></template></a-tab-pane>
            </a-tabs>

            <Transition name="inbox-table" mode="out-in">
                <div :key="activeTab" class="am-inbox-table-shell">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3 mt-5 mb-5">
                        <a-input-search
                            v-model:value="searchText"
                            placeholder="Cari nomor dokumen, perihal, pembuat, atau cabang"
                            allow-clear
                            size="large"
                            class="w-full sm:flex-1 sm:min-w-0"
                        />
                        <a-select v-model:value="selectedType" aria-label="Filter tipe dokumen" size="large" class="w-full sm:w-60 sm:flex-none">
                            <a-select-option value="all">Semua tipe dokumen</a-select-option>
                            <a-select-option value="memo">Memo</a-select-option>
                            <a-select-option value="ba">Berita Acara</a-select-option>
                            <a-select-option value="form_pengajuan">Form Pengajuan</a-select-option>
                        </a-select>
                        <div class="grid grid-cols-2 gap-2 w-full sm:w-80 sm:flex-none">
                            <a-date-picker
                                v-model:value="selectedStartDate"
                                aria-label="Filter dari tanggal"
                                placeholder="Tanggal awal"
                                format="DD MMM YYYY"
                                size="large"
                                :disabled-date="disableFutureEndDate"
                                class="w-full min-w-0"
                            />
                            <a-date-picker
                                v-model:value="selectedEndDate"
                                aria-label="Filter sampai tanggal"
                                placeholder="Tanggal akhir"
                                format="DD MMM YYYY"
                                size="large"
                                :disabled-date="disableFutureEndDate"
                                class="w-full min-w-0"
                            />
                        </div>
                    </div>
                    <a-table
                        :data-source="filteredItems"
                        :columns="unifiedColumns"
                        table-layout="fixed"
                        row-key="id"
                        :pagination="{ pageSize: 10, showSizeChanger: false, hideOnSinglePage: true }"
                        class="am-inbox-table"
                    >
                        <template #bodyCell="{ column, record }">
                            <template v-if="column.key === 'title_type' || column.dataIndex === 'title'">
                                <div class="font-semibold">{{ record.title }}</div>
                                <div class="text-xs opacity-70 mt-1 flex items-center gap-2">
                                    <a-tag :color="record._itemType === 'memo' ? 'blue' : isFormPengajuan(record) ? 'green' : 'purple'" style="margin-right: 0;">
                                        {{ itemTypeLabel(record) }}
                                    </a-tag>
                                    <span v-if="record._itemType === 'memo'">{{ record.template?.name || '' }}</span>
                                    <span v-else-if="isFormPengajuan(record)">{{ formPengajuanTemplateLabels[record.template] || '' }}</span>
                                </div>
                            </template>
                            <template v-if="column.key === 'creator' || column.dataIndex === 'creator'">{{ record.creator?.name || '-' }}</template>
                            <template v-if="column.key === 'branch' || column.dataIndex === 'branch'">{{ record.branch?.name || '-' }}</template>
                            <template v-if="column.key === 'status' || column.dataIndex === 'status'">
                                <a-tag :color="historyStatusConfig[record.status]?.color || 'default'">
                                    {{ historyStatusConfig[record.status]?.label || record.status }}
                                </a-tag>
                            </template>
                            <template v-if="column.key === 'date' || column.dataIndex === '_date'">
                                {{ formatDate(record._date) }}
                            </template>
                            <template v-if="column.key === 'action'">
                                <a-button type="primary" style="background-color: #1677ff; color: white;" size="small" @click="goToDetail(record)">
                                    {{ record.status === 'submitted' ? 'Review' : 'Detail' }}
                                </a-button>
                            </template>
                        </template>
                        <template #emptyText>
                            <a-empty description="Tidak ada dokumen pada kategori ini." />
                        </template>
                    </a-table>
                </div>
            </Transition>
        </a-card>
    </AuthenticatedLayout>
</template>

<style>
/* Dark mode (default) styles for Pending page */
.am-approval-page {
    background: rgba(30, 32, 53, 0.5) !important;
    border: 1px solid rgba(255,255,255,0.06) !important;
}
.am-approval-page .ant-tabs-tab {
    color: #94a3b8;
}
.am-approval-page .ant-tabs-tab-active {
    color: #ffffff;
}
.am-approval-page .ant-tabs-nav::before {
    border-bottom: 0;
}
.am-approval-page .ant-table {
    background: transparent !important;
    color: #e2e8f0;
}
.am-approval-page .ant-table-thead > tr > th {
    background: rgba(15, 23, 42, 0.4) !important;
    color: #ffffff !important;
    border-bottom: 1px solid rgba(255,255,255,0.06) !important;
}
.am-approval-page .ant-table-tbody > tr > td {
    border-bottom: 1px solid rgba(255,255,255,0.06) !important;
    background: transparent !important;
    color: #e2e8f0 !important;
}
.am-approval-page .ant-table-tbody > tr:hover > td {
    background: rgba(255,255,255,0.04) !important;
    color: #ffffff !important;
}
.am-approval-page .ant-table-placeholder {
    background: transparent !important;
    border-bottom: none !important;
}
.am-approval-page .ant-empty-description {
    color: #94a3b8 !important;
}
html:not(.theme-light) .am-approval-header-title {
    color: #f8fafc !important;
}
html:not(.theme-light) .am-approval-page .ant-tabs-tab-btn,
html:not(.theme-light) .am-approval-page .ant-tabs-tab .anticon {
    color: #94a3b8 !important;
}
html:not(.theme-light) .am-approval-page .ant-tabs-tab-active .ant-tabs-tab-btn,
html:not(.theme-light) .am-approval-page .ant-tabs-tab-active .anticon {
    color: #f8fafc !important;
}
html:not(.theme-light) .am-approval-page .ant-tabs-tab:hover .ant-tabs-tab-btn,
html:not(.theme-light) .am-approval-page .ant-tabs-tab:hover .anticon {
    color: #bfdbfe !important;
}
html:not(.theme-light) .am-approval-page .ant-tabs-ink-bar {
    background: #60a5fa !important;
}
html:not(.theme-light) .am-approval-page .ant-table-cell,
html:not(.theme-light) .am-approval-page .ant-table-cell .font-semibold,
html:not(.theme-light) .am-approval-page .ant-table-cell .text-xs {
    color: #e2e8f0 !important;
}
html:not(.theme-light) .am-approval-page .ant-table-thead .ant-table-cell {
    color: #f8fafc !important;
}
html:not(.theme-light) .am-approval-page .ant-tag {
    color: #f8fafc !important;
}
html:not(.theme-light) .am-approval-page .ant-tag-warning {
    background: rgba(245, 158, 11, 0.18) !important;
    border-color: rgba(251, 191, 36, 0.45) !important;
    color: #fde68a !important;
}
html:not(.theme-light) .am-approval-page .ant-tag-success {
    background: rgba(34, 197, 94, 0.18) !important;
    border-color: rgba(74, 222, 128, 0.45) !important;
    color: #bbf7d0 !important;
}
html:not(.theme-light) .am-approval-page .ant-tag-error {
    background: rgba(239, 68, 68, 0.18) !important;
    border-color: rgba(248, 113, 113, 0.45) !important;
    color: #fecaca !important;
}
html:not(.theme-light) .am-approval-page .ant-tag-default {
    background: rgba(148, 163, 184, 0.16) !important;
    border-color: rgba(148, 163, 184, 0.4) !important;
    color: #e2e8f0 !important;
}

html:not(.theme-light) .am-approval-page .ant-tag-blue {
    background: rgba(59, 130, 246, 0.18) !important;
    border-color: rgba(96, 165, 250, 0.45) !important;
    color: #bfdbfe !important;
}
html:not(.theme-light) .am-approval-page .ant-tag-purple {
    background: rgba(168, 85, 247, 0.18) !important;
    border-color: rgba(192, 132, 252, 0.45) !important;
    color: #e9d5ff !important;
}
html:not(.theme-light) .am-approval-page .ant-picker,
html:not(.theme-light) .am-approval-page .ant-select:not(.ant-select-customize-input) .ant-select-selector,
html:not(.theme-light) .am-approval-page .ant-input-affix-wrapper,
html:not(.theme-light) .am-approval-page .ant-input-search .ant-input,
html:not(.theme-light) .am-approval-page .ant-input-search .ant-input-search-button {
    background: #1e293b !important;
    border-color: #334155 !important;
    color: #e2e8f0 !important;
    box-shadow: none !important;
}
html:not(.theme-light) .am-approval-page .ant-picker-input > input,
html:not(.theme-light) .am-approval-page .ant-select-selection-item,
html:not(.theme-light) .am-approval-page .ant-input {
    background: transparent !important;
    color: #e2e8f0 !important;
    -webkit-text-fill-color: #e2e8f0 !important;
    caret-color: #f8fafc !important;
}
html:not(.theme-light) .am-approval-page .ant-picker-input > input::placeholder,
html:not(.theme-light) .am-approval-page .ant-input::placeholder {
    color: #94a3b8 !important;
    -webkit-text-fill-color: #94a3b8 !important;
    opacity: 1 !important;
}
html:not(.theme-light) .am-approval-page .ant-picker-suffix,
html:not(.theme-light) .am-approval-page .ant-picker-clear,
html:not(.theme-light) .am-approval-page .ant-select-arrow,
html:not(.theme-light) .am-approval-page .ant-input-clear-icon,
html:not(.theme-light) .am-approval-page .ant-input-search-button .anticon {
    background: #1e293b !important;
    color: #cbd5e1 !important;
}
html:not(.theme-light) .am-approval-page .ant-input-search .ant-input-group-addon,
html:not(.theme-light) .am-approval-page .ant-input-search .ant-input-search-button {
    background: #1e293b !important;
    border-color: #334155 !important;
    box-shadow: none !important;
}
html:not(.theme-light) .am-approval-page .ant-input-search .ant-input-search-button:hover {
    background: #273449 !important;
    color: #f8fafc !important;
}
html:not(.theme-light) .am-approval-page .ant-input-search .ant-input-search-button .anticon {
    background: transparent !important;
}
html:not(.theme-light) .am-approval-page .ant-input-search .ant-input-affix-wrapper {
    border-inline-end: 0 !important;
}
html:not(.theme-light) .am-approval-page .ant-pagination .ant-pagination-item a,
html:not(.theme-light) .am-approval-page .ant-pagination .ant-pagination-prev button,
html:not(.theme-light) .am-approval-page .ant-pagination .ant-pagination-next button,
html:not(.theme-light) .am-approval-page .ant-pagination .ant-pagination-item-ellipsis {
    color: #e2e8f0 !important;
}
html:not(.theme-light) .am-approval-page .ant-pagination .ant-pagination-item-active {
    background: transparent !important;
    border-color: #60a5fa !important;
}
html:not(.theme-light) .ant-select-dropdown {
    background: #1e293b !important;
}
html:not(.theme-light) .ant-select-item {
    color: #e2e8f0 !important;
}
html:not(.theme-light) .ant-select-item-option-active,
html:not(.theme-light) .ant-select-item-option-selected {
    background: rgba(59, 130, 246, 0.25) !important;
}

/* Light mode overrides */
html.theme-light .am-approval-page {
    background: #ffffff !important;
    border: 1px solid rgba(15, 23, 42, 0.08) !important;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05) !important;
}
html.theme-light .am-approval-page .ant-tabs-tab {
    color: #64748b;
}
html.theme-light .am-approval-page .ant-tabs-tab-active {
    color: #0f172a;
}
html.theme-light .am-approval-page .ant-tabs-nav::before {
    border-bottom: 0;
}
html.theme-light .am-approval-page .ant-table {
    background: #ffffff !important;
    color: #334155;
}
html.theme-light .am-approval-page .ant-table-thead > tr > th {
    background: #f8fafc !important;
    color: #0f172a !important;
    border-bottom: 1px solid #e2e8f0 !important;
}
html.theme-light .am-approval-page .ant-table-tbody > tr > td {
    border-bottom: 1px solid #f1f5f9 !important;
    background: #ffffff !important;
}
html.theme-light .am-approval-page .ant-table-tbody > tr:hover > td {
    background: #f8fafc !important;
}
html.theme-light .am-approval-page .ant-table-placeholder {
    background: #ffffff !important;
}
html.theme-light .am-approval-page .ant-empty-description {
    color: #64748b !important;
}
html.theme-light .am-approval-header-title {
    color: #1e293b !important;
}
html.theme-light .am-approval-page .ant-tabs-tab-btn,
html.theme-light .am-approval-page .ant-tabs-tab .anticon {
    color: #64748b !important;
}
html.theme-light .am-approval-page .ant-tabs-tab-active .ant-tabs-tab-btn,
html.theme-light .am-approval-page .ant-tabs-tab-active .anticon {
    color: #ffffff !important;
}
html.theme-light .am-approval-page .ant-tabs-tab:hover .ant-tabs-tab-btn,
html.theme-light .am-approval-page .ant-tabs-tab:hover .anticon {
    color: #334155 !important;
}
html.theme-light .am-approval-page .ant-tabs-ink-bar {
    background: #1677ff !important;
}
html.theme-light .am-approval-page .ant-table-cell,
html.theme-light .am-approval-page .ant-table-cell .font-semibold {
    color: #1e293b !important;
}
html.theme-light .am-approval-page .ant-table-cell .text-xs {
    color: #64748b !important;
}
html.theme-light .am-approval-page .ant-table-thead .ant-table-cell {
    background: #344f82 !important;
    color: #ffffff !important;
    border-bottom: 1px solid #c8ced8 !important;
}
html.theme-light .am-approval-page .ant-table-tbody > tr:hover > td,
html.theme-light .am-approval-page .ant-table-tbody > tr:hover > td .font-semibold,
html.theme-light .am-approval-page .ant-table-tbody > tr:hover > td .text-xs {
    color: #0f172a !important;
}

/* Header Text (inherited from global, but safety fallback) */
html.theme-light h2.text-white {
    color: #0f172a !important;
}
</style>
