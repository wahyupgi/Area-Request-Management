<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { PlusOutlined } from '@ant-design/icons-vue';

const props = defineProps({ items: { type: Array, default: () => [] } });
const statusFilter = ref('all');
const templateLabels = {
    form_permohonan_pinjaman: 'Form Permohonan Pinjaman (FPP)',
    form_ijin_tidak_masuk_kerja: 'Form Ijin Tidak Masuk Kerja (FITMK)',
};
const filteredItems = computed(() => statusFilter.value === 'all'
    ? props.items
    : props.items.filter((item) => item.status === statusFilter.value)
);
const statusLabels = {
    draft: 'Draft',
    submitted: 'Menunggu Persetujuan',
    approved: 'Disetujui',
    rejected: 'Ditolak',
};
const statusColors = { draft: 'default', submitted: 'processing', approved: 'success', rejected: 'error' };
const columns = [
    { title: 'Nomor Form', dataIndex: 'code', key: 'code', width: 230 },
    { title: 'Template', dataIndex: 'title', key: 'title' },
    { title: 'Cabang', key: 'branch' },
    { title: 'Status', key: 'status', width: 170 },
    { title: 'Tanggal', key: 'date', width: 160 },
    { title: 'Aksi', key: 'action', width: 90, align: 'center' },
];
</script>

<template>
    <Head title="Riwayat Form Pengajuan" />
    <AuthenticatedLayout>
        <template #header><h1 class="text-xl font-bold mb-0">Riwayat Form Pengajuan</h1></template>
        <div class="mb-4 flex items-center justify-between">
            <span class="text-sm text-gray-500">Total: {{ items.length }} pengajuan</span>
            <a-button type="primary" @click="router.visit(route('form-pengajuan.create'))">
                <template #icon><plus-outlined /></template>
                Buat Form Pengajuan
            </a-button>
        </div>
        <a-card :bordered="false" class="rounded-lg shadow-sm">
            <a-radio-group v-model:value="statusFilter" button-style="solid" class="mb-4">
                <a-radio-button value="all">Semua ({{ items.length }})</a-radio-button>
                <a-radio-button value="submitted">Menunggu ({{ items.filter(item => item.status === 'submitted').length }})</a-radio-button>
                <a-radio-button value="approved">Disetujui ({{ items.filter(item => item.status === 'approved').length }})</a-radio-button>
                <a-radio-button value="rejected">Ditolak ({{ items.filter(item => item.status === 'rejected').length }})</a-radio-button>
                <a-radio-button value="draft">Draft ({{ items.filter(item => item.status === 'draft').length }})</a-radio-button>
            </a-radio-group>
            <a-table :data-source="filteredItems" :columns="columns" row-key="id" :pagination="{ pageSize: 10, hideOnSinglePage: true }">
                <template #bodyCell="{ column, record }">
                    <template v-if="column.key === 'title'">
                        <div class="font-medium">{{ templateLabels[record.template] || record.title }}</div>
                    </template>
                    <template v-else-if="column.key === 'branch'">{{ record.branch?.name || '-' }}</template>
                    <template v-else-if="column.key === 'status'">
                        <a-tag :color="statusColors[record.status]">{{ statusLabels[record.status] || record.status }}</a-tag>
                    </template>
                    <template v-else-if="column.key === 'date'">
                        {{ new Date(record.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }) }}
                    </template>
                    <template v-else-if="column.key === 'action'">
                        <a-button type="link" @click="router.visit(route('approvals.form-pengajuan.history', record.id))">Detail</a-button>
                    </template>
                </template>
                <template #emptyText>
                    <a-empty description="Belum ada Form Pengajuan">
                        <a-button type="primary" @click="router.visit(route('form-pengajuan.create'))">Buat Form Pengajuan Pertama</a-button>
                    </a-empty>
                </template>
            </a-table>
        </a-card>
    </AuthenticatedLayout>
</template>
