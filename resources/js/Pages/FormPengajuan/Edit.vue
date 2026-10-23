<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { message } from 'ant-design-vue';
import { useSweetAlert } from '@/composables/useSweetAlert';
import { preventNonDigitPaste, restrictToDigits } from '@/composables/numericInput';
import {
    LeftOutlined,
    SaveOutlined,
    SendOutlined,
    PaperClipOutlined,
} from '@ant-design/icons-vue';

const props = defineProps({
    formPengajuan: Object,
    signature:     { type: Object, default: null },
});

const { success, confirm } = useSweetAlert();

const templateName = props.formPengajuan.template;
const isFPP  = templateName === 'form_permohonan_pinjaman';
const isFITMK = templateName === 'form_ijin_tidak_masuk_kerja';

const form = useForm({
    title: props.formPengajuan.title || '',
    meta: {
        request_data: { ...(props.formPengajuan.data || {}) },
    },
});

// ── Signature state ───────────────────────────────────────────────
const showSignatureDialog = ref(false);
const signatureFile       = ref(null);
const signaturePreview    = ref(null);
const signatureError      = ref('');
const submitting          = ref(false);

// ── Attachment state ──────────────────────────────────────────────
const fileInput = ref(null);
const uploading = ref(false);

// ── Input helpers ─────────────────────────────────────────────────
const formatRupiah = (value) => {
    const digits = String(value ?? '').replace(/\D/g, '');
    return digits ? `Rp ${digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.')}` : '';
};

const salaryAfterApproval = {
    get value() { return formatRupiah(form.meta.request_data.salary_after_approval); },
    set value(v) { form.meta.request_data.salary_after_approval = String(v).replace(/\D/g, ''); },
};
const minimumSalary = {
    get value() { return formatRupiah(form.meta.request_data.minimum_salary); },
    set value(v) { form.meta.request_data.minimum_salary = String(v).replace(/\D/g, ''); },
};
const loanAmount = {
    get value() { return formatRupiah(form.meta.request_data.loan_amount); },
    set value(v) { form.meta.request_data.loan_amount = String(v).replace(/\D/g, ''); },
};

const resetOtherLeaveType = () => { form.meta.request_data.leave_type = ''; form.meta.request_data.leave_type_other = ''; };

// ── Save draft ────────────────────────────────────────────────────
const save = () => {
    form.put(route('form-pengajuan.update', props.formPengajuan.id), {
        onSuccess: () => success('Form Pengajuan berhasil diperbarui.'),
    });
};

// ── Submit to AM ──────────────────────────────────────────────────
const doSubmit = (signatureToUpload = signatureFile.value) => {
    submitting.value = true;
    form.put(route('form-pengajuan.update', props.formPengajuan.id), {
        preserveScroll: true,
        onSuccess: () => {
            const formData = new FormData();
            if (signatureToUpload) formData.append('signature_image', signatureToUpload);
            router.post(route('form-pengajuan.submit', props.formPengajuan.id), formData, {
                forceFormData: true,
                onSuccess: () => {
                    showSignatureDialog.value = false;
                    success('Form Pengajuan berhasil dikirim ke Area Manager.');
                    router.visit(route('form-pengajuan.show', props.formPengajuan.id));
                },
                onError: (errors) => {
                    submitting.value = false;
                    showSignatureDialog.value = false;
                    if (!errors.signature) message.error(Object.values(errors).join(' '));
                },
                onFinish: () => { submitting.value = false; },
            });
        },
        onError: () => { submitting.value = false; showSignatureDialog.value = false; },
    });
};

const submitForm = () => {
    if (props.signature?.signature_image) { doSubmit(null); return; }
    showSignatureDialog.value = true;
};

const selectSignature = (event) => {
    const file = event.target.files?.[0];
    signatureError.value = '';
    signatureFile.value = file || null;
    signaturePreview.value = null;
    if (!file) return;
    if (!['image/png', 'image/jpeg'].includes(file.type)) {
        signatureError.value = 'Format harus PNG atau JPG.';
        signatureFile.value = null; event.target.value = ''; return;
    }
    if (file.size > 2 * 1024 * 1024) {
        signatureError.value = 'Ukuran maksimal 2MB.';
        signatureFile.value = null; event.target.value = ''; return;
    }
    const reader = new FileReader();
    reader.onload = (e) => { signaturePreview.value = e.target.result; };
    reader.readAsDataURL(file);
};
const confirmSubmit = () => doSubmit(signatureFile.value);

