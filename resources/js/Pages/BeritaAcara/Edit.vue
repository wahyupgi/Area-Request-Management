<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DocumentTableEditor from '@/Components/DocumentTableEditor.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import { message } from 'ant-design-vue';
import { useSweetAlert } from '@/composables/useSweetAlert';
import { preventNonDigitPaste, restrictToDigits } from '@/composables/numericInput';
import {
    LeftOutlined,
    SaveOutlined,
    SendOutlined,
    PlusOutlined,
    DeleteOutlined,
    PaperClipOutlined,
} from '@ant-design/icons-vue';

const props = defineProps({
    beritaAcara: Object,
    signature:   { type: Object, default: null },
});

const { confirm, success } = useSweetAlert();

// ── Form state ────────────────────────────────────────────────────
const form = useForm({
    title:                props.beritaAcara.title || '',
    pengantar:            props.beritaAcara.pengantar || '',
    rincian_data:         props.beritaAcara.rincian_data || [{ label: '', value: '' }],
    keterangan_tambahan:  props.beritaAcara.keterangan_tambahan || '',
    penutup:              props.beritaAcara.penutup || '',
    meta: {
        template:         props.beritaAcara.meta?.template || '',
        direktorat:       props.beritaAcara.meta?.direktorat || '',
        divisi:           props.beritaAcara.meta?.divisi || '',
        perihal:          props.beritaAcara.meta?.perihal || '',
        lampiran:         props.beritaAcara.meta?.lampiran || '',
        kepada_nama:      props.beritaAcara.meta?.kepada_nama || '',
        kepada_jabatan:   props.beritaAcara.meta?.kepada_jabatan || '',
        penyetuju_akhir:  props.beritaAcara.meta?.penyetuju_akhir || '',
        data:             props.beritaAcara.meta?.data || { rows: [], kronologi: '' },
    },
});

// ── Signature / submit state ──────────────────────────────────────
const showSignatureDialog = ref(false);
const signatureFile       = ref(null);
const signaturePreview    = ref(null);
const signatureError      = ref('');
const submittingMemo      = ref(false);

// ── Attachment state ──────────────────────────────────────────────
const fileInput  = ref(null);
const uploading  = ref(false);

// ── Template helpers ──────────────────────────────────────────────
const templateName = props.beritaAcara.meta?.template || '';
const isRevisiAbsensi      = templateName === 'Permintaan Revisi Absensi';
const isPenghapusanBarang  = templateName === 'Penghapusan Barang Sitaan';
const isLainnya            = templateName === 'Berita Acara Lainnya';
const isTableTemplate      = isRevisiAbsensi || isPenghapusanBarang || isLainnya;

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
const formatRupiahAmount = (value) => {
    const digits = String(value ?? '').replace(/\D/g, '');
    return digits ? `Rp ${digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.')}` : '';
};
const isRupiahDetail = (label) => /biaya kost|nominal|rupiah|pinjaman|harga/i.test(label || '');
const formattedDetailValue = (item) => isRupiahDetail(item.label) ? formatRupiahAmount(item.value) : item.value;
const updateDetailValue = (item, value) => { item.value = isRupiahDetail(item.label) ? formatRupiahAmount(value) : value; };
const restrictDetailDigits = (event, label) => { if (/nik|nomor telepon|no\.?\s*tlp|biaya kost/i.test(label || '')) restrictToDigits(event); };
const preventNonDigitDetailPaste = (event, label) => { if (/nik|nomor telepon|no\.?\s*tlp|biaya kost/i.test(label || '')) preventNonDigitPaste(event); };

const addRincian    = () => form.rincian_data.push({ label: '', value: '' });
const removeRincian = (index) => form.rincian_data.splice(index, 1);
const addTemplateRow = () => {
    const row = isRevisiAbsensi
        ? { nama: '', nik: '', tanggal: '', absensi_in: '', absensi_out: '', ket: 'Revisi Absen' }
        : isPenghapusanBarang
            ? { cabang: '', nama_nasabah: '', no_faktur: '', barang: '', nominal_pinjaman: '' }
            : Object.fromEntries((form.meta.data.table_columns || []).map(c => [c.key, '']));
    form.meta.data.rows.push(row);
};
const removeTemplateRow = (index) => form.meta.data.rows.splice(index, 1);

