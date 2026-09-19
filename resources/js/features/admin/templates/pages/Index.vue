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

        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="flex items-center justify-between gap-4 border-b border-slate-200 bg-slate-50 px-6 py-4">
                <h2 class="text-xl font-bold text-slate-800">Daftar Template ({{ templates.length }})</h2>
                <Link :href="route('admin.templates.create')" class="inline-flex items-center gap-2 rounded-xl bg-[#1f69a8] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-[#185a97]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Buat Template
                </Link>
            </div>

            <div class="divide-y divide-slate-200">
                <div v-for="t in templates" :key="t.id" class="px-6 py-5">
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2.5 mb-2">
                                <h3 class="text-xl font-bold text-slate-800">{{ t.name }}</h3>
                                <span v-if="t.category" class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-1 text-[11px] font-semibold text-blue-700 border border-blue-200">
                                    {{ t.category }}
                                </span>
                                <span :class="[t.is_active ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-red-200 bg-red-50 text-red-700', 'inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-semibold']">
                                    {{ t.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>
                            <p class="text-sm text-slate-500">{{ t.field_schema?.length || 0 }} field · Dibuat oleh {{ t.creator?.name }}</p>
                        </div>

                        <div class="flex items-center gap-2">
                            <button @click="toggleActive(t)" :class="[t.is_active ? 'border-red-200 bg-red-50 text-red-600 hover:bg-red-100' : 'border-emerald-200 bg-emerald-50 text-emerald-600 hover:bg-emerald-100', 'rounded-xl border px-3 py-2 text-xs font-semibold transition-colors']">
                                {{ t.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                            <Link :href="route('admin.templates.edit', t.id)" class="rounded-xl border border-sky-200 bg-sky-50 px-3 py-2 text-xs font-semibold text-sky-700 transition-colors hover:bg-sky-100">Edit</Link>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <span v-for="f in t.field_schema" :key="f.key" class="inline-flex items-center rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs font-medium text-slate-700">
                            {{ f.label }}
                            <span class="ml-1 text-slate-500">({{ f.type }})</span>
                            <span v-if="f.required" class="ml-1 text-red-500">*</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
