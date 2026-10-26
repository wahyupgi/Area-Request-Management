<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { useSweetAlert } from '@/composables/useSweetAlert';
import { preventNonDigitPaste, restrictToDigits } from '@/composables/numericInput';
import { SaveOutlined, SendOutlined, LeftOutlined } from '@ant-design/icons-vue';

const props = defineProps({
    branches:  { type: Array, default: () => [] },
    templates: { type: Array, default: () => [] },
});

const page = usePage();
const { success } = useSweetAlert();

const attachmentError = ref('');
const allowedAttachmentExtensions = '.docx,.pdf';

const availableTemplates = computed(() => {
    return (props.templates || []).map(t => ({
        value: t.name,
        label: t.name,
        templateData: t,
    }));
});

const formatRupiahAmount = (value) => {
    const digits = String(value ?? '').replace(/\D/g, '');
    return digits ? `Rp ${digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.')}` : '';
};

const requestAmountModel = (field) => computed({
    get: () => formatRupiahAmount(form.meta.request_data[field]),
    set: (value) => {
        const digits = String(value ?? '').replace(/\D/g, '');
        form.meta.request_data[field] = digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    },
});

const loanAmount = requestAmountModel('loan_amount');
const salaryAfterApproval = requestAmountModel('salary_after_approval');
const minimumSalary = requestAmountModel('minimum_salary');

const form = useForm({
    branch_id:   undefined,
    title:       '',
    template:    undefined,
    meta: {
        request_data: {},
    },
    attachments: [],
});

const attachmentValidationError = computed(() =>
    Object.entries(form.errors).find(([key]) => key === 'attachments' || key.startsWith('attachments.'))?.[1] || ''
);

watch(() => form.template, (templateName) => {
    const selected = availableTemplates.value.find(item => item.value === templateName);
    if (!selected) return;

    const t = selected.templateData;
    form.title = t.name;

    const requestData = {};
    (t.field_schema || []).forEach(field => {
        if (field.key === 'full_name') {
            requestData[field.key] = page.props.auth.user?.name || '';
        } else {
            requestData[field.key] = '';
        }
    });

    if (t.name === 'Form Permohonan Pinjaman (FPP)') {
        requestData.salary_deduction = 'Bersedia';
        requestData.loan_type = 'pinjaman_uang';
    } else if (t.name === 'Form Ijin Tidak Masuk Kerja (FITMK)') {
        requestData.leave_type = 'cuti_tahunan';
        requestData.substitute_position = '';
    }

    form.meta.request_data = requestData;
});

const selectAttachment = (event) => {
    attachmentError.value = '';
    const selectedFiles = Array.from(event.target.files || []);
    const allFiles = [...form.attachments, ...selectedFiles];

    if (allFiles.length > 3) {
        attachmentError.value = 'Maksimal 3 lampiran untuk setiap dokumen.';
    } else if (selectedFiles.some(file => file.size > 10 * 1024 * 1024)) {
        attachmentError.value = 'Ukuran setiap file maksimal 10 MB.';
    } else {
        form.attachments = allFiles;
    }

    event.target.value = '';
};

const removeAttachment = (index) => {
    form.attachments.splice(index, 1);
    attachmentError.value = '';
};

const resetOtherLeaveType = () => {
    form.meta.request_data.leave_type = 'cuti_tahunan';
    form.meta.request_data.leave_type_other = '';
};

const fpFieldErrors = ref({});

const clearFpError = (key) => {
    if (fpFieldErrors.value[key]) {
        const updated = { ...fpFieldErrors.value };
        delete updated[key];
        fpFieldErrors.value = updated;
    }
};

