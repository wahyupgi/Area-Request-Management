<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';

const props = defineProps({
    templates: Array,
});

const form = useForm({
    template_id: '',
    title: '',
    field_values: { pengantar: '', items: [{}] },
    submit_after_save: false,
});

const selectedTemplate = ref(null);
const attachment = ref(null);

const groupedTemplates = computed(() => {
    const groups = {};
    (props.templates || []).forEach(t => {
        let cat = t.category || 'Lainnya';

        if (cat === 'Ma-Link') {
            const name = (t.name || '').toUpperCase();
            cat = name.startsWith('FIN') ? 'Ma-Link - FIN' : 'Ma-Link - HRD';
        }

        if (!groups[cat]) groups[cat] = [];
        groups[cat].push(t);
    });
    return groups;
});

watch(() => form.template_id, (val) => {
    selectedTemplate.value = props.templates.find(t => t.id == val) || null;
    form.field_values = {
        pengantar: selectedTemplate.value
            ? `Sehubungan dengan pengajuan ${selectedTemplate.value.name}, saya ingin mengajukan permintaan dengan rincian sebagai berikut:`
            : '',
        items: [{}],
    };
});

const addItem = () => {
    form.field_values.items.push({});
};

const removeItem = (index) => {
    if (form.field_values.items.length > 1) {
        form.field_values.items.splice(index, 1);
    }
};

const selectAttachment = (event) => {
    attachment.value = event.target.files[0] || null;
};

const submit = () => {
    form.submit_after_save = false;
    form.transform((data) => ({ ...data, attachment: attachment.value })).post(route('memos.store'), { forceFormData: true });
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
            <h1 class="text-xl font-bold text-white">Buat Memo Baru</h1>
        </template>

        <div class="w-full max-w-none">
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Template Selection -->
                <div class="bg-slate-800/50 border border-white/5 rounded-2xl p-6 lg:col-span-2">
                    <h2 class="text-lg font-semibold text-white mb-2">Pilih Template Memo</h2>
                    <p class="text-xs text-slate-400 mb-4">Pilih jenis memo sesuai kebutuhan cabang pada Divisi GA atau Ma-Link (FIN dan HRD).</p>
                    <select v-model="form.template_id" class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors">
                        <option value="" disabled>-- Pilih Kategori & Template Memo --</option>
                        <optgroup v-for="(items, category) in groupedTemplates" :key="category" :label="category === 'GA' ? 'Divisi GA' : category">
                            <option v-for="t in items" :key="t.id" :value="t.id">
                                [{{ t.category === 'Ma-Link' ? (t.name.startsWith('FIN') ? 'FIN' : 'HRD') : t.category }}] {{ t.name.replace(/^(FIN|HRD)\s*-\s*/, '') }}
                            </option>
                        </optgroup>
                    </select>
                    <p v-if="form.errors.template_id" class="text-red-400 text-sm mt-2">{{ form.errors.template_id }}</p>
                </div>

                <div v-if="selectedTemplate" class="grid grid-cols-1 lg:grid-cols-[minmax(260px,0.7fr)_minmax(0,1.3fr)] gap-4 items-start">
                    <div class="flex flex-col gap-4 self-start">
                        <!-- Title -->
                        <div class="bg-slate-800/50 border border-white/5 rounded-2xl p-6">
                            <label class="block text-sm font-medium text-slate-300 mb-2">Judul Memo</label>
                            <input v-model="form.title" type="text" placeholder="Masukkan judul memo..." class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-slate-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors" />
                            <p v-if="form.errors.title" class="text-red-400 text-sm mt-2">{{ form.errors.title }}</p>
                        </div>

                        <div class="bg-slate-800/50 border border-white/5 rounded-2xl p-6">
                            <label class="block text-sm font-medium text-slate-300 mb-2">Isi Memo</label>
                            <textarea v-model="form.field_values.pengantar" rows="5" class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-slate-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors resize-y"></textarea>
                            <p class="text-xs text-slate-500 mt-2">Teks ini akan tampil pada bagian “Sehubungan dengan” dan dapat diubah sesuai kebutuhan pengajuan.</p>
                        </div>

                        <div class="bg-slate-800/50 border border-white/5 rounded-2xl p-6">
                            <h2 class="text-lg font-semibold text-white mb-4">Lampiran</h2>
                            <input type="file" @change="selectAttachment" class="max-w-full text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-indigo-600 file:text-white hover:file:bg-indigo-500 file:cursor-pointer" />
                            <p class="text-xs text-slate-500 mt-2">Maksimal 10MB. Lampiran akan tersimpan bersama draft memo.</p>
                        </div>
                    </div>

                    <!-- Dynamic Fields -->
                    <div class="bg-slate-800/50 border border-white/5 rounded-2xl p-6 min-w-0 self-start">
                        <div v-for="(item, itemIndex) in form.field_values.items" :key="itemIndex" class="border border-white/10 p-4 space-y-4 mb-4 last:mb-0">
                        <div class="flex items-center justify-between border-b border-white/10 pb-3">
                            <h3 class="text-sm font-semibold text-white">Item {{ itemIndex + 1 }}</h3>
                            <button v-if="form.field_values.items.length > 1" type="button" class="text-sm text-red-400 hover:text-red-300" @click="removeItem(itemIndex)">Hapus item</button>
                        </div>
                        <div v-for="field in selectedTemplate.field_schema" :key="field.key">
                            <label class="block text-sm font-medium text-slate-300 mb-2">
                                {{ field.label }}
                                <span v-if="field.required" class="text-red-400">*</span>
                            </label>
                            <input v-if="field.type === 'text'" v-model="item[field.key]" type="text" class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-slate-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors" />
                            <input v-else-if="field.type === 'number'" v-model="item[field.key]" type="number" class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-slate-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors" />
                            <input v-else-if="field.type === 'date'" v-model="item[field.key]" type="date" class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-slate-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors" />
                            <textarea v-else-if="field.type === 'textarea'" v-model="item[field.key]" rows="4" class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-slate-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors resize-none"></textarea>
                            <p v-if="form.errors['field_values.items.' + itemIndex + '.' + field.key]" class="text-red-400 text-sm mt-1">{{ form.errors['field_values.items.' + itemIndex + '.' + field.key] }}</p>
                        </div>
                        </div>
                        <button type="button" class="w-full mt-4 px-4 py-2.5 border border-indigo-400/40 text-indigo-300 hover:bg-indigo-500/10 text-sm font-medium" @click="addItem">+ Tambah Item</button>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex flex-wrap gap-3">
                    <button type="submit" :disabled="form.processing" class="px-6 py-3 bg-slate-700 hover:bg-slate-600 disabled:opacity-50 text-white text-sm font-medium rounded-xl transition-colors">
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Draft' }}
                    </button>
                    <button type="button" @click="submitAndSign" :disabled="form.processing" class="px-6 py-3 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white text-sm font-medium rounded-xl transition-colors shadow-lg shadow-emerald-500/25">
                        Tanda Tangani &amp; Kirim ke AM
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
