<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MemoDocument from '@/Components/MemoDocument.vue';
import MemoAttachments from '@/Components/MemoAttachments.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowLeftOutlined,
    PrinterOutlined,
    EditOutlined,
    PaperClipOutlined,
    CheckCircleOutlined,
    CloseCircleOutlined
} from '@ant-design/icons-vue';

const props = defineProps({ memo: Object });
const user = usePage().props.auth.user;

const statusConfig = {
    draft: { label: 'Draft', color: 'default' },
    submitted: { label: 'Menunggu Persetujuan', color: 'warning' },
    approved: { label: 'Disetujui Resmi', color: 'success' },
    rejected: { label: 'Ditolak / Revisi', color: 'error' },
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

const attachmentUrl = (attachment) => '/storage/' + attachment.file_path;
const printFileName = () => `Internal Memo - ${[props.memo.title, props.memo.code].filter(Boolean).join(' - ') || 'Dokumen'}`
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
            image.setAttribute('aria-label', source.getAttribute('aria-label') || 'Halaman lampiran');
            canvas.replaceWith(image);
        } catch {
        }
    });

    return clone.outerHTML;
};

const printMemo = () => {
    const printWindow = window.open('', '_blank');
    if (!printWindow) return;

    const memo = printableHtml(document.querySelector('#printable-memo'));
    const attachments = printableHtml(document.querySelector('.memo-print-attachments'));
    const stylesheetUrls = Array.from(document.querySelectorAll('link[rel="stylesheet"]')).map((link) => link.href);
    const activeStyles = Array.from(document.querySelectorAll('style')).map((style) => style.textContent).join('\n');

    const waitForAttachments = new Promise((resolve) => {
        const startedAt = Date.now();
        const check = () => {
            const isReady = !document.querySelector('[data-attachment-loading]');
            if (isReady || Date.now() - startedAt > 30000) {
                resolve();
                return;
            }
            window.setTimeout(check, 100);
        };
        check();
    });

    waitForAttachments.then(() => Promise.all(stylesheetUrls.map((url) => fetch(url).then((response) => response.text()).catch(() => '')))).then((styles) => {
        printWindow.document.write(`<!doctype html><html><head><title>Memo-${props.memo.code || 'Dokumen'}</title><style>${styles.join('\n')}\n${activeStyles}</style><style>
            @page { size: A4 portrait; margin: 0; }
            html, body { margin: 0 !important; padding: 0 !important; background: #fff !important; }
            body * { visibility: visible !important; }
            .memo-print-page { width: 210mm !important; box-sizing: border-box !important; }
            #printable-memo { position: relative !important; width: 210mm !important; min-height: 0 !important; box-sizing: border-box !important; }
            .memo-print-attachments { display: block !important; width: 210mm !important; }
            .memo-print-attachment { display: block !important; width: 210mm !important; min-height: 0 !important; box-sizing: border-box !important; }
            .memo-print-attachment:first-child { page-break-before: always !important; break-before: page !important; }
            .memo-print-attachment + .memo-print-attachment { page-break-before: always !important; break-before: page !important; }
            .memo-pdf-page-list { display: flex !important; flex-direction: column !important; align-items: center !important; width: 100% !important; gap: 0 !important; }
            .memo-pdf-page { width: auto !important; height: auto !important; max-width: 100% !important; max-height: 295mm !important; margin: 0 auto !important; object-fit: contain !important; break-inside: avoid !important; page-break-inside: avoid !important; }
            .memo-pdf-page:not(:last-child) { page-break-after: always !important; break-after: page !important; }
        </style></head><body><div class="memo-print-page">${memo}</div>${attachments}</body></html>`);
        printWindow.onload = () => {
            printWindow.document.title = printFileName();
            printWindow.onafterprint = () => printWindow.close();
            printWindow.focus();
            printWindow.print();
        };
        printWindow.document.close();
    });
};
</script>

