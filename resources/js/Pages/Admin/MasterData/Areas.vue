<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useSweetAlert } from '@/composables/useSweetAlert';

const props = defineProps({ areas: Array });

const showForm = ref(false);
const editingId = ref(null);
const form = useForm({ name: '' });
const { confirm } = useSweetAlert();

const openCreate = () => { form.reset(); editingId.value = null; showForm.value = true; };
const openEdit = (area) => { form.name = area.name; editingId.value = area.id; showForm.value = true; };

const save = () => {
    if (editingId.value) {
        form.put(route('admin.areas.update', editingId.value), { onSuccess: () => { showForm.value = false; } });
    } else {
        form.post(route('admin.areas.store'), { onSuccess: () => { showForm.value = false; form.reset(); } });
    }
};

const deleteArea = async (area) => {
    if (await confirm('Hapus area ' + area.name + '?', 'Area yang dihapus tidak dapat dipulihkan.')) {
        router.delete(route('admin.areas.destroy', area.id));
    }
};
</script>

<template>
    <Head title="Kelola Area" />
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-bold text-white">Kelola Area</h1>
        </template>

        <div class="max-w-2xl">
            <div class="flex items-center justify-between mb-6">
                <p class="text-sm text-slate-400">{{ areas.length }} area terdaftar</p>
                <button @click="openCreate" class="flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-xl transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Tambah Area
                </button>
            </div>

            <!-- Inline Form -->
            <div v-if="showForm" class="bg-indigo-500/5 border border-indigo-500/20 rounded-2xl p-5 mb-4">
                <form @submit.prevent="save" class="flex items-end gap-3">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-slate-300 mb-2">Nama Area</label>
                        <input v-model="form.name" type="text" placeholder="Contoh: Area Jakarta" class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
                        <p v-if="form.errors.name" class="text-red-400 text-sm mt-1">{{ form.errors.name }}</p>
                    </div>
                    <button type="submit" :disabled="form.processing" class="px-5 py-3 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-xl transition-colors">{{ editingId ? 'Update' : 'Simpan' }}</button>
                    <button type="button" @click="showForm = false" class="px-5 py-3 bg-slate-700 hover:bg-slate-600 text-white text-sm font-medium rounded-xl transition-colors">Batal</button>
                </form>
            </div>

            <div class="space-y-2">
                <div v-for="area in areas" :key="area.id" class="bg-slate-800/50 border border-white/5 rounded-2xl px-6 py-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-white font-medium">{{ area.name }}</h3>
                        <p class="text-xs text-slate-500">{{ area.branches_count }} cabang</p>
                    </div>
                    <div class="flex gap-2">
                        <button @click="openEdit(area)" class="text-sm text-indigo-400 hover:text-indigo-300 px-3 py-1 rounded-lg hover:bg-indigo-500/10 transition-colors">Edit</button>
                        <button @click="deleteArea(area)" class="text-sm text-red-400 hover:text-red-300 px-3 py-1 rounded-lg hover:bg-red-500/10 transition-colors">Hapus</button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
