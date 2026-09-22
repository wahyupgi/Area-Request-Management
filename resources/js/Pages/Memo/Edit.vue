<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { useSweetAlert } from '@/composables/useSweetAlert';
import {
    PlusOutlined,
    DeleteOutlined,
    SaveOutlined,
    SendOutlined,
    PaperClipOutlined,
    ArrowLeftOutlined
} from '@ant-design/icons-vue';

const props = defineProps({
    memo: Object,
    templates: Array,
    signature: Object,
    openSignature: Boolean,
});

const initialItems = Array.isArray(props.memo.field_values?.items)
    ? props.memo.field_values.items
    : [props.memo.field_values || {}];

const form = useForm({
    code: props.memo.code || '',
    template_id: props.memo.template_id,
    title: props.memo.title || '',
    field_values: {
        ...(props.memo.field_values || {}),
        pengantar: props.memo.field_values?.pengantar || `Sehubungan dengan pengajuan ${props.memo.template?.name || props.memo.title || 'memo ini'}, saya ingin mengajukan permintaan dengan rincian sebagai berikut:`,
        items: initialItems,
        meta: {
            direktorat: props.memo.field_values?.meta?.direktorat || 'Regional Branch Office',
            divisi: props.memo.field_values?.meta?.divisi || 'Branch Leader',
            perihal: props.memo.field_values?.meta?.perihal || props.memo.title || '',
            kepada: props.memo.field_values?.meta?.kepada || '',
            kepada_jabatan: props.memo.field_values?.meta?.kepada_jabatan || '',
            penyetuju_akhir: props.memo.field_values?.meta?.penyetuju_akhir || '',
            lampiran: props.memo.field_values?.meta?.lampiran || '',
        },
    },
});

const fileInput = ref(null);
const uploading = ref(false);
const showSignatureDialog = ref(props.openSignature);
const signatureFile = ref(null);
const signaturePreview = ref(null);
const submittingMemo = ref(false);
const autoPerihal = ref(props.memo.field_values?.meta?.perihal || props.memo.title || '');
const { confirm, success } = useSweetAlert();

watch(() => form.title, (title) => {
    if (!form.field_values.meta.perihal || form.field_values.meta.perihal === autoPerihal.value) {
        form.field_values.meta.perihal = title;
        autoPerihal.value = title;
    }
});

const save = () => {
    form.put(route('memos.update', props.memo.id), {
        onSuccess: () => success('Draft memo berhasil disimpan.'),
    });
};

const submitMemo = () => {
    showSignatureDialog.value = true;
};

const addItem = () => { form.field_values.items.push({}); };
const removeItem = (index) => { if (form.field_values.items.length > 1) { form.field_values.items.splice(index, 1); } };
const addTableRow = (item, fieldKey, columns) => { if (!Array.isArray(item[fieldKey])) { item[fieldKey] = []; } const emptyRow = {}; columns.forEach(col => { emptyRow[col.key] = ''; }); item[fieldKey].push(emptyRow); };
const removeTableRow = (item, fieldKey, rowIndex) => { if (Array.isArray(item[fieldKey]) && item[fieldKey].length > 1) { item[fieldKey].splice(rowIndex, 1); } };
const ensureTableRows = (item, fieldKey, columns) => { if (!Array.isArray(item[fieldKey]) || item[fieldKey].length === 0) { const emptyRow = {}; columns.forEach(col => { emptyRow[col.key] = ''; }); item[fieldKey] = [emptyRow]; } return item[fieldKey]; };
const hasFieldValue = (value) => {
    if (Array.isArray(value)) return value.some((row) => hasFieldValue(row));
    if (value && typeof value === 'object') return Object.values(value).some((entry) => hasFieldValue(entry));
    return value !== undefined && value !== null && String(value).trim() !== '';
};

const selectSignature = (event) => {
    const file = event.target.files[0];
    signatureFile.value = file || null;
    if (!file) { signaturePreview.value = null; return; }
    const reader = new FileReader();
    reader.onload = (event) => { signaturePreview.value = event.target.result; };
    reader.readAsDataURL(file);
};

