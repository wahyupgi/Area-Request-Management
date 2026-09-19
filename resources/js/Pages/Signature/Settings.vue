<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { reactive } from 'vue';
import { PlusOutlined, DeleteOutlined, SaveOutlined } from '@ant-design/icons-vue';

const props = defineProps({
    templates: Array,
});

const forms = reactive({});

const defaultSlots = (template) => (template.signature_schema || []).map((slot) => ({
    name: slot.name || slot.label || '',
    role: slot.role || '',
    location: slot.location || 'document',
}));

(props.templates || []).forEach((template) => {
    forms[template.id] = useForm({
        signature_schema: defaultSlots(template),
    });
});

const addSlot = (template) => {
    forms[template.id].signature_schema.push({
        name: '',
        role: '',
        location: 'document',
    });
};

const removeSlot = (template, index) => {
    forms[template.id].signature_schema.splice(index, 1);
};

const save = (template) => {
    forms[template.id].put(route('signature.settings.update', template.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Pengaturan Tanda Tangan" />
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-bold text-white">Pengaturan Tanda Tangan</h1>
        </template>

        <div class="am-signature-page max-w-5xl space-y-6">
            <a-alert
                message="Pengaturan Penandatangan per Template"
                description="Isi nama lengkap dan jabatan penandatangan. Slot juga dapat ditempatkan di kotak paraf kanan bawah."
                type="info"
                show-icon
                class="bg-sky-500/10 border-sky-500/20 text-sky-100 custom-dark-alert"
            />

            <a-card 
                v-for="template in templates" 
                :key="template.id" 
                :bordered="false" 
                class="bg-slate-800/50 border border-white/5 mb-6"
            >
                <template #title>
                    <div class="flex flex-col">
                        <span class="text-xs text-sky-400 uppercase tracking-wider font-semibold">{{ template.category || 'Tanpa Divisi' }}</span>
                        <span class="text-white font-medium mt-1">{{ template.name }}</span>
                    </div>
                </template>
                <template #extra>
                    <a-button type="dashed" size="small" @click="addSlot(template)">
                        <template #icon><PlusOutlined /></template>
                        Tambah Slot
                    </a-button>
                </template>

                <a-empty 
                    v-if="forms[template.id].signature_schema.length === 0" 
                    description="Belum ada slot tanda tangan" 
                    :image="false" 
                    class="py-6 border border-dashed border-white/10 rounded-xl"
                />

                <a-form layout="vertical">
                    <div 
                        v-for="(slot, index) in forms[template.id].signature_schema" 
                        :key="index" 
                        class="bg-slate-700/30 rounded-xl p-5 relative border border-white/5 mb-4"
                    >
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-slate-400 text-xs font-semibold uppercase tracking-wider m-0">Slot {{ index + 1 }}</h4>
                            <a-button type="text" danger size="small" @click="removeSlot(template, index)">
                                <template #icon><DeleteOutlined /></template>
                                Hapus
                            </a-button>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <a-form-item label="Nama Lengkap" class="mb-0">
                                <a-input v-model:value="slot.name" placeholder="Contoh: Fathurrahman M" />
                            </a-form-item>
                            
                            <a-form-item label="Jabatan" class="mb-0">
                                <a-input v-model:value="slot.role" placeholder="Contoh: Manager GA" />
                            </a-form-item>
                            
                            <a-form-item label="Lokasi pada dokumen" class="mb-0">
                                <a-select v-model:value="slot.location">
                                    <a-select-option value="document">Kolom tanda tangan utama</a-select-option>
                                    <a-select-option value="bottom_right">Kotak paraf kanan bawah</a-select-option>
                                </a-select>
                            </a-form-item>
                        </div>
                    </div>

                    <div v-if="forms[template.id].errors.signature_schema" class="text-red-400 text-sm mb-4">
                        {{ forms[template.id].errors.signature_schema }}
                    </div>

                    <div class="mt-4 flex justify-end">
                        <a-button type="primary" :loading="forms[template.id].processing" @click="save(template)">
                            <template #icon><SaveOutlined /></template>
                            Simpan Pengaturan
                        </a-button>
                    </div>
                </a-form>
            </a-card>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
:deep(.custom-dark-alert.ant-alert) {
    background-color: rgba(14, 165, 233, 0.1);
    border-color: rgba(14, 165, 233, 0.2);
}
:deep(.custom-dark-alert .ant-alert-message),
:deep(.custom-dark-alert .ant-alert-description) {
    color: #e0f2fe;
}
:deep(.custom-dark-alert .ant-alert-icon) {
    color: #38bdf8;
}
</style>
