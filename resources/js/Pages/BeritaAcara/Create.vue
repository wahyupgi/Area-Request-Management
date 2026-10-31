<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { useSweetAlert } from '@/composables/useSweetAlert';
import { preventNonDigitPaste, restrictToDigits } from '@/composables/numericInput';
import { 
    SaveOutlined, 
    SendOutlined, 
    PlusOutlined, 
    DeleteOutlined, 
    LeftOutlined, 
    PaperClipOutlined,
    UploadOutlined,
    FilePdfOutlined,
    FileWordOutlined,
    FileOutlined,
    CloseCircleOutlined
} from '@ant-design/icons-vue';

const props = defineProps({
    branches: { type: Array, default: () => [] },
    templates: { type: Array, default: () => [] },
    signature: { type: Object, default: null },
});

const page = usePage();
const { success, error: showError } = useSweetAlert();
const fileInputRef = ref(null);
const supportFileInputRef = ref(null);
const attachmentError = ref('');
const showSignatureDialog = ref(false);
const signatureFile = ref(null);
const signaturePreview = ref(null);
const signatureError = ref('');
const savingSignature = ref(false);
const attachmentValidationError = computed(() => Object.entries(form.errors)
    .find(([key]) => key === 'attachments' || key.startsWith('attachments.'))?.[1] || '');
const allowedAttachmentExtensions = '.pdf,application/pdf';

const triggerFileInput = () => fileInputRef.value?.click();
const triggerSupportFileInput = () => supportFileInputRef.value?.click();

const isPdf = (fileName) => /\.pdf$/i.test(fileName || '');
const isWord = (fileName) => /\.(docx?|doc)$/i.test(fileName || '');

const formatFileSize = (bytes) => {
    if (!bytes && bytes !== 0) return '';
    if (bytes < 1024) return bytes + ' B';
    const kb = bytes / 1024;
    if (kb < 1024) return kb.toFixed(1) + ' KB';
    return (kb / (1024 * 1024)).toFixed(1) + ' MB';
};
const availableTemplates = computed(() => {
    return (props.templates || []).map(t => ({ value: t.name, label: t.name, templateData: t }));
});



const form = useForm({
    branch_id: undefined,
    title: '',
    meta: {
        template: undefined,
        direktorat: 'Operasional',
        divisi: 'Support',
        perihal: 'Berita Acara Permohonan',
        lampiran: '-',
        kepada_nama: '',
        kepada_jabatan: '',
        penyetuju_akhir: '',
        data: { rows: [], kronologi: '' },
    },
    pengantar: 'Sehubungan dengan adanya berita acara ini, saya ingin memberitahukan bahwa...',
    rincian_data: [
        { label: 'Nama', value: '' },
        { label: 'NIK', value: '' },
        { label: 'Cabang', value: '' },
        { label: 'Jabatan', value: '' }
    ],
    keterangan_tambahan: 'Adapun alasan mengajukan...',
    penutup: 'Demikian berita acara ini dibuat agar dapat dipergunakan sebagai mana mestinya. Terima kasih atas perhatiannya dan kerjasamanya.',
    attachments: [],
    submit_after_save: false,
    signature_image: null,
});

watch(() => form.branch_id, (val) => {
    if (val && form.errors.branch_id) {
        form.clearErrors('branch_id');
    }
});

watch(() => form.meta.template, (templateName) => {
    if (templateName && form.errors['meta.template']) {
        form.clearErrors('meta.template');
    }
    const selected = availableTemplates.value.find((item) => item.value === templateName);
    if (!selected) return;

    const t = selected.templateData;
    form.title = t.name;
    form.meta.perihal = t.document_defaults?.perihal || t.name;
    form.meta.data = {
        rows: [],
        kronologi: '',
    };

    form.meta.direktorat = t.document_defaults?.direktorat || '';
    form.meta.divisi = t.document_defaults?.divisi || '';
    form.meta.kepada_nama = t.document_defaults?.kepada || '';
    form.meta.kepada_jabatan = t.document_defaults?.kepada_jabatan || '';
    form.meta.lampiran = t.document_defaults?.lampiran || '';
    form.pengantar = t.document_defaults?.pengantar || '';
    form.meta.penyetuju_akhir = t.document_defaults?.penyetuju_akhir || '';
    
    if (t.name === 'Permintaan Revisi Absensi') {
        form.rincian_data = [];
        form.meta.data.rows = [{ nama: '', nik: '', tanggal: '', absensi_in: '', absensi_out: '', ket: 'Revisi Absen' }];
    } else if (t.name === 'Penghapusan Barang Sitaan') {
        form.rincian_data = [];
        form.meta.data.rows = [{ cabang: '', nama_nasabah: '', no_faktur: '', barang: '', nominal_pinjaman: '' }];
    } else if (t.name === 'Berita Acara Lainnya') {
        form.rincian_data = [];
        form.meta.data.rows = [];
    } else {
        form.rincian_data = (t.field_schema || []).map(f => ({ label: f.label, value: '' }));
        form.meta.data.rows = [];
    }

    form.keterangan_tambahan = t.document_defaults?.keterangan_tambahan || '';
    form.penutup = t.document_defaults?.penutup || '';
});

