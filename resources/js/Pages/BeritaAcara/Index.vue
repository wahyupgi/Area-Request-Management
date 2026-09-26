<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { PlusOutlined, FileTextOutlined } from '@ant-design/icons-vue';

const props = defineProps({
    items: Array,
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
    { title: 'Nomor BA',   dataIndex: 'code',   key: 'code' },
    { title: 'Judul',      dataIndex: 'title',  key: 'title', ellipsis: true },
    { title: 'Status',     dataIndex: 'status', key: 'status' },
    { title: 'Tanggal',    dataIndex: 'created_at', key: 'created_at' },
    { title: 'Aksi',       key: 'action' },
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

        <a-card :bordered="false" class="rounded-lg shadow-sm">
            <a-table
                :dataSource="items"
                :columns="columns"
                rowKey="id"
                :pagination="{ pageSize: 10, showSizeChanger: false }"
            >
                <template #bodyCell="{ column, record }">
                    <template v-if="column.key === 'status'">
                        <a-badge :status="statusColor(record.status)" :text="statusLabel(record.status)" />
                    </template>
                    <template v-else-if="column.key === 'created_at'">
                        {{ new Date(record.created_at).toLocaleDateString('id-ID', { day:'2-digit', month:'long', year:'numeric' }) }}
                    </template>
                    <template v-else-if="column.key === 'action'">
                        <a-button size="small" type="text">
                            <template #icon><file-text-outlined /></template>
                            Detail
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
