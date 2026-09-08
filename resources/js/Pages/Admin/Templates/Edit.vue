<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

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
    form.field_schema[index].key = label.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');
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

        <div class="max-w-3xl">
            <form @submit.prevent="submit" class="space-y-6">
                <div class="bg-slate-800/50 border border-white/5 rounded-2xl p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Nama Template</label>
                        <input v-model="form.name" type="text" class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors" />
                        <p v-if="form.errors.name" class="text-red-400 text-sm mt-1">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Kategori</label>
                        <input v-model="form.category" type="text" class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors" />
                    </div>
                </div>

                <div class="bg-slate-800/50 border border-white/5 rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-white">Field Schema</h2>
                        <button type="button" @click="addField" class="flex items-center gap-1 text-sm text-indigo-400 hover:text-indigo-300">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Tambah Field
                        </button>
                    </div>
                    <div class="space-y-4">
                        <div v-for="(field, idx) in form.field_schema" :key="idx" class="bg-slate-700/30 rounded-xl p-4">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs text-slate-500 font-medium">Field #{{ idx + 1 }}</span>
                                <button v-if="form.field_schema.length > 1" type="button" @click="removeField(idx)" class="text-red-400 hover:text-red-300 text-xs">Hapus</button>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs text-slate-400 mb-1">Label</label>
                                    <input v-model="field.label" @blur="autoKey(idx)" type="text" class="w-full bg-slate-700/50 border border-white/10 rounded-lg px-3 py-2 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
                                </div>
                                <div>
                                    <label class="block text-xs text-slate-400 mb-1">Key</label>
                                    <input v-model="field.key" type="text" class="w-full bg-slate-700/50 border border-white/10 rounded-lg px-3 py-2 text-white text-sm font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
                                </div>
                                <div>
                                    <label class="block text-xs text-slate-400 mb-1">Tipe</label>
                                    <select v-model="field.type" class="w-full bg-slate-700/50 border border-white/10 rounded-lg px-3 py-2 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                        <option value="text">Text</option>
                                        <option value="textarea">Textarea</option>
                                        <option value="number">Number</option>
                                        <option value="date">Date</option>
                                        <option value="select">Select</option>
                                    </select>
                                </div>
                                <div class="flex items-end">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input v-model="field.required" type="checkbox" class="w-4 h-4 rounded bg-slate-700 border-white/10 text-indigo-600 focus:ring-indigo-500" />
                                        <span class="text-sm text-slate-300">Wajib diisi</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" :disabled="form.processing" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white text-sm font-medium rounded-xl transition-colors shadow-lg shadow-indigo-500/25">
                    {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                </button>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
