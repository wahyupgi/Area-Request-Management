<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { PlusOutlined, EditOutlined, DeleteOutlined, RightOutlined } from '@ant-design/icons-vue';
import { Modal } from 'ant-design-vue';

const props = defineProps({ areas: Array, kcUsers: Array });

const showModal = ref(false);
const editingId = ref(null);
const openKcDropdownId = ref(null);
const kcSearchQuery = ref('');
const form = useForm({ name: '', kc_user_ids: [] });

const selectedKcUsers = computed(() => (props.kcUsers || []).filter((user) =>
    form.kc_user_ids.includes(user.id)
));
const filteredKcUsers = computed(() => {
    const query = kcSearchQuery.value.trim().toLowerCase();
    return query
        ? (props.kcUsers || []).filter((user) => user.name.toLowerCase().includes(query))
        : [];
});

const toggleKcUser = (userId, checked) => {
    const selectedIds = new Set(form.kc_user_ids);
    if (checked) selectedIds.add(userId);
    else selectedIds.delete(userId);
    form.kc_user_ids = [...selectedIds];
};

const handleKcDropdownChange = (userId, isOpen) => {
    openKcDropdownId.value = isOpen ? userId : null;
};

const openCreate = () => {
    form.reset();
    form.kc_user_ids = [];
    editingId.value = null;
    kcSearchQuery.value = '';
    showModal.value = true;
};
const openEdit = (area) => {
    form.name = area.name;
    form.kc_user_ids = (area.kc_users || []).map((user) => user.id);
    editingId.value = area.id;
    kcSearchQuery.value = '';
    showModal.value = true;
};

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
    { title: 'Nama Wilayah', dataIndex: 'name', key: 'name' },
    { title: 'KC & Cabang yang Dipegang', key: 'kc_assignments' },
    { title: 'Aksi', key: 'action', width: '150px' },
];
</script>

<template>
    <Head title="Kelola Wilayah" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('dashboard')" class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-sm text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-white/10 dark:hover:text-white">Menu Admin</Link>
                <span class="text-slate-400 dark:text-slate-500">/</span>
                <h1 class="text-xl font-bold text-slate-900 dark:text-white">Kelola Wilayah</h1>
            </div>
        </template>

        <div class="admin-master-page w-full">
            <a-card :bordered="false" class="bg-slate-800/50 border border-white/5">
                <template #title>
                    <span class="text-white font-medium">Daftar Wilayah ({{ areas.length }})</span>
                </template>
                <template #extra>
                    <a-button type="primary" @click="openCreate">
                        <template #icon><PlusOutlined /></template>
                        Tambah Wilayah
                    </a-button>
                </template>

                <a-table 
                    :dataSource="areas" 
                    :columns="columns" 
                    :rowKey="(record) => record.id"
                    :pagination="{ pageSize: 10 }"
                    :scroll="{ x: 'max-content' }"
                    size="small"
                    class="admin-document-table ant-table-dark-custom"
                >
                    <template #bodyCell="{ column, record }">
                        <template v-if="column.key === 'kc_assignments'">
                            <a-space v-if="record.kc_users?.length" wrap>
                                <a-dropdown
                                    v-for="user in record.kc_users"
                                    :key="user.id"
                                    :open="openKcDropdownId === user.id"
                                    trigger="['click']"
                                    @open-change="(isOpen) => handleKcDropdownChange(user.id, isOpen)"
                                >
                                    <a-button
                                        size="small"
                                        :class="['kc-assignment-dropdown-trigger', { 'is-open': openKcDropdownId === user.id }]"
                                        :aria-expanded="openKcDropdownId === user.id"
                                    >
                                        {{ user.name }}
                                        <RightOutlined aria-hidden="true" />
                                    </a-button>
                                    <template #overlay>
                                        <div class="kc-branch-dropdown-panel">
                                            <strong>{{ user.name }}</strong>
                                            <div v-if="user.assigned_branches?.length" class="mt-2 flex flex-col gap-1">
                                                <span v-for="branch in user.assigned_branches" :key="branch.id">
                                                    {{ branch.name }}
                                                </span>
                                            </div>
                                            <span v-else class="mt-2 block text-slate-400">Belum menambahkan cabang</span>
                                        </div>
                                    </template>
                                </a-dropdown>
                            </a-space>
                            <span v-else class="text-slate-400">Belum ada KC</span>
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
            :title="editingId ? 'Edit Wilayah' : 'Tambah Wilayah'" 
            @ok="save"
            :confirmLoading="form.processing"
        >
            <a-form layout="vertical">
                <a-form-item 
                    label="Nama Wilayah" 
                    :validateStatus="form.errors.name ? 'error' : ''" 
                    :help="form.errors.name"
                >
                    <a-input v-model:value="form.name" placeholder="Contoh: Surakarta" />
                </a-form-item>
                <a-form-item
                    label="KC yang Bertugas"
                    :validateStatus="form.errors.kc_user_ids ? 'error' : ''"
                    :help="form.errors.kc_user_ids"
                >
                    <div class="kc-user-picker">
                        <div class="kc-user-picker-toolbar">
                            <a-input-search
                                v-model:value="kcSearchQuery"
                                placeholder="Cari nama KC"
                                allow-clear
                            />
                            <a-tag color="blue">{{ form.kc_user_ids.length }} dipilih</a-tag>
                        </div>
                        <div v-if="selectedKcUsers.length" class="kc-user-selected-tags">
                            <a-tag
                                v-for="user in selectedKcUsers"
                                :key="user.id"
                                closable
                                @close="toggleKcUser(user.id, false)"
                            >
                                {{ user.name }}
                            </a-tag>
                        </div>
                        <div v-if="kcSearchQuery.trim()" class="kc-user-picker-list">
                            <label v-for="user in filteredKcUsers" :key="user.id" class="kc-user-picker-option">
                                <a-checkbox
                                    :checked="form.kc_user_ids.includes(user.id)"
                                    @change="(event) => toggleKcUser(user.id, event.target.checked)"
                                />
                                <span class="kc-user-picker-name">{{ user.name }}</span>
                                <span v-if="user.area_id && user.area_id !== editingId" class="kc-user-assignment-note">
                                    Sudah ditugaskan
                                </span>
                            </label>
                            <a-empty v-if="!filteredKcUsers.length" description="KC tidak ditemukan" />
                        </div>
                    </div>
                </a-form-item>
            </a-form>
        </a-modal>
    </AuthenticatedLayout>
