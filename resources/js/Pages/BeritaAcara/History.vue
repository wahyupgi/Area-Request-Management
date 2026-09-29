<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BeritaAcaraDocument from '@/Components/BeritaAcaraDocument.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { useTheme } from '@/composables/useTheme';
import {
    CheckCircleOutlined,
    CloseCircleOutlined,
    PaperClipOutlined,
    DownloadOutlined,
    PrinterOutlined
} from '@ant-design/icons-vue';

const props = defineProps({
    beritaAcara: Object,
});

const page = usePage();
const { theme } = useTheme();
const isKcUser = page.props.auth.user?.role === 'KC';
const backUrl = isKcUser
    ? route('berita-acara.index')
    : route('approvals.pending', { tab: 'ba-approved' });
const backLabel = isKcUser ? 'Daftar Berita Acara' : 'Antrean Berita Acara';

const attachmentUrl = () => '/storage/' + props.beritaAcara.attachment_path;

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return `${days[date.getDay()]}, ${String(date.getDate()).padStart(2, '0')} ${months[date.getMonth()]} ${date.getFullYear()}`;
};

const printBA = () => {
        window.print();
};
</script>

<template>
    <Head :title="'Riwayat Berita Acara: ' + beritaAcara.code" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-3">
                    <Link :href="backUrl">
                        <span
                            class="ba-history-back-link"
                            :style="{ color: theme === 'light' ? '#000000' : '#f8fafc' }"
                        >
                            {{ backLabel }}
                        </span>
                    </Link>
                    <div>
                        <h1 class="text-base font-bold text-gray-800 mb-0 tracking-tight">Riwayat Berita Acara</h1>
                        <div class="flex items-center gap-2 text-xs text-gray-500">
                            <span class="font-mono font-semibold text-blue-600">{{ beritaAcara.code || 'BA Baru' }}</span>
                            <span>•</span>
                            <span>{{ beritaAcara.branch?.name || 'Cabang' }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <a-button @click="printBA" class="hidden sm:inline-flex print:hidden">
                        <template #icon><printer-outlined /></template>
                        Cetak BA
                    </a-button>
                    <a-tag v-if="beritaAcara.status === 'approved'" color="success" class="font-semibold">
                        <template #icon><check-circle-outlined /></template> Disetujui
                    </a-tag>
                    <a-tag v-else-if="beritaAcara.status === 'rejected'" color="error" class="font-semibold">
                        <template #icon><close-circle-outlined /></template> Ditolak
                    </a-tag>
                    <a-tag v-else color="warning" class="font-semibold">
                        Menunggu Persetujuan
                    </a-tag>
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto py-6 px-4">
            <a-row :gutter="[24, 24]">
                <a-col :xs="24" :lg="18">
                    <a-card :bordered="false" class="rounded-lg shadow-sm mb-6">
                        <div class="flex items-center justify-between border-b pb-2 mb-4">
                            <h2 class="text-lg font-bold mb-0">Dokumen Berita Acara</h2>
                            <a-button type="primary" @click="printBA" class="print:hidden">
                                <template #icon><printer-outlined /></template>
                                Cetak
                            </a-button>
                        </div>

                        <BeritaAcaraDocument :berita-acara="beritaAcara" />
                        <!-- End BA Document -->
                    </a-card>

                    <a-card v-if="beritaAcara.attachment_path" title="Lampiran Pendukung" :bordered="false" class="rounded-lg shadow-sm">
                        <a-list item-layout="horizontal" size="small">
                            <a-list-item>
                                <a-list-item-meta>
                                    <template #title>
                                        <a :href="attachmentUrl()" target="_blank" class="text-blue-600 hover:underline flex items-center gap-2">
                                            <paper-clip-outlined />
                                            {{ beritaAcara.attachment_name || 'Lampiran Dokumen' }}
                                        </a>
                                    </template>
                                </a-list-item-meta>
                                <template #actions>
                                    <a :href="attachmentUrl()" target="_blank">
                                        <a-button type="link" size="small">
                                            <template #icon><download-outlined /></template> Unduh
                                        </a-button>
                                    </a>
                                </template>
                            </a-list-item>
                        </a-list>
                    </a-card>
                </a-col>

                <a-col :xs="24" :lg="6">
                    <!-- Status Card -->
                    <a-card title="Status" :bordered="false" class="rounded-lg shadow-sm mb-6" size="small">
                        <template #extra>
                            <a-tag v-if="beritaAcara.status === 'approved'" color="success">Sudah Disetujui</a-tag>
                            <a-tag v-else-if="beritaAcara.status === 'rejected'" color="error">Ditolak</a-tag>
                            <a-tag v-else color="warning">Diproses</a-tag>
                        </template>

                        <div v-if="beritaAcara.status === 'approved'" class="space-y-4">
                            <a-alert type="success" show-icon message="Berita Acara ini telah disetujui secara resmi." />
                        </div>
                        <div v-else-if="beritaAcara.status === 'rejected'" class="space-y-4">
                            <a-alert type="error" show-icon message="Berita Acara ini ditolak." />
                        </div>
                        <div v-else>
                            <a-alert type="info" show-icon message="Sedang menunggu keputusan." />
                        </div>
                    </a-card>

                    <!-- Meta Data -->
                    <a-card title="Informasi Meta Dokumen" :bordered="false" class="rounded-lg shadow-sm" size="small">
                        <div class="grid grid-cols-[5.25rem_minmax(0,1fr)] items-start gap-x-3 gap-y-2 text-sm">
                            <span class="text-gray-500">Judul</span><span class="min-w-0 break-words text-left font-medium leading-5" :title="beritaAcara.title">{{ beritaAcara.title }}</span>
                            <span class="text-gray-500">Direktorat</span><span class="min-w-0 break-words text-left font-medium leading-5">{{ beritaAcara.meta?.direktorat || '-' }}</span>
                            <span class="text-gray-500">Divisi</span><span class="min-w-0 break-words text-left font-medium leading-5">{{ beritaAcara.meta?.divisi || '-' }}</span>
                            <span class="text-gray-500">Kepada</span><span class="min-w-0 break-words text-left font-medium leading-5">{{ beritaAcara.meta?.kepada_nama || '-' }}</span>
                            <span class="text-gray-500">Jabatan</span><span class="min-w-0 break-words text-left font-medium leading-5">{{ beritaAcara.meta?.kepada_jabatan || '-' }}</span>
                            <span class="text-gray-500">Tanggal Buat</span><span class="min-w-0 break-words text-left leading-5">{{ formatDate(beritaAcara.created_at) }}</span>
                        </div>
                    </a-card>
                </a-col>
            </a-row>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Semua styling BA document menggunakan Tailwind utility classes inline.
   Style block ini hanya untuk override minimal jika diperlukan. */
.ba-history-back-link {
    color: #f8fafc;
    font-size: 14px;
    font-weight: 400;
    white-space: nowrap;
}

.ba-history-back-link:hover {
    color: #e2e8f0;
    text-decoration: underline;
}

:global(html.theme-light) .ba-history-back-link {
    color: #000000;
}

:global(html.theme-light) .ba-history-back-link:hover {
    color: #1f2937;
}

</style>