const validateFpForm = () => {
    const errors = {};
    const rd = form.meta.request_data;

    if (!form.branch_id) {
        form.setError('branch_id', 'Silakan pilih cabang terlebih dahulu.');
        return false;
    }
    if (!form.template) {
        form.setError('template', 'Silakan pilih jenis form pengajuan.');
        return false;
    }

    if (form.template === 'Form Permohonan Pinjaman (FPP)') {
        if (!rd.full_name?.trim()) errors['full_name'] = 'Nama Lengkap wajib diisi';
        if (!rd.position?.trim()) errors['position'] = 'Jabatan wajib diisi';
        if (!rd.work_location?.trim()) errors['work_location'] = 'Cabang/Lokasi Kerja wajib diisi';
        if (!rd.employment_date?.trim()) errors['employment_date'] = 'Tanggal Masuk Kerja wajib diisi';
        const loanVal = String(rd.loan_amount ?? '').replace(/\D/g, '');
        if (!loanVal) errors['loan_amount'] = 'Jumlah Pinjaman wajib diisi';
        if (!rd.repayment_months) errors['repayment_months'] = 'Lama Pengembalian wajib diisi';
        if (!rd.reason?.trim()) errors['reason'] = 'Alasan Pinjaman wajib diisi';
    } else if (form.template === 'Form Ijin Tidak Masuk Kerja (FITMK)') {
        if (!rd.full_name?.trim()) errors['full_name'] = 'Nama wajib diisi';
        if (!rd.start_date?.trim()) errors['start_date'] = 'Tanggal Ijin wajib diisi';
        if (!rd.duration) errors['duration'] = 'Durasi wajib diisi';
        if (!rd.reason?.trim()) errors['reason'] = 'Alasan wajib diisi';
    }

    fpFieldErrors.value = errors;
    return Object.keys(errors).length === 0;
};

const saving = ref('');

const persist = (sendAfterSave) => {
    form.clearErrors();
    if (!validateFpForm()) {
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return;
    }
    saving.value = sendAfterSave ? 'send' : 'draft';
    form.transform(data => ({
        title: data.title || data.template,
        branch_id: data.branch_id,
        template: data.template,
        meta: { request_data: data.meta.request_data },
        submit: false,
        submit_after_save: sendAfterSave,
        attachments: data.attachments,
    })).post(route('form-pengajuan.store'), {
        forceFormData: true,
        onFinish: () => { saving.value = ''; },
    });
};

const submit = () => persist(false);
const submitAndSend = () => persist(true);
</script>

