<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { reactive } from 'vue';

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

        <div class="max-w-4xl space-y-6">
            <div class="bg-slate-800/50 border border-white/5 p-5">
                <h2 class="text-lg font-semibold text-white">Pengaturan Penandatangan per Template</h2>
                <p class="text-sm text-slate-400 mt-1">Isi nama lengkap dan jabatan penandatangan. Slot juga dapat ditempatkan di kotak paraf kanan bawah.</p>
            </div>

            <div v-for="template in templates" :key="template.id" class="bg-slate-800/50 border border-white/5 p-6">
                <div class="flex items-start justify-between gap-4 mb-5">
                    <div>
                        <p class="text-xs text-sky-400 uppercase tracking-wider font-semibold">{{ template.category || 'Tanpa Divisi' }}</p>
                        <h2 class="text-lg font-semibold text-white mt-1">{{ template.name }}</h2>
                    </div>
                    <button type="button" class="text-sm text-sky-300 hover:text-white" @click="addSlot(template)">+ Tambah Slot</button>
                </div>

                <div v-if="forms[template.id].signature_schema.length === 0" class="border border-dashed border-white/15 p-5 text-sm text-slate-500 text-center">
                    Belum ada slot tanda tangan.
                </div>

                <div v-for="(slot, index) in forms[template.id].signature_schema" :key="index" class="border border-white/10 p-4 mb-3">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold text-slate-400">Slot {{ index + 1 }}</span>
                        <button type="button" class="text-xs text-red-400 hover:text-red-300" @click="removeSlot(template, index)">Hapus</button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Nama lengkap</label>
                            <input v-model="slot.name" type="text" placeholder="Contoh: Fathurrahman M" class="w-full bg-slate-700/50 border border-white/10 px-3 py-2 text-sm text-white" />
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Jabatan</label>
                            <input v-model="slot.role" type="text" placeholder="Contoh: Manager GA" class="w-full bg-slate-700/50 border border-white/10 px-3 py-2 text-sm text-white" />
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Lokasi pada dokumen</label>
                            <select v-model="slot.location" class="w-full bg-slate-700/50 border border-white/10 px-3 py-2 text-sm text-white">
                                <option value="document">Kolom tanda tangan dokumen</option>
                                <option value="bottom_right">Kotak paraf kanan bawah</option>
                            </select>
                        </div>
                    </div>
                </div>

                <p v-if="forms[template.id].errors.signature_schema" class="text-sm text-red-400 mb-3">{{ forms[template.id].errors.signature_schema }}</p>
                <button type="button" :disabled="forms[template.id].processing" class="px-5 py-2.5 bg-sky-600 hover:bg-sky-500 disabled:opacity-50 text-white text-sm font-medium" @click="save(template)">
                    {{ forms[template.id].processing ? 'Menyimpan...' : 'Simpan Pengaturan' }}
                </button>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
