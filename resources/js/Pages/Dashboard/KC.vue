<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    memos: Array,
    stats: Object,
    submissionStats: { type: Object, default: () => ({}) },
    baStats: { type: Object, default: () => ({}) },
    recentBA: { type: Array, default: () => [] },
    formPengajuanStats: { type: Object, default: () => ({}) },
    recentFormPengajuans: { type: Array, default: () => [] },
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const searchQuery = ref('');
const statusFilter = ref('all');

// Realtime Clock & Timer
const now = ref(new Date());
let clockInterval = null;

onMounted(() => {
    clockInterval = setInterval(() => {
        now.value = new Date();
    }, 1000);
});

onUnmounted(() => {
    if (clockInterval) clearInterval(clockInterval);
});

// Dynamic Greeting based on hour
const greeting = computed(() => {
    const hour = now.value.getHours();
    if (hour >= 4 && hour < 11) {
        return { text: 'Selamat Pagi'}; 
    } else if (hour >= 11 && hour < 15) {
        return { text: 'Selamat Siang'}; 
    } else if (hour >= 15 && hour < 18) {
        return { text: 'Selamat Sore'}; 
    } else {
        return { text: 'Selamat Malam'}; 
    }
});

// Clean user display name
const userDisplayName = computed(() => {
    const name = user.value?.name || 'Kepala Cabang';
    return name.split('(')[0].trim();
});

const formattedDate = computed(() => {
    return new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    }).format(now.value);
});

const formattedTime = computed(() => {
    return now.value.toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false
    }) + ' WIB';
});

const statusConfig = {
    draft: { label: 'Draft' },
    submitted: { label: 'Menunggu AM' },
    approved: { label: 'Disetujui' },
    rejected: { label: 'Ditolak' },
};

const baTemplateLabels = {
    pengembalian_dana: 'Pengembalian Dana',
    permohonan_biaya_kost: 'Permohonan Biaya Kost',
    revisi_absensi: 'Permintaan Revisi Absensi',
    penghapusan_barang_sitaan: 'Penghapusan Barang Sitaan',
    lainnya: 'Lainnya',
    form_permohonan_pinjaman: 'Form Permohonan Pinjaman (FPP)',
    form_ijin_tidak_masuk_kerja: 'Form Ijin Tidak Masuk Kerja (FITMK)',
};
const formTemplateLabels = {
    form_permohonan_pinjaman: 'Form Permohonan Pinjaman (FPP)',
    form_ijin_tidak_masuk_kerja: 'Form Ijin Tidak Masuk Kerja (FITMK)',
};
const isFormPengajuan = (record) => record._documentType === 'form_pengajuan';

const documentColumns = [
    { title: 'Nomor Dokumen', dataIndex: 'code', key: 'code' },
    { title: 'Perihal', key: 'subject' },
    { title: 'Jenis', key: 'type' },
    { title: 'Tanggal Dibuat', key: 'created_at' },
    { title: 'Status', key: 'status' },
    { title: 'TTD Digital', key: 'signature' },
    { title: 'Aksi', key: 'action', align: 'center' },
];

const statusTagColor = (status) => ({
    draft: 'default',
    submitted: 'processing',
    approved: 'success',
    rejected: 'error',
}[status] || 'default');

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

const formatTime = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit'
    });
};

const documents = computed(() => [
    ...(props.memos || []).map((memo) => ({ ...memo, _documentType: 'memo' })),
    ...(props.recentBA || []).map((ba) => ({ ...ba, _documentType: 'ba' })),
    ...(props.recentFormPengajuans || []).map((form) => ({ ...form, _documentType: 'form_pengajuan' })),
].sort((a, b) => new Date(b.updated_at) - new Date(a.updated_at)));

