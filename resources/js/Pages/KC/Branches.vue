<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { BankOutlined, CheckCircleOutlined } from '@ant-design/icons-vue';
import { createTablePagination } from '@/utils/tablePagination';

const props = defineProps({
    area: Object,
    branches: { type: Array, default: () => [] },
    selectedBranchIds: { type: Array, default: () => [] },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const searchQuery = ref('');
const form = useForm({ branch_ids: [...props.selectedBranchIds] });

watch(() => props.selectedBranchIds, (ids) => {
    form.branch_ids = [...ids];
});

const filteredBranches = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();
    return query
        ? props.branches.filter((branch) => branch.name.toLowerCase().includes(query))
        : props.branches;
});
const pagination = createTablePagination({
    pageSize: 8,
    getTotal: () => filteredBranches.value.length,
});

const selectedCount = computed(() => form.branch_ids.length);
const isSelected = (branchId) => form.branch_ids.includes(branchId);

const toggleBranch = (branch, checked) => {
    if (branch.kc_user_id && branch.kc_user_id !== usePageUserId.value) return;

    const ids = new Set(form.branch_ids);
    if (checked) ids.add(branch.id);
    else ids.delete(branch.id);
    form.branch_ids = [...ids];
};

const usePageUserId = computed(() => user.value?.id);

const save = () => form.put(route('kc.branches.update'));

const columns = [
    { title: '', key: 'selection', width: 48, align: 'center' },
    { title: 'No', key: 'number', width: 58 },
    { title: 'Nama Cabang', dataIndex: 'name', key: 'name' },
    { title: 'Wilayah', key: 'area' },
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

        <div class="kc-branches-page w-full">
            <a-card :bordered="false" class="kc-branches-card">
                <div class="kc-branches-layout">
                    <section class="kc-branches-main">
                        <div class="kc-branches-heading">
                            <div>
                                <h2><BankOutlined /> Cabang yang Dipegang</h2>
                                <p>Wilayah: {{ area?.name || 'Belum ditetapkan' }} <span v-if="user?.name">| KC: {{ user.name }}</span></p>
                            </div>
                        </div>

                        <a-alert
                            type="info"
                            show-icon
                            message="Silakan pilih cabang yang Anda pegang. Cabang yang dipilih akan digunakan saat membuat memo dan Berita Acara."
                            class="kc-branches-alert"
                        />

                        <div class="kc-branches-toolbar">
                            <a-input-search v-model:value="searchQuery" placeholder="Cari nama cabang..." allow-clear />
                            <a-button type="primary" :loading="form.processing" :disabled="!area" @click="save">
                                Simpan Perubahan
                            </a-button>
                        </div>
                        <p v-if="form.errors.branch_ids || form.errors['branch_ids.0']" class="kc-branches-error">
                            {{ form.errors.branch_ids || form.errors['branch_ids.0'] }}
                        </p>
                        <a-alert v-if="form.errors.area" type="warning" show-icon :message="form.errors.area" class="mb-4" />

                        <a-table
                            :data-source="filteredBranches"
                            :columns="columns"
                            :row-key="(record) => record.id"
                            :pagination="pagination"
                            :scroll="{ x: 560 }"
                            size="small"
                            class="kc-branches-table"
                        >
                            <template #bodyCell="{ column, record, index }">
                                <template v-if="column.key === 'selection'">
                                    <a-checkbox
                                        :checked="isSelected(record.id)"
                                        :disabled="record.kc_user_id && record.kc_user_id !== user?.id"
                                        :aria-label="`Pilih cabang ${record.name}`"
                                        @change="(event) => toggleBranch(record, event.target.checked)"
                                    />
                                </template>
                                <template v-else-if="column.key === 'number'">{{ index + 1 }}</template>
                                <template v-else-if="column.key === 'area'">
                                    <span>{{ area?.name || '-' }}</span>
                                    <small v-if="record.kc_user_id && record.kc_user_id !== user?.id" class="kc-branches-owner">
                                        Ditangani {{ record.kc_user?.name || 'KC lain' }}
                                    </small>
                                </template>
                            </template>
                            <template #emptyText>
                                <a-empty :description="area ? 'Belum ada cabang di wilayah ini' : 'Wilayah Anda belum ditetapkan'" />
                            </template>
                        </a-table>

                    </section>

                    <aside class="kc-branches-summary">
                        <h3>Ringkasan</h3>
                        <div><span>KC Anda</span><strong>{{ user?.name || '-' }}</strong></div>
                        <div><span>Wilayah</span><strong>{{ area?.name || '-' }}</strong></div>
                        <div><span>Jumlah cabang dipilih</span><strong>{{ selectedCount }} dari {{ branches.length }}</strong></div>
                        <a-alert type="info" show-icon message="Perubahan akan tersimpan setelah Anda klik tombol Simpan Perubahan." />
                    </aside>
                </div>
            </a-card>
        </div>
    </AuthenticatedLayout>
</template>

<style>
.kc-branches-card {
    color: #e2e8f0;
    background: rgba(30, 41, 59, 0.5) !important;
    border: 1px solid rgba(255, 255, 255, 0.06);
}

.kc-branches-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 250px;
    gap: 20px;
}

