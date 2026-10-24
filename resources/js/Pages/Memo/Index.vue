<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { PlusOutlined, EditOutlined, FileTextOutlined } from '@ant-design/icons-vue';

const props = defineProps({
    memos: Object,
    filters: Object,
});

const memoList = computed(() => props.memos?.data ?? []);
const paginationLinks = computed(() => props.memos?.links ?? []);

const columns = [
    { title: 'Kode', dataIndex: 'code', key: 'code' },
    { title: 'Perihal', key: 'template' },
    { title: 'Cabang', dataIndex: ['branch', 'name'], key: 'branch' },
    { title: 'Status', dataIndex: 'status', key: 'status' },
    { title: 'Tanggal', dataIndex: 'created_at', key: 'created_at' },
    { title: 'Aksi', key: 'action', align: 'center', width: 85 }
];

const getStatusColor = (status) => {
    const colors = {
        draft: 'default',
        submitted: 'processing',
        approved: 'success',
        rejected: 'error',
    };
    return colors[status] || 'default';
};

const getStatusLabel = (status) => {
    const labels = {
        draft: 'Draft',
        submitted: 'Submitted',
        approved: 'Approved',
        rejected: 'Rejected',
    };
    return labels[status] || status;
};

const onFilterChange = (e) => {
    const status = e.target.value;
    const query = status ? { status } : {};
    router.get(route('memos.index'), query, { preserveState: true });
};
</script>

<template>
    <Head title="Daftar Memo" />
    <AuthenticatedLayout>
        <template #header>
            <h1 class="memo-page-title text-xl font-bold mb-0">Daftar Memo</h1>
        </template>

        <a-card :bordered="false" class="memo-index-card rounded-lg shadow-sm">
            <div class="memo-index-toolbar flex items-center justify-between mb-4">
                <a-radio-group
                    class="memo-status-filter"
                    :value="filters?.status || ''"
                    @change="onFilterChange"
                    button-style="solid"
                >
                    <a-radio-button value="">Semua</a-radio-button>
                    <a-radio-button value="draft">Draft</a-radio-button>
                    <a-radio-button value="submitted">Submitted</a-radio-button>
                    <a-radio-button value="approved">Approved</a-radio-button>
                    <a-radio-button value="rejected">Rejected</a-radio-button>
                </a-radio-group>
                
                <a-button class="memo-create-button" type="primary" @click="router.visit(route('memos.create'))">
                    <template #icon><plus-outlined /></template>
                    Buat Memo
                </a-button>
            </div>

            <a-table 
                :dataSource="memoList" 
                :columns="columns" 
                rowKey="id"
                :pagination="false"
                class="memo-table-pgi mb-4"
            >
                <template #bodyCell="{ column, record }">
                    <template v-if="column.key === 'template'">
                        {{ record.field_values?.meta?.perihal || record.title || record.template?.name || '-' }}
                    </template>
                    <template v-else-if="column.key === 'status'">
                        <a-tag :color="getStatusColor(record.status)">
                            {{ getStatusLabel(record.status) }}
                        </a-tag>
                    </template>
                    <template v-else-if="column.key === 'branch'">
                        {{ record.branch?.name || '-' }}
                    </template>
                    <template v-else-if="column.key === 'created_at'">
                        {{ new Date(record.created_at).toLocaleDateString('id-ID') }}
                    </template>
                    <template v-else-if="column.key === 'action'">
                        <div class="flex items-center justify-center gap-1">
                            <a-tooltip v-if="['draft', 'rejected'].includes(record.status)" title="Edit Dokumen" placement="top">
                                <Link :href="route('memos.edit', record.id)">
                                    <button 
                                        type="button" 
                                        class="table-btn-action table-btn-edit" 
                                        aria-label="Edit Dokumen"
                                    >
                                        <EditOutlined />
                                    </button>
                                </Link>
                            </a-tooltip>
                            <a-tooltip title="Lihat Detail" placement="top">
                                <Link :href="route('memos.show', record.id)">
                                    <button 
                                        type="button" 
                                        class="table-btn-action table-btn-detail" 
                                        aria-label="Lihat Detail Dokumen"
                                    >
                                        <FileTextOutlined />
                                    </button>
                                </Link>
                            </a-tooltip>
                        </div>
                    </template>
                </template>
            </a-table>

            <div v-if="paginationLinks.length > 3" class="flex justify-center mt-4">
                <a-space wrap>
                    <template v-for="(page, index) in paginationLinks" :key="index">
                        <a-button 
                            v-if="page.url"
                            :type="page.active ? 'primary' : 'default'"
                            @click="router.visit(page.url)"
                            v-html="page.label"
                        />
                        <a-button v-else disabled v-html="page.label" />
                    </template>
                </a-space>
            </div>
        </a-card>
    </AuthenticatedLayout>
</template>
