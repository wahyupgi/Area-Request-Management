<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BeritaAcaraDocument from '@/Components/BeritaAcaraDocument.vue';
import MemoAttachments from '@/Components/MemoAttachments.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowLeftOutlined,
    PaperClipOutlined,
    DownloadOutlined,
    PrinterOutlined
} from '@ant-design/icons-vue';

const props = defineProps({
    beritaAcara: Object,
});
const page = usePage();
const isKcUser = page.props.auth.user?.role === 'KC';
const isAdminUser = page.props.auth.user?.role === 'ADMIN';
const backUrl = isAdminUser
    ? route('dashboard')
    : isKcUser
        ? route('berita-acara.index')
        : route('approvals.pending', { tab: 'ba-approved' });
const backLabel = isAdminUser
    ? 'Dashboard'
    : isKcUser
        ? 'Riwayat Pengajuan'
        : 'Kotak Masuk';

const attachmentUrl = () => '/storage/' + props.beritaAcara.attachment_path;
const printAttachments = [
    ...(props.beritaAcara.attachments || []),
    ...(props.beritaAcara.attachment_path ? [{
        id: `legacy-ba-${props.beritaAcara.id}`,
        legacy: true,
        file_path: props.beritaAcara.attachment_path,
        original_name: props.beritaAcara.attachment_name,
    }] : []),
];
const canDeleteAttachments = props.beritaAcara.status === 'draft'
    && props.beritaAcara.created_by === page.props.auth.user?.id;

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return `${days[date.getDay()]}, ${String(date.getDate()).padStart(2, '0')} ${months[date.getMonth()]} ${date.getFullYear()}`;
};

const printBA = () => {
    const printWindow = window.open('', '_blank');
    if (!printWindow) return;

    const originalTitle = document.title;
    const baHtml = document.querySelector('#printable-ba')?.outerHTML || '';
    const stylesheetUrls = Array.from(document.querySelectorAll('link[rel="stylesheet"]')).map((link) => link.href);
    const activeStyles = Array.from(document.querySelectorAll('style')).map((style) => style.textContent).join('\n');

    const waitForAttachments = new Promise((resolve) => {
        const startedAt = Date.now();
        const check = () => {
            if (!document.querySelector('[data-attachment-loading]') || Date.now() - startedAt > 30000) {
                resolve();
                return;
            }
            window.setTimeout(check, 100);
        };
        check();
    });

    waitForAttachments.then(() => Promise.all(stylesheetUrls.map((url) => fetch(url).then((response) => response.text()).catch(() => '')))).then((styles) => {
        const attachments = document.querySelector('.memo-print-attachments')?.outerHTML || '';
        printWindow.document.write(`<!doctype html><html><head><title>${originalTitle}</title><style>${styles.join('\n')}\n${activeStyles}</style><style>
            @page { size: A4 portrait; margin: 0; }
            html, body { margin: 0 !important; padding: 0 !important; background: #fff !important; min-height: 0 !important; height: auto !important; }
            body * { visibility: visible !important; }
            #printable-ba { position: relative !important; inset: auto !important; width: 210mm !important; min-height: 295mm !important; page-break-after: avoid !important; break-after: avoid !important; box-sizing: border-box !important; }
            .memo-print-attachments { display: block !important; width: 210mm !important; margin: 0 !important; page-break-before: auto !important; break-before: auto !important; }
            .memo-print-attachment { display: block !important; width: 210mm !important; min-height: 0 !important; page-break-before: always !important; break-before: page !important; page-break-after: avoid !important; break-after: avoid !important; box-sizing: border-box !important; }
        </style></head><body>${baHtml}${attachments}</body></html>`);
        printWindow.onload = () => {
            printWindow.onafterprint = () => printWindow.close();
            printWindow.focus();
            printWindow.print();
        };
        printWindow.document.close();
    });
};
</script>

<template>
    <Head :title="`Riwayat Berita Acara: ${beritaAcara.code}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-3">
                    <Link :href="backUrl">
                        <a-button type="text" shape="circle" :title="`Kembali ke ${backLabel}`">
                            <template #icon><arrow-left-outlined /></template>
                        </a-button>
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

                        <div class="document-preview-scroll">
                            <BeritaAcaraDocument :berita-acara="beritaAcara" />
                        </div>
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
                            <a-alert type="success" show-icon :message="`Pengajuan ini telah disetujui secara resmi.`" />
                        </div>
                        <div v-else-if="beritaAcara.status === 'rejected'" class="space-y-4">
                            <a-alert type="error" show-icon message="Pengajuan ini ditolak." />
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
            <MemoAttachments
                :attachments="printAttachments"
                :subject="beritaAcara.meta?.perihal || beritaAcara.title"
                delete-route-name="berita-acara.attachments.destroy"
                :document-id="beritaAcara.id"
                :can-delete="canDeleteAttachments"
            />
        </div>
    </AuthenticatedLayout>
</template>