.kc-branches-heading h2 {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
    color: #f8fafc;
    font-size: 16px;
    font-weight: 700;
}

.kc-branches-heading h2 .anticon {
    color: #60a5fa;
    font-size: 19px;
}

.kc-branches-heading p {
    margin: 4px 0 16px;
    color: #94a3b8;
    font-size: 12px;
}

.kc-branches-alert {
    margin-bottom: 12px;
}

.kc-branches-toolbar {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 12px;
}

.kc-branches-toolbar .ant-input-search {
    max-width: 280px;
}

.kc-branches-error,
.kc-branches-owner {
    display: block;
    color: #fca5a5;
    font-size: 11px;
}

.kc-branches-table .ant-table-thead > tr > th {
    color: #ffffff !important;
    background: #315a84 !important;
}

html:not(.theme-light) .kc-branches-table .ant-table,
html:not(.theme-light) .kc-branches-table .ant-table-container,
html:not(.theme-light) .kc-branches-table table {
    background: #1e293b !important;
}

html:not(.theme-light) .kc-branches-table .ant-table-tbody > tr > td {
    color: #e2e8f0 !important;
    background: #1e293b !important;
}

html:not(.theme-light) .kc-branches-table .ant-table-tbody > tr:nth-child(even) > td {
    background: #243247 !important;
}

html:not(.theme-light) .kc-branches-table .ant-table-tbody > tr:hover > td {
    background: #334155 !important;
}

html:not(.theme-light) .kc-branches-table .ant-table-tbody > tr > td > * {
    color: inherit !important;
    opacity: 1;
}

.kc-branches-summary {
    align-self: stretch;
    padding: 16px;
    border: 1px solid rgba(148, 163, 184, 0.22);
    border-radius: 8px;
}

.kc-branches-summary h3 {
    margin: 0 0 12px;
    color: #f8fafc;
    font-size: 14px;
    font-weight: 700;
}

.kc-branches-summary > div {
    display: flex;
    justify-content: space-between;
    gap: 8px;
    padding: 9px 0;
    color: #94a3b8;
    border-top: 1px solid rgba(148, 163, 184, 0.16);
    font-size: 12px;
}

.kc-branches-summary strong {
    color: #e2e8f0;
    text-align: right;
}

.kc-branches-summary .ant-alert {
    margin-top: 14px;
}

html.theme-light .kc-branches-card {
    color: #334155;
    background: #ffffff !important;
    border-color: #e2e8f0;
}

html.theme-light .kc-branches-heading h2,
html.theme-light .kc-branches-summary h3 {
    color: #0f172a;
}

html.theme-light .kc-branches-heading p,
html.theme-light .kc-branches-summary > div {
    color: #64748b;
}

html.theme-light .kc-branches-table .ant-table-tbody > tr > td {
    color: #1e293b !important;
    background: #ffffff !important;
}

html.theme-light .kc-branches-table .ant-table-tbody > tr:nth-child(even) > td {
    background: #f8fafc !important;
}

html.theme-light .kc-branches-table .ant-table-tbody > tr:hover > td {
    background: #f1f5f9 !important;
}

html.theme-light .kc-branches-summary strong {
    color: #0f172a;
}

@media (max-width: 800px) {
    .kc-branches-layout {
        grid-template-columns: minmax(0, 1fr);
    }

    .kc-branches-toolbar {
        align-items: stretch;
        flex-direction: column;
    }

    .kc-branches-toolbar .ant-input-search {
        max-width: none;
    }
}
</style>
