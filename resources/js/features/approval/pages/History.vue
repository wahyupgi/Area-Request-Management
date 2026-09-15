<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({ memo: Object });
</script>

<template>
    <Head :title="'Riwayat: ' + memo.title" />
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-bold text-white">Riwayat Approval</h1>
        </template>

        <div class="max-w-3xl space-y-6">
            <div class="bg-slate-800/50 border border-white/5 rounded-2xl p-6">
                <h2 class="text-lg font-bold text-white">{{ memo.title }}</h2>
                <p class="text-sm text-slate-400 mt-1">{{ memo.code }} · {{ memo.template?.name }}</p>
            </div>

            <div class="space-y-4">
                <div v-for="(approval, idx) in memo.approvals" :key="approval.id" class="relative">
                    <div v-if="idx < memo.approvals.length - 1" class="absolute left-6 top-12 bottom-0 w-px bg-white/10"></div>
                    <div :class="[approval.action === 'approved' ? 'bg-emerald-500/5 border-emerald-500/20' : 'bg-red-500/5 border-red-500/20', 'border rounded-2xl p-6']">
                        <div class="flex items-center gap-3 mb-3">
                            <div :class="[approval.action === 'approved' ? 'bg-emerald-500' : 'bg-red-500', 'w-10 h-10 rounded-full flex items-center justify-center']">
                                <svg v-if="approval.action === 'approved'" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <svg v-else class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-white">{{ approval.approver?.name }}</p>
                                <p class="text-xs text-slate-500">{{ new Date(approval.created_at).toLocaleString('id-ID') }}</p>
                            </div>
                            <span :class="[approval.action === 'approved' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400', 'text-xs font-semibold px-3 py-1 rounded-full ml-auto uppercase']">{{ approval.action }}</span>
                        </div>
                        <p v-if="approval.notes" class="text-sm text-slate-300 mt-2 pl-13">{{ approval.notes }}</p>
                        <div v-if="approval.signature" class="mt-3 pl-13">
                            <img :src="'/storage/' + approval.signature.signature_image" alt="Signature" class="h-16 rounded-lg bg-white/5 p-2" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
