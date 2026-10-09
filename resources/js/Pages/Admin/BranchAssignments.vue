<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { DeleteOutlined, EditOutlined, PlusOutlined, TeamOutlined } from '@ant-design/icons-vue';
import { Modal } from 'ant-design-vue';

const props = defineProps({
    areas: { type: Array, default: () => [] },
    provinces: { type: Array, default: () => [] },
    kcUsers: { type: Array, default: () => [] },
    branches: { type: Array, default: () => [] },
});

const searchQuery = ref('');
const kcPage = ref(1);
const areaPage = ref(1);
const KC_PAGE_SIZE = 8;
const AREA_PAGE_SIZE = 6;
watch(searchQuery, () => { kcPage.value = 1; });
const editingKc = ref(null);
const editingAreaId = ref(null);
const areaModalOpen = ref(false);
const form = useForm({ area_id: null, branch_ids: [] });
const areaForm = useForm({ name: '', parent_id: null, existing_branches: [], branch_names: [''] });

const filteredKcUsers = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();
    if (!query) return props.kcUsers;

    return props.kcUsers.filter((kc) =>
        kc.name.toLowerCase().includes(query) ||
        kc.area?.name?.toLowerCase().includes(query) ||
        kc.province?.name?.toLowerCase().includes(query) ||
        kc.branches.some((branch) => branch.name.toLowerCase().includes(query))
    );
});

const availableBranches = computed(() => {
    if (!editingKc.value || !form.area_id) return [];

    return props.branches.filter((branch) => branch.area_id === form.area_id);
});

const openEdit = (kc) => {
    editingKc.value = kc;
    form.clearErrors();
    form.area_id = kc.area?.id ?? null;
    const areaBranchIds = new Set(props.branches.filter((branch) => branch.area_id === form.area_id).map((branch) => branch.id));
    form.branch_ids = kc.branch_ids.filter((id) => areaBranchIds.has(id));
};

const changeArea = (areaId) => {
    form.area_id = areaId ?? null;
    form.branch_ids = [];
    form.clearErrors('branch_ids');
};

const closeEdit = () => {
    editingKc.value = null;
    form.reset();
};

const toggleBranch = (branchId, checked) => {
    const ids = new Set(form.branch_ids);
    if (checked) ids.add(branchId);
    else ids.delete(branchId);
    form.branch_ids = [...ids];
};

const save = () => {
    const visibleIds = new Set(availableBranches.value.map((branch) => branch.id));
    form.branch_ids = form.branch_ids.filter((id) => visibleIds.has(id));

    form.put(route('admin.branch-assignments.update', editingKc.value.id), {
        onSuccess: closeEdit,
    });
};

const openCreateArea = () => {
    editingAreaId.value = null;
    areaForm.name = '';
    areaForm.parent_id = null;
    areaForm.existing_branches = [];
    areaForm.branch_names = [''];
    areaForm.clearErrors();
    areaModalOpen.value = true;
};

const openEditArea = (area) => {
    editingAreaId.value = area.id;
    areaForm.name = area.name;
    areaForm.parent_id = area.parent_id ?? null;
    areaForm.existing_branches = props.branches
        .filter((branch) => branch.area_id === area.id)
        .map((branch) => ({ id: branch.id, name: branch.name }));
    areaForm.branch_names = [];
    areaForm.clearErrors();
    areaModalOpen.value = true;
};

const addBranchField = () => areaForm.branch_names.push('');
const removeBranchField = (index) => areaForm.branch_names.splice(index, 1);
const branchNameError = (index) => areaForm.errors[`branch_names.${index}`];
const existingBranchError = (index) =>
    areaForm.errors[`existing_branches.${index}.name`] || areaForm.errors[`existing_branches.${index}.id`];

