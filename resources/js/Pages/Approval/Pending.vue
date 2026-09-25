<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { 
    InboxOutlined, 
    CheckCircleOutlined, 
    CloseCircleOutlined, 
    HistoryOutlined,
    RightOutlined,
    DownloadOutlined
} from '@ant-design/icons-vue';

const downloadCsv = () => {
    window.location.href = route('memos.export-csv');
};

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
const displayedMemos = computed(() => ({ masuk: memos, approved: approvedMemos, rejected: rejectedMemos, riwayat: history }[activeTab.value] || memos).value);

const tableColumns = [
    { title: 'Kode Memo', dataIndex: 'code', key: 'code' },
    { title: 'Memo & Template', key: 'memo' },
    { title: 'Pembuat', key: 'creator' },
    { title: 'Cabang', key: 'branch' },
    { title: 'Status', key: 'status' },
    { title: 'Aksi', key: 'action', align: 'center' },
];

const historyStatusConfig = {
    submitted: {
        label: 'Menunggu',
        color: 'warning',
    },
    approved: {
        label: 'Disetujui',
        color: 'success',
    },
    rejected: {
        label: 'Ditolak',
        color: 'error',
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
            <div class="flex justify-between items-center w-full">
                <h1 class="am-approval-header-title text-xl font-bold mb-0">Kotak Masuk & Riwayat Memo</h1>
                <a-button type="default" @click="downloadCsv">
                    <template #icon><download-outlined /></template>
                    Export/Download CSV
                </a-button>
            </div>
        </template>

        <a-card :bordered="false" class="am-approval-page rounded-lg shadow-sm">
            <a-tabs v-model:activeKey="activeTab" size="large" :animated="false">
                <a-tab-pane key="masuk"><template #tab><span><inbox-outlined /> Masuk</span></template></a-tab-pane>
                <a-tab-pane key="approved"><template #tab><span><check-circle-outlined /> Disetujui</span></template></a-tab-pane>
                <a-tab-pane key="rejected"><template #tab><span><close-circle-outlined /> Ditolak / Revisi</span></template></a-tab-pane>
                <a-tab-pane key="riwayat"><template #tab><span><history-outlined /> Semua Riwayat</span></template></a-tab-pane>
            </a-tabs>

            <Transition name="inbox-table" mode="out-in">
                <div :key="activeTab" class="am-inbox-table-shell">
                    <a-table
                        :data-source="displayedMemos"
                        :columns="tableColumns"
                        row-key="id"
                        :pagination="false"
                        class="am-inbox-table"
                    >
                <template #bodyCell="{ column, record }">
                    <template v-if="column.key === 'memo'">
                        <div class="font-semibold">{{ record.title }}</div>
                        <div class="text-xs opacity-70">{{ record.template?.name || '-' }}</div>
                    </template>
                    <template v-else-if="column.key === 'creator'">{{ record.creator?.name || '-' }}</template>
                    <template v-else-if="column.key === 'branch'">{{ record.branch?.name || '-' }}</template>
                    <template v-else-if="column.key === 'status'">
                        <a-tag :color="historyStatusConfig[record.status]?.color || 'default'">{{ historyStatusConfig[record.status]?.label || record.status }}</a-tag>
                    </template>
                    <template v-else-if="column.key === 'action'">
                        <a-button type="primary" ghost size="small" @click="router.visit(record.status === 'submitted' ? route('approvals.review', record.id) : route('approvals.history', record.id))">
                            {{ record.status === 'submitted' ? 'Review' : 'Detail' }}
                            <template #icon><right-outlined /></template>
                        </a-button>
                    </template>
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
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
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
    border-bottom: 1px solid #f0f0f0;
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
