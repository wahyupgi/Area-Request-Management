<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BeritaAcaraDocument from '@/Components/BeritaAcaraDocument.vue';
import MemoAttachments from '@/Components/MemoAttachments.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { escapeHtml } from '@/utils/sanitize';
import {
    LeftOutlined,
    PrinterOutlined,
    DownloadOutlined,
    EditOutlined,
    PaperClipOutlined,
    CheckCircleOutlined,
    CloseCircleOutlined,
} from '@ant-design/icons-vue';

const props = defineProps({ beritaAcara: Object });
const user = usePage().props.auth.user;

const isOtherTemplate = computed(() => ['lainnya', 'Berita Acara Lainnya'].includes(props.beritaAcara.meta?.template));

const statusConfig = {
    draft:     { label: 'Draft',                color: 'default' },
    submitted: { label: 'Menunggu Persetujuan', color: 'warning' },
    approved:  { label: 'Disetujui Resmi',      color: 'success' },
    rejected:  { label: 'Ditolak / Revisi',     color: 'error'   },
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: '2-digit', month: 'short', year: 'numeric',
    });
};

const attachmentUrl = (attachment) => '/storage/' + attachment.file_path;

const waitForAttachments = () => new Promise((resolve) => {
    const startedAt = Date.now();
    const check = () => {
        const isReady = !document.querySelector('[data-attachment-loading]');
        if (isReady || Date.now() - startedAt > 6000) {
            resolve();
            return;
        }
        window.setTimeout(check, 100);
    };
    check();
});

const downloadDocument = () => {
    if (isOtherTemplate.value && printAttachments.value?.length > 0) {
        const first = printAttachments.value[0];
        const url = first.preview_path ? ('/storage/' + first.preview_path) : attachmentUrl(first);
        const link = document.createElement('a');
        link.href = url;
        link.download = first.original_name || `${printFileName()}.${first.file_path?.split('.').pop() || 'pdf'}`;
        link.target = '_blank';
        document.body.appendChild(link);
        link.click();
        link.remove();
        return;
    }
    printDocument();
};

