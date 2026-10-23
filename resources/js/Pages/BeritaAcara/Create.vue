<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import DocumentTableEditor from '@/Components/DocumentTableEditor.vue';
import { computed, ref, watch } from 'vue';
import { useSweetAlert } from '@/composables/useSweetAlert';
import { preventNonDigitPaste, restrictToDigits } from '@/composables/numericInput';
import { SaveOutlined, SendOutlined, PlusOutlined, DeleteOutlined, LeftOutlined } from '@ant-design/icons-vue';

const props = defineProps({
    branches: { type: Array, default: () => [] },
    templates: { type: Array, default: () => [] },
    signature: { type: Object, default: null },
});

const page = usePage();
const { success } = useSweetAlert();
const attachmentError = ref('');
const showSignatureDialog = ref(false);
const signatureFile = ref(null);
const signaturePreview = ref(null);
const signatureError = ref('');
const savingSignature = ref(false);
const attachmentValidationError = computed(() => Object.entries(form.errors)
    .find(([key]) => key === 'attachments' || key.startsWith('attachments.'))?.[1] || '');
const allowedAttachmentExtensions = '.docx,.pdf';
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
});

watch(() => form.meta.template, (templateName) => {
    const selected = availableTemplates.value.find((item) => item.value === templateName);
    if (!selected) return;

    const t = selected.templateData;
    form.title = t.name;
    form.meta.perihal = t.document_defaults?.perihal || t.name;
    const isOtherBa = t.name === 'Berita Acara Lainnya';
    const tableColumns = isOtherBa
        ? (t.field_schema || []).map(({ key, label, type, required }) => ({ key, label, type, required }))
        : [];
    form.meta.data = {
        rows: isOtherBa ? [Object.fromEntries(tableColumns.map((column) => [column.key, '']))] : [],
        kronologi: '',
        ...(isOtherBa ? { table_columns: tableColumns } : {}),
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
        form.meta.data.rows = [
            Object.fromEntries(tableColumns.map((column) => [column.key, ''])),
        ];
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

const isRupiahDetail = (label) => /biaya kost|nominal|rupiah|pinjaman|harga/i.test(label || '');
const updateDetailValue = (item, value) => {
    item.value = isRupiahDetail(item.label)
        ? formatRupiahAmount(value)
        : value;
};
const formattedDetailValue = (item) => isRupiahDetail(item.label)
    ? formatRupiahAmount(item.value)
    : item.value;

const submit = () => {
    form.post(route('berita-acara.store'), {
        forceFormData: true,
    });
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

            <a-row :gutter="24" v-if="form.meta.template">
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

                <a-col :xs="24" :lg="14">
                    <div class="flex flex-col gap-6">
                        <a-card :bordered="false" class="rounded-lg shadow-sm">
                            <h2 class="text-sm font-semibold mb-4">Isi Berita Acara</h2>
                            <a-form-item label="Kalimat Pengantar (Sehubungan dengan...)" class="mb-4">
                                <a-textarea v-model:value="form.pengantar" :rows="3" />
                            </a-form-item>

                            <div v-if="!['Permintaan Revisi Absensi', 'Penghapusan Barang Sitaan', 'Berita Acara Lainnya'].includes(form.meta.template)" class="border border-gray-200 rounded p-4 mb-4">
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

                            <div v-else class="border border-gray-200 rounded p-4 mb-4 overflow-x-auto">
                                <h3 class="font-semibold text-sm mb-3">{{ form.meta.template === 'Permintaan Revisi Absensi' ? 'Data Absensi' : form.meta.template === 'Penghapusan Barang Sitaan' ? 'Data Barang Sitaan' : 'Daftar Item' }}</h3>
                                <template v-if="form.meta.template === 'Berita Acara Lainnya'">
                                    <DocumentTableEditor
                                        v-model:columns="form.meta.data.table_columns"
                                        v-model:rows="form.meta.data.rows"
                                    />
                                </template>
                                <template v-else>
                                <div v-for="(row, index) in form.meta.data.rows" :key="index" class="border border-gray-200 rounded p-3 mb-3">
                                    <div class="flex justify-between items-center mb-2">
                                        <strong class="text-xs">Baris {{ index + 1 }}</strong>
                                        <a-button type="text" danger @click="removeTemplateRow(index)">
                                            <template #icon><delete-outlined /></template>
                                        </a-button>
                                    </div>
                                    <template v-if="form.meta.template === 'Permintaan Revisi Absensi'">
                                        <a-row :gutter="8">
                                            <a-col :xs="24" :sm="12" :md="8"><a-input v-model:value="row.nama" placeholder="Nama" class="mb-2" /></a-col>
                                            <a-col :xs="24" :sm="12" :md="8"><a-input v-model:value="row.nik" placeholder="NIK" @keydown="restrictToDigits" @paste="preventNonDigitPaste" class="mb-2" /></a-col>
                                            <a-col :xs="24" :sm="12" :md="8"><a-input v-model:value="row.tanggal" type="date" placeholder="Tanggal" class="mb-2" /></a-col>
                                            <a-col :xs="24" :sm="12" :md="8"><a-input v-model:value="row.absensi_in" type="time" placeholder="Absensi IN" class="mb-2" /></a-col>
                                            <a-col :xs="24" :sm="12" :md="8"><a-input v-model:value="row.absensi_out" type="time" placeholder="Absensi Out" class="mb-2" /></a-col>
                                            <a-col :xs="24" :sm="12" :md="8"><a-input v-model:value="row.ket" placeholder="Keterangan" class="mb-2" /></a-col>
                                        </a-row>
                                    </template>
                                    <template v-else-if="form.meta.template === 'Penghapusan Barang Sitaan'">
                                        <div class="flex items-center gap-3 mb-2">
                                            <span class="w-[35%] flex-shrink-0">Cabang</span>
                                            <span class="text-gray-400">:</span>
                                            <a-input v-model:value="row.cabang" placeholder="Isi Data" class="flex-1" />
                                        </div>
                                        <div class="flex items-center gap-3 mb-2">
                                            <span class="w-[35%] flex-shrink-0">Nama Nasabah</span>
                                            <span class="text-gray-400">:</span>
                                            <a-input v-model:value="row.nama_nasabah" placeholder="Isi Data" class="flex-1" />
                                        </div>
                                        <div class="flex items-center gap-3 mb-2">
                                            <span class="w-[35%] flex-shrink-0">No. Faktur</span>
                                            <span class="text-gray-400">:</span>
                                            <a-input v-model:value="row.no_faktur" placeholder="Isi Data" class="flex-1" />
                                        </div>
                                        <div class="flex items-center gap-3 mb-2">
                                            <span class="w-[35%] flex-shrink-0">Barang</span>
                                            <span class="text-gray-400">:</span>
                                            <a-input v-model:value="row.barang" placeholder="Isi Data" class="flex-1" />
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="w-[35%] flex-shrink-0">Nominal Pinjaman</span>
                                            <span class="text-gray-400">:</span>
                                            <a-input-number v-model:value="row.nominal_pinjaman" :precision="0" :formatter="formatNominalInput" :parser="parseNominalInput" @keydown="restrictToDigits" @paste="preventNonDigitPaste" placeholder="Isi Data" class="flex-1 w-full" />
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
                            <a-button size="large" type="primary" html-type="submit" :loading="form.processing">
                                <template #icon><save-outlined /></template>
                                Simpan Draft
                            </a-button>
                        </div>
                    </div>
                </a-col>
            </a-row>
        </a-form>

    </AuthenticatedLayout>
</template>