<template>
    <Head title="Buat Form Pengajuan Baru" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-2.5">
                <Link :href="route('form-pengajuan.index')" class="text-gray-400 hover:text-gray-800 dark:hover:text-white transition-colors p-1 -ml-1 flex items-center">
                    <left-outlined class="text-base" />
                </Link>
                <h1 class="text-xl font-bold mb-0">Buat Form Pengajuan Baru</h1>
            </div>
        </template>

        <a-form layout="vertical" @finish="submit" class="memo-create-page">
            <!-- Pilihan Cabang & Template -->
            <a-card :bordered="false" class="mb-6 rounded-lg shadow-sm">
                <h2 class="text-lg font-semibold mb-2">Pilih Cabang dan Jenis Form Pengajuan</h2>
                <a-form-item
                    label="Cabang"
                    :validateStatus="form.errors.branch_id ? 'error' : ''"
                    :help="form.errors.branch_id"
                >
                    <a-select
                        v-model:value="form.branch_id"
                        placeholder="Pilih cabang"
                        :options="branches.map(b => ({ value: b.id, label: b.area?.name ? `${b.name} — ${b.area.name}` : b.name }))"
                        size="large"
                    />
                </a-form-item>
                <a-form-item
                    label="Jenis Form Pengajuan"
                    :validateStatus="form.errors.template ? 'error' : ''"
                    :help="form.errors.template"
                    class="mb-0"
                >
                    <a-select
                        v-model:value="form.template"
                        placeholder="Pilih template form pengajuan"
                        :options="availableTemplates"
                        size="large"
                    />
                </a-form-item>
            </a-card>

            <a-row :gutter="24" v-if="form.template">
                <!-- Sisi Kiri: Informasi Judul & Lampiran -->
                <a-col :xs="24" :lg="10" class="mb-6">
                    <div class="flex flex-col gap-6">
                        <a-card :bordered="false" class="rounded-lg shadow-sm">
                            <h2 class="text-sm font-semibold mb-1">Informasi Form Pengajuan</h2>
                            <p class="text-xs text-gray-500 mb-4">Judul dokumen yang tampil pada rekap dan cetak.</p>

                            <a-form-item
                                label="Judul Dokumen"
                                class="mb-0"
                                :validateStatus="form.errors.title ? 'error' : ''"
                                :help="form.errors.title"
                            >
                                <a-input v-model:value="form.title" placeholder="Masukkan judul dokumen" size="large" />
                            </a-form-item>
                        </a-card>

                        <!-- Lampiran Pendukung -->
                        <a-card :bordered="false" class="rounded-lg shadow-sm">
                            <h2 class="text-sm font-semibold mb-1">Lampiran Pendukung</h2>
                            <p class="text-xs text-gray-400 mb-3">
                                Unggah file DOCX atau PDF. Maksimal 3 file, 10 MB per file.
                            </p>
                            <input
                                type="file"
                                @change="selectAttachment"
                                multiple
                                :accept="allowedAttachmentExtensions"
                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                            />
                            <a-alert v-if="attachmentError" class="mt-3" type="error" show-icon :message="attachmentError" />
                            <a-alert v-if="attachmentValidationError" class="mt-3" type="error" show-icon :message="attachmentValidationError" />
                            <a-list v-if="form.attachments.length" class="mt-3" size="small" :data-source="form.attachments">
                                <template #renderItem="{ item, index }">
                                    <a-list-item>
                                        <span class="truncate">{{ item.name }}</span>
                                        <template #actions>
                                            <a-button type="link" danger size="small" @click="removeAttachment(index)">Hapus</a-button>
                                        </template>
                                    </a-list-item>
                                </template>
                            </a-list>
                        </a-card>
                    </div>
                </a-col>

                <!-- Sisi Kanan: Form Isian -->
                <a-col :xs="24" :lg="14">
                    <div class="flex flex-col gap-6">
                        <a-card :bordered="false" class="rounded-lg shadow-sm">
                            <h2 class="text-sm font-semibold mb-4">Isi Form Pengajuan</h2>

                            <!-- Template FPP -->
                            <template v-if="form.template === 'Form Permohonan Pinjaman (FPP)'">
                                <a-alert
                                    class="mb-4"
                                    type="info"
                                    show-icon
                                    message="Form Permohonan Pinjaman (FPP)"
                                    description="Lengkapi data pemohon dan rincian pinjaman. Lampirkan bukti pendukung bila diperlukan."
                                />
                                <a-row :gutter="12">
                                    <a-col :xs="24" :md="12">
                                        <a-form-item label="Nama Lengkap" :validateStatus="fpFieldErrors.full_name || form.errors['meta.request_data.full_name'] ? 'error' : ''" :help="fpFieldErrors.full_name || form.errors['meta.request_data.full_name']">
                                            <a-input v-model:value="form.meta.request_data.full_name" @change="clearFpError('full_name')" />
                                        </a-form-item>
                                    </a-col>
                                    <a-col :xs="24" :md="12">
                                        <a-form-item label="Jabatan" :validateStatus="fpFieldErrors.position || form.errors['meta.request_data.position'] ? 'error' : ''" :help="fpFieldErrors.position || form.errors['meta.request_data.position']">
                                            <a-input v-model:value="form.meta.request_data.position" @change="clearFpError('position')" />
                                        </a-form-item>
                                    </a-col>
                                    <a-col :xs="24" :md="12">
                                        <a-form-item label="Cabang / Lokasi Kerja" :validateStatus="fpFieldErrors.work_location || form.errors['meta.request_data.work_location'] ? 'error' : ''" :help="fpFieldErrors.work_location || form.errors['meta.request_data.work_location']">
                                            <a-input v-model:value="form.meta.request_data.work_location" @change="clearFpError('work_location')" />
                                        </a-form-item>
                                    </a-col>
                                    <a-col :xs="24" :md="12">
                                        <a-form-item label="Tanggal Masuk Kerja" :validateStatus="fpFieldErrors.employment_date || form.errors['meta.request_data.employment_date'] ? 'error' : ''" :help="fpFieldErrors.employment_date || form.errors['meta.request_data.employment_date']">
                                            <a-input v-model:value="form.meta.request_data.employment_date" type="date" @change="clearFpError('employment_date')" />
                                        </a-form-item>
                                    </a-col>
                                    <a-col :xs="24" :md="8">
                                        <a-form-item label="Terlambat (bulan)">
                                            <a-input-number v-model:value="form.meta.request_data.late_months" :min="0" @keydown="restrictToDigits" @paste="preventNonDigitPaste" class="w-full" />
                                        </a-form-item>
                                    </a-col>
                                    <a-col :xs="24" :md="8">
                                        <a-form-item label="Tidak Masuk (bulan)">
                                            <a-input-number v-model:value="form.meta.request_data.absence_months" :min="0" @keydown="restrictToDigits" @paste="preventNonDigitPaste" class="w-full" />
                                        </a-form-item>
                                    </a-col>
                                    <a-col :xs="24" :md="8">
                                        <a-form-item label="Permohonan ke-">
                                            <a-input v-model:value="form.meta.request_data.request_number" @keydown="restrictToDigits" @paste="preventNonDigitPaste" />
                                        </a-form-item>
                                    </a-col>
                                    <a-col :xs="24" :md="12">
                                        <a-form-item label="Gaji setelah disetujui">
                                            <a-input v-model:value="salaryAfterApproval" placeholder="Contoh: 6.000.000" />
                                        </a-form-item>
                                    </a-col>
                                    <a-col :xs="24" :md="12">
                                        <a-form-item label="Gaji minimal">
                                            <a-input v-model:value="minimumSalary" placeholder="Contoh: 4.500.000" />
                                        </a-form-item>
                                    </a-col>
                                    <a-col :xs="24" :md="12">
                                        <a-form-item label="Jumlah pinjaman yang diajukan" :validateStatus="fpFieldErrors.loan_amount || form.errors['meta.request_data.loan_amount'] ? 'error' : ''" :help="fpFieldErrors.loan_amount || form.errors['meta.request_data.loan_amount']">
                                            <a-input v-model:value="loanAmount" placeholder="Contoh: 5.000.000" @change="clearFpError('loan_amount')" />
                                        </a-form-item>
                                    </a-col>
                                    <a-col :xs="24" :md="12">
                                        <a-form-item label="Lama pengembalian (bulan)" :validateStatus="fpFieldErrors.repayment_months || form.errors['meta.request_data.repayment_months'] ? 'error' : ''" :help="fpFieldErrors.repayment_months || form.errors['meta.request_data.repayment_months']">
                                            <a-input-number v-model:value="form.meta.request_data.repayment_months" :min="1" @keydown="restrictToDigits" @paste="preventNonDigitPaste" @change="clearFpError('repayment_months')" class="w-full" />
                                        </a-form-item>
                                    </a-col>
                                    <a-col :xs="24" :md="12">
                                        <a-form-item label="Bersedia potong gaji setiap bulan">
                                            <a-radio-group v-model:value="form.meta.request_data.salary_deduction">
                                                <a-radio value="Bersedia">Bersedia</a-radio>
                                                <a-radio value="Tidak bersedia">Tidak bersedia</a-radio>
                                            </a-radio-group>
                                        </a-form-item>
                                    </a-col>
                                    <a-col :xs="24" :md="12">
                                        <a-form-item label="Jenis Pinjaman">
                                            <a-radio-group v-model:value="form.meta.request_data.loan_type">
                                                <a-radio value="pinjaman_uang">Pinjaman Uang</a-radio>
                                                <a-radio value="pembelian_barang">Pembelian Barang</a-radio>
                                            </a-radio-group>
                                        </a-form-item>
                                    </a-col>
                                    <a-col :span="24">
                                        <a-form-item label="Alasan Pinjaman" :validateStatus="fpFieldErrors.reason || form.errors['meta.request_data.reason'] ? 'error' : ''" :help="fpFieldErrors.reason || form.errors['meta.request_data.reason']">
                                            <a-textarea v-model:value="form.meta.request_data.reason" :rows="4" @change="clearFpError('reason')" />
                                        </a-form-item>
                                    </a-col>
                                </a-row>
                            </template>

                            <!-- Template FITMK -->
                            <template v-else-if="form.template === 'Form Ijin Tidak Masuk Kerja (FITMK)'">
                                <a-alert
                                    class="mb-4"
                                    type="info"
                                    show-icon
                                    message="Form Ijin Tidak Masuk Kerja (FITMK)"
                                    description="Isi detail ketidakhadiran dan serah terima pekerjaan."
                                />
                                <a-row :gutter="12">
                                    <a-col :xs="24" :md="12">
                                        <a-form-item label="Nama" :validateStatus="fpFieldErrors.full_name || form.errors['meta.request_data.full_name'] ? 'error' : ''" :help="fpFieldErrors.full_name || form.errors['meta.request_data.full_name']">
                                            <a-input v-model:value="form.meta.request_data.full_name" @change="clearFpError('full_name')" />
                                        </a-form-item>
                                    </a-col>
                                    <a-col :xs="24" :md="12">
                                        <a-form-item label="Detail ijin tidak masuk kerja" :validateStatus="form.errors['meta.request_data.leave_type_other'] ? 'error' : ''" :help="form.errors['meta.request_data.leave_type_other']">
                                            <a-input v-if="form.meta.request_data.leave_type === 'lainnya'" v-model:value="form.meta.request_data.leave_type_other" placeholder="Tuliskan jenis ijin">
                                                <template #addonAfter><a-button type="link" size="small" @click="resetOtherLeaveType">Pilih jenis lain</a-button></template>
                                            </a-input>
                                            <a-select v-else v-model:value="form.meta.request_data.leave_type">
                                                <a-select-option value="cuti_tahunan">Cuti Tahunan</a-select-option>
                                                <a-select-option value="ijin">Ijin (Belum Ada Hak Cuti)</a-select-option>
                                                <a-select-option value="cuti_melahirkan">Cuti Melahirkan</a-select-option>
                                                <a-select-option value="cuti_khusus">Cuti Khusus (Menikah/Kematian/Hari Raya)</a-select-option>
                                                <a-select-option value="sakit">Sakit (Melampirkan Surat Dokter)</a-select-option>
                                                <a-select-option value="lainnya">Lainnya</a-select-option>
                                            </a-select>
                                        </a-form-item>
                                    </a-col>
                                    <a-col :xs="24" :md="12">
                                        <a-form-item label="Tanggal ijin tidak masuk kerja" :validateStatus="fpFieldErrors.start_date || form.errors['meta.request_data.start_date'] ? 'error' : ''" :help="fpFieldErrors.start_date || form.errors['meta.request_data.start_date']">
                                            <a-input v-model:value="form.meta.request_data.start_date" type="date" @change="clearFpError('start_date')" />
                                        </a-form-item>
                                    </a-col>
                                    <a-col :xs="24" :md="12">
                                        <a-form-item label="Durasi (hari)" :validateStatus="fpFieldErrors.duration || form.errors['meta.request_data.duration'] ? 'error' : ''" :help="fpFieldErrors.duration || form.errors['meta.request_data.duration']">
                                            <a-input-number v-model:value="form.meta.request_data.duration" :min="1" @keydown="restrictToDigits" @paste="preventNonDigitPaste" @change="clearFpError('duration')" class="w-full" />
                                        </a-form-item>
                                    </a-col>
                                    <a-col :span="24">
                                        <a-form-item label="Alasan ijin tidak masuk kerja" :validateStatus="fpFieldErrors.reason || form.errors['meta.request_data.reason'] ? 'error' : ''" :help="fpFieldErrors.reason || form.errors['meta.request_data.reason']">
                                            <a-textarea v-model:value="form.meta.request_data.reason" :rows="3" @change="clearFpError('reason')" />
                                        </a-form-item>
                                    </a-col>
                                    <a-col :xs="24" :md="12">
                                        <a-form-item label="Pekerjaan selama tidak masuk dilimpahkan ke">
                                            <a-input v-model:value="form.meta.request_data.handover" />
                                        </a-form-item>
                                    </a-col>
                                    <a-col :xs="24" :md="12">
                                        <a-form-item label="Jabatan pengganti">
                                            <a-input v-model:value="form.meta.request_data.substitute_position" placeholder="Contoh: Kepala Unit" />
                                        </a-form-item>
                                    </a-col>
                                    <a-col :span="24">
                                        <a-form-item label="Kontak selama tidak masuk kerja (Telepon / HP)">
                                            <a-input v-model:value="form.meta.request_data.phone" @keydown="restrictToDigits" @paste="preventNonDigitPaste" />
                                        </a-form-item>
                                    </a-col>
                                </a-row>
                            </template>
                        </a-card>

                        <!-- Action Button -->
                        <div class="pt-2 flex justify-end">
                            <a-button size="large" class="mr-3" @click="submit" :loading="form.processing && saving === 'draft'">
                                <template #icon><save-outlined /></template>
                                Simpan Draft
                            </a-button>
                            <a-button size="large" type="primary" @click="submitAndSend" :loading="form.processing && saving === 'send'" class="bg-green-600 hover:bg-green-500 border-green-600">
                                <template #icon><send-outlined /></template>
                                Kirim ke AM
                            </a-button>
                        </div>
                    </div>
                </a-col>
            </a-row>
        </a-form>
    </AuthenticatedLayout>
</template>
