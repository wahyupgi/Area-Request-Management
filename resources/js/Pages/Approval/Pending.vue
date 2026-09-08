<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({ memos: Array });
</script>

<template>
    <Head title="Pending Approval" />
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-bold text-white">Memo Menunggu Persetujuan</h1>
        </template>

        <div v-if="memos.length === 0" class="bg-slate-800/50 border border-white/5 rounded-2xl px-6 py-16 text-center">
            <svg class="w-16 h-16 text-slate-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="text-slate-500">Semua memo sudah diproses. Tidak ada yang menunggu.</p>
        </div>

        <div v-else class="space-y-3">
            <Link
                v-for="memo in memos"
                :key="memo.id"
                :href="route('approvals.review', memo.id)"
                class="block bg-slate-800/50 border border-white/5 rounded-2xl p-6 hover:bg-slate-800/80 hover:border-indigo-500/20 transition-all duration-200 group"
            >
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="text-xs font-mono text-slate-500">{{ memo.code }}</span>
                            <span class="text-xs bg-amber-500/20 text-amber-400 px-2.5 py-0.5 rounded-full font-medium">Pending</span>
                            <span class="text-xs text-slate-500">{{ memo.template?.name }}</span>
                        </div>
                        <h3 class="text-lg text-white font-semibold group-hover:text-indigo-400 transition-colors">{{ memo.title }}</h3>
                        <div class="flex items-center gap-4 mt-2 text-sm text-slate-500">
                            <span>Dari: {{ memo.creator?.name }}</span>
                            <span>Cabang: {{ memo.branch?.name }}</span>
                            <span>{{ memo.attachments?.length || 0 }} lampiran</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 text-slate-500 group-hover:text-indigo-400 transition-colors mt-2">
                        <span class="text-sm font-medium">Review</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </div>
            </Link>
        </div>
    </AuthenticatedLayout>
</template>