const saveArea = () => {
    if (!areaForm.parent_id) {
        areaForm.setError('parent_id', 'Pilih provinsi untuk kota/kabupaten ini.');
        return;
    }

    areaForm.transform((data) => ({
        ...data,
        branch_names: data.branch_names.map((name) => name.trim()).filter(Boolean),
    }));

    const options = { onSuccess: () => { areaModalOpen.value = false; areaForm.reset(); } };

    if (editingAreaId.value) {
        areaForm.put(route('admin.areas.update', editingAreaId.value), options);
        return;
    }

    areaForm.post(route('admin.areas.store'), options);
};

const deleteArea = (area) => {
    Modal.confirm({
        title: `Hapus wilayah ${area.name}?`,
        content: 'Wilayah yang dihapus tidak dapat dipulihkan.',
        okText: 'Hapus',
        okType: 'danger',
        cancelText: 'Batal',
        onOk: () => router.delete(route('admin.areas.destroy', area.id)),
    });
};

const columns = [
    { title: 'No', key: 'number', width: 54 },
    { title: 'Nama KC', dataIndex: 'name', key: 'name' },
    { title: 'Wilayah', key: 'area' },
    { title: 'Cabang yang Dipegang', key: 'branches' },
    { title: 'Jumlah Cabang', key: 'count', width: 120 },
    { title: 'Aksi', key: 'action', width: 72, align: 'center' },
];
</script>

