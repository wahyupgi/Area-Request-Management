<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { useSweetAlert } from '@/composables/useSweetAlert';
import { 
    PlusOutlined, 
    DeleteOutlined, 
    SaveOutlined, 
    SendOutlined
} from '@ant-design/icons-vue';

const props = defineProps({
    templates: Array,
});

const form = useForm({
    code: null,
    template_id: undefined,
    title: '',
    field_values: {
        pengantar: '',
        items: [{}],
        meta: { direktorat: '', divisi: '', perihal: '', kepada: '', kepada_jabatan: '', penyetuju_akhir: '', lampiran: '' },
    },
    submit_after_save: false,
});

const selectedTemplate = ref(null);
const attachment = ref(null);
const autoPerihal = ref('');
const { success } = useSweetAlert();

const defaultDocumentMeta = {
    direktorat: 'Regional Branch Office',
    divisi: 'Branch Leader',
    perihal: '',
    kepada: '',
    kepada_jabatan: '',
    penyetuju_akhir: '',
    lampiran: '',
};

const groupedTemplates = computed(() => {
    const groups = {};
    (props.templates || []).forEach(t => {
        const category = String(t.category || '').trim();
        const templateName = String(t.name || '').trim();
        const normalizedCategory = category.toLowerCase();
        let cat = category || 'Lainnya';

        if (normalizedCategory === 'ma-link' || /^(fin|hrd)\s*-/i.test(templateName)) {
            cat = /^fin\s*-/i.test(templateName) ? 'Ma-Link - FIN' : 'Ma-Link - HRD';
        }

        if (!groups[cat]) groups[cat] = [];
        groups[cat].push(t);
    });
    return Object.keys(groups).map(key => ({
        label: key === 'GA' ? 'Divisi GA' : key,
        options: groups[key].map(t => ({
            value: t.id,
            label: `[${/^(fin|hrd)\s*-/i.test(String(t.name || '').trim()) ? (String(t.name).trim().toUpperCase().startsWith('FIN') ? 'FIN' : 'HRD') : String(t.category || '').trim()}] ${String(t.name || '').replace(/^(FIN|HRD)\s*-\s*/i, '')}`
        }))
    }));
});

watch(() => form.template_id, (val) => {
    selectedTemplate.value = props.templates.find(t => t.id == val) || null;
    form.field_values = {
        pengantar: selectedTemplate.value
            ? `Sehubungan dengan pengajuan ${selectedTemplate.value.name}, saya ingin mengajukan permintaan dengan rincian sebagai berikut:`
            : '',
        items: [{}],
        meta: {
            ...defaultDocumentMeta,
            ...(selectedTemplate.value?.document_defaults || {}),
            perihal: selectedTemplate.value?.document_defaults?.perihal || form.title,
        },
    };
    autoPerihal.value = form.title;
});

watch(() => form.title, (title) => {
    if (!form.field_values.meta.perihal || form.field_values.meta.perihal === autoPerihal.value) {
        form.field_values.meta.perihal = title;
        autoPerihal.value = title;
    }
});

const addItem = () => {
    form.field_values.items.push({});
};

const removeItem = (index) => {
    if (form.field_values.items.length > 1) {
        form.field_values.items.splice(index, 1);
    }
};

const addTableRow = (item, fieldKey, columns) => {
    if (!Array.isArray(item[fieldKey])) {
        item[fieldKey] = [];
    }
    const emptyRow = {};
    columns.forEach(col => { emptyRow[col.key] = ''; });
    item[fieldKey].push(emptyRow);
};

const removeTableRow = (item, fieldKey, rowIndex) => {
    if (Array.isArray(item[fieldKey]) && item[fieldKey].length > 1) {
        item[fieldKey].splice(rowIndex, 1);
    }
};

const ensureTableRows = (item, fieldKey, columns) => {
    if (!Array.isArray(item[fieldKey]) || item[fieldKey].length === 0) {
        const emptyRow = {};
        columns.forEach(col => { emptyRow[col.key] = ''; });
        item[fieldKey] = [emptyRow];
    }
    return item[fieldKey];
};

const hasFieldValue = (value) => {
    if (Array.isArray(value)) return value.some((row) => hasFieldValue(row));
    if (value && typeof value === 'object') return Object.values(value).some((entry) => hasFieldValue(entry));
    return value !== undefined && value !== null && String(value).trim() !== '';
};

const selectAttachment = (event) => {
    attachment.value = event.target.files[0] || null;
};

const submit = () => {
    form.submit_after_save = false;
    form.transform((data) => ({ ...data, attachment: attachment.value })).post(route('memos.store'), {
        forceFormData: true,
        onSuccess: () => success('Draft memo berhasil disimpan.'),
    });
};