// ── Save draft ────────────────────────────────────────────────────
const save = () => {
    form.put(route('berita-acara.update', props.beritaAcara.id), {
        onSuccess: () => success('Berita Acara berhasil diperbarui.'),
    });
};

// ── Submit to AM ──────────────────────────────────────────────────
const doSubmit = (signatureToUpload = signatureFile.value) => {
    if (!props.signature?.signature_image && !signatureToUpload) return;
    submittingMemo.value = true;

    // First save the latest form data, then submit
    form.put(route('berita-acara.update', props.beritaAcara.id), {
        preserveScroll: true,
        onSuccess: () => {
            const formData = new FormData();
            if (signatureToUpload) formData.append('signature_image', signatureToUpload);
            router.post(route('berita-acara.submit', props.beritaAcara.id), formData, {
                forceFormData: true,
                onSuccess: () => {
                    showSignatureDialog.value = false;
                    success('Berita Acara berhasil dikirim ke Area Manager.');
                    router.visit(route('berita-acara.show', props.beritaAcara.id));
                },
                onError: (errors) => {
                    submittingMemo.value = false;
                    showSignatureDialog.value = false;
                    if (!errors.signature) message.error(Object.values(errors).join(' '));
                },
                onFinish: () => { submittingMemo.value = false; },
            });
        },
        onError: () => { submittingMemo.value = false; showSignatureDialog.value = false; },
    });
};

