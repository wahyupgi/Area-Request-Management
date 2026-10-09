<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import { DeleteOutlined, PlusOutlined, SaveOutlined } from '@ant-design/icons-vue';
import { useSweetAlert } from '@/composables/useSweetAlert';

const props = defineProps({
    templates: Array,
});

const { success } = useSweetAlert();
const forms = reactive({});
const visibleAdditionalSlots = reactive({});

const documentTypes = [
    { key: 'memo', label: 'Memo' },
    { key: 'ba', label: 'Berita Acara' },
    { key: 'form', label: 'Form' },
];
const activeType = ref('memo');
const templateType = (template) => template.type || 'memo';
const templatesByType = computed(() => Object.fromEntries(
    documentTypes.map((type) => [type.key, (props.templates || []).filter((template) => templateType(template) === type.key)])
));

const automaticSlots = [
    { name: '', role: 'Kepala Cabang', location: 'document' },
    { name: '', role: 'Area Manager', location: 'document' },
];

const defaultSlots = (template) => {
    const configuredSlots = (template.signature_schema || []).map((slot) => ({
        name: slot.name || '',
        role: slot.role || '',
        location: slot.location || 'document',
    }));
    const additionalSlots = configuredSlots.slice(2);

    while (additionalSlots.length < 2) {
        additionalSlots.push({ name: '', role: '', location: 'document' });
    }

    return [...automaticSlots.map((slot) => ({ ...slot })), ...additionalSlots];
};

(props.templates || []).forEach((template) => {
    visibleAdditionalSlots[template.id] = Math.min(2, Math.max(0, (template.signature_schema || []).length - 2));
    forms[template.id] = useForm({
        signature_schema: defaultSlots(template),
    });
});

const addSlot = (template) => {
    const form = forms[template.id];
    const nextIndex = visibleAdditionalSlots[template.id] + 2;
    if (nextIndex >= 4) return;

    if (!form.signature_schema[nextIndex]) {
        form.signature_schema.splice(nextIndex, 0, { name: '', role: '', location: 'document' });
    }

    visibleAdditionalSlots[template.id] += 1;
};

const removeSlot = (template, additionalIndex) => {
    forms[template.id].signature_schema.splice(additionalIndex + 2, 1);
    visibleAdditionalSlots[template.id] -= 1;
};

const save = (template) => {
    const form = forms[template.id];
    const signatureSchema = form.signature_schema.map((slot) => ({ ...slot }));

    while (signatureSchema.length > 2) {
        const lastSlot = signatureSchema[signatureSchema.length - 1];
        if (lastSlot.name || lastSlot.role) break;
        signatureSchema.pop();
    }

    signatureSchema[0] = { ...automaticSlots[0] };
    signatureSchema[1] = { ...automaticSlots[1] };

    form.transform(() => ({ signature_schema: signatureSchema })).put(route('signature.settings.update', template.id), {
        preserveScroll: true,
        onSuccess: () => success('Pengaturan tanda tangan template berhasil disimpan.'),
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
                description="Slot 1 dan 2 otomatis diisi oleh KC dan AM. Atur penandatangan tambahan pada slot 3 dan 4. Slot juga dapat ditempatkan di kotak paraf kanan bawah."
                type="info"
                show-icon
                class="bg-sky-500/10 border-sky-500/20 text-sky-100 custom-dark-alert"
            />

            <a-tabs v-model:activeKey="activeType">
                <a-tab-pane
                    v-for="type in documentTypes"
                    :key="type.key"
                    :tab="`${type.label} (${templatesByType[type.key].length})`"
                >
                    <a-empty v-if="!templatesByType[type.key].length" description="Belum ada template" />
            <a-card 
                v-for="template in templatesByType[type.key]" 
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
                    <a-button
                        type="dashed"
                        size="small"
                        :disabled="visibleAdditionalSlots[template.id] >= 2"
                        @click="addSlot(template)"
                    >
                        <template #icon><PlusOutlined /></template>
                        Tambah Slot
                    </a-button>
                </template>
                <a-form layout="vertical">
                    <div 
                        v-for="(slot, additionalIndex) in forms[template.id].signature_schema.slice(2, 2 + visibleAdditionalSlots[template.id])" 
                        :key="additionalIndex" 
                        class="bg-slate-700/30 rounded-xl p-5 relative border border-white/5 mb-4"
                    >
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-slate-400 text-xs font-semibold uppercase tracking-wider m-0">Slot {{ additionalIndex + 3 }}</h4>
                            <a-button type="text" danger size="small" @click="removeSlot(template, additionalIndex)">
                                <template #icon><DeleteOutlined /></template>
                                Hapus Slot
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
                            Simpan
                        </a-button>
                    </div>
                </a-form>
            </a-card>
                </a-tab-pane>
            </a-tabs>
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
