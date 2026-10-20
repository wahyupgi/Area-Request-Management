<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FormPengajuanDocument from '@/Components/FormPengajuanDocument.vue';
import MemoAttachments from '@/Components/MemoAttachments.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    ArrowLeftOutlined,
    PrinterOutlined,
    PaperClipOutlined,
    CheckCircleOutlined,
    CloseCircleOutlined,
} from '@ant-design/icons-vue';

const props = defineProps({ formPengajuan: Object });
const user = usePage().props.auth.user;

const backRoute = computed(() => {
    if (user?.role === 'AM') return route('approvals.pending');
    if (user?.role === 'ADMIN') return route('dashboard');
    return route('form-pengajuan.index');
});

const templateLabels = {
    form_permohonan_pinjaman:    'Form Permohonan Pinjaman (FPP)',
    form_ijin_tidak_masuk_kerja: 'Form Ijin Tidak Masuk Kerja (FITMK)',
};

const statusConfig = {
    draft:     { label: 'Draft',                color: 'default' },
    submitted: { label: 'Menunggu Persetujuan', color: 'warning' },
    approved:  { label: 'Disetujui Resmi',      color: 'success' },
    rejected:  { label: 'Ditolak / Revisi',     color: 'error'   },
};

// Normalize agar sesuai struktur yang diperlukan FormPengajuanDocument
const document = {
    ...props.formPengajuan,
    meta: {
        document_kind: 'form_pengajuan',
        template: props.formPengajuan.template,
        request_data: props.formPengajuan.data,
    },
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: '2-digit', month: 'short', year: 'numeric',
    });
};

const attachmentUrl = (attachment) => '/storage/' + attachment.file_path;

const printFileName = () =>
    `Form Pengajuan - ${[props.formPengajuan.title, props.formPengajuan.code].filter(Boolean).join(' - ') || 'Dokumen'}`
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

const printDocument = () => {
    const printWindow = window.open('', '_blank');
    if (!printWindow) return;

    const docEl    = printableHtml(document.querySelector('#printable-fp'));
    const attachEl = printableHtml(document.querySelector('.memo-print-attachments'));
    const stylesheetUrls = Array.from(document.querySelectorAll('link[rel="stylesheet"]')).map(l => l.href);
    const activeStyles   = Array.from(document.querySelectorAll('style')).map(s => s.textContent).join('\n');

    Promise.all(stylesheetUrls.map(url => fetch(url).then(r => r.text()).catch(() => ''))).then(styles => {
        printWindow.document.write(`<!doctype html><html><head><title>${printFileName()}</title><style>${styles.join('\n')}\n${activeStyles}</style><style>
            @page { size: A4 portrait; margin: 0; }
            html, body { margin: 0 !important; padding: 0 !important; background: #fff !important; }
            #printable-fp { width: 210mm !important; box-sizing: border-box !important; }
            .memo-print-attachments { display: block !important; width: 210mm !important; }
            .memo-print-attachment:first-child { page-break-before: always !important; break-before: page !important; }
            .memo-print-attachment + .memo-print-attachment { page-break-before: always !important; break-before: page !important; }
        </style></head><body>${docEl}${attachEl}</body></html>`);
        printWindow.onload = () => {
            printWindow.document.title = printFileName();
            printWindow.onafterprint = () => printWindow.close();
            printWindow.focus();
            printWindow.print();
        };
        printWindow.document.close();
    });
};

const attachments = [
    ...(props.formPengajuan.attachments || []),
    ...(props.formPengajuan.attachment_path ? [{
        id: `legacy-form-${props.formPengajuan.id}`,
        legacy: true,
        file_path: props.formPengajuan.attachment_path,
        original_name: props.formPengajuan.attachment_name,
    }] : []),
];
</script>