const selectAttachment = (event) => {
    attachmentError.value = '';
    const selectedFiles = Array.from(event.target.files || []);
    if (selectedFiles.some((file) => !isPdf(file.name) && file.type !== 'application/pdf')) {
        attachmentError.value = 'Format file lampiran harus berupa PDF (.pdf).';
        event.target.value = '';
        return;
    }
    const allFiles = [...form.attachments, ...selectedFiles];

    if (allFiles.length > 3) {
        attachmentError.value = 'Maksimal 3 lampiran untuk setiap dokumen.';
    } else if (selectedFiles.some((file) => file.size > 10 * 1024 * 1024)) {
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

const addRincian = () => {
    form.rincian_data.push({ label: '', value: '' });
};

const removeRincian = (index) => {
    form.rincian_data.splice(index, 1);
};



const addTemplateRow = () => {
    const row = form.meta.template === 'Permintaan Revisi Absensi'
        ? { nama: '', nik: '', tanggal: '', absensi_in: '', absensi_out: '', ket: 'Revisi Absen' }
        : form.meta.template === 'Penghapusan Barang Sitaan'
            ? { cabang: '', nama_nasabah: '', no_faktur: '', barang: '', nominal_pinjaman: '' }
            : Object.fromEntries((form.meta.data.table_columns || []).map((column) => [column.key, '']));
    form.meta.data.rows.push(row);
};

const removeTemplateRow = (index) => {
    form.meta.data.rows.splice(index, 1);
};

const formatNominalInput = (value) => {
    if (value === undefined || value === null || value === '') return '';
    const digits = String(value).replace(/\D/g, '');
    if (!digits) return '';
    return `Rp ${Number(digits).toLocaleString('id-ID')}`;
};

const parseNominalInput = (value) => {
    const digits = String(value ?? '').replace(/\D/g, '');
    return digits ? Number(digits) : null;
};

const restrictDetailDigits = (event, label) => {
    if (/nik|nomor telepon|no\.?\s*tlp|biaya kost/i.test(label || '')) {
        restrictToDigits(event);
    }
};

const preventNonDigitDetailPaste = (event, label) => {
    if (/nik|nomor telepon|no\.?\s*tlp|biaya kost/i.test(label || '')) {
        preventNonDigitPaste(event);
    }
};

const formatRupiahAmount = (value) => {
    const digits = String(value ?? '').replace(/\D/g, '');
    return digits ? `Rp ${digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.')}` : '';
};
const isRupiahDetail = (label) => /biaya kost|nominal|rupiah|pinjaman|harga/i.test(label || '');
const updateDetailValue = (item, value) => {
    item.value = isRupiahDetail(item.label)
        ? formatRupiahAmount(value)
        : value;
};
const formattedDetailValue = (item) => isRupiahDetail(item.label)
    ? formatRupiahAmount(item.value)
    : item.value;

const baFieldErrors = ref({});

const clearBaError = (key) => {
    if (baFieldErrors.value[key]) {
        const updated = { ...baFieldErrors.value };
        delete updated[key];
        baFieldErrors.value = updated;
    }
};

const validateBaForm = () => {
    attachmentError.value = '';
    form.clearErrors();
    let hasError = false;

    if (!form.branch_id) {
        form.setError('branch_id', 'Silakan pilih cabang terlebih dahulu.');
        hasError = true;
    }

    if (!form.meta.template) {
        form.setError('meta.template', 'Silakan pilih jenis Berita Acara terlebih dahulu.');
        hasError = true;
    }

    if (form.meta.template === 'Berita Acara Lainnya') {
        if (!form.attachments || form.attachments.length === 0) {
            attachmentError.value = 'Lampiran dokumen Berita Acara (PDF) wajib diunggah.';
            hasError = true;
        }
        if (!form.title) {
            form.title = 'Berita Acara Lainnya';
        }
    } else if (form.meta.template) {
        if (!form.title || !form.title.trim()) {
            form.setError('title', 'Judul Berita Acara wajib diisi.');
            hasError = true;
        }

        // Validasi baris tabel
        const rowErrors = {};
        if (form.meta.template === 'Permintaan Revisi Absensi') {
            form.meta.data.rows.forEach((row, rIdx) => {
                if (!row.nama?.trim()) rowErrors[`row.${rIdx}.nama`] = 'Nama wajib diisi';
                if (!row.nik?.trim()) rowErrors[`row.${rIdx}.nik`] = 'NIK wajib diisi';
                if (!row.tanggal?.trim()) rowErrors[`row.${rIdx}.tanggal`] = 'Tanggal wajib diisi';
                if (!row.absensi_in?.trim()) rowErrors[`row.${rIdx}.absensi_in`] = 'Absensi IN wajib diisi';
                if (!row.absensi_out?.trim()) rowErrors[`row.${rIdx}.absensi_out`] = 'Absensi OUT wajib diisi';
            });
        } else if (form.meta.template === 'Penghapusan Barang Sitaan') {
            form.meta.data.rows.forEach((row, rIdx) => {
                if (!row.nama_nasabah?.trim()) rowErrors[`row.${rIdx}.nama_nasabah`] = 'Nama Nasabah wajib diisi';
                if (!row.no_faktur?.trim()) rowErrors[`row.${rIdx}.no_faktur`] = 'No. Faktur wajib diisi';
                if (!row.barang?.trim()) rowErrors[`row.${rIdx}.barang`] = 'Barang wajib diisi';
                const nom = row.nominal_pinjaman;
                if (nom === undefined || nom === null || String(nom).trim() === '') rowErrors[`row.${rIdx}.nominal_pinjaman`] = 'Nominal Pinjaman wajib diisi';
            });
        } else {
            // template lain dengan rincian_data
            form.rincian_data.forEach((item, idx) => {
                if (!item.value?.trim()) rowErrors[`rincian.${idx}`] = `${item.label || 'Field ini'} wajib diisi`;
            });
        }

        if (Object.keys(rowErrors).length > 0) {
            baFieldErrors.value = { ...baFieldErrors.value, ...rowErrors };
            hasError = true;
        }
    }

    if (hasError) {
        showError('Data Belum Lengkap', 'Mohon lengkapi semua field yang wajib diisi sebelum mengirim.');
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return false;
    }

    return true;
};

const submit = () => {
    if (!validateBaForm()) return;
    form.submit_after_save = false;
    form.post(route('berita-acara.store'), {
        forceFormData: true,
    });
};

const sendWithForm = () => {
    form.submit_after_save = true;
    form.post(route('berita-acara.store'), {
        forceFormData: true,
        onSuccess: () => { showSignatureDialog.value = false; },
        onFinish: () => { savingSignature.value = false; },
    });
};

const submitAndSend = () => {
    if (!validateBaForm()) return;

    // TTD belum ada: minta TTD dulu lewat dialog, tanpa pindah halaman.
    if (!props.signature?.signature_image) {
        showSignatureDialog.value = true;
        return;
    }

    form.signature_image = null;
    sendWithForm();
};

const selectSignature = (event) => {
    const file = event.target.files?.[0];
    signatureError.value = '';
    signatureFile.value = file || null;
    signaturePreview.value = null;
    if (!file) return;
    if (!['image/png', 'image/jpeg'].includes(file.type)) {
        signatureError.value = 'Tanda tangan harus berupa file PNG atau JPG.';
        signatureFile.value = null; event.target.value = ''; return;
    }
    if (file.size > 2 * 1024 * 1024) {
        signatureError.value = 'Ukuran tanda tangan maksimal 2MB.';
        signatureFile.value = null; event.target.value = ''; return;
    }
    const reader = new FileReader();
    reader.onload = (e) => { signaturePreview.value = e.target.result; };
    reader.readAsDataURL(file);
};

const confirmSignature = () => {
    if (!signatureFile.value) return;
    savingSignature.value = true;
    form.signature_image = signatureFile.value;
    sendWithForm();
};



</script>


<template>
    <Head title="Buat Berita Acara Baru" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-2.5">
                <Link :href="route('berita-acara.index')" class="text-gray-400 hover:text-gray-800 dark:hover:text-white transition-colors p-1 -ml-1 flex items-center">
                    <left-outlined class="text-base" />
                </Link>
                <h1 class="text-xl font-bold mb-0">Buat Berita Acara Baru</h1>
            </div>
        </template>

        <a-form layout="vertical" @finish="submit" class="memo-create-page">
            <a-card :bordered="false" class="mb-6 rounded-lg shadow-sm">
                <h2 class="text-lg font-semibold mb-2">Pilih Cabang dan Jenis Berita Acara</h2>
                <a-form-item
                    label="Cabang"
                    :validateStatus="form.errors.branch_id ? 'error' : ''"
                    :help="form.errors.branch_id"
                >
                    <a-select
                        v-model:value="form.branch_id"
                        placeholder="Pilih cabang"
                        :options="branches.map(branch => ({ value: branch.id, label: branch.area?.name ? `${branch.name} — ${branch.area.name}` : branch.name }))"
                        size="large"
                    />
                </a-form-item>
                <a-form-item
                    label="Jenis Berita Acara"
                    :validateStatus="form.errors['meta.template'] ? 'error' : ''"
                    :help="form.errors['meta.template']"
                    class="mb-0"
                >
                    <a-select
                        v-model:value="form.meta.template"
                        placeholder="Pilih template"
                        :options="availableTemplates"
                        size="large"
                    />
                </a-form-item>
            </a-card>

            <!-- Khusus Berita Acara Lainnya: Hanya 1 Field Upload File PDF & Tombol Kirim -->
            <a-card v-if="form.meta.template === 'Berita Acara Lainnya'" :bordered="false" class="mb-6 rounded-lg shadow-sm">
                <h2 class="text-base font-semibold mb-1">Upload Berkas Berita Acara</h2>
                <p class="text-xs text-gray-500 mb-4">Unggah dokumen Berita Acara Anda dalam format file PDF (maksimal 10 MB).</p>

                <a-form-item
                    label="File Dokumen (PDF)"
                    :validateStatus="attachmentError || attachmentValidationError ? 'error' : ''"
                    :help="attachmentError || attachmentValidationError"
                    class="mb-4"
                >
                    <input
                        ref="fileInputRef"
                        type="file"
                        @change="selectAttachment"
                        multiple
                        :accept="allowedAttachmentExtensions"
                        style="display: none !important;"
                    />

                    <div class="flex flex-wrap items-center gap-3 p-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50/70 dark:bg-slate-900/40">
                        <a-button 
                            type="primary" 
                            ghost
                            class="!flex !items-center !gap-1.5"
                            :disabled="form.attachments.length >= 3"
                            @click="triggerFileInput"
                        >
                            <template #icon><upload-outlined /></template>
                            <span>Choose Files</span>
                        </a-button>

                        <span v-if="!form.attachments.length" class="text-sm text-gray-400">
                            No file chosen
                        </span>

                        <div v-else class="flex flex-wrap items-center gap-2">
                            <div 
                                v-for="(item, index) in form.attachments" 
                                :key="index"
                                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-md text-xs font-medium border bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 shadow-sm"
                            >
                                <file-pdf-outlined v-if="isPdf(item.name)" class="text-red-500 text-sm" />
                                <file-word-outlined v-else-if="isWord(item.name)" class="text-blue-500 text-sm" />
                                <file-outlined v-else class="text-gray-400 text-sm" />
                                
                                <span class="max-w-[260px] truncate" :title="item.name">{{ item.name }}</span>
                                <span class="text-gray-400 text-[11px]">({{ formatFileSize(item.size) }})</span>
                                
                                <button
                                    type="button"
                                    class="ml-1 text-gray-400 hover:text-red-500 transition-colors cursor-pointer"
                                    title="Hapus file"
                                    aria-label="Hapus file"
                                    @click.stop="removeAttachment(index)"
                                >
                                    <close-circle-outlined class="text-sm" />
                                </button>
                            </div>
                        </div>
                    </div>
                </a-form-item>

                <div class="mt-6 pt-4 border-t flex justify-end gap-3">
                    <a-button size="large" @click="submit" :loading="form.processing && !form.submit_after_save">
                        <template #icon><save-outlined /></template>
                        Simpan Draft
                    </a-button>
                    <a-button size="large" type="primary" @click="submitAndSend" :loading="form.processing && form.submit_after_save" class="bg-green-600 hover:bg-green-500 border-green-600">
                        <template #icon><send-outlined /></template>
                        Kirim ke AM
                    </a-button>
                </div>
            </a-card>

            <!-- Template BA lainnya (dengan informasi dokumen & isian) -->
            <a-row :gutter="24" v-else-if="form.meta.template">
                <a-col :xs="24" :lg="10" class="mb-6">
                    <div class="flex flex-col gap-6">
                        <a-card :bordered="false" class="rounded-lg shadow-sm">
                            <h2 class="text-sm font-semibold mb-1">Informasi Dokumen</h2>
                            <p class="text-xs text-gray-500 mb-4">Detail header yang tampil di dokumen cetak.</p>

                            <a-form-item label="Judul Berita Acara" class="mb-3"
                                :validateStatus="form.errors.title ? 'error' : ''"
                                :help="form.errors.title"
                            >
                                <a-input v-model:value="form.title" placeholder="Masukkan judul berita acara" size="large" />
                            </a-form-item>

                            <a-form-item label="Direktorat" class="mb-3">
                                <a-input v-model:value="form.meta.direktorat" placeholder="Contoh: Regional Branch Office" />
                            </a-form-item>

                            <a-form-item label="Divisi" class="mb-3">
                                <a-input v-model:value="form.meta.divisi" placeholder="Contoh: Branch Leader / HRD" />
                            </a-form-item>

                            <a-form-item label="Perihal" extra="Opsional, jika berbeda dari judul" class="mb-3">
                                <a-input v-model:value="form.meta.perihal" :placeholder="form.title || 'Mengikuti judul berita acara'" />
                            </a-form-item>

                            <a-form-item label="Kepada (Yth.)" class="mb-3">
                                <a-input v-model:value="form.meta.kepada_nama" placeholder="Nama penerima" />
                            </a-form-item>

                            <a-form-item label="Jabatan Penerima" class="mb-3">
                                <a-input v-model:value="form.meta.kepada_jabatan" placeholder="Contoh: Area Manager / SPV HC Payroll" />
                            </a-form-item>

                            <a-form-item label="Lampiran" extra="Keterangan lampiran yang tercetak pada dokumen." class="mb-3">
                                <a-input v-model:value="form.meta.lampiran" placeholder="Contoh: 1 Lembar, 3 Berkas, atau -" />
                            </a-form-item>

                        </a-card>

                        <!-- Lampiran Pendukung -->
                        <a-card :bordered="false" class="rounded-lg shadow-sm">
                            <h2 class="text-sm font-semibold mb-1">Lampiran Pendukung</h2>
                            <p class="text-xs text-gray-400 mb-3">
                                Unggah file PDF. Maksimal 3 file, 10 MB per file.
                            </p>
                            <input
                                ref="supportFileInputRef"
                                type="file"
                                @change="selectAttachment"
                                multiple
                                :accept="allowedAttachmentExtensions"
                                style="display: none !important;"
                            />

                            <div class="flex flex-wrap items-center gap-2 p-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50/70 dark:bg-slate-900/40">
                                <a-button 
                                    type="primary" 
                                    ghost
                                    size="small"
                                    class="!flex !items-center !gap-1.5"
                                    :disabled="form.attachments.length >= 3"
                                    @click="triggerSupportFileInput"
                                >
                                    <template #icon><upload-outlined /></template>
                                    <span>Choose Files</span>
                                </a-button>

                                <span v-if="!form.attachments.length" class="text-xs text-gray-400">
                                    No file chosen
                                </span>

                                <div v-else class="flex flex-wrap items-center gap-1.5">
                                    <div 
                                        v-for="(item, index) in form.attachments" 
                                        :key="index"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded text-xs font-medium border bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 shadow-sm"
                                    >
                                        <file-pdf-outlined v-if="isPdf(item.name)" class="text-red-500 text-xs" />
                                        <file-word-outlined v-else-if="isWord(item.name)" class="text-blue-500 text-xs" />
                                        <file-outlined v-else class="text-gray-400 text-xs" />
                                        
                                        <span class="max-w-[180px] truncate" :title="item.name">{{ item.name }}</span>
                                        <span class="text-gray-400 text-[10px]">({{ formatFileSize(item.size) }})</span>
                                        
                                        <button
                                            type="button"
                                            class="ml-0.5 text-gray-400 hover:text-red-500 transition-colors cursor-pointer"
                                            title="Hapus file"
                                            aria-label="Hapus file"
                                            @click.stop="removeAttachment(index)"
                                        >
                                            <close-circle-outlined class="text-xs" />
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <a-alert v-if="attachmentError" class="mt-3" type="error" show-icon :message="attachmentError" />
                            <a-alert v-if="attachmentValidationError" class="mt-3" type="error" show-icon :message="attachmentValidationError" />
                        </a-card>
                    </div>
                </a-col>

                <a-col :xs="24" :lg="14">
                    <div class="flex flex-col gap-6">
                        <a-card :bordered="false" class="rounded-lg shadow-sm">
                            <h2 class="text-sm font-semibold mb-4">Isi Berita Acara</h2>
                            <a-form-item label="Kalimat Pengantar (Sehubungan dengan...)" class="mb-4">
                                <a-textarea v-model:value="form.pengantar" :rows="3" />
                            </a-form-item>

                            <div v-if="!['Permintaan Revisi Absensi', 'Penghapusan Barang Sitaan'].includes(form.meta.template)" class="border border-gray-200 rounded p-4 mb-4">
                                <h3 class="font-semibold text-sm mb-3">Data Rincian</h3>
                                <template v-if="form.meta.template === 'template_1'">
                                    <div v-for="(label, index) in ['Cabang', 'Faktur', 'Tujuan', 'Nominal']" :key="label" class="flex gap-3 mb-2 items-center">
                                        <span class="w-[35%] flex-shrink-0">{{ label }}</span>
                                        <span class="text-gray-400">:</span>
                                        <a-input
                                            :value="formattedDetailValue(form.rincian_data[index])"
                                            placeholder="Isi Data"
                                            class="flex-1"
                                            @update:value="value => updateDetailValue(form.rincian_data[index], value)"
                                            @keydown="event => restrictDetailDigits(event, label)"
                                            @paste="event => preventNonDigitDetailPaste(event, label)"
                                        />
                                    </div>
                                </template>
                                <template v-else>
                                    <div v-for="(item, index) in form.rincian_data" :key="index" class="flex gap-3 mb-3 items-start">
                                        <a-input v-model:value="item.label" placeholder="Label (Misal: NIK)" style="width: 35%;" />
                                        <span class="text-gray-400 mt-1">:</span>
                                        <div class="flex flex-col flex-1">
                                            <a-input
                                                :value="formattedDetailValue(item)"
                                                placeholder="Isi Data"
                                                :status="baFieldErrors[`rincian.${index}`] ? 'error' : ''"
                                                @update:value="value => { updateDetailValue(item, value); clearBaError(`rincian.${index}`); }"
                                                @keydown="event => restrictDetailDigits(event, item.label)"
                                                @paste="event => preventNonDigitDetailPaste(event, item.label)"
                                            />
                                            <div v-if="baFieldErrors[`rincian.${index}`]" class="text-red-500 text-xs mt-1">{{ baFieldErrors[`rincian.${index}`] }}</div>
                                        </div>
                                        <a-button type="text" danger @click="removeRincian(index)" class="flex-shrink-0">
                                            <template #icon><delete-outlined /></template>
                                        </a-button>
                                    </div>
                                    <a-button type="dashed" block class="mt-2" @click="addRincian">
                                        <template #icon><plus-outlined /></template>
                                        Tambah Baris Data
                                    </a-button>
                                </template>
                            </div>

                            <div v-else class="border border-gray-200 rounded p-4 mb-4 overflow-x-auto">
                                <h3 class="font-semibold text-sm mb-3">{{ form.meta.template === 'Permintaan Revisi Absensi' ? 'Data Absensi' : 'Data Barang Sitaan' }}</h3>
                                <template>
                                <div v-for="(row, index) in form.meta.data.rows" :key="index" class="border border-gray-200 rounded p-3 mb-3">
                                    <div class="flex justify-between items-center mb-2">
                                        <strong class="text-xs">Baris {{ index + 1 }}</strong>
                                        <a-button type="text" danger @click="removeTemplateRow(index)">
                                            <template #icon><delete-outlined /></template>
                                        </a-button>
                                    </div>
                                    <template v-if="form.meta.template === 'Permintaan Revisi Absensi'">
                                        <a-row :gutter="8">
                                            <a-col :xs="24" :sm="12" :md="8">
                                                <a-input v-model:value="row.nama" placeholder="Nama" :status="baFieldErrors[`row.${index}.nama`] ? 'error' : ''" @change="clearBaError(`row.${index}.nama`)" class="mb-1" />
                                                <div v-if="baFieldErrors[`row.${index}.nama`]" class="text-red-500 text-xs mb-2">{{ baFieldErrors[`row.${index}.nama`] }}</div>
                                            </a-col>
                                            <a-col :xs="24" :sm="12" :md="8">
                                                <a-input v-model:value="row.nik" placeholder="NIK" @keydown="restrictToDigits" @paste="preventNonDigitPaste" :status="baFieldErrors[`row.${index}.nik`] ? 'error' : ''" @change="clearBaError(`row.${index}.nik`)" class="mb-1" />
                                                <div v-if="baFieldErrors[`row.${index}.nik`]" class="text-red-500 text-xs mb-2">{{ baFieldErrors[`row.${index}.nik`] }}</div>
                                            </a-col>
                                            <a-col :xs="24" :sm="12" :md="8">
                                                <a-input v-model:value="row.tanggal" type="date" placeholder="Tanggal" :status="baFieldErrors[`row.${index}.tanggal`] ? 'error' : ''" @change="clearBaError(`row.${index}.tanggal`)" class="mb-1" />
                                                <div v-if="baFieldErrors[`row.${index}.tanggal`]" class="text-red-500 text-xs mb-2">{{ baFieldErrors[`row.${index}.tanggal`] }}</div>
                                            </a-col>
                                            <a-col :xs="24" :sm="12" :md="8">
                                                <a-input v-model:value="row.absensi_in" type="time" placeholder="Absensi IN" :status="baFieldErrors[`row.${index}.absensi_in`] ? 'error' : ''" @change="clearBaError(`row.${index}.absensi_in`)" class="mb-1" />
                                                <div v-if="baFieldErrors[`row.${index}.absensi_in`]" class="text-red-500 text-xs mb-2">{{ baFieldErrors[`row.${index}.absensi_in`] }}</div>
                                            </a-col>
                                            <a-col :xs="24" :sm="12" :md="8">
                                                <a-input v-model:value="row.absensi_out" type="time" placeholder="Absensi Out" :status="baFieldErrors[`row.${index}.absensi_out`] ? 'error' : ''" @change="clearBaError(`row.${index}.absensi_out`)" class="mb-1" />
                                                <div v-if="baFieldErrors[`row.${index}.absensi_out`]" class="text-red-500 text-xs mb-2">{{ baFieldErrors[`row.${index}.absensi_out`] }}</div>
                                            </a-col>
                                            <a-col :xs="24" :sm="12" :md="8"><a-input v-model:value="row.ket" placeholder="Keterangan" class="mb-2" /></a-col>
                                        </a-row>
                                    </template>
                                    <template v-else-if="form.meta.template === 'Penghapusan Barang Sitaan'">
                                        <div class="flex items-start gap-3 mb-2">
                                            <span class="w-[35%] flex-shrink-0 mt-1">Cabang</span>
                                            <span class="text-gray-400 mt-1">:</span>
                                            <a-input v-model:value="row.cabang" placeholder="Isi Data" class="flex-1" />
                                        </div>
                                        <div class="flex items-start gap-3 mb-2">
                                            <span class="w-[35%] flex-shrink-0 mt-1">Nama Nasabah</span>
                                            <span class="text-gray-400 mt-1">:</span>
                                            <div class="flex flex-col flex-1">
                                                <a-input v-model:value="row.nama_nasabah" placeholder="Isi Data" :status="baFieldErrors[`row.${index}.nama_nasabah`] ? 'error' : ''" @change="clearBaError(`row.${index}.nama_nasabah`)" />
                                                <div v-if="baFieldErrors[`row.${index}.nama_nasabah`]" class="text-red-500 text-xs mt-1">{{ baFieldErrors[`row.${index}.nama_nasabah`] }}</div>
                                            </div>
                                        </div>
                                        <div class="flex items-start gap-3 mb-2">
                                            <span class="w-[35%] flex-shrink-0 mt-1">No. Faktur</span>
                                            <span class="text-gray-400 mt-1">:</span>
                                            <div class="flex flex-col flex-1">
                                                <a-input v-model:value="row.no_faktur" placeholder="Isi Data" :status="baFieldErrors[`row.${index}.no_faktur`] ? 'error' : ''" @change="clearBaError(`row.${index}.no_faktur`)" />
                                                <div v-if="baFieldErrors[`row.${index}.no_faktur`]" class="text-red-500 text-xs mt-1">{{ baFieldErrors[`row.${index}.no_faktur`] }}</div>
                                            </div>
                                        </div>
                                        <div class="flex items-start gap-3 mb-2">
                                            <span class="w-[35%] flex-shrink-0 mt-1">Barang</span>
                                            <span class="text-gray-400 mt-1">:</span>
                                            <div class="flex flex-col flex-1">
                                                <a-input v-model:value="row.barang" placeholder="Isi Data" :status="baFieldErrors[`row.${index}.barang`] ? 'error' : ''" @change="clearBaError(`row.${index}.barang`)" />
                                                <div v-if="baFieldErrors[`row.${index}.barang`]" class="text-red-500 text-xs mt-1">{{ baFieldErrors[`row.${index}.barang`] }}</div>
                                            </div>
                                        </div>
                                        <div class="flex items-start gap-3">
                                            <span class="w-[35%] flex-shrink-0 mt-1">Nominal Pinjaman</span>
                                            <span class="text-gray-400 mt-1">:</span>
                                            <div class="flex flex-col flex-1">
                                                <a-input-number v-model:value="row.nominal_pinjaman" :precision="0" :formatter="formatNominalInput" :parser="parseNominalInput" @keydown="restrictToDigits" @paste="preventNonDigitPaste" placeholder="Isi Data" :status="baFieldErrors[`row.${index}.nominal_pinjaman`] ? 'error' : ''" @change="clearBaError(`row.${index}.nominal_pinjaman`)" class="w-full" />
                                                <div v-if="baFieldErrors[`row.${index}.nominal_pinjaman`]" class="text-red-500 text-xs mt-1">{{ baFieldErrors[`row.${index}.nominal_pinjaman`] }}</div>
                                            </div>
                                        </div>
                                    </template>
                                    <template v-else>
                                        <a-row :gutter="8">
                                            <a-col :span="24"><a-input v-model:value="row.cabang" placeholder="Cabang" class="mb-2" /></a-col>
                                            <a-col :span="24"><a-input v-model:value="row.nama_nasabah" placeholder="Nama Nasabah" class="mb-2" /></a-col>
                                            <a-col :span="24"><a-input v-model:value="row.no_faktur" placeholder="No. Faktur" class="mb-2" /></a-col>
                                            <a-col :span="24"><a-input v-model:value="row.barang" placeholder="Barang" class="mb-2" /></a-col>
                                            <a-col :span="24"><a-input-number v-model:value="row.nominal_pinjaman" :precision="0" :formatter="formatNominalInput" :parser="parseNominalInput" @keydown="restrictToDigits" @paste="preventNonDigitPaste" placeholder="Nominal Pinjaman" class="mb-2 w-full" /></a-col>
                                        </a-row>
                                    </template>
                                </div>
                                <a-button type="dashed" block @click="addTemplateRow">
                                    <template #icon><plus-outlined /></template>
                                    Tambah Baris
                                </a-button>
                                <a-form-item v-if="form.meta.template === 'Penghapusan Barang Sitaan'" label="Kronologi" class="mt-4 mb-0">
                                    <a-textarea v-model:value="form.meta.data.kronologi" :rows="5" />
                                </a-form-item>
                                </template>
                            </div>

                            <a-form-item v-if="form.meta.template !== 'Penghapusan Barang Sitaan'" label="Keterangan Tambahan / Alasan" class="mb-4">
                                <a-textarea v-model:value="form.keterangan_tambahan" :rows="4" placeholder="Misal: Rincian pinalty, atau penjelasan lebih lanjut..." />
                            </a-form-item>
                            
                            <a-form-item label="Kalimat Penutup" class="mb-0">
                                <a-textarea v-model:value="form.penutup" :rows="2" />
                            </a-form-item>
                        </a-card>

                        <!-- Action Button -->
                        <div class="pt-2 flex justify-end">
                            <a-button size="large" class="mr-3" @click="submit" :loading="form.processing && !form.submit_after_save">
                                <template #icon><save-outlined /></template>
                                Simpan Draft
                            </a-button>
                            <a-button size="large" type="primary" @click="submitAndSend" :loading="form.processing && form.submit_after_save" class="bg-green-600 hover:bg-green-500 border-green-600">
                                <template #icon><send-outlined /></template>
                                Kirim ke AM
                            </a-button>
                        </div>
                    </div>
                </a-col>
            </a-row>
        </a-form>

        <!-- Modal TTD: muncul bila TTD KC belum ada saat klik Kirim -->
        <a-modal
            v-model:open="showSignatureDialog"
            title="Tanda Tangan Digital KC"
            :confirmLoading="savingSignature"
            :okButtonProps="{ disabled: savingSignature || !signatureFile }"
            okText="Tanda Tangani & Kirim ke AM"
            cancelText="Batal"
            @ok="confirmSignature"
            centered
        >
            <p class="text-sm text-gray-500 mb-4">Masukkan tanda tangan terlebih dahulu sebelum Berita Acara dikirim ke Area Manager.</p>
            <a-form layout="vertical">
                <a-form-item label="Upload tanda tangan" extra="Format PNG/JPG, maksimal 2MB.">
                    <input type="file" @change="selectSignature" accept="image/png,image/jpeg" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                </a-form-item>
            </a-form>
            <img v-if="signaturePreview" :src="signaturePreview" alt="Preview tanda tangan" class="h-16 mt-4 object-contain border border-gray-200 rounded-lg p-1" />
            <p v-if="signatureError" class="text-red-500 text-sm mt-2">{{ signatureError }}</p>
            <p v-if="form.errors.signature_image" class="text-red-500 text-sm mt-2">{{ form.errors.signature_image }}</p>
        </a-modal>
    </AuthenticatedLayout>
</template>
