<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FormPengajuanDocument from '@/Components/FormPengajuanDocument.vue';
import MemoAttachments from '@/Components/MemoAttachments.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useSweetAlert } from '@/composables/useSweetAlert';
import { escapeHtml } from '@/utils/sanitize';
import {
    LeftOutlined,
    SafetyCertificateOutlined,
    PrinterOutlined,
    DownloadOutlined
} from '@ant-design/icons-vue';

const props = defineProps({
    formPengajuan: { type: Object, required: true },
    signature: { type: Object, default: null },
});
const { success } = useSweetAlert();
const showRejectModal = ref(false);
const showApproveModal = ref(false);
const approveForm = useForm({});
const rejectForm = useForm({ notes: '' });
const templateLabels = {
    form_permohonan_pinjaman: 'Form Permohonan Pinjaman (FPP)',
    form_ijin_tidak_masuk_kerja: 'Form Ijin Tidak Masuk Kerja (FITMK)',
};
const formDocument = {
    ...props.formPengajuan,
    meta: { document_kind: 'form_pengajuan', template: props.formPengajuan.template, request_data: props.formPengajuan.data },
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
const approve = () => approveForm.post(route('approvals.form-pengajuan.approve', props.formPengajuan.id), {
    onSuccess: () => {
        showApproveModal.value = false;
        success('Form Pengajuan berhasil disetujui.');
    },
});
const requestApprove = () => {
    showApproveModal.value = true;
};
const reject = () => rejectForm.post(route('approvals.form-pengajuan.reject', props.formPengajuan.id), {
    onSuccess: () => { showRejectModal.value = false; },
});
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
    const docEl = printableHtml(window.document.querySelector('#printable-fp'));
    if (!docEl) {
        window.print();
        return;
    }
    const attachEl = printableHtml(window.document.querySelector('.memo-print-attachments'));

    const oldIframe = window.document.getElementById('print-iframe');
    if (oldIframe) {
        oldIframe.remove();
    }

    const iframe = window.document.createElement('iframe');
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
    window.document.body.appendChild(iframe);

    const headStyles = Array.from(window.document.querySelectorAll('link[rel="stylesheet"], style'))
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
        #printable-fp { width: 210mm !important; box-sizing: border-box !important; margin: 0 auto !important; }
        .memo-print-attachments { display: block !important; width: 210mm !important; }
        .memo-print-attachment:first-child { page-break-before: always !important; break-before: page !important; }
        .memo-print-attachment + .memo-print-attachment { page-break-before: always !important; break-before: page !important; }
    </style>
</head>
<body>
    ${docEl}
    ${attachEl}
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

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};
</script>

