<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { 
    InboxOutlined, 
    CheckCircleOutlined, 
    CloseCircleOutlined, 
    HistoryOutlined,
    RightOutlined 
} from '@ant-design/icons-vue';

const props = defineProps({
    allMemos: Object,
    defaultTab: String,
});

const activeTab = ref(props.defaultTab || 'masuk');

const memoList = computed(() => props.allMemos?.data ?? []);
const memos = computed(() => memoList.value.filter(m => m.status === 'submitted'));
const approvedMemos = computed(() => memoList.value.filter(m => m.status === 'approved'));
const rejectedMemos = computed(() => memoList.value.filter(m => m.status === 'rejected'));
const history = computed(() => memoList.value);
const paginationLinks = computed(() => props.allMemos?.links ?? []);
const displayedMemos = computed(() => ({ masuk: memos, approved: approvedMemos, rejected: rejectedMemos, riwayat: history }[activeTab.value] || memos).value);

const tableColumns = [
    { title: 'Kode Memo', dataIndex: 'code', key: 'code' },
    { title: 'Memo & Template', key: 'memo' },
    { title: 'Pembuat', key: 'creator' },
    { title: 'Cabang', key: 'branch' },
    { title: 'Status', key: 'status' },
    { title: 'Aksi', key: 'action', align: 'center' },
];

const historyStatusConfig = {
    submitted: {
        label: 'Menunggu',
        color: 'warning',
    },
    approved: {
        label: 'Disetujui',
        color: 'success',
    },
    rejected: {
        label: 'Ditolak',
        color: 'error',
    },
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric', month: 'short', year: 'numeric',
    });
};
</script>

<template>
    <Head title="Kotak Masuk AM" />
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-bold text-gray-800 mb-0">Kotak Masuk & Riwayat Memo</h1>
        </template>

        <a-card :bordered="false" class="am-approval-page rounded-lg shadow-sm">
            <a-tabs v-model:activeKey="activeTab" size="large" :animated="false">
                <a-tab-pane key="masuk"><template #tab><span><inbox-outlined /> Masuk</span></template></a-tab-pane>
                <a-tab-pane key="approved"><template #tab><span><check-circle-outlined /> Disetujui</span></template></a-tab-pane>
                <a-tab-pane key="rejected"><template #tab><span><close-circle-outlined /> Ditolak / Revisi</span></template></a-tab-pane>
                <a-tab-pane key="riwayat"><template #tab><span><history-outlined /> Semua Riwayat</span></template></a-tab-pane>
            </a-tabs>

            <Transition name="inbox-table" mode="out-in">
                <div :key="activeTab" class="am-inbox-table-shell">
                    <a-table
                        :data-source="displayedMemos"
                        :columns="tableColumns"
                        row-key="id"
                        :pagination="false"
                        class="am-inbox-table"
                    >
                <template #bodyCell="{ column, record }">
                    <template v-if="column.key === 'memo'">
                        <div class="font-semibold">{{ record.title }}</div>
                        <div class="text-xs text-gray-500">{{ record.template?.name || '-' }}</div>
                    </template>
                    <template v-else-if="column.key === 'creator'">{{ record.creator?.name || '-' }}</template>
                    <template v-else-if="column.key === 'branch'">{{ record.branch?.name || '-' }}</template>
                    <template v-else-if="column.key === 'status'">
                        <a-tag :color="historyStatusConfig[record.status]?.color || 'default'">{{ historyStatusConfig[record.status]?.label || record.status }}</a-tag>
                    </template>
                    <template v-else-if="column.key === 'action'">
                        <a-button type="primary" ghost size="small" @click="router.visit(record.status === 'submitted' ? route('approvals.review', record.id) : route('approvals.history', record.id))">
                            {{ record.status === 'submitted' ? 'Review' : 'Detail' }}
                            <template #icon><right-outlined /></template>
                        </a-button>
                    </template>
                </template>
                    </a-table>
                </div>
            </Transition>
        </a-card>
    </AuthenticatedLayout>
</template>
