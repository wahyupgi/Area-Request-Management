<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MemoDocument from '@/Components/MemoDocument.vue';
import MemoAttachments from '@/Components/MemoAttachments.vue';
import { Head } from '@inertiajs/vue3';
import { escapeHtml } from '@/utils/sanitize';
import { 
    LeftOutlined,
    CheckCircleOutlined,
    CloseCircleOutlined,
    PrinterOutlined,
    DownloadOutlined
} from '@ant-design/icons-vue';

const props = defineProps({ memo: Object });

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
        } catch {}
    });
    return clone.outerHTML;
};

const printMemo = () => {
    const docEl = printableHtml(document.querySelector('#printable-memo'));
    if (!docEl) {
        window.print();
        return;
    }
    const attachments = printableHtml(document.querySelector('.memo-print-attachments'));

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

    const originalTitle = document.title;
    const headStyles = Array.from(document.querySelectorAll('link[rel="stylesheet"], style'))
        .map(el => el.outerHTML)
        .join('\n');

    const html = `<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>${escapeHtml(originalTitle)}</title>
    ${headStyles}
    <style>
        @page { size: A4 portrait; margin: 0; }
        html, body { margin: 0 !important; padding: 0 !important; background: #fff !important; width: 210mm !important; min-height: 0 !important; height: auto !important; }
        body * { visibility: visible !important; }
        .memo-print-page { width: 210mm !important; box-sizing: border-box !important; }
        #printable-memo { position: relative !important; width: 210mm !important; min-height: 0 !important; box-sizing: border-box !important; margin: 0 auto !important; page-break-after: avoid !important; break-after: avoid !important; }
        .memo-print-attachments { display: block !important; width: 210mm !important; margin: 0 !important; page-break-before: auto !important; break-before: auto !important; }
        .memo-print-attachment { display: block !important; width: 210mm !important; min-height: 0 !important; box-sizing: border-box !important; }
        .memo-print-attachment:first-child { page-break-before: always !important; break-before: page !important; }
        .memo-print-attachment + .memo-print-attachment { page-break-before: always !important; break-before: page !important; }
        .memo-pdf-page-list { display: flex !important; flex-direction: column !important; align-items: center !important; width: 100% !important; gap: 0 !important; }
        .memo-pdf-page { width: auto !important; height: auto !important; max-width: 100% !important; max-height: 295mm !important; margin: 0 auto !important; object-fit: contain !important; break-inside: avoid !important; page-break-inside: avoid !important; }
        .memo-pdf-page:not(:last-child) { page-break-after: always !important; break-after: page !important; }
        .memo-attachment-docx .docx-wrapper > section.docx:last-of-type { page-break-after: auto !important; break-after: auto !important; margin-bottom: 0 !important; }
        .memo-print-attachment:last-child { page-break-after: avoid !important; break-after: avoid !important; }
    </style>
</head>
<body>
    <div class="memo-print-page">${docEl}</div>
    ${attachments}
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
    }, 250);
};
</script>

<template>
    <Head :title="'Riwayat: ' + memo.title" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-2.5">
                    <button type="button" onclick="history.back()" class="text-gray-400 hover:text-gray-800 dark:hover:text-white transition-colors p-1 -ml-1 flex items-center bg-transparent border-0 cursor-pointer">
                        <left-outlined class="text-base" />
                    </button>
                    <div>
                        <h1 class="memo-page-title text-xl font-bold text-gray-800 dark:text-white mb-0">Riwayat Approval</h1>
                        <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-slate-400">
                            <span class="font-mono font-semibold text-blue-600 dark:text-blue-400">{{ memo.code }}</span>
                            <span>•</span>
                            <span>{{ memo.branch?.name || 'Cabang' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <a-row :gutter="[24, 24]" class="max-w-7xl mx-auto">
            <!-- Left Column: Memo Document -->
            <a-col :xs="24" :lg="16" class="flex justify-center">
                <div class="document-preview-scroll w-full flex flex-col items-center">
                    <div class="document-preview-paper shadow-md bg-white">
                        <MemoDocument :memo="memo" :show-am-signature="memo.status === 'approved'" />
                    </div>
                </div>
            </a-col>

            <!-- Right Column: Approval History -->
            <a-col :xs="24" :lg="8">
                <a-card :bordered="false" class="rounded-lg shadow-sm mb-6 bg-gray-50">
                    <h2 class="text-lg font-bold text-gray-800 mb-1">{{ memo.title }}</h2>
                    <p class="text-sm text-gray-500 mb-0">{{ memo.code }} · {{ memo.template?.name }}</p>
                </a-card>

                <a-card title="Timeline Approval" :bordered="false" class="rounded-lg shadow-sm">
                    <a-timeline>
                        <a-timeline-item v-for="(approval, idx) in memo.approvals" :key="approval.id" :color="approval.action === 'approved' ? 'green' : 'red'">
                            <template #dot>
                                <check-circle-outlined v-if="approval.action === 'approved'" />
                                <close-circle-outlined v-else />
                            </template>
                            
                            <div class="mb-4 bg-white border border-gray-100 rounded-lg p-4 shadow-sm" :class="approval.action === 'approved' ? 'border-green-100' : 'border-red-100'">
                                <div class="flex items-center justify-between mb-2">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800 mb-0">{{ approval.approver?.name }}</p>
                                        <p class="text-xs text-gray-500 mb-0">{{ new Date(approval.created_at).toLocaleString('id-ID') }}</p>
                                    </div>
                                    <a-tag :color="approval.action === 'approved' ? 'success' : 'error'" class="uppercase m-0">
                                        {{ approval.action === 'approved' ? 'Disetujui' : 'Ditolak' }}
                                    </a-tag>
                                </div>
                                
                                <div v-if="approval.notes" class="text-sm text-gray-600 bg-gray-50 p-3 rounded mt-3">
                                    {{ approval.notes }}
                                </div>
                                
                                <div v-if="approval.signature" class="mt-3">
                                    <img :src="'/storage/' + approval.signature.signature_image" alt="Signature" class="h-16 rounded border border-gray-200 bg-white p-1" />
                                </div>
                            </div>
                        </a-timeline-item>
                    </a-timeline>
                </a-card>
            </a-col>
        </a-row>
        <MemoAttachments :attachments="memo.attachments || []" :subject="memo.field_values?.meta?.perihal || memo.title" />
    </AuthenticatedLayout>
</template>
