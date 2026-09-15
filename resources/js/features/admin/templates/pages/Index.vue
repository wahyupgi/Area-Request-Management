<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({ templates: Array });

const toggleActive = (template) => {
    router.post(route('admin.templates.toggle', template.id));
};
</script>

<template>
    <Head title="Kelola Template" />
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-bold text-white">Kelola Template Memo</h1>
        </template>

        <div class="flex items-center justify-between mb-6">
            <p class="text-sm text-slate-400">{{ templates.length }} template terdaftar</p>
            <Link :href="route('admin.templates.create')" class="flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-xl transition-colors shadow-lg shadow-indigo-500/25">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Buat Template
            </Link>
        </div>

        <div class="space-y-3">
            <div v-for="t in templates" :key="t.id" class="bg-slate-800/50 border border-white/5 rounded-2xl p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-3 mb-1">
                            <h3 class="text-white font-semibold">{{ t.name }}</h3>
                            <span v-if="t.category" class="text-xs bg-indigo-500/20 text-indigo-400 px-2.5 py-0.5 rounded-full">{{ t.category }}</span>
                            <span :class="[t.is_active ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400', 'text-xs px-2.5 py-0.5 rounded-full font-medium']">
                                {{ t.is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>
                        <p class="text-sm text-slate-500">{{ t.field_schema?.length || 0 }} field · Dibuat oleh {{ t.creator?.name }}</p>
                        <div class="flex flex-wrap gap-2 mt-3">
                            <span v-for="f in t.field_schema" :key="f.key" class="text-xs bg-slate-700/50 text-slate-400 px-2.5 py-1 rounded-lg">
                                {{ f.label }} <span class="text-slate-600">({{ f.type }})</span>
                                <span v-if="f.required" class="text-red-400">*</span>
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="toggleActive(t)" :class="[t.is_active ? 'text-red-400 hover:bg-red-500/10' : 'text-emerald-400 hover:bg-emerald-500/10', 'px-3 py-2 text-sm rounded-xl transition-colors']">
                            {{ t.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                        </button>
                        <Link :href="route('admin.templates.edit', t.id)" class="px-3 py-2 text-sm text-indigo-400 hover:bg-indigo-500/10 rounded-xl transition-colors">Edit</Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
