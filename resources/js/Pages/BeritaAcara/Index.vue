<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { PlusOutlined, EyeOutlined } from '@ant-design/icons-vue';

const props = defineProps({
    items: Array,
});

const statusFilter = ref('all');
const filteredItems = computed(() => {
    if (statusFilter.value === 'all') return props.items || [];
    return (props.items || []).filter((item) => item.status === statusFilter.value);
});

const statusColors = { draft: 'default', submitted: 'processing', approved: 'success', rejected: 'error' };
const statusLabels = { draft: 'Draft', submitted: 'Submitted', approved: 'Approved', rejected: 'Rejected' };
const templateLabels = {
    pengembalian_dana: 'Pengembalian Dana',
    permohonan_biaya_kost: 'Permohonan Biaya Kost',
    revisi_absensi: 'Permintaan Revisi Absensi',
    penghapusan_barang_sitaan: 'Penghapusan Barang Sitaan',
    lainnya: 'Lainnya',
};
const onFilterChange = (event) => {
    statusFilter.value = event.target.value || 'all';
};
const columns = [
    { title: 'Kode', dataIndex: 'code', key: 'code' },
    { title: 'Perihal', key: 'template' },
    { title: 'Cabang', key: 'branch' },
    { title: 'Status', dataIndex: 'status', key: 'status' },
    { title: 'Tanggal', dataIndex: 'created_at', key: 'created_at' },
    { title: 'Aksi', key: 'action', align: 'center' },
];
</script>

<template>
    <Head title="Daftar Berita Acara" />
    <AuthenticatedLayout>
        <template #header>
            <h1 class="memo-page-title text-xl font-bold mb-0">Daftar Berita Acara</h1>
        </template>

        <a-card :bordered="false" class="memo-index-card rounded-lg shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <a-radio-group :value="statusFilter === 'all' ? '' : statusFilter" @change="onFilterChange" button-style="solid">
                    <a-radio-button value="">Semua</a-radio-button>
                    <a-radio-button value="draft">Draft</a-radio-button>
                    <a-radio-button value="submitted">Submitted</a-radio-button>
                    <a-radio-button value="approved">Approved</a-radio-button>
                    <a-radio-button value="rejected">Rejected</a-radio-button>
                </a-radio-group>
                <a-button type="primary" @click="router.visit(route('berita-acara.create'))">
                    <template #icon><plus-outlined /></template>
                    Buat Baru
                </a-button>
            </div>

            <a-table
                class="memo-table-pgi mb-4"
                :dataSource="filteredItems"
                :columns="columns"
                rowKey="id"
                :pagination="false"
            >
                <template #bodyCell="{ column, record }">
                    <template v-if="column.key === 'branch'">
                        {{ record.branch?.name || '-' }}
                    </template>
                    <template v-else-if="column.key === 'template'">
                        {{ record.meta?.perihal || record.title || templateLabels[record.meta?.template] || '-' }}
                    </template>
                    <template v-else-if="column.key === 'status'">
                        <a-tag :color="statusColors[record.status]">{{ statusLabels[record.status] || record.status }}</a-tag>
                    </template>
                    <template v-else-if="column.key === 'created_at'">
                        {{ new Date(record.created_at).toLocaleDateString('id-ID') }}
                    </template>
                    <template v-else-if="column.key === 'action'">
                        <a-button size="small" @click="router.visit(route('approvals.ba.history', record.id))">
                            <template #icon><eye-outlined /></template>
                            Detail
                        </a-button>
                    </template>
                </template>

                <template #emptyText>
                    <a-empty description="Belum ada pengajuan." />
                </template>
            </a-table>
        </a-card>
    </AuthenticatedLayout>
</template>