<template>
    <Head title="Cabang Saya" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('dashboard')" class="text-sm text-slate-500 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white">Dashboard</Link>
                <span class="text-slate-400">/</span>
                <h1 class="text-xl font-bold text-slate-900 dark:text-white">Cabang Saya</h1>
            </div>
        </template>

        <div class="admin-master-page w-full">
            <a-card :bordered="false" class="admin-branch-mapping-card">
                <div class="admin-branch-mapping-heading">
                    <div>
                        <h2><TeamOutlined /> Mapping KC – Wilayah – Cabang</h2>
                        <p>Daftar KC beserta wilayah dan cabang yang dipegang.</p>
                    </div>
                    <a-input-search
                        v-model:value="searchQuery"
                        placeholder="Cari KC / Wilayah..."
                        allow-clear
                    />
                </div>

                <a-table
                    :data-source="filteredKcUsers"
                    :columns="columns"
                    :row-key="(record) => record.id"
                    :pagination="{ pageSize: KC_PAGE_SIZE, current: kcPage, showSizeChanger: false, hideOnSinglePage: true }"
                    :scroll="{ x: 900 }"
                    size="small"
                    class="admin-branch-mapping-table"
                    @change="(pagination) => (kcPage = pagination.current)"
                >
                    <template #bodyCell="{ column, record, index }">
                        <template v-if="column.key === 'number'">{{ (kcPage - 1) * KC_PAGE_SIZE + index + 1 }}</template>
                        <template v-else-if="column.key === 'area'">
                            {{ record.area ? `${record.area.name} — ${record.province?.name || 'Provinsi belum ditetapkan'}` : 'Belum ditetapkan' }}
                        </template>
                        <template v-else-if="column.key === 'branches'">
                            <div v-if="record.branches.length" class="admin-branch-tags">
                                <a-tag v-for="branch in record.branches.slice(0, 3)" :key="branch.id" color="blue">
                                    {{ branch.name }}
                                </a-tag>
                                <a-popover v-if="record.branches.length > 3" trigger="click" placement="bottom">
                                    <template #content>
                                        <div class="max-w-64">
                                            <p class="mb-2 text-xs font-medium">Cabang lainnya</p>
                                            <div class="flex flex-col items-start gap-1">
                                                <a-tag v-for="branch in record.branches.slice(3)" :key="branch.id" color="blue">
                                                    {{ branch.name }}
                                                </a-tag>
                                            </div>
                                        </div>
                                    </template>
                                    <a-tag color="default" class="cursor-pointer">
                                        +{{ record.branches.length - 3 }}
                                    </a-tag>
                                </a-popover>
                            </div>
                            <span v-else class="text-slate-400">Belum ada cabang</span>
                        </template>
                        <template v-else-if="column.key === 'count'">{{ record.branches.length }}</template>
                        <template v-else-if="column.key === 'action'">
                            <a-tooltip title="Atur wilayah dan cabang">
                                <a-button
                                    type="primary"
                                    ghost
                                    size="small"
                                    aria-label="Atur cabang KC"
                                    @click="openEdit(record)"
                                >
                                    <template #icon><EditOutlined /></template>
                                </a-button>
                            </a-tooltip>
                        </template>
                    </template>
                    <template #emptyText>
                        <a-empty description="Tidak ada data KC yang cocok" />
                    </template>
                </a-table>
            </a-card>

            <a-card :bordered="false" class="admin-branch-mapping-card admin-area-list-card">
                <div class="admin-area-list-heading">
                    <div>
                        <h2>Daftar Kota / Kabupaten</h2>
                        <p>Kelola kota/kabupaten di dalam provinsi yang dapat ditetapkan kepada KC.</p>
                    </div>
                    <a-button type="primary" @click="openCreateArea">
                        <template #icon><PlusOutlined /></template>
                        Tambah Kota / Kabupaten
                    </a-button>
                </div>
                <a-table
                    :data-source="areas"
                    :columns="[
                        { title: 'No', key: 'number', width: 54 },
                        { title: 'Kota / Kabupaten', dataIndex: 'name', key: 'name' },
                        { title: 'Provinsi', key: 'province' },
                        { title: 'Jumlah KC', dataIndex: 'kc_users_count', key: 'kc_users_count', width: 120 },
                        { title: 'Jumlah Cabang', dataIndex: 'branches_count', key: 'branches_count', width: 150 },
                        { title: 'Aksi', key: 'action', width: 100, align: 'center' },
                    ]"
                    :row-key="(record) => record.id"
                    :pagination="{ pageSize: AREA_PAGE_SIZE, current: areaPage, showSizeChanger: false, hideOnSinglePage: true }"
                    size="small"
                    class="admin-branch-mapping-table"
                    @change="(pagination) => (areaPage = pagination.current)"
                >
                    <template #bodyCell="{ column, record, index }">
                        <template v-if="column.key === 'number'">{{ (areaPage - 1) * AREA_PAGE_SIZE + index + 1 }}</template>
                        <template v-else-if="column.key === 'province'">{{ record.parent?.name || '-' }}</template>
                        <template v-if="column.key === 'action'">
                            <a-space>
                                <a-button type="link" aria-label="Edit wilayah" @click="openEditArea(record)">
                                    <template #icon><EditOutlined /></template>
                                </a-button>
                                <a-button type="link" danger aria-label="Hapus wilayah" @click="deleteArea(record)">
                                    <template #icon><DeleteOutlined /></template>
                                </a-button>
                            </a-space>
                        </template>
                    </template>
                    <template #emptyText><a-empty description="Belum ada wilayah" /></template>
                </a-table>
            </a-card>

        </div>

        <a-modal
            :open="!!editingKc"
            :title="editingKc ? `Atur Wilayah & Cabang — ${editingKc.name}` : 'Atur Wilayah & Cabang'"
            :confirm-loading="form.processing"
            ok-text="Simpan Perubahan"
            cancel-text="Batal"
            @ok="save"
            @cancel="closeEdit"
        >
            <template v-if="editingKc">
                <a-form-item label="Wilayah" :validate-status="form.errors.area_id ? 'error' : ''" :help="form.errors.area_id">
                    <a-select
                        v-model:value="form.area_id"
                    :options="areas.map((area) => ({ value: area.id, label: `${area.name} — ${area.parent?.name || 'Provinsi belum ditetapkan'}` }))"
                    placeholder="Pilih kota/kabupaten"
                        allow-clear
                        @change="changeArea"
                    />
                </a-form-item>
                <p v-if="form.errors.branch_ids || form.errors['branch_ids.0']" class="admin-branch-modal-error">
                    {{ form.errors.branch_ids || form.errors['branch_ids.0'] }}
                </p>
                <div v-if="availableBranches.length" class="admin-branch-picker">
                    <label v-for="branch in availableBranches" :key="branch.id" class="admin-branch-picker-option">
                        <a-checkbox
                            :checked="form.branch_ids.includes(branch.id)"
                            @change="(event) => toggleBranch(branch.id, event.target.checked)"
                        />
                        <span class="admin-branch-option-name">
                            {{ branch.name }}
                            <small v-if="branch.kc_user_id && branch.kc_user_id !== editingKc.id">
                                Saat disimpan, cabang dialihkan dari {{ branch.kc_user_name || 'KC lain' }}
                            </small>
                        </span>
                    </label>
                </div>
                <a-empty v-else description="Belum ada cabang yang tersedia di kota/kabupaten ini" />
                <p class="admin-branch-modal-hint">Pilih kota/kabupaten terlebih dahulu, lalu tentukan cabang yang menjadi tanggung jawab {{ editingKc.name }}.</p>
            </template>
        </a-modal>

        <a-modal
            v-model:open="areaModalOpen"
            :title="editingAreaId ? 'Edit Kota / Kabupaten' : 'Tambah Kota / Kabupaten'"
            :confirm-loading="areaForm.processing"
            ok-text="Simpan"
            cancel-text="Batal"
            @ok="saveArea"
        >
            <a-form layout="vertical">
                <a-form-item label="Provinsi" :validate-status="areaForm.errors.parent_id ? 'error' : ''" :help="areaForm.errors.parent_id">
                    <a-select v-model:value="areaForm.parent_id" placeholder="Pilih provinsi" @change="areaForm.clearErrors('parent_id')">
                        <a-select-option v-for="province in provinces" :key="province.id" :value="province.id">{{ province.name }}</a-select-option>
                    </a-select>
                </a-form-item>
                <a-form-item label="Nama Kota / Kabupaten" :validate-status="areaForm.errors.name ? 'error' : ''" :help="areaForm.errors.name">
                    <a-input v-model:value="areaForm.name" placeholder="Contoh: Surakarta" />
                </a-form-item>
                <a-form-item v-if="editingAreaId" label="Cabang Saat Ini">
                    <div v-for="(branch, index) in areaForm.existing_branches" :key="branch.id" class="admin-branch-field">
                        <a-input
                            v-model:value="branch.name"
                            :status="existingBranchError(index) ? 'error' : ''"
                        />
                        <small v-if="existingBranchError(index)" class="admin-branch-field-error">{{ existingBranchError(index) }}</small>
                    </div>
                    <span v-if="!areaForm.existing_branches.length" class="text-slate-400">Belum ada cabang di wilayah ini</span>
                </a-form-item>
                <a-form-item :label="editingAreaId ? 'Tambah Cabang Baru' : 'Cabang'">
                    <div v-for="(_, index) in areaForm.branch_names" :key="index" class="admin-branch-field">
                        <a-input
                            v-model:value="areaForm.branch_names[index]"
                            placeholder="Contoh: SKT001"
                            :status="branchNameError(index) ? 'error' : ''"
                        />
                        <a-button type="text" danger aria-label="Hapus cabang" @click="removeBranchField(index)">
                            <template #icon><DeleteOutlined /></template>
                        </a-button>
                        <small v-if="branchNameError(index)" class="admin-branch-field-error">{{ branchNameError(index) }}</small>
                    </div>
                    <a-button type="dashed" block @click="addBranchField">
                        <template #icon><PlusOutlined /></template>
                        Tambah Cabang
                    </a-button>
                </a-form-item>
                <p class="admin-branch-modal-hint">
                    Atur KC yang menangani cabang melalui tombol edit pada tabel mapping KC.
                </p>
            </a-form>
        </a-modal>
    </AuthenticatedLayout>
