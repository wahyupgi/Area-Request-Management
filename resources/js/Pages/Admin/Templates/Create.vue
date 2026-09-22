<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { PlusOutlined, DeleteOutlined, SaveOutlined } from '@ant-design/icons-vue';
import { useSweetAlert } from '@/composables/useSweetAlert';

const props = defineProps({ template: { type: Object, default: null } });

const isEdit = !!props.template;
const { success } = useSweetAlert();

const form = useForm({
    name: props.template?.name || '',
    category: props.template?.category || '',
    document_defaults: {
        direktorat: props.template?.document_defaults?.direktorat || '',
        divisi: props.template?.document_defaults?.divisi || '',
        perihal: props.template?.document_defaults?.perihal || '',
        kepada: props.template?.document_defaults?.kepada || '',
        kepada_jabatan: props.template?.document_defaults?.kepada_jabatan || '',
        penyetuju_akhir: props.template?.document_defaults?.penyetuju_akhir || '',
        lampiran: props.template?.document_defaults?.lampiran || '',
    },
    field_schema: props.template?.field_schema || [{ key: '', label: '', type: 'text', required: false }],
});

const addField = () => {
    form.field_schema.push({ key: '', label: '', type: 'text', required: false });
};

const removeField = (index) => {
    form.field_schema.splice(index, 1);
};

const autoKey = (index) => {
    const label = form.field_schema[index].label;
    if (label && !form.field_schema[index].key) {
        form.field_schema[index].key = label.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');
    }
};

const submit = () => {
    if (isEdit) {
        form.put(route('admin.templates.update', props.template.id), {
            onSuccess: () => success('Template berhasil diperbarui.'),
        });
    } else {
        form.post(route('admin.templates.store'), {
            onSuccess: () => success('Template berhasil dibuat.'),
        });
    }
};
</script>

<template>
    <Head :title="isEdit ? 'Edit Template' : 'Buat Template'" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('admin.templates.index')" class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-sm text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-white/10 dark:hover:text-white">
                    <span>Template Memo</span>
                </Link>
                <span class="text-slate-400 dark:text-slate-500">/</span>
                <h1 class="text-xl font-bold text-slate-900 dark:text-white">{{ isEdit ? 'Edit Memo' : 'Buat Memo' }}</h1>
            </div>
        </template>

        <div class="max-w-4xl">
            <a-form layout="vertical" @finish="submit">
                <a-card :bordered="false" class="bg-slate-800/50 border border-white/5 mb-6">
                    <template #title>
                        <span class="text-white font-medium">Informasi Template</span>
                    </template>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <a-form-item label="Nama Template" :validateStatus="form.errors.name ? 'error' : ''" :help="form.errors.name">
                            <a-input v-model:value="form.name" placeholder="Contoh: Permohonan Penambahan Karyawan" />
                        </a-form-item>
                        <a-form-item label="Kategori" :validateStatus="form.errors.category ? 'error' : ''" :help="form.errors.category">
                            <a-input v-model:value="form.category" placeholder="Contoh: SDM, Keuangan, Fasilitas" />
                        </a-form-item>
                    </div>
                </a-card>

                <a-card :bordered="false" class="bg-slate-800/50 border border-white/5 mb-6">
                    <template #title>
                        <span class="text-white font-medium">Default Informasi Dokumen</span>
                    </template>
                    <p class="text-slate-400 text-sm mb-4">Nilai ini otomatis diisi saat user memilih template dan dapat disesuaikan saat membuat memo.</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <a-form-item v-for="field in [
                            { key: 'direktorat', label: 'Direktorat' },
                            { key: 'divisi', label: 'Divisi' },
                            { key: 'perihal', label: 'Perihal' },
                            { key: 'kepada', label: 'Kepada (Yth.)' },
                            { key: 'kepada_jabatan', label: 'Jabatan Penerima' },
                            { key: 'penyetuju_akhir', label: 'Penyetuju Akhir' },
                            { key: 'lampiran', label: 'Lampiran' },
                        ]" :key="field.key" :label="field.label" class="mb-0">
                            <a-input v-model:value="form.document_defaults[field.key]" />
                        </a-form-item>
                    </div>
                </a-card>

                <a-card :bordered="false" class="bg-slate-800/50 border border-white/5 mb-6">
                    <template #title>
                        <span class="text-white font-medium">Field Schema</span>
                    </template>
                    <template #extra>
                        <a-button type="dashed" @click="addField">
                            <template #icon><PlusOutlined /></template>
                            Tambah Field
                        </a-button>
                    </template>

                    <div class="space-y-4">
                        <div v-for="(field, idx) in form.field_schema" :key="idx" class="bg-slate-700/30 rounded-xl p-5 relative border border-white/5">
                            <div class="absolute top-4 right-4">
                                <a-button v-if="form.field_schema.length > 1" type="text" danger @click="removeField(idx)">
                                    <template #icon><DeleteOutlined /></template>
                                </a-button>
                            </div>
                            
                            <h4 class="text-slate-400 text-xs font-semibold mb-4 uppercase tracking-wider">Field #{{ idx + 1 }}</h4>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <a-form-item label="Label" class="mb-0">
                                    <a-input v-model:value="field.label" @blur="autoKey(idx)" placeholder="Nama Lengkap" />
                                </a-form-item>
                                
                                <a-form-item label="Key" class="mb-0">
                                    <a-input v-model:value="field.key" placeholder="nama_lengkap" />
                                </a-form-item>
                                
                                <a-form-item label="Tipe" class="mb-0">
                                    <a-select v-model:value="field.type">
                                        <a-select-option value="text">Text</a-select-option>
                                        <a-select-option value="textarea">Textarea</a-select-option>
                                        <a-select-option value="number">Number</a-select-option>
                                        <a-select-option value="date">Date</a-select-option>
                                        <a-select-option value="select">Select</a-select-option>
                                    </a-select>
                                </a-form-item>
                                
                                <div class="flex items-end pb-2">
                                    <a-checkbox v-model:checked="field.required">
                                        <span class="text-slate-300">Wajib diisi</span>
                                    </a-checkbox>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-if="form.errors.field_schema" class="text-red-400 text-sm mt-3">{{ form.errors.field_schema }}</div>
                </a-card>

                <div class="flex justify-end">
                    <a-button type="primary" html-type="submit" :loading="form.processing" size="large">
                        <template #icon><SaveOutlined /></template>
                        {{ isEdit ? 'Simpan Perubahan' : 'Buat Template' }}
                    </a-button>
                </div>
            </a-form>
        </div>
    </AuthenticatedLayout>
</template>
