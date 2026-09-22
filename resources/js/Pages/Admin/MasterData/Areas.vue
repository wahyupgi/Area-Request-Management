<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { PlusOutlined, EditOutlined, DeleteOutlined } from '@ant-design/icons-vue';
import { Modal } from 'ant-design-vue';

const props = defineProps({ areas: Array });

const showModal = ref(false);
const editingId = ref(null);
const form = useForm({ name: '' });

const openCreate = () => { form.reset(); editingId.value = null; showModal.value = true; };
const openEdit = (area) => { form.name = area.name; editingId.value = area.id; showModal.value = true; };

const save = () => {
    if (editingId.value) {
        form.put(route('admin.areas.update', editingId.value), { onSuccess: () => { showModal.value = false; } });
    } else {
        form.post(route('admin.areas.store'), { onSuccess: () => { showModal.value = false; form.reset(); } });
    }
};

const deleteArea = (area) => {
    Modal.confirm({
        title: `Hapus area ${area.name}?`,
        content: 'Area yang dihapus tidak dapat dipulihkan.',
        okText: 'Hapus',
        okType: 'danger',
        cancelText: 'Batal',
        onOk() {
            router.delete(route('admin.areas.destroy', area.id));
        }
    });
};

const columns = [
    { title: 'Nama Area', dataIndex: 'name', key: 'name' },
    { title: 'Jumlah Cabang', dataIndex: 'branches_count', key: 'branches_count' },
    { title: 'Aksi', key: 'action', width: '150px' },
];
</script>

<template>
    <Head title="Kelola Area" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('dashboard')" class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-sm text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-white/10 dark:hover:text-white">Menu Admin</Link>
                <span class="text-slate-400 dark:text-slate-500">/</span>
                <h1 class="text-xl font-bold text-slate-900 dark:text-white">Kelola Area</h1>
            </div>
        </template>

        <div class="admin-master-page max-w-4xl">
            <a-card :bordered="false" class="bg-slate-800/50 border border-white/5">
                <template #title>
                    <span class="text-white font-medium">Daftar Area ({{ areas.length }})</span>
                </template>
                <template #extra>
                    <a-button type="primary" @click="openCreate">
                        <template #icon><PlusOutlined /></template>
                        Tambah Area
                    </a-button>
                </template>

                <a-table 
                    :dataSource="areas" 
                    :columns="columns" 
                    :rowKey="(record) => record.id"
                    :pagination="{ pageSize: 10 }"
                    :scroll="{ x: 'max-content' }"
                    class="ant-table-dark-custom"
                >
                    <template #bodyCell="{ column, record }">
                        <template v-if="column.key === 'branches_count'">
                            {{ record.branches_count || 0 }} cabang
                        </template>
                        <template v-if="column.key === 'action'">
                            <a-space>
                                <a-button type="link" class="admin-action-button admin-action-edit" @click="openEdit(record)">
                                    <template #icon><EditOutlined /></template>
                                </a-button>
                                <a-button type="link" danger class="admin-action-button admin-action-delete" @click="deleteArea(record)">
                                    <template #icon><DeleteOutlined /></template>
                                </a-button>
                            </a-space>
                        </template>
                    </template>
                </a-table>
            </a-card>
        </div>

        <a-modal 
            v-model:open="showModal" 
            :title="editingId ? 'Edit Area' : 'Tambah Area'" 
            @ok="save"
            :confirmLoading="form.processing"
        >
            <a-form layout="vertical">
                <a-form-item 
                    label="Nama Area" 
                    :validateStatus="form.errors.name ? 'error' : ''" 
                    :help="form.errors.name"
                >
                    <a-input v-model:value="form.name" placeholder="Contoh: Area Jakarta" />
                </a-form-item>
            </a-form>
        </a-modal>
    </AuthenticatedLayout>
</template>
