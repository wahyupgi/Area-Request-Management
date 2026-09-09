<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    branches: Array,
    areas: Array,
    kcUsers: Array,
});

const showForm = ref(false);
const form = useForm({ name: '', area_id: '', kc_user_id: '' });

const save = () => {
    form.post(route('admin.branches.store'), {
        onSuccess: () => { showForm.value = false; form.reset(); },
    });
};
</script>

<template>
    <Head title="Kelola Cabang" />
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-bold text-white">Kelola Cabang</h1>
        </template>

        <div class="max-w-3xl">
            <div class="flex items-center justify-between mb-6">
                <p class="text-sm text-slate-400">{{ branches.length }} cabang terdaftar</p>
                <button @click="showForm = !showForm" class="flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-xl transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Tambah Cabang
                </button>
            </div>

            <div v-if="showForm" class="bg-indigo-500/5 border border-indigo-500/20 rounded-2xl p-6 mb-4">
                <form @submit.prevent="save" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Nama Cabang</label>
                        <input v-model="form.name" type="text" placeholder="Contoh: Cabang Menteng" class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Area</label>
                        <select v-model="form.area_id" class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            <option value="">-- Pilih Area --</option>
                            <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Kepala Cabang (opsional)</label>
                        <select v-model="form.kc_user_id" class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            <option value="">-- Belum ditetapkan --</option>
                            <option v-for="u in kcUsers" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                    </div>
                    <div class="flex gap-3">
                        <button type="submit" :disabled="form.processing" class="px-5 py-3 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-xl transition-colors">Simpan</button>
                        <button type="button" @click="showForm = false" class="px-5 py-3 bg-slate-700 hover:bg-slate-600 text-white text-sm font-medium rounded-xl transition-colors">Batal</button>
                    </div>
                </form>
            </div>

            <div class="bg-slate-800/50 border border-white/5 rounded-2xl overflow-hidden">
                <table class="w-full table-head-pgi">
                    <thead>
                        <tr class="border-b border-white/5">
                            <th class="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-6 py-4">Cabang</th>
                            <th class="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-6 py-4">Area</th>
                            <th class="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-6 py-4">Kepala Cabang</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="b in branches" :key="b.id" class="border-b border-white/5 last:border-0">
                            <td class="px-6 py-4 text-sm text-white font-medium">{{ b.name }}</td>
                            <td class="px-6 py-4 text-sm text-slate-400">{{ b.area?.name }}</td>
                            <td class="px-6 py-4 text-sm text-slate-400">{{ b.kc_user?.name || '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
