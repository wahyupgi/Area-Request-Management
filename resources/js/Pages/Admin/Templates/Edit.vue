<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { PlusOutlined, DeleteOutlined, SaveOutlined } from '@ant-design/icons-vue';

const props = defineProps({ template: Object });

const form = useForm({
    name: props.template.name,
    category: props.template.category || '',
    field_schema: props.template.field_schema || [],
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
    form.put(route('admin.templates.update', props.template.id));
};
</script>

<template>
    <Head title="Edit Template" />
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-bold text-white">Edit Template</h1>
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
                        Simpan Perubahan
                    </a-button>
                </div>
            </a-form>
        </div>
    </AuthenticatedLayout>
</template>