const submitAndSign = () => {
    form.submit_after_save = true;
    form.transform((data) => ({ ...data, attachment: attachment.value })).post(route('memos.store'), { forceFormData: true });
};
</script>

<template>
    <Head title="Buat Memo Baru" />
    <AuthenticatedLayout>
        <template #header>
            <h1 class="memo-page-title text-xl font-bold mb-0">Buat Memo Baru</h1>
        </template>

        <a-form layout="vertical" @finish="submit" class="memo-create-page">
            <!-- Template Selection -->
            <a-card :bordered="false" class="mb-6 rounded-lg shadow-sm">
                <h2 class="text-lg font-semibold mb-2">Pilih Template Memo</h2>
                <p class="text-gray-500 text-sm mb-4">Pilih jenis memo sesuai kebutuhan cabang pada Divisi GA atau Ma-Link (FIN dan HRD).</p>
                <a-form-item 
                    :validateStatus="form.errors.template_id ? 'error' : ''" 
                    :help="form.errors.template_id"
                >
                    <a-select
                        v-model:value="form.template_id"
                        placeholder="-- Pilih Kategori & Template Memo --"
                        :options="groupedTemplates"
                        :listHeight="420"
                        popup-class-name="memo-template-dropdown"
                        style="width: 100%"
                        size="large"
                    />
                </a-form-item>
            </a-card>

            <a-row :gutter="24" v-if="selectedTemplate">
                <a-col :xs="24" :lg="10" class="mb-6">
                    <div class="flex flex-col gap-6">
                        <!-- Informasi Memo -->
                        <a-card :bordered="false" class="memo-compact-field-card rounded-lg shadow-sm">
                            <h2 class="text-sm font-semibold mb-4">Informasi Memo</h2>
                            <a-form-item 
                                label="Nomor Memo" 
                                extra="Nomor memo otomatis dikelola oleh Area Manager (AM)."
                                :validateStatus="form.errors.code ? 'error' : ''" 
                                :help="form.errors.code"
                                class="mb-3"
                            >
                                <a-input v-model:value="form.code" placeholder="Diatur otomatis oleh AM" size="large" />
                            </a-form-item>
                            <a-form-item 
                                label="Judul Memo" 
                                :validateStatus="form.errors.title ? 'error' : ''" 
                                :help="form.errors.title"
                                class="mb-3"
                            >
                                <a-input v-model:value="form.title" placeholder="Masukkan judul memo..." size="large" />
                            </a-form-item>
                            <a-form-item 
                                label="Isi Memo" 
                                class="mb-0"
                                extra="Teks ini akan tampil pada bagian “Sehubungan dengan” dan dapat diubah sesuai kebutuhan pengajuan."
                            >
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
                                <a-input v-model:value="form.field_values.meta.divisi" :placeholder="selectedTemplate?.category || 'contoh: GA / Ma-Link'" />
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
                            <h2 class="text-sm font-semibold mb-2">Lampiran Dokumen</h2>
                            <input type="file" @change="selectAttachment" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                            <p class="text-xs text-gray-400 mt-2">Maksimal 10MB. Lampiran akan tersimpan bersama draft memo.</p>
                        </a-card>
                    </div>
                </a-col>

                <a-col :xs="24" :lg="14">
                    <!-- Dynamic Fields -->
                    <a-card :bordered="false" class="rounded-lg shadow-sm">
                        <div v-for="(item, itemIndex) in form.field_values.items" :key="itemIndex" class="border border-gray-200 rounded-lg p-5 mb-4 relative">
                            <div class="flex justify-between items-center border-b pb-2 mb-4">
                                <h3 class="font-semibold">Item {{ itemIndex + 1 }}</h3>
                                <a-button v-if="form.field_values.items.length > 1" type="text" danger size="small" @click="removeItem(itemIndex)">
                                    <template #icon><delete-outlined /></template>
                                    Hapus
                                </a-button>
                            </div>

                            <div v-for="field in selectedTemplate.field_schema" :key="field.key" class="mb-4">
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
                                    
                                    <!-- Table field: multiple sub-rows (e.g. area + qty) -->
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
                            <a-button size="large" type="default" class="memo-save-draft-button" @click="submit" :loading="form.processing && !form.submit_after_save">
                                <template #icon><save-outlined /></template>
                                Simpan Draft
                            </a-button>
                            <a-button size="large" type="primary" @click="submitAndSign" :loading="form.processing && form.submit_after_save" class="memo-submit-button bg-green-600 hover:bg-green-500 border-green-600">
                                <template #icon><send-outlined /></template>
                                Tanda Tangani &amp; Kirim ke AM
                            </a-button>
                        </div>
                    </a-card>
                </a-col>
            </a-row>
        </a-form>
    </AuthenticatedLayout>
</template>