// ── Attachment ────────────────────────────────────────────────────
const deleteAttachment = async (attachment) => {
    if (await confirm('Hapus lampiran ini?', 'Lampiran akan dihapus dari dokumen.')) {
        router.delete(route('form-pengajuan.attachments.destroy', [props.formPengajuan.id, attachment.id]));
    }
};
</script>

<template>
    <Head :title="'Edit Form Pengajuan: ' + formPengajuan.title" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-2.5">
                <Link :href="route('form-pengajuan.index')" class="text-gray-400 hover:text-gray-800 dark:hover:text-white transition-colors p-1 -ml-1 flex items-center">
                    <LeftOutlined class="text-base" />
                </Link>
                <h1 class="memo-page-title text-xl font-bold mb-0">Edit Form Pengajuan</h1>
            </div>
        </template>

        <div class="memo-create-page space-y-6">
            <!-- Status bar -->
            <a-card :bordered="false" class="rounded-lg shadow-sm">
                <div class="flex items-center gap-3 mb-2">
                    <a-tag color="default" class="font-mono">{{ formPengajuan.code }}</a-tag>
                    <a-tag :color="formPengajuan.status === 'rejected' ? 'error' : 'default'" class="font-semibold uppercase">{{ formPengajuan.status }}</a-tag>
                </div>
                <p class="text-sm text-gray-500 mb-0">Jenis: <span class="font-medium text-gray-800">{{ isFPP ? 'Form Permohonan Pinjaman (FPP)' : 'Form Ijin Tidak Masuk Kerja (FITMK)' }}</span></p>
            </a-card>

            <!-- Catatan penolakan -->
            <a-alert v-if="formPengajuan.status === 'rejected'" type="error" show-icon class="rounded-lg">
                <template #message><span class="font-semibold">Form Pengajuan Ditolak</span></template>
                <template #description>{{ formPengajuan.rejection_notes || 'Tidak ada catatan.' }}</template>
            </a-alert>

            <a-form layout="vertical" @finish="save">
                <a-card :bordered="false" class="rounded-lg shadow-sm">
                    <h2 class="text-sm font-semibold mb-4">{{ isFPP ? 'Data Permohonan Pinjaman' : 'Data Ijin Tidak Masuk Kerja' }}</h2>

                    <a-form-item label="Judul Dokumen" class="mb-4" :validateStatus="form.errors.title ? 'error' : ''" :help="form.errors.title">
                        <a-input v-model:value="form.title" placeholder="Judul dokumen" size="large" />
                    </a-form-item>

                    <!-- FPP Fields -->
                    <template v-if="isFPP">
                        <a-alert class="mb-4" type="info" show-icon message="Form Permohonan Pinjaman (FPP)" description="Lengkapi data pemohon dan rincian pinjaman." />
                        <a-row :gutter="12">
                            <a-col :xs="24" :md="12"><a-form-item label="Nama Lengkap" :validateStatus="form.errors['meta.request_data.full_name'] ? 'error' : ''" :help="form.errors['meta.request_data.full_name']"><a-input v-model:value="form.meta.request_data.full_name" /></a-form-item></a-col>
                            <a-col :xs="24" :md="12"><a-form-item label="Jabatan"><a-input v-model:value="form.meta.request_data.position" /></a-form-item></a-col>
                            <a-col :xs="24" :md="12"><a-form-item label="Cabang / Lokasi Kerja"><a-input v-model:value="form.meta.request_data.work_location" /></a-form-item></a-col>
                            <a-col :xs="24" :md="12"><a-form-item label="Tanggal Masuk Kerja"><a-input v-model:value="form.meta.request_data.employment_date" type="date" /></a-form-item></a-col>
                            <a-col :xs="24" :md="8"><a-form-item label="Terlambat (bulan)"><a-input-number v-model:value="form.meta.request_data.late_months" :min="0" @keydown="restrictToDigits" @paste="preventNonDigitPaste" class="w-full" /></a-form-item></a-col>
                            <a-col :xs="24" :md="8"><a-form-item label="Tidak Masuk (bulan)"><a-input-number v-model:value="form.meta.request_data.absence_months" :min="0" @keydown="restrictToDigits" @paste="preventNonDigitPaste" class="w-full" /></a-form-item></a-col>
                            <a-col :xs="24" :md="8"><a-form-item label="Permohonan ke-"><a-input v-model:value="form.meta.request_data.request_number" @keydown="restrictToDigits" @paste="preventNonDigitPaste" /></a-form-item></a-col>
                            <a-col :xs="24" :md="12"><a-form-item label="Gaji setelah disetujui"><a-input v-model:value="salaryAfterApproval.value" placeholder="Contoh: 6.000.000" /></a-form-item></a-col>
                            <a-col :xs="24" :md="12"><a-form-item label="Gaji minimal"><a-input v-model:value="minimumSalary.value" placeholder="Contoh: 4.500.000" /></a-form-item></a-col>
                            <a-col :xs="24" :md="12"><a-form-item label="Jumlah pinjaman" :validateStatus="form.errors['meta.request_data.loan_amount'] ? 'error' : ''" :help="form.errors['meta.request_data.loan_amount']"><a-input v-model:value="loanAmount.value" placeholder="Contoh: 5.000.000" /></a-form-item></a-col>
                            <a-col :xs="24" :md="12"><a-form-item label="Lama pengembalian (bulan)" :validateStatus="form.errors['meta.request_data.repayment_months'] ? 'error' : ''" :help="form.errors['meta.request_data.repayment_months']"><a-input-number v-model:value="form.meta.request_data.repayment_months" :min="1" @keydown="restrictToDigits" @paste="preventNonDigitPaste" class="w-full" /></a-form-item></a-col>
                            <a-col :xs="24" :md="12"><a-form-item label="Bersedia potong gaji setiap bulan"><a-radio-group v-model:value="form.meta.request_data.salary_deduction"><a-radio value="Bersedia">Bersedia</a-radio><a-radio value="Tidak bersedia">Tidak bersedia</a-radio></a-radio-group></a-form-item></a-col>
                            <a-col :xs="24" :md="12"><a-form-item label="Jenis Pinjaman"><a-radio-group v-model:value="form.meta.request_data.loan_type"><a-radio value="pinjaman_uang">Pinjaman Uang</a-radio><a-radio value="pembelian_barang">Pembelian Barang</a-radio></a-radio-group></a-form-item></a-col>
                            <a-col :span="24"><a-form-item label="Alasan Pinjaman" :validateStatus="form.errors['meta.request_data.reason'] ? 'error' : ''" :help="form.errors['meta.request_data.reason']"><a-textarea v-model:value="form.meta.request_data.reason" :rows="4" /></a-form-item></a-col>
                        </a-row>
                    </template>

                    <!-- FITMK Fields -->
                    <template v-else-if="isFITMK">
                        <a-alert class="mb-4" type="info" show-icon message="Form Ijin Tidak Masuk Kerja (FITMK)" description="Isi detail ketidakhadiran dan serah terima pekerjaan." />
                        <a-row :gutter="12">
                            <a-col :xs="24" :md="12"><a-form-item label="Nama" :validateStatus="form.errors['meta.request_data.full_name'] ? 'error' : ''" :help="form.errors['meta.request_data.full_name']"><a-input v-model:value="form.meta.request_data.full_name" /></a-form-item></a-col>
                            <a-col :xs="24" :md="12">
                                <a-form-item label="Jenis ijin" :validateStatus="form.errors['meta.request_data.leave_type_other'] ? 'error' : ''" :help="form.errors['meta.request_data.leave_type_other']">
                                    <a-input v-if="form.meta.request_data.leave_type === 'lainnya'" v-model:value="form.meta.request_data.leave_type_other" placeholder="Tuliskan jenis ijin">
                                        <template #addonAfter><a-button type="link" size="small" @click="resetOtherLeaveType">Pilih jenis lain</a-button></template>
                                    </a-input>
                                    <a-select v-else v-model:value="form.meta.request_data.leave_type">
                                        <a-select-option value="cuti_tahunan">Cuti Tahunan</a-select-option>
                                        <a-select-option value="ijin">Ijin (Belum Ada Hak Cuti)</a-select-option>
                                        <a-select-option value="cuti_melahirkan">Cuti Melahirkan</a-select-option>
                                        <a-select-option value="cuti_khusus">Cuti Khusus</a-select-option>
                                        <a-select-option value="sakit">Sakit</a-select-option>
                                        <a-select-option value="lainnya">Lainnya</a-select-option>
                                    </a-select>
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :md="12"><a-form-item label="Tanggal mulai ijin" :validateStatus="form.errors['meta.request_data.start_date'] ? 'error' : ''" :help="form.errors['meta.request_data.start_date']"><a-input v-model:value="form.meta.request_data.start_date" type="date" /></a-form-item></a-col>
                            <a-col :xs="24" :md="12"><a-form-item label="Durasi (hari)" :validateStatus="form.errors['meta.request_data.duration'] ? 'error' : ''" :help="form.errors['meta.request_data.duration']"><a-input-number v-model:value="form.meta.request_data.duration" :min="1" @keydown="restrictToDigits" @paste="preventNonDigitPaste" class="w-full" /></a-form-item></a-col>
                            <a-col :span="24"><a-form-item label="Alasan ijin" :validateStatus="form.errors['meta.request_data.reason'] ? 'error' : ''" :help="form.errors['meta.request_data.reason']"><a-textarea v-model:value="form.meta.request_data.reason" :rows="3" /></a-form-item></a-col>
                            <a-col :xs="24" :md="12"><a-form-item label="Pekerjaan dilimpahkan ke"><a-input v-model:value="form.meta.request_data.handover" /></a-form-item></a-col>
                            <a-col :xs="24" :md="12"><a-form-item label="Jabatan pengganti"><a-input v-model:value="form.meta.request_data.substitute_position" /></a-form-item></a-col>
                            <a-col :span="24"><a-form-item label="Kontak selama tidak masuk"><a-input v-model:value="form.meta.request_data.phone" @keydown="restrictToDigits" @paste="preventNonDigitPaste" /></a-form-item></a-col>
                        </a-row>
                    </template>

                    <!-- Lampiran -->
                    <div class="mt-4 pt-4 border-t">
                        <h3 class="text-sm font-semibold mb-3">Lampiran Dokumen</h3>
                        <a-list v-if="formPengajuan.attachments?.length > 0" item-layout="horizontal" :data-source="formPengajuan.attachments" size="small" class="mb-3">
                            <template #renderItem="{ item }">
                                <a-list-item>
                                    <a-list-item-meta :title="item.original_name || item.file_path">
                                        <template #avatar><paper-clip-outlined /></template>
                                    </a-list-item-meta>
                                    <template #actions>
                                        <a-button type="link" danger size="small" @click="deleteAttachment(item)">Hapus</a-button>
                                    </template>
                                </a-list-item>
                            </template>
                        </a-list>
                        <p class="mb-2 text-xs text-gray-400">Maksimal 3 lampiran DOCX/PDF, 10 MB per file.</p>
                    </div>

                    <!-- Action buttons -->
                    <div class="mt-6 pt-4 border-t flex flex-col sm:flex-row justify-end gap-3">
                        <a-button size="large" type="default" @click="save" :loading="form.processing && !showSignatureDialog">
                            <template #icon><save-outlined /></template>
                            Simpan Perubahan
                        </a-button>
                        <a-button size="large" type="primary" @click="submitForm" :loading="submitting" class="bg-green-600 hover:bg-green-500 border-green-600">
                            <template #icon><send-outlined /></template>
                            Tanda Tangani &amp; Kirim ke AM
                        </a-button>
                    </div>
                </a-card>
            </a-form>
        </div>

        <!-- Modal TTD -->
        <a-modal
            v-model:open="showSignatureDialog"
            title="Tanda Tangan Digital KC"
            :confirmLoading="submitting"
            :okButtonProps="{ disabled: submitting || (!signature && !signatureFile) }"
            okText="Tanda Tangani & Kirim ke AM"
            cancelText="Batal"
            @ok="confirmSubmit"
            centered
        >
            <p class="text-sm text-gray-500 mb-4">Tanda tangan digital wajib diisi sebelum Form Pengajuan dikirim ke Area Manager.</p>
            <div v-if="signature && !signatureFile" class="flex items-center gap-4 mb-4 p-3 rounded-xl border border-gray-200 bg-gray-50">
                <img :src="'/storage/' + signature.signature_image" alt="TTD KC" class="h-14 w-32 object-contain bg-white border border-gray-200 rounded-lg p-1" />
                <div>
                    <p class="text-sm text-blue-500 font-medium mb-1">Tanda tangan terdaftar</p>
                    <p class="text-xs text-gray-400">Tanda tangan ini akan digunakan untuk dokumen.</p>
                </div>
            </div>
            <a-form layout="vertical">
                <a-form-item :label="signature ? 'Ganti tanda tangan (opsional)' : 'Upload tanda tangan'" extra="Format PNG/JPG, maksimal 2MB.">
                    <input type="file" @change="selectSignature" accept="image/png,image/jpeg" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                </a-form-item>
            </a-form>
            <img v-if="signaturePreview" :src="signaturePreview" alt="Preview TTD" class="h-16 mt-4 object-contain border border-gray-200 rounded-lg p-1" />
            <p v-if="signatureError" class="text-red-500 text-sm mt-2">{{ signatureError }}</p>
        </a-modal>
    </AuthenticatedLayout>
</template>