const confirmSubmit = () => {
    if (!props.signature && !signatureFile.value) return;
    submittingMemo.value = true;
    const formData = new FormData();
    if (signatureFile.value) formData.append('signature_image', signatureFile.value);
    router.post(route('memos.submit', props.memo.id), formData, {
        forceFormData: true,
        onFinish: () => { submittingMemo.value = false; showSignatureDialog.value = false; },
    });
};

const uploadFile = () => {
    const file = fileInput.value?.files[0];
    if (!file) return;
    uploading.value = true;
    const formData = new FormData();
    formData.append('file', file);
    router.post(route('memos.attachments.upload', props.memo.id), formData, {
        forceFormData: true,
        onFinish: () => { uploading.value = false; fileInput.value.value = ''; },
    });
};

const deleteAttachment = async (attachment) => {
    if (await confirm('Hapus lampiran ini?', 'Lampiran akan dihapus dari memo.')) {
        router.delete(route('memos.attachments.delete', [props.memo.id, attachment.id]));
    }
};

const deleteMemo = async () => {
    if (await confirm('Hapus memo ini?')) {
        router.delete(route('memos.destroy', props.memo.id));
    }
};
</script>

<template>
    <Head :title="'Edit: ' + memo.title" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-2">
                <Link :href="route('memos.index')" class="memo-breadcrumb-back inline-flex items-center gap-1 rounded-md px-2 py-1 text-sm transition-colors">
                    <ArrowLeftOutlined />
                    <span>Kembali</span>
                </Link>
                <span class="text-gray-400">/</span>
                <h1 class="memo-page-title text-xl font-bold mb-0">Edit Memo</h1>
            </div>
        </template>

        <div class="memo-create-page space-y-6">
            <!-- Rejection Notes -->
            <a-alert 
                v-if="memo.status === 'rejected' && memo.approvals?.length > 0" 
                type="error" 
                show-icon 
                class="mb-6 rounded-lg"
            >
                <template #message>
                    <span class="font-semibold">Memo Ditolak</span>
                </template>
                <template #description>
                    <p class="mb-1">{{ memo.approvals[0]?.notes }}</p>
                    <p class="text-xs text-gray-500 mb-0">Oleh: {{ memo.approvals[0]?.approver?.name }}</p>
                </template>
            </a-alert>

            <a-card :bordered="false" class="rounded-lg shadow-sm">
                <div class="flex items-center justify-between gap-3 mb-2">
                    <div class="flex items-center gap-3">
                        <a-tag color="default" class="memo-code-tag font-mono">{{ memo.code }}</a-tag>
                        <a-tag :color="memo.status === 'rejected' ? 'error' : 'default'" class="memo-status-tag font-semibold uppercase">{{ memo.status }}</a-tag>
                    </div>
                    <div v-if="memo.status === 'draft'" class="delete-memo-wrapper">
                        <a-button danger type="text" size="small" @click="deleteMemo">
                            Hapus Memo
                        </a-button>
                    </div>
                </div>
                <p class="text-sm text-gray-500 mb-0">Template: <span class="font-medium text-gray-800">{{ memo.template?.name }}</span></p>
            </a-card>

            <!-- Edit Form -->
            <a-form layout="vertical" @finish="save">
                <a-row :gutter="24">
                    <a-col :xs="24" :lg="10" class="mb-6">
                        <div class="flex flex-col gap-6">
                            <!-- Informasi Memo -->
                            <a-card v-if="memo.template" :bordered="false" class="memo-compact-field-card rounded-lg shadow-sm">
                                <h2 class="text-sm font-semibold mb-4">Informasi Memo</h2>
                                <a-form-item label="Nomor Memo" extra="Nomor memo otomatis dikelola oleh Area Manager (AM)." :validateStatus="form.errors.code ? 'error' : ''" :help="form.errors.code" class="mb-3">
                                    <a-input v-model:value="form.code" placeholder="Diatur otomatis oleh AM" size="large" />
                                </a-form-item>
                                <a-form-item label="Judul Memo" :validateStatus="form.errors.title ? 'error' : ''" :help="form.errors.title" class="mb-3">
                                    <a-input v-model:value="form.title" placeholder="Masukkan judul memo..." size="large" />
                                </a-form-item>
                                <a-form-item label="Isi Memo" class="mb-0" extra="Teks ini akan tampil pada bagian “Sehubungan dengan” dan dapat diubah sesuai kebutuhan pengajuan.">
                                    <a-textarea v-model:value="form.field_values.pengantar" :rows="5" />
                                </a-form-item>
                            </a-card>

                            <!-- Informasi Dokumen -->
                            <a-card :bordered="false" class="rounded-lg shadow-sm">
                                <h2 class="text-sm font-semibold mb-1">Informasi Dokumen</h2>
                                <p class="text-xs text-gray-500 mb-4">Detail header yang tampil di dokumen cetak.</p>
                                <a-form-item label="Direktorat" class="mb-3">
                                    <a-input v-model:value="form.field_values.meta.direktorat" placeholder="contoh: Operasional" />
                                </a-form-item>
                                <a-form-item label="Divisi" class="mb-3">
                                    <a-input v-model:value="form.field_values.meta.divisi" :placeholder="memo.template?.category || 'contoh: GA / Ma-Link'" />
                                </a-form-item>
                                <a-form-item label="Perihal" extra="opsional, jika beda dari judul" class="mb-3">
                                    <a-input v-model:value="form.field_values.meta.perihal" :placeholder="form.title || 'Mengikuti judul memo'" />
                                </a-form-item>
                                <a-form-item label="Kepada (Yth.)" extra="Nama penerima memo. Kosongkan untuk memakai Area Manager." class="mb-3">
                                    <a-input v-model:value="form.field_values.meta.kepada" placeholder="Nama penerima" />
                                </a-form-item>
                                <a-form-item label="Jabatan Penerima" extra="Jabatan penerima memo." class="mb-3">
                                    <a-input v-model:value="form.field_values.meta.kepada_jabatan" placeholder="Contoh: Area Manager" />
                                </a-form-item>
                                <a-form-item label="Penyetuju Akhir" extra="Nama penyetuju akhir. Nantinya dapat diatur otomatis oleh admin." class="mb-3">
                                    <a-input v-model:value="form.field_values.meta.penyetuju_akhir" placeholder="Nama penyetuju akhir" />
                                </a-form-item>
                                <a-form-item label="Lampiran" extra="keterangan teks" class="mb-0">
                                    <a-input v-model:value="form.field_values.meta.lampiran" placeholder="contoh: 1 Lembar, 3 Berkas" />
                                </a-form-item>
                            </a-card>

                            <!-- Lampiran -->
                            <a-card :bordered="false" class="rounded-lg shadow-sm">
                                <h2 class="text-sm font-semibold mb-3">Lampiran Dokumen</h2>
                                <div v-if="memo.attachments?.length > 0" class="mb-4">
                                    <a-list item-layout="horizontal" :data-source="memo.attachments" size="small">
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
                                <div class="flex items-center gap-3">
                                    <input ref="fileInput" type="file" @change="uploadFile" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                                    <span v-if="uploading" class="text-sm text-gray-500">Mengupload...</span>
                                </div>
                            </a-card>
                        </div>
                    </a-col>

                    <a-col :xs="24" :lg="14">
                        <!-- Dynamic Fields -->
                        <a-card v-if="memo.template" :bordered="false" class="rounded-lg shadow-sm">
                            <div v-for="(item, itemIndex) in form.field_values.items" :key="itemIndex" class="border border-gray-200 rounded-lg p-5 mb-4">
                                <div class="flex justify-between items-center border-b pb-2 mb-4">
                                    <h3 class="font-semibold">Item {{ itemIndex + 1 }}</h3>
                                    <a-button v-if="form.field_values.items.length > 1" type="text" danger size="small" @click="removeItem(itemIndex)">
                                        <template #icon><delete-outlined /></template>
                                        Hapus
                                    </a-button>
                                </div>

                                <div v-for="field in memo.template.field_schema" :key="field.key" class="mb-4">
                                    <a-form-item 
                                        :label="field.label" 
                                        :required="field.required && !hasFieldValue(item[field.key])"
                                        :validateStatus="form.errors['field_values.items.' + itemIndex + '.' + field.key] ? 'error' : ''"
                                        :help="form.errors['field_values.items.' + itemIndex + '.' + field.key]"
                                        class="mb-0"
                                    >
                                        <a-input v-if="field.type === 'text'" v-model:value="item[field.key]" />
                                        <a-input v-else-if="field.type === 'number'" type="number" v-model:value="item[field.key]" />
                                        <a-input v-else-if="field.type === 'date'" type="date" v-model:value="item[field.key]" />
                                        <a-textarea v-else-if="field.type === 'textarea'" v-model:value="item[field.key]" :rows="4" />
                                        
                                        <div v-else-if="field.type === 'table'" class="mt-2 space-y-3">
                                            <div v-for="(subRow, subIndex) in ensureTableRows(item, field.key, field.columns)" :key="'sub-' + subIndex" class="flex gap-2 items-start bg-gray-50 p-3 rounded border border-gray-200">
                                                <a-row :gutter="12" class="flex-1">
                                                    <a-col :span="24 / field.columns.length" v-for="col in field.columns" :key="col.key">
                                                        <div class="text-xs text-gray-500 mb-1">{{ col.label }}</div>
                                                        <a-input v-model:value="subRow[col.key]" :type="col.type === 'number' ? 'number' : 'text'" size="small" />
                                                    </a-col>
                                                </a-row>
                                                <a-button v-if="item[field.key].length > 1" type="text" danger class="mt-5" @click="removeTableRow(item, field.key, subIndex)">
                                                    <delete-outlined />
                                                </a-button>
                                            </div>
                                            <a-button type="dashed" block @click="addTableRow(item, field.key, field.columns)">
                                                <template #icon><plus-outlined /></template>
                                                Tambah Baris
                                            </a-button>
                                        </div>
                                    </a-form-item>
                                </div>
                            </div>
                            
                            <a-button type="dashed" block class="mt-2" @click="addItem">
                                <template #icon><plus-outlined /></template>
                                Tambah Item
                            </a-button>

                            <div class="mt-8 pt-4 border-t flex flex-col sm:flex-row justify-end gap-3">
                                <a-button size="large" type="default" class="memo-save-draft-button" @click="save" :loading="form.processing && !showSignatureDialog">
                                    <template #icon><save-outlined /></template>
                                    Simpan Perubahan
                                </a-button>
                                <a-button size="large" type="primary" @click="submitMemo" class="memo-submit-button bg-green-600 hover:bg-green-500 border-green-600">
                                    <template #icon><send-outlined /></template>
                                    Tanda Tangani &amp; Kirim ke AM
                                </a-button>
                            </div>
                        </a-card>
                    </a-col>
                </a-row>
            </a-form>

        </div>

        <!-- Digital signature confirmation before submission -->
        <a-modal
            v-model:open="showSignatureDialog"
            title="Tanda Tangan Digital KC"
            :confirmLoading="submittingMemo"
            :okButtonProps="{ disabled: submittingMemo || (!signature && !signatureFile) }"
            okText="Tanda Tangani & Kirim ke AM"
            cancelText="Batal"
            @ok="confirmSubmit"
            centered
        >
            <p class="text-sm text-gray-500 mb-4">Masukkan tanda tangan terlebih dahulu sebelum memo dikirim ke Area Manager.</p>

            <div v-if="signature && !signatureFile" class="flex items-center gap-4 mb-4 p-3 rounded-xl border border-gray-200 bg-gray-50">
                <img :src="'/storage/' + signature.signature_image" alt="Tanda tangan digital KC" class="h-14 w-32 object-contain bg-white border border-gray-200 rounded-lg p-1" />
                <div>
                    <p class="text-sm text-blue-500 font-medium mb-1">Tanda tangan terdaftar</p>
                    <p class="text-xs text-gray-400">Tanda tangan ini akan digunakan untuk memo.</p>
                </div>
            </div>

            <a-form layout="vertical">
                <a-form-item :label="signature ? 'Ganti tanda tangan (opsional)' : 'Upload tanda tangan'" extra="Format PNG/JPG, maksimal 2MB.">
                    <input type="file" @change="selectSignature" accept="image/png,image/jpeg" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                </a-form-item>
            </a-form>
            <img v-if="signaturePreview" :src="signaturePreview" alt="Preview tanda tangan digital KC" class="h-16 mt-4 object-contain border border-gray-200 rounded-lg p-1" />
            <p v-if="$page.props.errors?.signature" class="text-red-500 text-sm mt-2">{{ $page.props.errors.signature }}</p>
        </a-modal>

    </AuthenticatedLayout>
</template>
