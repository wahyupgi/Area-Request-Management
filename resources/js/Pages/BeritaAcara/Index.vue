<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { PlusOutlined } from '@ant-design/icons-vue';

const props = defineProps({
    items: Array,
});

const statusFilter = ref('all');
const filteredItems = computed(() => {
    if (statusFilter.value === 'all') return props.items || [];
    return (props.items || []).filter((item) => item.status === statusFilter.value);
});

const statusColor = (status) => {
    const map = {
        draft:     'default',
        submitted: 'processing',
        approved:  'success',
        rejected:  'error',
    };
    return map[status] || 'default';
};

const statusLabel = (status) => {
    const map = {
        draft:     'Draft',
        submitted: 'Menunggu Persetujuan',
        approved:  'Disetujui',
        rejected:  'Ditolak',
    };
    return map[status] || status;
};

const columns = [
    { title: 'Nomor BA',   dataIndex: 'code',   key: 'code', width: 240 },
    { title: 'Judul',      dataIndex: 'title',  key: 'title', width: 280, ellipsis: true },
    { title: 'Status',     dataIndex: 'status', key: 'status', width: 170 },
    { title: 'Tanggal',    dataIndex: 'created_at', key: 'created_at', width: 190 },
    { title: 'Aksi',       key: 'action', width: 96, align: 'center' },
];
</script>

<template>
    <Head title="Daftar Berita Acara" />
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-bold mb-0">Daftar Berita Acara</h1>
        </template>

        <div class="flex justify-between items-center mb-4">
            <span class="text-gray-500 text-sm">Total: {{ items.length }} dokumen</span>
            <a-button type="primary" @click="router.visit(route('berita-acara.create'))">
                <template #icon><plus-outlined /></template>
                Buat Baru
            </a-button>
        </div>

        <a-card :bordered="false" class="rounded-lg shadow-sm berita-acara-history-card">
            <div class="ba-history-toolbar">
                <a-radio-group v-model:value="statusFilter" button-style="solid" class="ba-history-status-tabs">
                    <a-radio-button value="all">Semua ({{ items.length }})</a-radio-button>
                    <a-radio-button value="approved">Disetujui ({{ items.filter(item => item.status === 'approved').length }})</a-radio-button>
                    <a-radio-button value="rejected">Ditolak ({{ items.filter(item => item.status === 'rejected').length }})</a-radio-button>
                    <a-radio-button value="submitted">Menunggu ({{ items.filter(item => item.status === 'submitted').length }})</a-radio-button>
                </a-radio-group>
            </div>
            <a-table
                class="berita-acara-history-table"
                :dataSource="filteredItems"
                :columns="columns"
                rowKey="id"
                :pagination="{ pageSize: 10, showSizeChanger: false }"
                table-layout="fixed"
                :scroll="{ x: 976 }"
            >
                <template #bodyCell="{ column, record }">
                    <template v-if="column.key === 'status'">
                        <a-badge :status="statusColor(record.status)" :text="statusLabel(record.status)" />
                    </template>
                    <template v-else-if="column.key === 'created_at'">
                        {{ new Date(record.created_at).toLocaleDateString('id-ID', { day:'2-digit', month:'long', year:'numeric' }) }}
                    </template>
                    <template v-else-if="column.key === 'action'">
                        <a-button
                            class="ba-detail-button"
                            type="text"
                            title="Detail"
                            aria-label="Detail Berita Acara"
                            @click="router.visit(route('approvals.ba.history', record.id))"
                        >
                            <template #icon>
                                <span class="ba-detail-icon"><span class="ba-detail-glyph">i</span></span>
                            </template>
                        </a-button>
                    </template>
                </template>

                <template #emptyText>
                    <a-empty description="Belum ada Berita Acara yang diajukan.">
                        <a-button type="primary" @click="router.visit(route('berita-acara.create'))">
                            Buat Berita Acara Pertama
                        </a-button>
                    </a-empty>
                </template>
            </a-table>
        </a-card>
    </AuthenticatedLayout>
</template>

<style>
.berita-acara-history-card {
    background: rgba(30, 41, 59, 0.5) !important;
    border: 1px solid rgba(255, 255, 255, 0.06);
}

.ba-history-toolbar {
    margin-bottom: 20px;
}

.ba-detail-button {
    width: 36px;
    height: 36px;
    padding: 0;
}

