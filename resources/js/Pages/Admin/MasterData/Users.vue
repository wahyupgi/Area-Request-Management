<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { PlusOutlined, EditOutlined, DeleteOutlined } from '@ant-design/icons-vue';
import { Modal } from 'ant-design-vue';
import { createTablePagination } from '@/utils/tablePagination';

const props = defineProps({
    users: Array,
    areas: Array,
});

const sortedUsers = computed(() =>
    [...(props.users ?? [])].sort((a, b) => {
        const roleOrder = { ADMIN: 0, AM: 1, KC: 2 };
        const roleDifference = (roleOrder[a.role] ?? 3) - (roleOrder[b.role] ?? 3);
        if (roleDifference !== 0) return roleDifference;
        return (a.name ?? '').localeCompare(b.name ?? '', 'id', { sensitivity: 'base' });
    })
);
const searchQuery = ref('');
const filteredUsers = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();
    if (!query) return sortedUsers.value;

    return sortedUsers.value.filter((user) => [
        user.name,
        user.username,
        user.email,
        roleConfig[user.role]?.label,
        user.role,
        user.area?.name,
        user.area?.parent?.name,
        user.branch?.name,
    ].some((value) => value?.toLowerCase().includes(query)));
});
const pagination = createTablePagination({ pageSize: 10, getTotal: () => filteredUsers.value.length });
const availableAreas = computed(() => (props.areas || []).filter((area) =>
    form.role === 'AM' ? !area.parent_id : form.role === 'KC' ? Boolean(area.parent_id) : false
));

const showModal = ref(false);
const editingId = ref(null);
const form = useForm({
    name: '',
    username: '',
    email: '',
    password: '',
    role: 'KC',
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
    form.area_id = user.area_id || null;
    form.clearErrors();
    showModal.value = true;
};
const changeRole = () => {
    form.area_id = null;
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

const headerCenter = () => ({ style: { textAlign: 'center' } });

const columns = [
    { title: 'Nama', dataIndex: 'name', key: 'name', customHeaderCell: headerCenter },
    { title: 'Username', dataIndex: 'username', key: 'username', customHeaderCell: headerCenter },
    { title: 'Email', dataIndex: 'email', key: 'email', customHeaderCell: headerCenter },
    { title: 'Role', dataIndex: 'role', key: 'role', customHeaderCell: headerCenter },
    { title: 'Wilayah', key: 'location', width: '180px', customHeaderCell: headerCenter },
    { title: 'Aksi', key: 'action', width: '100px', customHeaderCell: headerCenter },
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

        <div class="admin-master-page w-full">
            <a-card :bordered="false" class="bg-slate-800/50 border border-white/5 admin-user-list-card">
                <template #title>
                    <span class="text-white font-medium">Daftar User ({{ users.length }})</span>
                </template>
                <template #extra>
                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <a-input-search
                            v-model:value="searchQuery"
                            placeholder="Cari nama, username, email, role, atau wilayah"
                            allow-clear
                            class="w-full sm:w-72"
                        />
                        <a-button type="primary" @click="openCreate">
                            <template #icon><PlusOutlined /></template>
                            Tambah User
                        </a-button>
                    </div>
                </template>

                <a-table 
                    :dataSource="filteredUsers"
                    :columns="columns" 
                    :rowKey="(record) => record.id"
                    :pagination="pagination"
                    :scroll="{ x: 'max-content' }"
                    size="small"
                    class="admin-document-table ant-table-dark-custom"
                >
                    <template #bodyCell="{ column, record }">
                        <template v-if="column.key === 'role'">
                            {{ roleConfig[record.role]?.label || record.role }}
                        </template>
                        <template v-if="column.key === 'location'">
                            {{ record.role === 'AM'
                                ? record.area?.name || '-'
                                : record.role === 'KC'
                                    ? [record.area?.name, record.area?.parent?.name].filter(Boolean).join(' — ') || record.branch?.name || '-'
                                    : '-' }}
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
                        <a-select v-model:value="form.role" @change="changeRole">
                            <a-select-option value="KC">Kepala Cabang</a-select-option>
                            <a-select-option value="AM">Area Manager</a-select-option>
                            <a-select-option value="ADMIN">Administrator</a-select-option>
                        </a-select>
                    </a-form-item>
                    
                    <a-form-item v-if="form.role === 'AM' || form.role === 'KC'" class="col-span-2" :label="form.role === 'AM' ? 'Provinsi' : 'Kota / Kabupaten'" :validateStatus="form.errors.area_id ? 'error' : ''" :help="form.errors.area_id">
                        <a-select v-model:value="form.area_id" :placeholder="form.role === 'AM' ? 'Pilih provinsi' : 'Pilih kota/kabupaten'" allowClear>
                            <a-select-option v-for="area in availableAreas" :key="area.id" :value="area.id">
                                {{ area.name }}{{ form.role === 'KC' && area.parent ? ` — ${area.parent.name}` : '' }}
                            </a-select-option>
                        </a-select>
                    </a-form-item>
                </div>
            </a-form>
        </a-modal>
    </AuthenticatedLayout>
</template>
