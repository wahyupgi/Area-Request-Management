<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { PlusOutlined, EditOutlined, DeleteOutlined } from '@ant-design/icons-vue';
import { Modal } from 'ant-design-vue';

const props = defineProps({
    users: Array,
    areas: Array,
    branches: Array,
});

const showModal = ref(false);
const editingId = ref(null);
const form = useForm({
    name: '',
    username: '',
    email: '',
    password: '',
    role: 'KC',
    branch_id: null,
    area_id: null,
});

const roleConfig = {
    KC: { label: 'Kepala Cabang' },
    AM: { label: 'Area Manager' },
    ADMIN: { label: 'Administrator' },
};

const resetForm = () => {
    editingId.value = null;
    form.reset();
    form.role = 'KC';
    form.clearErrors();
};

const openCreate = () => {
    resetForm();
    showModal.value = true;
};

const openEdit = (user) => {
    editingId.value = user.id;
    form.name = user.name;
    form.username = user.username;
    form.email = user.email;
    form.password = '';
    form.role = user.role;
    form.branch_id = user.branch_id || null;
    form.area_id = user.area_id || null;
    form.clearErrors();
    showModal.value = true;
};

const save = () => {
    const options = {
        onSuccess: () => { showModal.value = false; resetForm(); },
    };

    if (editingId.value) {
        form.put(route('admin.users.update', editingId.value), options);
    } else {
        form.post(route('admin.users.store'), options);
    }
};

const remove = (user) => {
    Modal.confirm({
        title: `Hapus user ${user.username}?`,
        content: 'User yang dihapus tidak dapat dipulihkan.',
        okText: 'Hapus',
        okType: 'danger',
        cancelText: 'Batal',
        onOk() {
            form.delete(route('admin.users.destroy', user.id), {
                preserveScroll: true,
            });
        }
    });
};

const columns = [
    { title: 'Nama', dataIndex: 'name', key: 'name' },
    { title: 'Username', dataIndex: 'username', key: 'username' },
    { title: 'Email', dataIndex: 'email', key: 'email' },
    { title: 'Role', dataIndex: 'role', key: 'role' },
    { title: 'Cabang / Area', key: 'location' },
    { title: 'Aksi', key: 'action', width: '150px' },
];
</script>

<template>
    <Head title="Kelola User" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('dashboard')" class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-sm text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-white/10 dark:hover:text-white">Menu Admin</Link>
                <span class="text-slate-400 dark:text-slate-500">/</span>
                <h1 class="text-xl font-bold text-slate-900 dark:text-white">Kelola User</h1>
            </div>
        </template>

        <div class="admin-master-page max-w-6xl">
            <a-card :bordered="false" class="bg-slate-800/50 border border-white/5">
                <template #title>
                    <span class="text-white font-medium">Daftar User ({{ users.length }})</span>
                </template>
                <template #extra>
                    <a-button type="primary" @click="openCreate">
                        <template #icon><PlusOutlined /></template>
                        Tambah User
                    </a-button>
                </template>

                <a-table 
                    :dataSource="users" 
                    :columns="columns" 
                    :rowKey="(record) => record.id"
                    :pagination="{ pageSize: 10 }"
                    :scroll="{ x: 'max-content' }"
                    class="ant-table-dark-custom"
                >
                    <template #bodyCell="{ column, record }">
                        <template v-if="column.key === 'role'">
                            {{ roleConfig[record.role]?.label || record.role }}
                        </template>
                        <template v-if="column.key === 'location'">
                            {{ record.branch?.name || record.area?.name || '-' }}
                        </template>
                        <template v-if="column.key === 'action'">
                            <a-space>
                                <a-button type="link" class="admin-action-button admin-action-edit" @click="openEdit(record)">
                                    <template #icon><EditOutlined /></template>
                                </a-button>
                                <a-button type="link" danger class="admin-action-button admin-action-delete" @click="remove(record)">
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
            :title="editingId ? 'Edit User' : 'Tambah User Baru'" 
            @ok="save"
            :confirmLoading="form.processing"
            width="600px"
        >
            <a-form layout="vertical">
                <div class="grid grid-cols-2 gap-4">
                    <a-form-item label="Nama" :validateStatus="form.errors.name ? 'error' : ''" :help="form.errors.name">
                        <a-input v-model:value="form.name" />
                    </a-form-item>
                    <a-form-item label="Username" :validateStatus="form.errors.username ? 'error' : ''" :help="form.errors.username">
                        <a-input v-model:value="form.username" />
                    </a-form-item>
                    <a-form-item label="Email" :validateStatus="form.errors.email ? 'error' : ''" :help="form.errors.email">
                        <a-input v-model:value="form.email" type="email" />
                    </a-form-item>
                    <a-form-item :label="editingId ? 'Password Baru (opsional)' : 'Password'" :validateStatus="form.errors.password ? 'error' : ''" :help="form.errors.password">
                        <a-input-password v-model:value="form.password" />
                    </a-form-item>
                    <a-form-item class="col-span-2" label="Role" :validateStatus="form.errors.role ? 'error' : ''" :help="form.errors.role">
                        <a-select v-model:value="form.role">
                            <a-select-option value="KC">Kepala Cabang</a-select-option>
                            <a-select-option value="AM">Area Manager</a-select-option>
                            <a-select-option value="ADMIN">Administrator</a-select-option>
                        </a-select>
                    </a-form-item>
                    
                    <a-form-item v-if="form.role === 'KC'" class="col-span-2" label="Cabang" :validateStatus="form.errors.branch_id ? 'error' : ''" :help="form.errors.branch_id">
                        <a-select v-model:value="form.branch_id" placeholder="Pilih Cabang" allowClear>
                            <a-select-option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</a-select-option>
                        </a-select>
                    </a-form-item>
                    
                    <a-form-item v-if="form.role === 'AM'" class="col-span-2" label="Area" :validateStatus="form.errors.area_id ? 'error' : ''" :help="form.errors.area_id">
                        <a-select v-model:value="form.area_id" placeholder="Pilih Area" allowClear>
                            <a-select-option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</a-select-option>
                        </a-select>
                    </a-form-item>
                </div>
            </a-form>
        </a-modal>
    </AuthenticatedLayout>
</template>
