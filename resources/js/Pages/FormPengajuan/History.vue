<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FormPengajuanDocument from '@/Components/FormPengajuanDocument.vue';
import MemoAttachments from '@/Components/MemoAttachments.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

const props = defineProps({ formPengajuan: { type: Object, required: true } });
const isAdmin = usePage().props.auth.user?.role === 'ADMIN';
const templateLabels = {
    form_permohonan_pinjaman: 'Form Permohonan Pinjaman (FPP)',
    form_ijin_tidak_masuk_kerja: 'Form Ijin Tidak Masuk Kerja (FITMK)',
};
const document = {
    ...props.formPengajuan,
    meta: { document_kind: 'form_pengajuan', template: props.formPengajuan.template, request_data: props.formPengajuan.data },
};
const attachment = props.formPengajuan.attachment_path ? [{
    id: props.formPengajuan.id,
    file_path: props.formPengajuan.attachment_path,
    original_name: props.formPengajuan.attachment_name,
}] : [];
const statusLabels = { draft: 'Draft', submitted: 'Menunggu Persetujuan', approved: 'Disetujui', rejected: 'Ditolak' };
const printDocument = () => window.print();
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
                <div class="flex items-center gap-3">
                    <a-button class="print:hidden" @click="printDocument">Cetak Form</a-button>
                    <a-tag :color="{ draft: 'default', submitted: 'warning', approved: 'success', rejected: 'error' }[formPengajuan.status]">
                        {{ statusLabels[formPengajuan.status] }}
                    </a-tag>
                </div>
            </div>
        </template>
        <div class="mx-auto grid max-w-7xl gap-6 py-6 px-4 lg:grid-cols-[minmax(0,1fr)_280px]">
            <a-card title="Dokumen Form Pengajuan" :bordered="false" class="rounded-lg shadow-sm">
                <FormPengajuanDocument :berita-acara="document" />
            </a-card>
            <a-card title="Informasi Pengajuan" :bordered="false" class="h-fit rounded-lg shadow-sm">
                <a-descriptions :column="1" size="small">
                    <a-descriptions-item label="Template">{{ templateLabels[formPengajuan.template] }}</a-descriptions-item>
                    <a-descriptions-item label="Pemohon">{{ formPengajuan.data?.full_name || formPengajuan.creator?.name || '-' }}</a-descriptions-item>
                    <a-descriptions-item label="Cabang">{{ formPengajuan.branch?.name || '-' }}</a-descriptions-item>
                    <a-descriptions-item label="Status">{{ statusLabels[formPengajuan.status] }}</a-descriptions-item>
                    <a-descriptions-item v-if="formPengajuan.rejection_notes" label="Catatan Penolakan">{{ formPengajuan.rejection_notes }}</a-descriptions-item>
                    <a-descriptions-item v-if="formPengajuan.approver" label="Disetujui oleh">{{ formPengajuan.approver.name }}</a-descriptions-item>
                </a-descriptions>
            </a-card>
        </div>
        <div class="mx-auto max-w-7xl px-4"><MemoAttachments :attachments="attachment" :subject="formPengajuan.title" /></div>
    </AuthenticatedLayout>
</template>