</template>

<style>
.admin-branch-mapping-card {
    color: #e2e8f0;
    background: rgba(30, 41, 59, 0.5) !important;
    border: 1px solid rgba(255, 255, 255, 0.06);
}

.admin-area-list-card {
    margin-top: 20px;
}

.admin-area-list-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 16px;
}

.admin-area-list-heading h2 {
    margin: 0;
    color: #f8fafc;
    font-size: 16px;
    font-weight: 700;
}

.admin-area-list-heading p {
    margin: 4px 0 0;
    color: #94a3b8;
    font-size: 12px;
}

.admin-branch-mapping-heading {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 20px;
}

.admin-branch-mapping-heading h2 {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
    color: #f8fafc;
    font-size: 16px;
    font-weight: 700;
}

.admin-branch-mapping-heading h2 .anticon {
    color: #60a5fa;
}

.admin-branch-mapping-heading p {
    margin: 4px 0 0;
    color: #94a3b8;
    font-size: 12px;
}

.admin-branch-mapping-heading .ant-input-search {
    max-width: 280px;
}

.admin-branch-mapping-table .ant-table-thead > tr > th {
    color: #ffffff !important;
    background: #315a84 !important;
}

.admin-branch-mapping-table .ant-table-tbody > tr > td {
    color: #e2e8f0 !important;
}