const filteredDocuments = computed(() => {
    let list = documents.value;

    if (statusFilter.value === 'received') {
        list = list.filter(m => m.status !== 'draft');
    } else if (statusFilter.value !== 'all') {
        list = list.filter(m => m.status === statusFilter.value);
    }

    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase().trim();
        list = list.filter(m =>
            (m.code && m.code.toLowerCase().includes(q)) ||
            (m.title && m.title.toLowerCase().includes(q)) ||
            (m.template?.name && m.template.name.toLowerCase().includes(q)) ||
            (isFormPengajuan(m) && `form pengajuan ${formTemplateLabels[m.template] || ''}`.toLowerCase().includes(q)) ||
            (m._documentType === 'ba' && 'berita acara'.includes(q))
        );
    }

    return list;
});

const focusDocuments = (status) => {
    statusFilter.value = status;
    document.getElementById('dashboard-documents')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

</script>

<template>
    <Head title="Dashboard Kepala Cabang" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-base font-bold text-white tracking-tight">Dashboard Kepala Cabang</h2>
        </template>

        <div class="dashboard-kc-theme">

        <!-- Page Context Banner with Realtime Greeting & Live Clock -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-white tracking-tight flex items-center gap-2.5 flex-wrap">
                    <span>{{ greeting.icon }} {{ greeting.text }}, <span class="dashboard-greeting-name">{{ userDisplayName }}</span>!</span>
                </h1>
                <p class="text-xs md:text-sm text-slate-400 mt-1">
                    Kelola pembuatan dan pantau status persetujuan memo pengajuan wilayah <span class="text-slate-200 font-medium">{{ user?.area?.name || 'Anda' }}</span>.
                </p>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <!-- Date & Realtime Clock Pill -->
                <div class="dashboard-clock hidden sm:flex items-center gap-2.5 px-4 py-2 rounded-xl bg-slate-800/60 border border-white/5 text-xs text-slate-300 shadow-sm">
                    <!-- Date -->
                    <div class="flex items-center gap-1.5 font-medium">
                        <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>{{ formattedDate }}</span>
                    </div>
                    <span class="text-slate-600 font-bold">•</span>
                    <!-- Clock -->
                    <div class="flex items-center gap-1.5 font-mono text-indigo-300 font-semibold">
                        <svg class="w-3.5 h-3.5 text-indigo-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>{{ formattedTime }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards Grid -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
            <!-- Memo Masuk -->
            <button type="button" @click="focusDocuments('received')" class="dashboard-kpi-card card-hover-rise bg-slate-800/50 border border-white/5 rounded-2xl p-4 hover:border-indigo-500/20 shadow-sm block w-full text-left transition-all">
                <span class="text-xs font-semibold text-slate-400 tracking-wider">Pengajuan Masuk</span>
                <p class="text-2xl font-bold text-blue-400 mt-1">{{ submissionStats.received ?? 0 }}</p>
                <p class="text-[11px] text-slate-500 mt-2">Memo, BA &amp; Form Pengajuan terkirim</p>
            </button>



            <!-- Draft -->
            <button type="button" @click="focusDocuments('draft')" class="dashboard-kpi-card card-hover-rise bg-slate-800/50 border border-white/5 rounded-2xl p-4 hover:border-slate-500/20 shadow-sm block w-full text-left transition-all">
                <span class="text-xs font-semibold text-slate-400 tracking-wider">Draft Tersimpan</span>
                <p class="text-2xl font-bold text-blue-400 mt-1">{{ submissionStats.draft ?? 0 }}</p>
                <p class="text-[11px] text-slate-500 mt-2">Belum diajukan</p>
            </button>

            <!-- Submitted / Pending -->
            <button type="button" @click="focusDocuments('submitted')" class="dashboard-kpi-card card-hover-rise bg-slate-800/50 border border-white/5 rounded-2xl p-4 hover:border-amber-500/20 shadow-sm block w-full text-left transition-all">
                <span class="text-xs font-semibold text-amber-400/90 tracking-wider">Menunggu AM</span>
                <p class="text-2xl font-bold text-blue-400 mt-1">{{ submissionStats.submitted ?? 0 }}</p>
                <p class="text-[11px] text-amber-400/70 mt-2">Dalam proses verifikasi</p>
            </button>

            <!-- Approved -->
            <button type="button" @click="focusDocuments('approved')" class="dashboard-kpi-card card-hover-rise bg-slate-800/50 border border-white/5 rounded-2xl p-4 hover:border-emerald-500/20 shadow-sm block w-full text-left transition-all">
                <span class="text-xs font-semibold text-emerald-400 tracking-wider">Disetujui</span>
                <p class="text-2xl font-bold text-blue-400 mt-1">{{ submissionStats.approved ?? 0 }}</p>
                <p class="text-[11px] text-emerald-500/70 mt-2">Selesai & resmi</p>
            </button>

            <!-- Rejected -->
            <button type="button" @click="focusDocuments('rejected')" class="dashboard-kpi-card card-hover-rise bg-slate-800/50 border border-white/5 rounded-2xl p-4 hover:border-rose-500/20 shadow-sm block w-full text-left transition-all">
                <span class="text-xs font-semibold text-rose-400 tracking-wider">Perlu Revisi</span>
                <p class="text-2xl font-bold text-blue-400 mt-1">{{ submissionStats.rejected ?? 0 }}</p>
                <p class="text-[11px] text-rose-500/70 mt-2">Ditolak Area Manager</p>
            </button>
        </div>

        <!-- Memos Table Section -->
            <a-card id="dashboard-documents" :bordered="false" class="kc-memo-list-card">
            <!-- Header & Controls -->
            <div class="kc-memo-list-heading">
                <div>
                    <h2 class="kc-memo-list-title">Daftar Dokumen Kantor Cabang</h2>
                    <p class="kc-memo-list-description">Memo, Berita Acara, dan Form Pengajuan dari akun kantor cabang Anda.</p>
                </div>

                <a-input-search
                    v-model:value="searchQuery"
                    class="kc-memo-search"
                    placeholder="Cari kode, perihal, atau jenis..."
                    allow-clear
                />
            </div>

            <!-- Filter Status Tabs -->
            <a-space wrap class="kc-memo-status-filters">
                <a-button
                    size="small"
                    :type="statusFilter === 'all' ? 'primary' : 'default'"
                    @click="statusFilter = 'all'"
                >
                    Semua
                </a-button>
                <a-button
                    size="small"
                    :type="statusFilter === 'submitted' ? 'primary' : 'default'"
                    @click="statusFilter = 'submitted'"
                >
                    Menunggu AM
                </a-button>
                <a-button
                    size="small"
                    :type="statusFilter === 'approved' ? 'primary' : 'default'"
                    @click="statusFilter = 'approved'"
                >
                    Disetujui
                </a-button>
                <a-button
                    size="small"
                    :type="statusFilter === 'draft' ? 'primary' : 'default'"
                    @click="statusFilter = 'draft'"
                >
                    Draft
                </a-button>
                <a-button
                    size="small"
                    :type="statusFilter === 'rejected' ? 'primary' : 'default'"
                    @click="statusFilter = 'rejected'"
                >
                    Perlu Revisi
                </a-button>
            </a-space>

            <!-- Table -->
            <a-table
                class="kc-memo-table"
                :data-source="filteredDocuments"
                :columns="documentColumns"
                :row-key="record => `${record._documentType}-${record.id}`"
                :pagination="{ pageSize: 8, showSizeChanger: false }"
                :scroll="{ x: 900 }"
            >
                <template #bodyCell="{ column, record }">
                    <template v-if="column.key === 'code'">
                        <span class="kc-memo-code">{{ record.code }}</span>
                    </template>
                    <template v-else-if="column.key === 'subject'">
                        <div class="kc-memo-subject">
                            <span>{{ record.title }}</span>
                            <small v-if="record._documentType === 'memo' && record.template">
                                {{ record.template.name }}
                            </small>
                            <small v-else-if="record._documentType === 'ba'">
                                {{ baTemplateLabels[record.meta?.template] || 'Berita Acara' }}
                            </small>
                            <small v-else-if="isFormPengajuan(record)">
                                {{ formTemplateLabels[record.template] || 'Form Pengajuan' }}
                            </small>
                        </div>
                    </template>
                    <template v-else-if="column.key === 'type'">
                        <a-tag
                            :color="record._documentType === 'memo' ? 'blue' : isFormPengajuan(record) ? 'green' : 'purple'"
                            :class="['kc-memo-type-tag', record._documentType === 'memo' ? 'kc-memo-type-tag-memo' : 'kc-memo-type-tag-ba']"
                        >
                            {{ record._documentType === 'memo' ? 'Memo' : isFormPengajuan(record) ? 'Form Pengajuan' : 'Berita Acara' }}
                        </a-tag>
                    </template>
                    <template v-else-if="column.key === 'created_at'">
                        <div class="kc-memo-date">
                            <span>{{ formatDate(record.created_at) }}</span>
                            <small>{{ formatTime(record.created_at) }}</small>
                        </div>
                    </template>
                    <template v-else-if="column.key === 'status'">
                        <a-tag :color="statusTagColor(record.status)" :class="['kc-memo-status-tag', `kc-memo-status-tag-${record.status}`]">
                            {{ statusConfig[record.status]?.label || record.status }}
                        </a-tag>
                    </template>
                    <template v-else-if="column.key === 'signature' && record._documentType === 'memo'">
                        <a-space v-if="record.status === 'approved' || record.latest_approval?.signature" size="small">
                            <img
                                v-if="record.latest_approval?.signature"
                                :src="'/storage/' + record.latest_approval.signature.signature_image"
                                alt="Tanda tangan digital AM"
                                class="kc-memo-signature"
                            />
                            <a-typography-text type="success">Sudah ditandatangani</a-typography-text>
                        </a-space>
                        <a-typography-text v-else type="secondary" class="kc-signature-pending">Belum ditandatangani</a-typography-text>
                    </template>
                    <template v-else-if="column.key === 'signature' && record._documentType === 'ba'">
                        <a-typography-text :type="record.status === 'approved' ? 'success' : 'secondary'" :class="record.status === 'approved' ? '' : 'kc-signature-pending'">
                            {{ record.status === 'approved' ? 'Sudah ditandatangani' : 'Belum ditandatangani' }}
                        </a-typography-text>
                    </template>
                    <template v-else-if="column.key === 'signature' && isFormPengajuan(record)">
                        <a-typography-text :type="record.status === 'approved' ? 'success' : 'secondary'" :class="record.status === 'approved' ? '' : 'kc-signature-pending'">
                            {{ record.status === 'approved' ? 'Sudah ditandatangani' : 'Belum ditandatangani' }}
                        </a-typography-text>
                    </template>
                    <template v-else-if="column.key === 'signature'">-</template>
                    <template v-else-if="column.key === 'action'">
                        <a-space>
                            <Link v-if="record._documentType === 'memo' && (record.status === 'draft' || record.status === 'rejected')" :href="route('memos.edit', record.id)">
                                <a-button type="primary" ghost size="small">Edit</a-button>
                            </Link>
                            <Link :href="record._documentType === 'memo' ? route('memos.show', record.id) : isFormPengajuan(record) ? route('approvals.form-pengajuan.history', record.id) : route('approvals.ba.history', record.id)">
                                <a-button type="primary" ghost size="small">Detail</a-button>
                            </Link>
                        </a-space>
                    </template>
                </template>

                <template #emptyText>
                    <a-empty description="Belum ada dokumen yang sesuai">
                        <a-space>
                            <Link :href="route('berita-acara.create')"><a-button type="primary">Buat BA</a-button></Link>
                            <Link :href="route('form-pengajuan.create')"><a-button>Buat Form Pengajuan</a-button></Link>
                        </a-space>
                    </a-empty>
                </template>
            </a-table>

            <!-- Footer -->
            <div class="kc-memo-list-footer">
                <span>Menampilkan {{ filteredDocuments.length }} dari {{ documents.length }} dokumen terbaru</span>
                <span class="kc-memo-footer-brand">PT Pusat Gadai Indonesia</span>
            </div>
        </a-card>
        </div>
    </AuthenticatedLayout>
</template>

<style>
.kc-memo-list-card {
    color: #e2e8f0;
    background: rgba(30, 41, 59, 0.5) !important;
    border: 1px solid rgba(255, 255, 255, 0.06);
}

.kc-memo-list-card .ant-card-body {
    padding: 24px;
}

.kc-memo-list-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding-bottom: 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.kc-memo-list-title {
    margin: 0;
    color: #f8fafc;
    font-size: 18px;
    font-weight: 700;
}

.kc-memo-list-description {
    margin: 4px 0 0;
    color: #94a3b8;
    font-size: 12px;
}

.kc-memo-search {
    width: min(100%, 280px);
}

.kc-memo-status-filters {
    margin: 16px 0;
}

.kc-memo-table {
    margin-top: 20px;
}

.kc-memo-list-card .kc-memo-status-filters .ant-btn-default {
    color: #cbd5e1;
    background: #1e293b;
    border-color: #475569;
}

.kc-memo-table .ant-table {
    color: #e2e8f0;
    background: transparent !important;
}

.kc-memo-table .ant-table-container {
    border: 1px solid #475569;
    border-radius: 6px;
    overflow: hidden;
}

.kc-memo-table .ant-table-thead > tr > th {
    color: #ffffff !important;
    background: #203c5b !important;
    border-bottom: 1px solid #34516e !important;
}

.kc-memo-table .ant-table-tbody > tr > td {
    color: #e2e8f0 !important;
    background: transparent !important;
    border-bottom: 1px solid #475569 !important;
    border-right: 1px solid #475569 !important;
}

.kc-memo-table .ant-table-tbody > tr > td:last-child {
    border-right: 0 !important;
}

.kc-memo-table .kc-memo-type-tag-memo {
    color: #bfdbfe !important;
    background: #1e3a5f !important;
    border-color: #3b82f6 !important;
}

.kc-memo-table .kc-memo-type-tag-ba {
    color: #e9d5ff !important;
    background: #3b2456 !important;
    border-color: #8b5cf6 !important;
}

.kc-memo-table .kc-memo-status-tag-draft {
    color: #cbd5e1 !important;
    background: #334155 !important;
    border-color: #64748b !important;
}

.kc-memo-table .kc-memo-status-tag-submitted {
    color: #fde68a !important;
    background: #493719 !important;
    border-color: #d97706 !important;
}

.kc-memo-table .kc-memo-status-tag-approved {
    color: #bbf7d0 !important;
    background: #17432f !important;
    border-color: #22c55e !important;
}

.kc-memo-table .kc-memo-status-tag-rejected {
    color: #fecaca !important;
    background: #4c2528 !important;
    border-color: #ef4444 !important;
}

.kc-memo-table .ant-table-tbody > tr:hover > td {
    color: #ffffff !important;
    background: rgba(255, 255, 255, 0.06) !important;
}

.kc-memo-table .ant-table-placeholder {
    color: #cbd5e1;
    background: transparent !important;
}

.kc-memo-table .ant-empty-description,
.kc-memo-table .ant-empty-normal {
    color: #cbd5e1;
}

.kc-memo-table .ant-pagination-item a,
.kc-memo-table .ant-pagination-prev .ant-pagination-item-link,
.kc-memo-table .ant-pagination-next .ant-pagination-item-link {
    color: #cbd5e1;
}

.kc-memo-code {
    color: #93c5fd;
    font-family: monospace;
    font-size: 12px;
    font-weight: 600;
}

.kc-memo-subject,
.kc-memo-date {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.kc-memo-subject > span {
    overflow: hidden;
    color: #f8fafc;
    font-weight: 500;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.kc-memo-subject small,
.kc-memo-date small {
    color: #94a3b8;
    font-size: 11px;
}

.kc-memo-signature {
    width: 80px;
    height: 32px;
    padding: 4px;
    border-radius: 4px;
    background: rgba(255, 255, 255, 0.12);
    object-fit: contain;
}

html:not(.theme-light) .kc-memo-table .kc-signature-pending {
    color: #cbd5e1 !important;
}

html.theme-light .kc-memo-table .kc-signature-pending {
    color: #64748b !important;
}

.kc-memo-list-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-top: 16px;
    padding-top: 16px;
    color: #94a3b8;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    font-size: 12px;
}

html.theme-light .kc-memo-list-card {
    color: #334155;
    background: #ffffff !important;
    border-color: #e2e8f0;
}

html.theme-light .kc-memo-list-heading,
html.theme-light .kc-memo-list-footer {
    border-color: #e2e8f0;
}

html.theme-light .kc-memo-list-title,
html.theme-light .kc-memo-subject > span {
    color: #0f172a;
}

html.theme-light .kc-memo-list-description,
html.theme-light .kc-memo-subject small,
html.theme-light .kc-memo-date small,
html.theme-light .kc-memo-list-footer {
    color: #64748b;
}

html.theme-light .kc-memo-list-card .kc-memo-status-filters .ant-btn-default {
    color: #334155;
    background: #ffffff;
    border-color: #cbd5e1;
}

html.theme-light .kc-memo-table .ant-table {
    color: #1e293b;
    background: #ffffff !important;
}

html.theme-light .kc-memo-table .ant-table-container {
    border-color: #cbd5e1;
}

html.theme-light .kc-memo-table .ant-table-thead > tr > th {
    color: #ffffff !important;
    background: #315a84 !important;
    border-bottom: 1px solid #274b70 !important;
}

html.theme-light .kc-memo-table .ant-table-tbody > tr > td {
    color: #1e293b !important;
    background: #ffffff !important;
    border-bottom: 1px solid #cbd5e1 !important;
    border-right: 1px solid #cbd5e1 !important;
}

html.theme-light .kc-memo-table .ant-table-tbody > tr > td:last-child {
    border-right: 0 !important;
}

html.theme-light .kc-memo-table .kc-memo-type-tag-memo {
    color: #1d4ed8 !important;
    background: #dbeafe !important;
    border-color: #93c5fd !important;
}

html.theme-light .kc-memo-table .kc-memo-type-tag-ba {
    color: #6b21a8 !important;
    background: #f3e8ff !important;
    border-color: #d8b4fe !important;
}

html.theme-light .kc-memo-table .kc-memo-status-tag-draft {
    color: #475569 !important;
    background: #f1f5f9 !important;
    border-color: #cbd5e1 !important;
}

html.theme-light .kc-memo-table .kc-memo-status-tag-submitted {
    color: #92400e !important;
    background: #fef3c7 !important;
    border-color: #fcd34d !important;
}

html.theme-light .kc-memo-table .kc-memo-status-tag-approved {
    color: #166534 !important;
    background: #dcfce7 !important;
    border-color: #86efac !important;
}

html.theme-light .kc-memo-table .kc-memo-status-tag-rejected {
    color: #991b1b !important;
    background: #fee2e2 !important;
    border-color: #fca5a5 !important;
}

html.theme-light .kc-memo-table .ant-table-tbody > tr:hover > td {
    color: #0f172a !important;
    background: #eff6ff !important;
}

html.theme-light .kc-memo-table .ant-table-placeholder,
html.theme-light .kc-memo-table .ant-empty-description,
html.theme-light .kc-memo-table .ant-empty-normal {
    color: #64748b;
    background: #ffffff !important;
}

html.theme-light .kc-memo-table .ant-pagination-item a,
html.theme-light .kc-memo-table .ant-pagination-prev .ant-pagination-item-link,
html.theme-light .kc-memo-table .ant-pagination-next .ant-pagination-item-link {
    color: #334155;
}

html.theme-light .kc-memo-code {
    color: #1677ff;
}

html.theme-light .kc-memo-signature {
    background: #f1f5f9;
}

@media (max-width: 640px) {
    .kc-memo-list-card .ant-card-body {
        padding: 16px;
    }

    .kc-memo-list-heading {
        align-items: stretch;
        flex-direction: column;
    }

    .kc-memo-search {
        width: 100%;
    }

    .kc-memo-footer-brand {
        display: none;
    }
}
</style>
