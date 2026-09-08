<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    users: Array,
    areas: Array,
    branches: Array,
});

const showForm = ref(false);
const form = useForm({
    name: '',
    email: '',
    password: '',
    role: 'KC',
    branch_id: '',
    area_id: '',
});

const roleConfig = {
    KC: { label: 'Kepala Cabang', class: 'bg-blue-500/20 text-blue-400' },
    AM: { label: 'Area Manager', class: 'bg-emerald-500/20 text-emerald-400' },
    ADMIN: { label: 'Administrator', class: 'bg-purple-500/20 text-purple-400' },
};

const save = () => {
    form.post(route('admin.users.store'), {
        onSuccess: () => { showForm.value = false; form.reset(); },
    });
};
</script>

<template>
    <Head title="Kelola User" />
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-bold text-white">Kelola User</h1>
        </template>

        <div>
            <div class="flex items-center justify-between mb-6">
                <p class="text-sm text-slate-400">{{ users.length }} user terdaftar</p>
                <button @click="showForm = !showForm" class="flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-xl transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Tambah User
                </button>
            </div>

            <div v-if="showForm" class="bg-indigo-500/5 border border-indigo-500/20 rounded-2xl p-6 mb-6">
                <h3 class="text-lg font-semibold text-white mb-4">Tambah User Baru</h3>
                <form @submit.prevent="save" class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Nama</label>
                        <input v-model="form.name" type="text" class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
                        <p v-if="form.errors.name" class="text-red-400 text-xs mt-1">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Email</label>
                        <input v-model="form.email" type="email" class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
                        <p v-if="form.errors.email" class="text-red-400 text-xs mt-1">{{ form.errors.email }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Password</label>
                        <input v-model="form.password" type="password" class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Role</label>
                        <select v-model="form.role" class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            <option value="KC">Kepala Cabang</option>
                            <option value="AM">Area Manager</option>
                            <option value="ADMIN">Administrator</option>
                        </select>
                    </div>
                    <div v-if="form.role === 'KC'">
                        <label class="block text-sm font-medium text-slate-300 mb-2">Cabang</label>
                        <select v-model="form.branch_id" class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            <option value="">-- Pilih Cabang --</option>
                            <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                        </select>
                    </div>
                    <div v-if="form.role === 'AM'">
                        <label class="block text-sm font-medium text-slate-300 mb-2">Area</label>
                        <select v-model="form.area_id" class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            <option value="">-- Pilih Area --</option>
                            <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                        </select>
                    </div>
                    <div class="col-span-2 flex gap-3 pt-2">
                        <button type="submit" :disabled="form.processing" class="px-5 py-3 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-xl transition-colors">Simpan</button>
                        <button type="button" @click="showForm = false" class="px-5 py-3 bg-slate-700 hover:bg-slate-600 text-white text-sm font-medium rounded-xl transition-colors">Batal</button>
                    </div>
                </form>
            </div>

            <div class="bg-slate-800/50 border border-white/5 rounded-2xl overflow-hidden">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-white/5">
                            <th class="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-6 py-4">Nama</th>
                            <th class="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-6 py-4">Email</th>
                            <th class="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-6 py-4">Role</th>
                            <th class="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-6 py-4">Cabang / Area</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="u in users" :key="u.id" class="border-b border-white/5 last:border-0 hover:bg-white/[0.02]">
                            <td class="px-6 py-4 text-sm text-white font-medium">{{ u.name }}</td>
                            <td class="px-6 py-4 text-sm text-slate-400">{{ u.email }}</td>
                            <td class="px-6 py-4">
                                <span :class="[roleConfig[u.role]?.class, 'text-xs font-semibold px-3 py-1 rounded-full']">{{ roleConfig[u.role]?.label }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-400">{{ u.branch?.name || u.area?.name || '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