const submitMemo = () => {
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
const confirmSubmit = () => doSubmit(signatureFile.value);

// ── Attachment upload ─────────────────────────────────────────────
const uploadFile = () => {
    const file = fileInput.value?.files[0];
    if (!file) return;
    uploading.value = true;
    const formData = new FormData();
    formData.append('file', file);
    router.post(route('berita-acara.attachments.upload', props.beritaAcara.id), formData, {
        forceFormData: true,
        onFinish: () => { uploading.value = false; fileInput.value.value = ''; },
    });
};

const deleteAttachment = async (attachment) => {
    if (await confirm('Hapus lampiran ini?', 'Lampiran akan dihapus dari dokumen.')) {
        router.delete(route('berita-acara.attachments.destroy', [props.beritaAcara.id, attachment.id]));
    }
};

const deleteDraft = async () => {
    if (await confirm('Hapus draft ini?', 'Draft Berita Acara akan dihapus permanen.')) {
        router.delete(route('berita-acara.index')); // fallback ke index setelah hapus
    }
};
</script>

<template>
    <Head :title="'Edit Berita Acara: ' + beritaAcara.title" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-2.5">
                <Link :href="route('berita-acara.index')" class="text-gray-400 hover:text-gray-800 dark:hover:text-white transition-colors p-1 -ml-1 flex items-center">
                    <LeftOutlined class="text-base" />
                </Link>
                <h1 class="memo-page-title text-xl font-bold mb-0">Edit Berita Acara</h1>
            </div>
        </template>

        <div class="memo-create-page space-y-6">
            <!-- Status bar -->
            <a-card :bordered="false" class="rounded-lg shadow-sm">
                <div class="flex items-center justify-between gap-3 mb-2">
                    <div class="flex items-center gap-3">
                        <a-tag color="default" class="memo-code-tag font-mono">{{ beritaAcara.code }}</a-tag>
                        <a-tag :color="beritaAcara.status === 'rejected' ? 'error' : 'default'" class="memo-status-tag font-semibold uppercase">{{ beritaAcara.status }}</a-tag>
                    </div>
                </div>
                <p class="text-sm text-gray-500 mb-0">Template: <span class="font-medium text-gray-800">{{ beritaAcara.meta?.template || '-' }}</span></p>
            </a-card>

            <!-- Catatan penolakan -->
            <a-alert v-if="beritaAcara.status === 'rejected'" type="error" show-icon class="rounded-lg">
                <template #message><span class="font-semibold">Berita Acara Ditolak</span></template>
                <template #description>
                    <p class="mb-1">{{ beritaAcara.rejection_notes || 'Tidak ada catatan.' }}</p>
                </template>
            </a-alert>

            <a-form layout="vertical" @finish="save">
                <a-row :gutter="24">
                    <!-- Kolom kiri: Info dokumen + lampiran -->
                    <a-col :xs="24" :lg="10" class="mb-6">
                        <div class="flex flex-col gap-6">
                            <!-- Info Dokumen -->
                            <a-card v-if="!['Form Permohonan Pinjaman (FPP)', 'Form Ijin Tidak Masuk Kerja (FITMK)'].includes(templateName)" :bordered="false" class="rounded-lg shadow-sm">
                                <h2 class="text-sm font-semibold mb-4">Informasi Dokumen</h2>

                                <a-form-item label="Judul Berita Acara" class="mb-3" :validateStatus="form.errors.title ? 'error' : ''" :help="form.errors.title">
                                    <a-input v-model:value="form.title" placeholder="Judul berita acara" size="large" />
                                </a-form-item>
                                <a-form-item label="Direktorat" class="mb-3">
                                    <a-input v-model:value="form.meta.direktorat" placeholder="Contoh: Regional Branch Office" />
                                </a-form-item>
                                <a-form-item label="Divisi" class="mb-3">
                                    <a-input v-model:value="form.meta.divisi" placeholder="Contoh: Branch Leader" />
                                </a-form-item>
                                <a-form-item label="Perihal" extra="Opsional, jika berbeda dari judul" class="mb-3">
                                    <a-input v-model:value="form.meta.perihal" :placeholder="form.title || 'Mengikuti judul'" />
                                </a-form-item>
                                <a-form-item label="Kepada (Yth.)" class="mb-3">
                                    <a-input v-model:value="form.meta.kepada_nama" placeholder="Nama penerima" />
                                </a-form-item>
                                <a-form-item label="Jabatan Penerima" class="mb-3">
                                    <a-input v-model:value="form.meta.kepada_jabatan" placeholder="Jabatan penerima" />
                                </a-form-item>
                                <a-form-item label="Lampiran" class="mb-0">
                                    <a-input v-model:value="form.meta.lampiran" placeholder="Contoh: 1 Lembar, 3 Berkas" />
                                </a-form-item>
                            </a-card>

                            <!-- Lampiran -->
                            <a-card :bordered="false" class="rounded-lg shadow-sm">
                                <h2 class="text-sm font-semibold mb-3">Lampiran Dokumen</h2>
                                <div v-if="beritaAcara.attachments?.length > 0" class="mb-4">
                                    <a-list item-layout="horizontal" :data-source="beritaAcara.attachments" size="small">
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
                                </div>
                                <p class="mb-2 text-xs text-gray-400">Maksimal 3 lampiran DOCX/PDF, 10 MB per file. {{ beritaAcara.attachments?.length || 0 }}/3 file digunakan.</p>
                                <div class="flex items-center gap-3">
                                    <input
                                        ref="fileInput"
                                        type="file"
                                        @change="uploadFile"
                                        accept=".docx,.pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/pdf"
                                        :disabled="(beritaAcara.attachments?.length || 0) >= 3 || uploading"
                                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                    />
                                    <span v-if="uploading" class="text-sm text-gray-500">Mengupload...</span>
                                </div>
                            </a-card>
                        </div>
                    </a-col>

                    <!-- Kolom kanan: Isi konten -->
                    <a-col :xs="24" :lg="14">
                        <a-card :bordered="false" class="rounded-lg shadow-sm">
                            <h2 class="text-sm font-semibold mb-4">Isi Berita Acara</h2>

                            <!-- Non-table BA templates -->
                            <template v-if="!isTableTemplate">
                                <a-form-item label="Kalimat Pengantar" class="mb-4">
                                    <a-textarea v-model:value="form.pengantar" :rows="3" />
                                </a-form-item>

                                <div class="border border-gray-200 rounded p-4 mb-4">
                                    <h3 class="font-semibold text-sm mb-3">Data Rincian</h3>
                                    <template v-if="templateName === 'template_1'">
                                        <div v-for="(label, index) in ['Cabang', 'Faktur', 'Tujuan', 'Nominal']" :key="label" class="flex gap-3 mb-2 items-center">
                                            <span class="w-[35%] flex-shrink-0">{{ label }}</span>
                                            <span class="text-gray-400">:</span>
                                            <a-input
                                                :value="formattedDetailValue(form.rincian_data[index] || {})"
                                                placeholder="Isi Data"
                                                class="flex-1"
                                                @update:value="value => updateDetailValue(form.rincian_data[index] || {}, value)"
                                                @keydown="event => restrictDetailDigits(event, label)"
                                                @paste="event => preventNonDigitDetailPaste(event, label)"
                                            />
                                        </div>
                                    </template>
                                    <template v-else>
                                        <div v-for="(item, index) in form.rincian_data" :key="index" class="flex gap-3 mb-2 items-center">
                                            <a-input v-model:value="item.label" placeholder="Label (Misal: NIK)" style="width: 35%;" />
                                            <span class="text-gray-400">:</span>
                                            <a-input :value="formattedDetailValue(item)" placeholder="Isi Data" class="flex-1" @update:value="value => updateDetailValue(item, value)" @keydown="event => restrictDetailDigits(event, item.label)" @paste="event => preventNonDigitDetailPaste(event, item.label)" />
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

                                <a-form-item v-if="templateName !== 'Penghapusan Barang Sitaan'" label="Keterangan Tambahan / Alasan" class="mb-4">
                                    <a-textarea v-model:value="form.keterangan_tambahan" :rows="4" />
                                </a-form-item>

                                <a-form-item label="Kalimat Penutup" class="mb-0">
                                    <a-textarea v-model:value="form.penutup" :rows="2" />
                                </a-form-item>
                            </template>

                            <!-- Table BA templates: Revisi Absensi, Penghapusan Barang Sitaan, Lainnya -->
                            <template v-else>
                                <a-form-item label="Kalimat Pengantar" class="mb-4">
                                    <a-textarea v-model:value="form.pengantar" :rows="3" />
                                </a-form-item>

                                <div class="border border-gray-200 rounded p-4 mb-4 overflow-x-auto">
                                    <h3 class="font-semibold text-sm mb-3">{{ isRevisiAbsensi ? 'Data Absensi' : isPenghapusanBarang ? 'Data Barang Sitaan' : 'Daftar Item' }}</h3>

                                    <template v-if="isLainnya">
                                        <DocumentTableEditor v-model:columns="form.meta.data.table_columns" v-model:rows="form.meta.data.rows" />
                                    </template>
                                    <template v-else>
                                        <div v-for="(row, index) in form.meta.data.rows" :key="index" class="border border-gray-200 rounded p-3 mb-3">
                                            <div class="flex justify-between items-center mb-2">
                                                <strong class="text-xs">Baris {{ index + 1 }}</strong>
                                                <a-button type="text" danger @click="removeTemplateRow(index)">
                                                    <template #icon><delete-outlined /></template>
                                                </a-button>
                                            </div>
                                            <template v-if="isRevisiAbsensi">
                                                <a-row :gutter="8">
                                                    <a-col :xs="24" :sm="12" :md="8"><a-input v-model:value="row.nama" placeholder="Nama" class="mb-2" /></a-col>
                                                    <a-col :xs="24" :sm="12" :md="8"><a-input v-model:value="row.nik" placeholder="NIK" @keydown="restrictToDigits" @paste="preventNonDigitPaste" class="mb-2" /></a-col>
                                                    <a-col :xs="24" :sm="12" :md="8"><a-input v-model:value="row.tanggal" type="date" class="mb-2" /></a-col>
                                                    <a-col :xs="24" :sm="12" :md="8"><a-input v-model:value="row.absensi_in" type="time" placeholder="Absensi IN" class="mb-2" /></a-col>
                                                    <a-col :xs="24" :sm="12" :md="8"><a-input v-model:value="row.absensi_out" type="time" placeholder="Absensi OUT" class="mb-2" /></a-col>
                                                    <a-col :xs="24" :sm="12" :md="8"><a-input v-model:value="row.ket" placeholder="Keterangan" class="mb-2" /></a-col>
                                                </a-row>
                                            </template>
                                            <template v-else-if="isPenghapusanBarang">
                                                <div v-for="field in [['cabang','Cabang'],['nama_nasabah','Nama Nasabah'],['no_faktur','No. Faktur'],['barang','Barang']]" :key="field[0]" class="flex items-center gap-3 mb-2">
                                                    <span class="w-[35%] flex-shrink-0">{{ field[1] }}</span>
                                                    <span class="text-gray-400">:</span>
                                                    <a-input v-model:value="row[field[0]]" placeholder="Isi Data" class="flex-1" />
                                                </div>
                                                <div class="flex items-center gap-3">
                                                    <span class="w-[35%] flex-shrink-0">Nominal Pinjaman</span>
                                                    <span class="text-gray-400">:</span>
                                                    <a-input-number v-model:value="row.nominal_pinjaman" :precision="0" :formatter="formatNominalInput" :parser="parseNominalInput" @keydown="restrictToDigits" @paste="preventNonDigitPaste" placeholder="Isi Data" class="flex-1 w-full" />
                                                </div>
                                            </template>
                                        </div>
                                        <a-button type="dashed" block @click="addTemplateRow">
                                            <template #icon><plus-outlined /></template>
                                            Tambah Baris
                                        </a-button>
                                        <a-form-item v-if="isPenghapusanBarang" label="Kronologi" class="mt-4 mb-0">
                                            <a-textarea v-model:value="form.meta.data.kronologi" :rows="5" />
                                        </a-form-item>
                                    </template>
                                </div>

                                <a-form-item label="Keterangan Tambahan" class="mb-4">
                                    <a-textarea v-model:value="form.keterangan_tambahan" :rows="3" />
                                </a-form-item>
                                <a-form-item label="Kalimat Penutup" class="mb-0">
                                    <a-textarea v-model:value="form.penutup" :rows="2" />
                                </a-form-item>
                            </template>

                            <!-- Action buttons -->
                            <div class="mt-8 pt-4 border-t flex flex-col sm:flex-row justify-end gap-3">
                                <a-button size="large" type="default" class="memo-save-draft-button" @click="save" :loading="form.processing && !showSignatureDialog">
                                    <template #icon><save-outlined /></template>
                                    Simpan Perubahan
                                </a-button>
                                <a-button size="large" type="primary" @click="submitMemo" :loading="submittingMemo" class="memo-submit-button bg-green-600 hover:bg-green-500 border-green-600">
                                    <template #icon><send-outlined /></template>
                                    Tanda Tangani &amp; Kirim ke AM
                                </a-button>
                            </div>
                        </a-card>
                    </a-col>
                </a-row>
            </a-form>
        </div>

        <!-- Modal TTD -->
        <a-modal
            v-model:open="showSignatureDialog"
            title="Tanda Tangan Digital KC"
            :confirmLoading="submittingMemo"
            :okButtonProps="{ disabled: submittingMemo || (!signature && !signatureFile) }"
            okText="Tanda Tangani & Kirim ke AM"
            cancelText="Batal"
            @ok="confirmSubmit"
            centered
            wrapClassName="memo-signature-modal"
        >
            <p class="text-sm text-gray-500 mb-4">Masukkan tanda tangan terlebih dahulu sebelum Berita Acara dikirim ke Area Manager.</p>

            <div v-if="signature && !signatureFile" class="flex items-center gap-4 mb-4 p-3 rounded-xl border border-gray-200 bg-gray-50">
                <img :src="'/storage/' + signature.signature_image" alt="Tanda tangan digital KC" class="h-14 w-32 object-contain bg-white border border-gray-200 rounded-lg p-1" />
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
            <img v-if="signaturePreview" :src="signaturePreview" alt="Preview tanda tangan" class="h-16 mt-4 object-contain border border-gray-200 rounded-lg p-1" />
            <p v-if="signatureError" class="text-red-500 text-sm mt-2">{{ signatureError }}</p>
            <p v-if="$page.props.errors?.signature" class="text-red-500 text-sm mt-2">{{ $page.props.errors.signature }}</p>
        </a-modal>
    </AuthenticatedLayout>
</template>