</template>

<style>
.kc-user-picker-toolbar {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 10px;
}

.kc-user-picker-toolbar .ant-input-search {
    flex: 1;
}

.kc-user-selected-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    margin-bottom: 10px;
}

.kc-user-picker-list {
    max-height: 220px;
    overflow-y: auto;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
}

.kc-user-picker-option {
    display: flex;
    align-items: center;
    gap: 10px;
    min-height: 42px;
    padding: 8px 12px;
    color: #334155;
    cursor: pointer;
}

.kc-user-picker-option + .kc-user-picker-option {
    border-top: 1px solid #e2e8f0;
}

.kc-user-picker-option:hover {
    background: #f8fafc;
}

.kc-user-picker-name {
    flex: 1;
}

.kc-user-assignment-note {
    color: #b45309;
    font-size: 11px;
}

html:not(.theme-light) .kc-user-picker-list {
    border-color: #475569;
}

html:not(.theme-light) .kc-user-picker-option {
    color: #e2e8f0;
}

html:not(.theme-light) .kc-user-picker-option + .kc-user-picker-option {
    border-color: #475569;
}

html:not(.theme-light) .kc-user-picker-option:hover {
    background: #334155;
}

.kc-assignment-dropdown-trigger {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #334155;
    background: #ffffff;
    border-color: #cbd5e1;
    border-radius: 6px;
    transition: color 160ms ease, background-color 160ms ease, border-color 160ms ease, box-shadow 160ms ease;
}

.kc-assignment-dropdown-trigger .anticon {
    font-size: 10px;
    transition: transform 180ms ease;
}

.kc-assignment-dropdown-trigger.is-open {
    color: #1d4ed8 !important;
    background: #eff6ff !important;
    border-color: #60a5fa !important;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.16);
}

.kc-assignment-dropdown-trigger.is-open .anticon {
    transform: rotate(90deg);
}

.kc-assignment-dropdown-trigger:focus-visible {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}

html:not(.theme-light) .kc-assignment-dropdown-trigger {
    color: #cbd5e1;
    background: #1e293b;
    border-color: #475569;
}

html:not(.theme-light) .kc-assignment-dropdown-trigger.is-open {
    color: #bfdbfe !important;
    background: #1e3a5f !important;
    border-color: #3b82f6 !important;
}

.kc-branch-dropdown-panel {
    min-width: 220px;
    max-width: 320px;
    padding: 12px;
    color: #334155;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.16);
}

html:not(.theme-light) .kc-branch-dropdown-panel {
    color: #e2e8f0;
    background: #1e293b;
    border-color: #475569;
}
</style>
