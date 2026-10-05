<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FormPengajuanDocument from '@/Components/FormPengajuanDocument.vue';
import MemoAttachments from '@/Components/MemoAttachments.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ formPengajuan: { type: Object, required: true } });
const showRejectModal = ref(false);
const showApproveModal = ref(false);
const approveForm = useForm({});
const rejectForm = useForm({ notes: '' });
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
const printDocument = () => window.print();
const approve = () => approveForm.post(route('approvals.form-pengajuan.approve', props.formPengajuan.id), {
    onSuccess: () => { showApproveModal.value = false; },
});
const reject = () => rejectForm.post(route('approvals.form-pengajuan.reject', props.formPengajuan.id), {
    onSuccess: () => { showRejectModal.value = false; },
});
</script>

<template>
    <Head :title="`Review Form Pengajuan: ${formPengajuan.code}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <Link :href="route('approvals.pending')">Kotak Masuk</Link>
                    <div>
                        <h1 class="mb-0 text-base font-bold">Review Form Pengajuan</h1>
                        <span class="font-mono text-xs text-blue-600">{{ formPengajuan.code }} · {{ formPengajuan.branch?.name || '-' }}</span>
                    </div>
                </div>
                <a-button class="print:hidden" @click="printDocument">Cetak Form</a-button>
            </div>
        </template>
        <div class="mx-auto grid max-w-7xl gap-6 py-6 px-4 lg:grid-cols-[minmax(0,1fr)_280px]">
            <a-card :title="templateLabels[formPengajuan.template]" :bordered="false" class="rounded-lg shadow-sm">
                <FormPengajuanDocument :berita-acara="document" />
            </a-card>
            <div class="space-y-4">
                <a-card title="Keputusan Persetujuan" :bordered="false" class="rounded-lg shadow-sm">
                    <a-alert type="info" show-icon message="Periksa formulir dan lampiran sebelum memberi keputusan." />
                    <div class="mt-4 flex flex-col gap-2">
                        <a-button type="primary" block @click="showApproveModal = true">Setujui Form</a-button>
                        <a-button danger block @click="showRejectModal = true">Tolak Form</a-button>
                    </div>
                    <a-alert v-if="approveForm.errors.signature" class="mt-3" type="error" show-icon :message="approveForm.errors.signature" />
                </a-card>
                <a-card title="Pemohon" :bordered="false" class="rounded-lg shadow-sm">
                    <a-descriptions :column="1" size="small">
                        <a-descriptions-item label="Nama">{{ formPengajuan.data?.full_name || formPengajuan.creator?.name || '-' }}</a-descriptions-item>
                        <a-descriptions-item label="Cabang">{{ formPengajuan.branch?.name || '-' }}</a-descriptions-item>
                        <a-descriptions-item label="Tanggal Diajukan">{{ new Date(formPengajuan.submitted_at).toLocaleDateString('id-ID') }}</a-descriptions-item>
                    </a-descriptions>
                </a-card>
            </div>
        </div>
        <div class="mx-auto max-w-7xl px-4"><MemoAttachments :attachments="attachment" :subject="formPengajuan.title" /></div>

        <a-modal v-model:open="showApproveModal" title="Setujui Form Pengajuan" :confirm-loading="approveForm.processing" ok-text="Ya, Setujui" cancel-text="Batal" @ok="approve">
            <p>Pastikan tanda tangan digital Anda telah terdaftar sebelum menyetujui.</p>
            <a-alert v-if="approveForm.errors.signature" class="mt-3" type="error" show-icon :message="approveForm.errors.signature" />
        </a-modal>
        <a-modal v-model:open="showRejectModal" title="Tolak Form Pengajuan" :confirm-loading="rejectForm.processing" ok-text="Ya, Tolak" cancel-text="Batal" ok-type="danger" @ok="reject">
            <a-form-item label="Alasan Penolakan" required :validate-status="rejectForm.errors.notes ? 'error' : ''" :help="rejectForm.errors.notes">
                <a-textarea v-model:value="rejectForm.notes" :rows="4" placeholder="Tuliskan alasan penolakan" />
            </a-form-item>
        </a-modal>
    </AuthenticatedLayout>
</template>
