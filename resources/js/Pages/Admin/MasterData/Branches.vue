<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { PlusOutlined, EditOutlined, DeleteOutlined } from '@ant-design/icons-vue';
import { Modal } from 'ant-design-vue';

const props = defineProps({
    branches: Array,
    areas: Array,
    kcUsers: Array,
});

const showModal = ref(false);
const editingId = ref(null);
const form = useForm({ name: '', area_id: null, kc_user_id: null });

const openCreate = () => { form.reset(); editingId.value = null; showModal.value = true; };
const openEdit = (branch) => { 
    form.name = branch.name; 
    form.area_id = branch.area_id; 
    form.kc_user_id = branch.kc_user_id; 
    editingId.value = branch.id; 
    showModal.value = true; 
};

const save = () => {
    if (editingId.value) {
        form.put(route('admin.branches.update', editingId.value), { onSuccess: () => { showModal.value = false; } });
    } else {
        form.post(route('admin.branches.store'), { onSuccess: () => { showModal.value = false; form.reset(); } });
    }
};

const deleteBranch = (branch) => {
    Modal.confirm({
        title: `Hapus cabang ${branch.name}?`,
        content: 'Cabang yang dihapus tidak dapat dipulihkan.',
        okText: 'Hapus',
        okType: 'danger',
        cancelText: 'Batal',
        onOk() {
            router.delete(route('admin.branches.destroy', branch.id));
        }
    });
};

const columns = [
    { title: 'Cabang', dataIndex: 'name', key: 'name' },
    { title: 'Area', dataIndex: ['area', 'name'], key: 'area' },
    { title: 'Kepala Cabang', dataIndex: ['kc_user', 'name'], key: 'kc_user' },
    { title: 'Aksi', key: 'action', width: '150px' },
];
</script>

<template>
    <Head title="Kelola Cabang" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('dashboard')" class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-sm text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-white/10 dark:hover:text-white">Menu Admin</Link>
                <span class="text-slate-400 dark:text-slate-500">/</span>
                <h1 class="text-xl font-bold text-slate-900 dark:text-white">Kelola Cabang</h1>
            </div>
        </template>

        <div class="admin-master-page max-w-5xl">
            <a-card :bordered="false" class="bg-slate-800/50 border border-white/5">
                <template #title>
                    <span class="text-white font-medium">Daftar Cabang ({{ branches.length }})</span>
                </template>
                <template #extra>
                    <a-button type="primary" @click="openCreate">
                        <template #icon><PlusOutlined /></template>
                        Tambah Cabang
                    </a-button>
                </template>

                <a-table 
                    :dataSource="branches" 
                    :columns="columns" 
                    :rowKey="(record) => record.id"
                    :pagination="{ pageSize: 10 }"
                    :scroll="{ x: 'max-content' }"
                    class="ant-table-dark-custom"
                >
                    <template #bodyCell="{ column, record }">
                        <template v-if="column.key === 'area'">
                            {{ record.area?.name || '-' }}
                        </template>
                        <template v-if="column.key === 'kc_user'">
                            {{ record.kc_user?.name || '-' }}
                        </template>
                        <template v-if="column.key === 'action'">
                            <a-space>
                                <a-button type="link" class="admin-action-button admin-action-edit" @click="openEdit(record)">
                                    <template #icon><EditOutlined /></template>
                                </a-button>
                                <a-button type="link" danger class="admin-action-button admin-action-delete" @click="deleteBranch(record)">
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
            :title="editingId ? 'Edit Cabang' : 'Tambah Cabang'" 
            @ok="save"
            :confirmLoading="form.processing"
        >
            <a-form layout="vertical">
                <a-form-item 
                    label="Nama Cabang" 
                    :validateStatus="form.errors.name ? 'error' : ''" 
                    :help="form.errors.name"
                >
                    <a-input v-model:value="form.name" placeholder="Contoh: Cabang Menteng" />
                </a-form-item>
                <a-form-item 
                    label="Area" 
                    :validateStatus="form.errors.area_id ? 'error' : ''" 
                    :help="form.errors.area_id"
                >
                    <a-select v-model:value="form.area_id" placeholder="Pilih Area" allowClear>
                        <a-select-option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</a-select-option>
                    </a-select>
                </a-form-item>
                <a-form-item 
                    label="Kepala Cabang (Opsional)" 
                    :validateStatus="form.errors.kc_user_id ? 'error' : ''" 
                    :help="form.errors.kc_user_id"
                >
                    <a-select v-model:value="form.kc_user_id" placeholder="Pilih Kepala Cabang" allowClear>
                        <a-select-option v-for="u in kcUsers" :key="u.id" :value="u.id">{{ u.name }}</a-select-option>
                    </a-select>
                </a-form-item>
            </a-form>
        </a-modal>
    </AuthenticatedLayout>
</template>