<template>
    <Head :title="'Detail Memo: ' + memo.code" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-3">
                    <a-button type="text" shape="circle" onclick="history.back()">
                        <template #icon><arrow-left-outlined /></template>
                    </a-button>
                    <div>
                        <h1 class="text-base font-bold text-gray-800 mb-0">Detail Dokumen Memo</h1>
                        <div class="flex items-center gap-2 text-xs text-gray-500">
                            <span class="font-mono font-semibold text-blue-600">{{ memo.code }}</span>
                            <span>•</span>
                            <span>{{ memo.branch?.name || 'Kantor Cabang' }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <a-tag :color="statusConfig[memo.status]?.color" class="font-semibold">
                        {{ statusConfig[memo.status]?.label }}
                    </a-tag>

                    <a-button class="hidden sm:inline-flex print:hidden" @click="printMemo">
                        <template #icon><printer-outlined /></template>
                        Cetak
                    </a-button>

                    <div v-if="user.role === 'KC' && ['draft','rejected'].includes(memo.status)">
                        <Link :href="route('memos.edit', memo.id)">
                            <a-button type="primary">
                                <template #icon><edit-outlined /></template>
                                Edit Memo
                            </a-button>
                        </Link>
                    </div>
                </div>
            </div>
        </template>

        <div :class="['memo-print-root max-w-7xl mx-auto py-2 print:p-0 print:m-0 print:max-w-none print:w-full', { 'has-memo-attachments': memo.attachments?.length }]">
            <a-row :gutter="[24, 24]" class="print:block print:w-full print:m-0 print:p-0">
                
                <!-- Left: Document Preview Stage -->
                <a-col :xs="24" :lg="16" class="flex flex-col items-center justify-start print:block print:w-full print:m-0 print:p-0">
                    <div class="w-full max-w-[210mm] print:max-w-none print:w-full shadow-md print:shadow-none bg-white">
                        <MemoDocument :memo="memo" :show-am-signature="memo.status === 'approved'" />
                    </div>
                </a-col>

                <!-- Right: Information & History Sidebar -->
                <a-col :xs="24" :lg="8" class="print:hidden">
                    
                    <!-- Card 1: Metadata Summary -->
                    <a-card title="Informasi Dokumen" :bordered="false" class="rounded-lg shadow-sm mb-6" size="small">
                        <template #extra>
                            <a-button type="link" size="small" @click="printMemo">
                                <template #icon><printer-outlined /></template>
                                Cetak
                            </a-button>
                        </template>
                        
                        <div class="space-y-3 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Kode Memo</span>
                                <span class="font-mono text-blue-600 font-semibold">{{ memo.code }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Status</span>
                                <span class="font-semibold">{{ statusConfig[memo.status]?.label }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Pembuat</span>
                                <span class="font-medium">{{ memo.creator?.name || '-' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Cabang</span>
                                <span class="font-medium">{{ memo.branch?.name || '-' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Area Manager</span>
                                <span class="font-medium">{{ memo.area_manager?.name || '-' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Tgl Pengajuan</span>
                                <span>{{ formatDate(memo.submitted_at || memo.created_at) }}</span>
                            </div>
                        </div>

                        <!-- Action if editable -->
                        <div v-if="user.role === 'KC' && ['draft','rejected'].includes(memo.status)" class="mt-4 pt-3 border-t border-gray-100">
                            <Link :href="route('memos.edit', memo.id)">
                                <a-button type="primary" block>
                                    <template #icon><edit-outlined /></template>
                                    Edit & Perbaiki Memo
                                </a-button>
                            </Link>
                        </div>
                    </a-card>

                    <!-- Card 2: Attachments (if any) -->
                    <a-card v-if="memo.attachments?.length > 0" :title="'Lampiran (' + memo.attachments.length + ')'" :bordered="false" class="rounded-lg shadow-sm mb-6" size="small">
                        <a-list item-layout="horizontal" :data-source="memo.attachments" size="small">
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

                    <!-- Card 3: Approval Timeline & Log -->
                    <a-card v-if="memo.approvals?.length > 0" title="Riwayat Persetujuan" :bordered="false" class="rounded-lg shadow-sm" size="small">
                        <a-timeline class="mt-2">
                            <a-timeline-item v-for="a in memo.approvals" :key="a.id" :color="a.action === 'approved' ? 'green' : 'red'">
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
                                    <div v-if="a.notes" class="bg-gray-50 p-2 rounded text-xs text-gray-600 mt-1">
                                        {{ a.notes }}
                                    </div>
                                </div>
                            </a-timeline-item>
                        </a-timeline>
                    </a-card>
                </a-col>

            </a-row>
            <MemoAttachments :attachments="memo.attachments" :subject="memo.field_values?.meta?.perihal || memo.title" />
        </div>
    </AuthenticatedLayout>
</template>
