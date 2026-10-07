<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { PlusOutlined, EyeOutlined } from '@ant-design/icons-vue';

const props = defineProps({ items: { type: Array, default: () => [] } });
const statusFilter = ref('');
const templateLabels = {
    form_permohonan_pinjaman: 'Form Permohonan Pinjaman (FPP)',
    form_ijin_tidak_masuk_kerja: 'Form Ijin Tidak Masuk Kerja (FITMK)',
};
const filteredItems = computed(() => statusFilter.value
    ? props.items.filter((item) => item.status === statusFilter.value)
    : props.items
);
const statusColors = { draft: 'default', submitted: 'processing', approved: 'success', rejected: 'error' };
const statusLabels = { draft: 'Draft', submitted: 'Submitted', approved: 'Approved', rejected: 'Rejected' };
const columns = [
    { title: 'Kode', dataIndex: 'code', key: 'code' },
    { title: 'Perihal', key: 'template' },
    { title: 'Cabang', key: 'branch' },
    { title: 'Status', dataIndex: 'status', key: 'status' },
    { title: 'Tanggal', dataIndex: 'created_at', key: 'created_at' },
    { title: 'Aksi', key: 'action', align: 'center' },
];

const onFilterChange = (event) => {
    statusFilter.value = event.target.value;
};
</script>

<template>
    <Head title="Daftar Form Pengajuan" />
    <AuthenticatedLayout>
        <template #header>
            <h1 class="memo-page-title text-xl font-bold mb-0">Daftar Form Pengajuan</h1>
        </template>

        <a-card :bordered="false" class="memo-index-card rounded-lg shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <a-radio-group :value="statusFilter" @change="onFilterChange" button-style="solid">
                    <a-radio-button value="">Semua</a-radio-button>
                    <a-radio-button value="draft">Draft</a-radio-button>
                    <a-radio-button value="submitted">Submitted</a-radio-button>
                    <a-radio-button value="approved">Approved</a-radio-button>
                    <a-radio-button value="rejected">Rejected</a-radio-button>
                </a-radio-group>

                <a-button type="primary" @click="router.visit(route('form-pengajuan.create'))">
                    <template #icon><plus-outlined /></template>
                    Buat Form Pengajuan
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
                        {{ templateLabels[record.template] || record.template }}
                    </template>
                    <template v-else-if="column.key === 'status'">
                        <a-tag :color="statusColors[record.status]">
                            {{ statusLabels[record.status] || record.status }}
                        </a-tag>
                    </template>
                    <template v-else-if="column.key === 'created_at'">
                        {{ new Date(record.created_at).toLocaleDateString('id-ID') }}
                    </template>
                    <template v-else-if="column.key === 'action'">
                        <a-button
                            size="small"
                            @click="router.visit(route('approvals.form-pengajuan.history', record.id))"
                        >
                            <template #icon><eye-outlined /></template>
                            Detail
                        </a-button>
                    </template>
                </template>

                <template #emptyText>
                    <a-empty description="Belum ada Form Pengajuan." />
                </template>
            </a-table>
        </a-card>
    </AuthenticatedLayout>
</template>