const printFileName = () =>
    `Berita Acara - ${[props.beritaAcara.title, props.beritaAcara.code].filter(Boolean).join(' - ') || 'Dokumen'}`
        .replace(/[<>:"|?*]/g, '-')
        .replaceAll('/', '-')
        .replaceAll('\\', '-')
        .replace(/[\u0000-\u001F]/g, '')
        .replace(/\s+/g, ' ')
        .trim();

const printableHtml = (element) => {
    if (!element) return '';
    const clone = element.cloneNode(true);
    const sourceCanvases = element.querySelectorAll('canvas');
    clone.querySelectorAll('canvas').forEach((canvas, index) => {
        const source = sourceCanvases[index];
        if (!source) return;
        try {
            const image = document.createElement('img');
            image.src = source.toDataURL('image/png');
            image.className = source.className;
            image.style.cssText = source.style.cssText;
            canvas.replaceWith(image);
        } catch {}
    });
    return clone.outerHTML;
};

const printDocument = async () => {
    await waitForAttachments();

    const docEl    = isOtherTemplate.value ? '' : printableHtml(document.querySelector('#printable-ba'));
    const attachEl = printableHtml(document.querySelector('.memo-print-attachments'));
    if (!docEl && !attachEl) {
        window.print();
        return;
    }

    const oldIframe = document.getElementById('print-iframe');
    if (oldIframe) {
        oldIframe.remove();
    }

    const iframe = document.createElement('iframe');
    iframe.id = 'print-iframe';
    iframe.style.position = 'fixed';
    iframe.style.top = '-9999px';
    iframe.style.left = '-9999px';
    iframe.style.width = '210mm';
    iframe.style.height = '297mm';
    iframe.style.border = '0';
    iframe.style.opacity = '0';
    iframe.style.zIndex = '-9999';
    iframe.style.pointerEvents = 'none';
    document.body.appendChild(iframe);

    const headStyles = Array.from(document.querySelectorAll('link[rel="stylesheet"], style'))
        .map(el => el.outerHTML)
        .join('\n');

    const fileName = printFileName();

    const html = `<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>${escapeHtml(fileName)}</title>
    ${headStyles}
    <style>
        @page { size: A4 portrait; margin: 0; }
        html, body { margin: 0 !important; padding: 0 !important; background: #fff !important; width: 210mm !important; }
        body * { visibility: visible !important; }
        .ba-print-page, .ba-print-page * { visibility: visible !important; }
        #printable-ba, #printable-ba * { visibility: visible !important; }
        .memo-print-attachments, .memo-print-attachments * { visibility: visible !important; }
        .memo-print-attachment, .memo-print-attachment * { visibility: visible !important; }
        .memo-pdf-pages, .memo-pdf-pages * { visibility: visible !important; }
        .memo-pdf-page { visibility: visible !important; opacity: 1 !important; }
        .ba-print-page { width: 210mm !important; box-sizing: border-box !important; margin: 0 auto !important; }
        #printable-ba { position: relative !important; width: 210mm !important; box-sizing: border-box !important; margin: 0 auto !important; }
        .memo-print-attachments { display: block !important; width: 210mm !important; margin: 0 auto !important; }
        .memo-print-attachment { display: block !important; width: 210mm !important; box-sizing: border-box !important; }
        .memo-print-attachment:first-child { page-break-before: ${isOtherTemplate.value ? 'auto' : 'always'} !important; break-before: ${isOtherTemplate.value ? 'auto' : 'page'} !important; }
        .memo-print-attachment + .memo-print-attachment { page-break-before: always !important; break-before: page !important; }
        .memo-pdf-page-list { display: flex !important; flex-direction: column !important; align-items: center !important; width: 100% !important; gap: 0 !important; }
        .memo-pdf-page { width: auto !important; height: auto !important; max-width: 100% !important; max-height: 295mm !important; margin: 0 auto !important; object-fit: contain !important; break-inside: avoid !important; page-break-inside: avoid !important; }
        .memo-pdf-page:not(:last-child) { page-break-after: always !important; break-after: page !important; }
    </style>
</head>
<body>
    <div class="ba-print-page">${isOtherTemplate.value ? attachEl : `${docEl}${attachEl}`}</div>
</body>
</html>`;

    const doc = iframe.contentDocument || iframe.contentWindow.document;
    doc.open();
    doc.write(html);
    doc.close();

    setTimeout(() => {
        try {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
        } catch (err) {
            console.error('Print iframe failed, fallback to window.print', err);
            window.print();
        }
    }, 400);
};

const printAttachments = computed(() => [
    ...(props.beritaAcara.attachments || []),
    ...(props.beritaAcara.attachment_path ? [{
        id: `legacy-ba-${props.beritaAcara.id}`,
        legacy: true,
        file_path: props.beritaAcara.attachment_path,
        original_name: props.beritaAcara.attachment_name,
    }] : []),
]);
</script>

<template>
    <Head :title="'Detail Berita Acara: ' + (beritaAcara.code || beritaAcara.title)" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-2.5">
                <Link :href="user?.role === 'AM' ? route('approvals.pending') : user?.role === 'ADMIN' ? route('dashboard') : route('berita-acara.index')" class="text-gray-400 hover:text-gray-800 dark:hover:text-white transition-colors p-1 -ml-1 flex items-center">
                    <left-outlined class="text-base" />
                </Link>
                <div>
                    <h1 class="memo-page-title text-base font-bold text-gray-800 dark:text-white mb-0">Detail Berita Acara</h1>
                    <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-slate-400">
                        <span class="font-mono font-semibold text-blue-600 dark:text-blue-400">{{ beritaAcara.code || 'Belum ada kode' }}</span>
                        <span>•</span>
                        <span>{{ beritaAcara.branch?.name || 'Kantor Cabang' }}</span>
                    </div>
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto py-2 print:p-0 print:m-0 print:max-w-none print:w-full">
            <a-row :gutter="[24, 24]" class="print:block print:w-full print:m-0 print:p-0">

                <!-- Kiri: Preview Dokumen -->
                <a-col :xs="24" :lg="16" class="flex flex-col items-center justify-start print:block print:w-full print:m-0 print:p-0">
                    <div class="document-preview-scroll print:overflow-visible w-full flex flex-col items-center">
                        <div v-if="isOtherTemplate" id="printable-ba" class="w-full">
                            <div v-if="printAttachments.length > 0" class="mb-3 px-1">
                                <a-alert
                                    type="info"
                                    show-icon
                                    message="Dokumen Lampiran"
                                    description="Template ini berupa file yang diunggah. Tampilan di bawah adalah isi file lampiran."
                                    class="mb-4 rounded-lg"
                                />
                            </div>
                            <MemoAttachments
                                v-if="printAttachments.length > 0"
                                :attachments="printAttachments"
                                :subject="beritaAcara.meta?.perihal || beritaAcara.title"
                            />
                            <div v-else class="p-8 text-center text-gray-400 bg-white rounded-lg shadow-sm w-full">
                                Belum ada berkas dokumen yang diunggah.
                            </div>
                        </div>
                        <div v-else class="document-preview-paper shadow-md print:shadow-none bg-white">
                            <div id="printable-ba">
                                <BeritaAcaraDocument :berita-acara="beritaAcara" />
                            </div>
                        </div>
                    </div>
                </a-col>

                <!-- Kanan: Sidebar Info -->
                <a-col :xs="24" :lg="8" class="print:hidden">

                    <!-- Card 1: Info Dokumen -->
                    <a-card title="Informasi Dokumen" :bordered="false" class="rounded-lg shadow-sm mb-6" size="small">
                        <template #extra v-if="user?.role !== 'KC'">
                            <div class="flex items-center gap-1">
                                <a-button type="link" size="small" @click="printDocument">
                                    <template #icon><printer-outlined /></template>
                                    Cetak
                                </a-button>
                                <a-button type="link" size="small" @click="downloadDocument">
                                    <template #icon><download-outlined /></template>
                                    Unduh PDF
                                </a-button>
                            </div>
                        </template>

                        <div class="space-y-3 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Kode BA</span>
                                <span class="font-mono text-blue-600 font-semibold">{{ beritaAcara.code || '-' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Status</span>
                                <a-tag :color="statusConfig[beritaAcara.status]?.color" class="font-semibold m-0">
                                    {{ statusConfig[beritaAcara.status]?.label }}
                                </a-tag>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Jenis</span>
                                <span class="font-medium text-right max-w-[160px] truncate">{{ beritaAcara.meta?.template || '-' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Pembuat</span>
                                <span class="font-medium">{{ beritaAcara.creator?.name || '-' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Cabang</span>
                                <span class="font-medium">{{ beritaAcara.branch?.name || '-' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Area Manager</span>
                                <span class="font-medium">{{ beritaAcara.area_manager?.name || beritaAcara.areaManager?.name || '-' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Tgl Pengajuan</span>
                                <span>{{ formatDate(beritaAcara.submitted_at || beritaAcara.created_at) }}</span>
                            </div>
                        </div>

                        <!-- Edit jika masih draft/rejected -->
                        <div v-if="user.role === 'KC' && ['draft', 'rejected'].includes(beritaAcara.status)" class="mt-4 pt-3 border-t border-gray-100">
                            <Link :href="route('berita-acara.create')">
                                <a-button type="primary" block>
                                    <template #icon><edit-outlined /></template>
                                    Buat Berita Acara Baru
                                </a-button>
                            </Link>
                        </div>
                    </a-card>

                    <!-- Card 2: Catatan Penolakan (jika rejected) -->
                    <a-card v-if="beritaAcara.status === 'rejected'" title="Catatan Penolakan" :bordered="false" class="rounded-lg shadow-sm mb-6" size="small">
                        <a-alert type="error" show-icon>
                            <template #message><span class="font-semibold">Dokumen Ditolak / Perlu Revisi</span></template>
                            <template #description>
                                <p class="text-sm mt-1">{{ beritaAcara.rejection_notes || 'Tidak ada catatan.' }}</p>
                            </template>
                        </a-alert>
                    </a-card>

                    <!-- Card 3: Lampiran -->
                    <a-card
                        v-if="printAttachments.length > 0"
                        :title="'Lampiran (' + printAttachments.length + ')'"
                        :bordered="false"
                        class="rounded-lg shadow-sm mb-6"
                        size="small"
                    >
                        <a-list item-layout="horizontal" :data-source="printAttachments" size="small">
                            <template #renderItem="{ item }">
                                <a-list-item>
                                    <a-list-item-meta>
                                        <template #title>
                                            <a :href="attachmentUrl(item)" target="_blank" class="text-blue-600 hover:underline text-sm truncate block max-w-[200px]">
                                                {{ item.original_name || item.file_path }}
                                            </a>
                                        </template>
                                        <template #avatar><paper-clip-outlined class="text-gray-400" /></template>
                                    </a-list-item-meta>
                                </a-list-item>
                            </template>
                        </a-list>
                    </a-card>

                    <!-- Card 4: Riwayat Approval -->
                    <a-card
                        v-if="beritaAcara.approvals?.length > 0"
                        title="Riwayat Persetujuan"
                        :bordered="false"
                        class="rounded-lg shadow-sm"
                        size="small"
                    >
                        <a-timeline class="mt-2">
                            <a-timeline-item
                                v-for="a in beritaAcara.approvals"
                                :key="a.id"
                                :color="a.action === 'approved' ? 'green' : 'red'"
                            >
                                <template #dot>
                                    <check-circle-outlined v-if="a.action === 'approved'" />
                                    <close-circle-outlined v-else />
                                </template>
                                <div class="text-sm">
                                    <div class="flex justify-between">
                                        <span class="font-semibold" :class="a.action === 'approved' ? 'text-green-600' : 'text-red-600'">
                                            {{ a.action === 'approved' ? 'Disetujui' : 'Ditolak' }}
                                        </span>
                                        <span class="text-xs text-gray-400">{{ formatDate(a.created_at) }}</span>
                                    </div>
                                    <p class="text-gray-500 text-xs mb-1">oleh {{ a.approver?.name }}</p>
                                    <div v-if="a.notes" class="bg-gray-50 p-2 rounded text-xs text-gray-600 mt-1">{{ a.notes }}</div>
                                </div>
                            </a-timeline-item>
                        </a-timeline>
                    </a-card>

                </a-col>
            </a-row>

            <MemoAttachments v-if="!isOtherTemplate" :attachments="printAttachments" :subject="beritaAcara.meta?.perihal || beritaAcara.title" />
        </div>
    </AuthenticatedLayout>
</template>