<template>
    <Head :title="'Detail Form Pengajuan: ' + (formPengajuan.code || formPengajuan.title)" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="backRoute">
                    <a-button type="text" shape="circle" class="text-slate-700 dark:text-slate-200 dark:hover:text-white dark:hover:bg-slate-700/50">
                        <template #icon><arrow-left-outlined /></template>
                    </a-button>
                </Link>
                <div>
                    <h1 class="memo-page-title text-base font-bold text-gray-800 dark:text-white mb-0">Detail Form Pengajuan</h1>
                    <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-slate-400">
                        <span class="font-mono font-semibold text-blue-600 dark:text-blue-400">{{ formPengajuan.code || 'Belum ada kode' }}</span>
                        <span>•</span>
                        <span>{{ formPengajuan.branch?.name || 'Kantor Cabang' }}</span>
                    </div>
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto py-2 print:p-0 print:m-0 print:max-w-none print:w-full">
            <a-row :gutter="[24, 24]" class="print:block print:w-full print:m-0 print:p-0">

                <!-- Kiri: Preview Dokumen -->
                <a-col :xs="24" :lg="16" class="flex flex-col items-center justify-start print:block print:w-full print:m-0 print:p-0">
                    <div class="document-preview-scroll print:overflow-visible">
                        <div class="document-preview-paper shadow-md print:shadow-none bg-white">
                            <div id="printable-fp">
                                <FormPengajuanDocument :berita-acara="document" />
                            </div>
                        </div>
                    </div>
                </a-col>

                <!-- Kanan: Sidebar Info -->
                <a-col :xs="24" :lg="8" class="print:hidden">

                    <!-- Card 1: Info Dokumen -->
                    <a-card title="Informasi Dokumen" :bordered="false" class="rounded-lg shadow-sm mb-6" size="small">
                        <template #extra>
                            <a-button type="link" size="small" @click="printDocument">
                                <template #icon><printer-outlined /></template>
                                Cetak
                            </a-button>
                        </template>

                        <div class="space-y-3 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Kode</span>
                                <span class="font-mono text-blue-600 font-semibold">{{ formPengajuan.code || '-' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Status</span>
                                <a-tag :color="statusConfig[formPengajuan.status]?.color" class="font-semibold m-0">
                                    {{ statusConfig[formPengajuan.status]?.label }}
                                </a-tag>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Jenis Form</span>
                                <span class="font-medium text-right max-w-[160px] text-xs">
                                    {{ templateLabels[formPengajuan.template] || formPengajuan.template || '-' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Pemohon</span>
                                <span class="font-medium">{{ formPengajuan.creator?.name || '-' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Cabang</span>
                                <span class="font-medium">{{ formPengajuan.branch?.name || '-' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Area Manager</span>
                                <span class="font-medium">{{ formPengajuan.area_manager?.name || formPengajuan.areaManager?.name || '-' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Tgl Pengajuan</span>
                                <span>{{ formatDate(formPengajuan.submitted_at || formPengajuan.created_at) }}</span>
                            </div>
                        </div>
                    </a-card>

                    <!-- Card 2: Catatan Penolakan (jika rejected) -->
                    <a-card v-if="formPengajuan.status === 'rejected'" title="Catatan Penolakan" :bordered="false" class="rounded-lg shadow-sm mb-6" size="small">
                        <a-alert type="error" show-icon>
                            <template #message><span class="font-semibold">Form Ditolak / Perlu Revisi</span></template>
                            <template #description>
                                <p class="text-sm mt-1">{{ formPengajuan.rejection_notes || 'Tidak ada catatan.' }}</p>
                            </template>
                        </a-alert>
                        <div v-if="user?.role === 'KC'" class="mt-3">
                            <Link :href="route('form-pengajuan.create')">
                                <a-button type="primary" block>Ajukan Kembali</a-button>
                            </Link>
                        </div>
                    </a-card>

                    <!-- Card 3: Lampiran -->
                    <a-card
                        v-if="attachments.length > 0"
                        :title="'Lampiran (' + attachments.length + ')'"
                        :bordered="false"
                        class="rounded-lg shadow-sm mb-6"
                        size="small"
                    >
                        <a-list item-layout="horizontal" :data-source="attachments" size="small">
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

                    <!-- Card 4: Penandatangan -->
                    <a-card title="Penandatangan" :bordered="false" class="rounded-lg shadow-sm mb-6" size="small">
                        <a-list item-layout="horizontal" size="small">
                            <a-list-item>
                                <a-list-item-meta
                                    :title="formPengajuan.creator?.name || formPengajuan.data?.full_name || '-'"
                                    description="Kepala Cabang (Pemohon)"
                                />
                            </a-list-item>
                            <a-list-item>
                                <a-list-item-meta
                                    :title="formPengajuan.area_manager?.name || formPengajuan.areaManager?.name || '-'"
                                    description="Area Manager (Penyetuju)"
                                />
                            </a-list-item>
                        </a-list>
                    </a-card>

                    <!-- Card 5: Riwayat Approval -->
                    <a-card
                        v-if="formPengajuan.approvals?.length > 0"
                        title="Riwayat Persetujuan"
                        :bordered="false"
                        class="rounded-lg shadow-sm"
                        size="small"
                    >
                        <a-timeline class="mt-2">
                            <a-timeline-item
                                v-for="a in formPengajuan.approvals"
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

            <MemoAttachments :attachments="attachments" :subject="formPengajuan.title" />
        </div>
    </AuthenticatedLayout>
</template>