.ba-detail-icon {
    display: inline-flex;
    width: 32px;
    height: 32px;
    align-items: center;
    justify-content: center;
    border-radius: 9px;
    color: #ffffff;
    background: #203c5b;
    transition: background-color 0.18s ease;
}

.ba-detail-button:hover .ba-detail-icon {
    color: #ffffff;
    background: #315a84;
}

html.theme-light .ba-detail-icon {
    background: #315a84;
}

html.theme-light .ba-detail-button:hover .ba-detail-icon {
    background: #3d6e9b;
}

.ba-detail-glyph {
    display: flex;
    width: 100%;
    height: 100%;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-family: Georgia, 'Times New Roman', serif;
    font-size: 27px;
    font-style: italic;
    font-weight: 700;
    line-height: 1;
}

.ba-history-status-tabs .ant-radio-button-wrapper {
    color: #cbd5e1;
    background: #1e293b;
    border-color: #475569;
}

.ba-history-status-tabs .ant-radio-button-wrapper-checked:not(.ant-radio-button-wrapper-disabled) {
    color: #ffffff;
    background: #1677ff;
    border-color: #1677ff;
}

.ba-history-status-tabs .ant-radio-button-wrapper-checked:not(.ant-radio-button-wrapper-disabled)::before {
    background-color: #1677ff;
}

.berita-acara-history-table .ant-table {
    color: #e2e8f0;
    background: transparent !important;
}

.berita-acara-history-table .ant-table-container {
    border: 1px solid #34516e;
    border-radius: 6px;
}

.berita-acara-history-table .ant-table-thead > tr > th {
    color: #ffffff !important;
    background: #203c5b !important;
    border-bottom: 1px solid #34516e !important;
}

.berita-acara-history-table .ant-table-tbody > tr > td {
    color: #e2e8f0 !important;
    background: transparent !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
}

.berita-acara-history-table .ant-table-tbody > tr > td:not(:last-child) {
    border-right: 1px solid rgba(255, 255, 255, 0.14) !important;
}

.berita-acara-history-table .ant-table-tbody > tr:hover > td {
    color: #ffffff !important;
    background: rgba(255, 255, 255, 0.06) !important;
}

.berita-acara-history-table .ant-table-placeholder {
    color: #cbd5e1;
    background: transparent !important;
}

.berita-acara-history-table .ant-pagination-item a,
.berita-acara-history-table .ant-pagination-prev .ant-pagination-item-link,
.berita-acara-history-table .ant-pagination-next .ant-pagination-item-link {
    color: #cbd5e1;
}

html.theme-light .berita-acara-history-card {
    background: #ffffff !important;
    border-color: #e2e8f0;
}

html.theme-light .ba-history-status-tabs .ant-radio-button-wrapper {
    color: #334155;
    background: #ffffff;
    border-color: #cbd5e1;
}

html.theme-light .ba-history-status-tabs .ant-radio-button-wrapper-checked:not(.ant-radio-button-wrapper-disabled) {
    color: #ffffff;
    background: #1677ff;
    border-color: #1677ff;
}

html.theme-light .berita-acara-history-table .ant-table {
    color: #1e293b;
    background: #ffffff !important;
}

html.theme-light .berita-acara-history-table .ant-table-container {
    border-color: #cbd5e1;
}

html.theme-light .berita-acara-history-table .ant-table-thead > tr > th {
    color: #ffffff !important;
    background: #315a84 !important;
    border-bottom: 1px solid #274b70 !important;
}

html.theme-light .berita-acara-history-table .ant-table-tbody > tr > td {
    color: #1e293b !important;
    background: #ffffff !important;
    border-bottom: 1px solid #e2e8f0 !important;
}

html.theme-light .berita-acara-history-table .ant-table-tbody > tr > td:not(:last-child) {
    border-right: 1px solid #e2e8f0 !important;
}

html.theme-light .berita-acara-history-table .ant-table-tbody > tr:hover > td {
    color: #0f172a !important;
    background: #eff6ff !important;
}

html.theme-light .berita-acara-history-table .ant-table-placeholder {
    color: #64748b;
    background: #ffffff !important;
}

html.theme-light .berita-acara-history-table .ant-pagination-item a,
html.theme-light .berita-acara-history-table .ant-pagination-prev .ant-pagination-item-link,
html.theme-light .berita-acara-history-table .ant-pagination-next .ant-pagination-item-link {
    color: #334155;
}
</style>