.admin-branch-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 2px;
}

.admin-branch-tags .ant-tag {
    margin-inline-end: 2px;
}

.admin-branch-modal-subtitle,
.admin-branch-modal-hint {
    color: #94a3b8;
    font-size: 11px;
}

.admin-branch-picker {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #475569;
    border-radius: 6px;
}

.admin-branch-picker-option {
    display: flex;
    align-items: center;
    gap: 10px;
    min-height: 42px;
    padding: 8px 12px;
    color: #e2e8f0;
    cursor: pointer;
}

.admin-branch-option-name {
    display: flex;
    flex: 1;
    flex-direction: column;
    gap: 2px;
}

.admin-branch-option-name small {
    color: #fbbf24;
    font-size: 10px;
}

.admin-branch-picker-option + .admin-branch-picker-option {
    border-top: 1px solid #475569;
}

.admin-branch-picker-option:hover {
    background: #334155;
}

.admin-branch-field {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 4px;
    margin-bottom: 8px;
}

.admin-branch-field .ant-input {
    flex: 1;
}

.admin-branch-field-error {
    flex-basis: 100%;
    color: #dc2626;
}

.admin-branch-modal-error {
    color: #dc2626;
    font-size: 12px;
}

.admin-branch-modal-hint {
    margin: 12px 0 0;
}

html.theme-light .admin-branch-mapping-card {
    color: #334155;
    background: #ffffff !important;
    border-color: #e2e8f0;
}

html.theme-light .admin-branch-mapping-heading h2 {
    color: #0f172a;
}

html.theme-light .admin-area-list-heading h2 {
    color: #0f172a;
}

html.theme-light .admin-branch-mapping-heading p {
    color: #64748b;
}

html.theme-light .admin-area-list-heading p {
    color: #64748b;
}

html.theme-light .admin-branch-mapping-table .ant-table-tbody > tr > td {
    color: #1e293b !important;
}

html.theme-light .admin-branch-modal-subtitle,
html.theme-light .admin-branch-modal-hint {
    color: #64748b;
}

html.theme-light .admin-branch-picker-option {
    color: #334155;
}

html.theme-light .admin-branch-picker-option + .admin-branch-picker-option {
    border-color: #e2e8f0;
}

html.theme-light .admin-branch-picker-option:hover {
    background: #f8fafc;
}

html.theme-light .admin-branch-option-name small {
    color: #b45309;
}

@media (max-width: 700px) {
    .admin-branch-mapping-heading {
        flex-direction: column;
    }

    .admin-branch-mapping-heading .ant-input-search {
        max-width: none;
        width: 100%;
    }

    .admin-area-list-heading {
        align-items: stretch;
        flex-direction: column;
    }
}
</style>
