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
    field_values: { items: [{}] },
    submit_after_save: false,
});

const selectedTemplate = ref(null);

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
    form.field_values = { items: [{}] };
});

const addItem = () => {
    form.field_values.items.push({});
};

const removeItem = (index) => {
    if (form.field_values.items.length > 1) {
        form.field_values.items.splice(index, 1);
    }
};

const submit = () => {
    form.submit_after_save = false;
    form.post(route('memos.store'));
};

const submitAndSign = () => {
    form.submit_after_save = true;
    form.post(route('memos.store'));
};
</script>

<template>
    <Head title="Buat Memo Baru" />
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-bold text-white">Buat Memo Baru</h1>
        </template>

        <div class="max-w-3xl">
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Template Selection -->
                <div class="bg-slate-800/50 border border-white/5 rounded-2xl p-6">
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

                <!-- Title -->
                <div class="bg-slate-800/50 border border-white/5 rounded-2xl p-6">
                    <label class="block text-sm font-medium text-slate-300 mb-2">Judul Memo</label>
                    <input v-model="form.title" type="text" placeholder="Masukkan judul memo..." class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-slate-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors" />
                    <p v-if="form.errors.title" class="text-red-400 text-sm mt-2">{{ form.errors.title }}</p>
                </div>

                <!-- Dynamic Fields -->
                <div v-if="selectedTemplate" class="bg-slate-800/50 border border-white/5 rounded-2xl p-6">
                    <div v-for="(item, itemIndex) in form.field_values.items" :key="itemIndex" class="border border-white/10 p-4 space-y-4">
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
                    <button type="button" class="mt-4 px-4 py-2 border border-indigo-400/40 text-indigo-300 hover:bg-indigo-500/10 text-sm font-medium" @click="addItem">+ Tambah Item</button>
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