<template>
    <Head :title="`Review Form Pengajuan: ${formPengajuan.code}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-2.5">
                    <Link :href="route('approvals.pending', { tab: 'masuk' })" class="text-gray-400 hover:text-gray-800 dark:hover:text-white transition-colors p-1 -ml-1 flex items-center" title="Kembali ke Daftar Antrean">
                        <left-outlined class="text-base" />
                    </Link>
                    <div>
                        <h1 class="memo-page-title text-base font-bold text-gray-800 dark:text-white mb-0 tracking-tight">Review Form Pengajuan</h1>
                        <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-slate-400">
                            <span class="font-mono font-semibold text-blue-600 dark:text-blue-400">{{ formPengajuan.code || 'Form Baru' }}</span>
                            <span>•</span>
                            <span>{{ formPengajuan.branch?.name || 'Cabang' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </template>
        <div class="memo-print-root max-w-7xl mx-auto py-2">
            <a-row :gutter="[24, 24]">
                <a-col :xs="24" :lg="16" class="flex flex-col items-center justify-start">
                    <a-card :title="templateLabels[formPengajuan.template] || 'Form Pengajuan'" :bordered="false" class="w-full rounded-lg shadow-sm">
                        <template #extra>
                            <div class="flex items-center gap-1.5">
                                <a-tooltip title="Cetak Dokumen">
                                    <a-button size="small" @click="printDocument" class="flex items-center justify-center">
                                        <template #icon><printer-outlined /></template>
                                    </a-button>
                                </a-tooltip>
                                <a-tooltip title="Unduh PDF">
                                    <a-button type="primary" size="small" @click="printDocument" class="flex items-center justify-center">
                                        <template #icon><download-outlined /></template>
                                    </a-button>
                                </a-tooltip>
                            </div>
                        </template>
                        <div class="document-preview-scroll">
                            <div id="printable-fp">
                                <FormPengajuanDocument :berita-acara="formDocument" />
                            </div>
                        </div>
                    </a-card>
                    <MemoAttachments :attachments="attachments" :subject="formPengajuan.title" />
                </a-col>

                <a-col :xs="24" :lg="8">
                    <a-card title="Status Keputusan AM" :bordered="false" class="rounded-lg shadow-sm mb-6" size="small">
                        <template #extra>
                            <a-tag v-if="formPengajuan.status === 'approved'" color="success">Sudah Disetujui</a-tag>
                            <a-tag v-else-if="formPengajuan.status === 'rejected'" color="error">Ditolak</a-tag>
                            <a-tag v-else color="warning">Perlu Tindakan</a-tag>
                        </template>

                        <div v-if="formPengajuan.status === 'approved'" class="space-y-4">
                            <a-alert type="success" show-icon message="Form Pengajuan ini telah disetujui dan tanda tangan digital telah dibubuhkan pada dokumen." />
                            <Link :href="route('approvals.pending', { tab: 'masuk' })">
                                <a-button block>&larr; Kembali ke Daftar Antrean</a-button>
                            </Link>
                        </div>
                        <div v-else-if="formPengajuan.status === 'rejected'" class="space-y-4">
                            <a-alert type="error" show-icon message="Form Pengajuan ini telah ditolak untuk revisi cabang." />
                            <p v-if="formPengajuan.rejection_notes" class="text-sm text-gray-500 mb-0">{{ formPengajuan.rejection_notes }}</p>
                            <Link :href="route('approvals.pending', { tab: 'masuk' })">
                                <a-button block>&larr; Kembali ke Daftar Antrean</a-button>
                            </Link>
                        </div>
                        <div v-else class="space-y-3">
                            <p class="text-sm text-gray-500 mb-4">
                                Periksa kelayakan formulir dan lampiran. Keputusan Anda akan dibubuhkan tanda tangan digital dan tercatat pada riwayat sistem.
                            </p>
                            <a-button type="primary" block size="large" class="bg-green-600 hover:bg-green-500 border-green-600" @click="requestApprove">
                                Setujui & Tanda Tangani Form
                            </a-button>
                            <a-button danger block size="large" @click="showRejectModal = true">
                                Tolak / Minta Revisi Cabang
                            </a-button>
                        </div>
                    </a-card>

                    <a-card title="Tanda Tangan Digital Anda" :bordered="false" class="rounded-lg shadow-sm mb-6" size="small">
                        <template #extra>
                            <Link :href="route('profile.edit')" class="text-blue-500 text-xs hover:underline">Ubah</Link>
                        </template>
                        <div v-if="signature" class="flex items-center gap-4">
                            <div class="h-16 w-28 rounded-lg bg-gray-50 border border-gray-200 flex items-center justify-center p-2">
                                <img :src="'/storage/' + signature.signature_image" alt="TTD Anda" class="max-h-full object-contain" />
                            </div>
                            <div>
                                <div class="flex items-center gap-1 text-green-600 text-sm font-semibold mb-1">
                                    <safety-certificate-outlined /> Siap Dibubuhkan
                                </div>
                                <p class="text-xs text-gray-400 m-0">Sertifikat: {{ signature.certificate_no || 'PGI-DS-AUTO' }}</p>
                            </div>
                        </div>
                        <a-alert v-else type="warning" show-icon class="mt-2">
                            <template #message><span class="font-semibold">Tanda Tangan Belum Didaftarkan</span></template>
                            <template #description>Daftarkan tanda tangan melalui menu Profil sebelum menyetujui dokumen.</template>
                        </a-alert>
                        <a-alert v-if="approveForm.errors.signature" class="mt-3" type="error" show-icon :message="approveForm.errors.signature" />
                    </a-card>

                    <a-card title="Informasi Permohonan" :bordered="false" class="rounded-lg shadow-sm mb-6" size="small">
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between gap-3"><span class="text-gray-500">Pemohon</span><span class="font-medium text-right">{{ formPengajuan.data?.full_name || formPengajuan.creator?.name || '-' }}</span></div>
                            <div class="flex justify-between gap-3"><span class="text-gray-500">Unit Cabang</span><span class="font-medium text-right">{{ formPengajuan.branch?.name || '-' }}</span></div>
                            <div class="flex justify-between gap-3"><span class="text-gray-500">Jenis Form</span><span class="font-medium text-right">{{ templateLabels[formPengajuan.template] || formPengajuan.template || '-' }}</span></div>
                            <div class="flex justify-between gap-3"><span class="text-gray-500">Diajukan</span><span class="text-right">{{ formatDate(formPengajuan.submitted_at || formPengajuan.created_at) }}</span></div>
                        </div>
                    </a-card>

                    <a-card title="Penandatangan Form" :bordered="false" class="rounded-lg shadow-sm" size="small">
                        <a-list item-layout="horizontal" size="small" class="bg-gray-50 rounded-lg p-2 border border-gray-100">
                            <a-list-item>
                                <a-list-item-meta :title="formPengajuan.creator?.name || formPengajuan.data?.full_name || '-'" description="Kepala Cabang (Dibuat)" />
                            </a-list-item>
                            <a-list-item class="border-0">
                                <a-list-item-meta :title="formPengajuan.area_manager?.name || '-'" description="Area Manager (Disetujui)" />
                            </a-list-item>
                        </a-list>
                    </a-card>
                </a-col>
            </a-row>
        </div>

        <a-modal
            v-model:open="showApproveModal"
            title="Konfirmasi Persetujuan Form Pengajuan"
            :confirm-loading="approveForm.processing"
            @ok="approve"
            ok-text="Ya, Setujui Form"
            cancel-text="Batal"
            ok-type="primary"
            :ok-button-props="{ class: 'bg-green-600 hover:bg-green-500 border-green-600' }"
            centered
        >
            <p class="text-sm text-gray-500 mb-4">Form Pengajuan ini akan disetujui dan tanda tangan digital Anda akan dibubuhkan pada dokumen resmi.</p>
            <a-alert v-if="approveForm.errors.signature" class="mt-3" type="error" show-icon :message="approveForm.errors.signature" />
        </a-modal>
        <a-modal
            v-model:open="showRejectModal"
            title="Tolak Form Pengajuan"
            :confirm-loading="rejectForm.processing"
            @ok="reject"
            ok-text="Ya, Tolak Form"
            cancel-text="Batal"
            ok-type="danger"
            centered
        >
            <p class="text-sm text-gray-500 mb-4">Berikan alasan penolakan agar Kepala Cabang dapat melakukan revisi permohonan.</p>
            <a-form layout="vertical">
                <a-form-item label="Alasan Penolakan" required :validate-status="rejectForm.errors.notes ? 'error' : ''" :help="rejectForm.errors.notes">
                    <a-textarea v-model:value="rejectForm.notes" :rows="4" placeholder="Tuliskan alasan penolakan secara jelas..." />
                </a-form-item>
            </a-form>
        </a-modal>
    </AuthenticatedLayout>
</template>
