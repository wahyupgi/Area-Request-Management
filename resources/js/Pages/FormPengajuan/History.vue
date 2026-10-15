<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FormPengajuanDocument from '@/Components/FormPengajuanDocument.vue';
import MemoAttachments from '@/Components/MemoAttachments.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
const page = usePage();

const props = defineProps({ formPengajuan: { type: Object, required: true } });
const currentUser = page.props.auth.user;
const isAdmin = currentUser?.role === 'ADMIN';
const templateLabels = {
    form_permohonan_pinjaman: 'Form Permohonan Pinjaman (FPP)',
    form_ijin_tidak_masuk_kerja: 'Form Ijin Tidak Masuk Kerja (FITMK)',
};
const document = {
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
const canDeleteAttachments = props.formPengajuan.status === 'draft'
    && props.formPengajuan.created_by === currentUser?.id;
const statusConfig = {
    draft: { label: 'Draft', color: 'default' },
    submitted: { label: 'Menunggu AM', color: 'processing' },
    approved: { label: 'Sudah Disetujui', color: 'success' },
    rejected: { label: 'Perlu Revisi', color: 'error' },
};
</script>

<template>
    <Head :title="`Form Pengajuan: ${formPengajuan.code}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <Link :href="isAdmin ? route('dashboard') : route('form-pengajuan.index')">
                        {{ isAdmin ? 'Dashboard' : 'Riwayat Form Pengajuan' }}
                    </Link>
                    <div>
                        <h1 class="mb-0 text-base font-bold">Detail Form Pengajuan</h1>
                        <span class="font-mono text-xs text-blue-600">{{ formPengajuan.code }} · {{ formPengajuan.branch?.name || '-' }}</span>
                    </div>
                </div>
            </div>
        </template>
        <div class="mx-auto grid max-w-7xl gap-6 py-6 px-4 lg:grid-cols-[minmax(0,1fr)_280px]">
            <a-card title="Dokumen Form Pengajuan" :bordered="false" class="rounded-lg shadow-sm">
                <div class="document-preview-scroll">
                    <FormPengajuanDocument :berita-acara="document" />
                </div>
            </a-card>
            <a-card title="Informasi Pengajuan" :bordered="false" class="h-fit rounded-lg shadow-sm">
                <div class="space-y-4">
                    <div class="grid grid-cols-[82px_minmax(0,1fr)] items-start gap-3">
                        <span class="text-xs text-gray-500">Template</span>
                        <span class="min-w-0 break-words text-sm font-medium text-gray-800">{{ templateLabels[formPengajuan.template] || formPengajuan.template }}</span>
                    </div>
                    <div class="grid grid-cols-[82px_minmax(0,1fr)] items-start gap-3">
                        <span class="text-xs text-gray-500">Pemohon</span>
                        <span class="min-w-0 break-words text-sm font-medium text-gray-800">{{ formPengajuan.data?.full_name || formPengajuan.creator?.name || '-' }}</span>
                    </div>
                    <div class="grid grid-cols-[82px_minmax(0,1fr)] items-start gap-3">
                        <span class="text-xs text-gray-500">Cabang</span>
                        <span class="min-w-0 break-words text-sm font-medium text-gray-800">{{ formPengajuan.branch?.name || '-' }}</span>
                    </div>
                    <div class="grid grid-cols-[82px_minmax(0,1fr)] items-start gap-3">
                        <span class="text-xs text-gray-500">Status</span>
                        <a-tag :color="statusConfig[formPengajuan.status]?.color || 'default'" class="m-0 w-fit">
                            {{ statusConfig[formPengajuan.status]?.label || formPengajuan.status }}
                        </a-tag>
                    </div>
                    <div v-if="formPengajuan.rejection_notes" class="grid grid-cols-[82px_minmax(0,1fr)] items-start gap-3 border-t border-gray-100 pt-3">
                        <span class="text-xs text-gray-500">Catatan</span>
                        <span class="min-w-0 break-words text-sm text-gray-700">{{ formPengajuan.rejection_notes }}</span>
                    </div>
                    <div v-if="formPengajuan.approver" class="grid grid-cols-[82px_minmax(0,1fr)] items-start gap-3">
                        <span class="text-xs text-gray-500">Disetujui oleh</span>
                        <span class="min-w-0 break-words text-sm font-medium text-gray-800">{{ formPengajuan.approver.name }}</span>
                    </div>
                </div>
            </a-card>
        </div>
        <div class="mx-auto max-w-7xl px-4">
            <MemoAttachments
                :attachments="attachments"
                :subject="formPengajuan.title"
                delete-route-name="form-pengajuan.attachments.destroy"
                :document-id="formPengajuan.id"
                :can-delete="canDeleteAttachments"
            />
        </div>
    </AuthenticatedLayout>
</template>
