<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useSweetAlert } from '@/composables/useSweetAlert';

const props = defineProps({
    users: Array,
    areas: Array,
    branches: Array,
});

const showForm = ref(false);
const editingId = ref(null);
const form = useForm({
    name: '',
    username: '',
    email: '',
    password: '',
    role: 'KC',
    branch_id: '',
    area_id: '',
});
const { confirm } = useSweetAlert();

const roleConfig = {
    KC: { label: 'Kepala Cabang' },
    AM: { label: 'Area Manager' },
    ADMIN: { label: 'Administrator' },
};

const resetForm = () => {
    editingId.value = null;
    form.reset();
    form.role = 'KC';
    form.clearErrors();
};

const openCreate = () => {
    resetForm();
    showForm.value = true;
};

const openEdit = (user) => {
    editingId.value = user.id;
    form.name = user.name;
    form.username = user.username;
    form.email = user.email;
    form.password = '';
    form.role = user.role;
    form.branch_id = user.branch_id || '';
    form.area_id = user.area_id || '';
    form.clearErrors();
    showForm.value = true;
};

const closeForm = () => {
    showForm.value = false;
    resetForm();
};

const save = () => {
    const options = {
        onSuccess: closeForm,
    };

    if (editingId.value) {
        form.put(route('admin.users.update', editingId.value), options);
        return;
    }

    form.post(route('admin.users.store'), {
        ...options,
        onSuccess: closeForm,
    });
};

const remove = async (user) => {
    if (!await confirm(`Hapus user ${user.username}?`, 'User yang dihapus tidak dapat dipulihkan.')) return;

    form.delete(route('admin.users.destroy', user.id), {
        preserveScroll: true,
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
                <button @click="showForm ? closeForm() : openCreate()" class="flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-xl transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Tambah User
                </button>
            </div>

            <div v-if="showForm" class="bg-indigo-500/5 border border-indigo-500/20 rounded-2xl p-6 mb-6">
                <h3 class="text-lg font-semibold text-white mb-4">{{ editingId ? 'Edit User' : 'Tambah User Baru' }}</h3>
                <form @submit.prevent="save" class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Nama</label>
                        <input v-model="form.name" type="text" class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
                        <p v-if="form.errors.name" class="text-red-400 text-xs mt-1">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Username</label>
                        <input v-model="form.username" type="text" class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
                        <p v-if="form.errors.username" class="text-red-400 text-xs mt-1">{{ form.errors.username }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Email</label>
                        <input v-model="form.email" type="email" class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
                        <p v-if="form.errors.email" class="text-red-400 text-xs mt-1">{{ form.errors.email }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Password{{ editingId ? ' Baru (opsional)' : '' }}</label>
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
                        <button type="button" @click="closeForm" class="px-5 py-3 bg-slate-700 hover:bg-slate-600 text-white text-sm font-medium rounded-xl transition-colors">Batal</button>
                    </div>
                </form>
            </div>

            <div class="bg-slate-800/50 border border-white/5 rounded-2xl overflow-hidden">
                <table class="w-full table-head-pgi">
                    <thead>
                        <tr class="border-b border-white/5">
                            <th class="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-6 py-4">Nama</th>
                            <th class="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-6 py-4">Username</th>
                            <th class="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-6 py-4">Email</th>
                            <th class="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-6 py-4">Role</th>
                            <th class="text-left text-xs font-medium text-slate-400 uppercase tracking-wider px-6 py-4">Cabang / Area</th>
                            <th class="text-center text-xs font-medium text-slate-400 uppercase tracking-wider px-6 py-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="u in users" :key="u.id" class="border-b border-white/5 last:border-0 hover:bg-white/[0.02]">
                            <td class="px-6 py-4 text-sm text-white font-medium">{{ u.name }}</td>
                            <td class="px-6 py-4 text-sm text-slate-300">{{ u.username }}</td>
                            <td class="px-6 py-4 text-sm text-slate-400">{{ u.email }}</td>
                            <td class="px-6 py-4 text-sm text-slate-300">{{ roleConfig[u.role]?.label || u.role }}</td>
                            <td class="px-6 py-4 text-sm text-slate-400">{{ u.branch?.name || u.area?.name || '-' }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <button type="button" title="Edit user" class="p-2 text-sky-400 hover:text-sky-300 hover:bg-sky-500/10" @click="openEdit(u)">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16.862 3.487a2.25 2.25 0 113.182 3.182L7.5 19.213 3 20.5l1.287-4.5L16.862 3.487z" /></svg>
                                    </button>
                                    <button type="button" title="Hapus user" class="p-2 text-red-400 hover:text-red-300 hover:bg-red-500/10" @click="remove(u)">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 7h12m-9 0V5a1 1 0 011-1h4a1 1 0 011 1v2m2 0v12a1 1 0 01-1 1H7a1 1 0 01-1-1V7m3 4v5m4-5v5" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
