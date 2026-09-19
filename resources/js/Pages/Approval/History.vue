<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MemoDocument from '@/Components/MemoDocument.vue';
import { Head } from '@inertiajs/vue3';
import { 
    ArrowLeftOutlined,
    CheckCircleOutlined,
    CloseCircleOutlined
} from '@ant-design/icons-vue';

const props = defineProps({ memo: Object });
</script>

<template>
    <Head :title="'Riwayat: ' + memo.title" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <a-button type="text" shape="circle" onclick="history.back()">
                    <template #icon><arrow-left-outlined /></template>
                </a-button>
                <h1 class="text-xl font-bold text-gray-800 mb-0">Riwayat Approval</h1>
            </div>
        </template>

        <a-row :gutter="[24, 24]" class="max-w-7xl mx-auto">
            <!-- Left Column: Memo Document -->
            <a-col :xs="24" :lg="16" class="flex justify-center">
                <div class="w-full max-w-[210mm] shadow-md bg-white">
                    <MemoDocument :memo="memo" :show-am-signature="memo.status === 'approved'" />
                </div>
            </a-col>

            <!-- Right Column: Approval History -->
            <a-col :xs="24" :lg="8">
                <a-card :bordered="false" class="rounded-lg shadow-sm mb-6 bg-gray-50">
                    <h2 class="text-lg font-bold text-gray-800 mb-1">{{ memo.title }}</h2>
                    <p class="text-sm text-gray-500 mb-0">{{ memo.code }} · {{ memo.template?.name }}</p>
                </a-card>

                <a-card title="Timeline Approval" :bordered="false" class="rounded-lg shadow-sm">
                    <a-timeline>
                        <a-timeline-item v-for="(approval, idx) in memo.approvals" :key="approval.id" :color="approval.action === 'approved' ? 'green' : 'red'">
                            <template #dot>
                                <check-circle-outlined v-if="approval.action === 'approved'" />
                                <close-circle-outlined v-else />
                            </template>
                            
                            <div class="mb-4 bg-white border border-gray-100 rounded-lg p-4 shadow-sm" :class="approval.action === 'approved' ? 'border-green-100' : 'border-red-100'">
                                <div class="flex items-center justify-between mb-2">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800 mb-0">{{ approval.approver?.name }}</p>
                                        <p class="text-xs text-gray-500 mb-0">{{ new Date(approval.created_at).toLocaleString('id-ID') }}</p>
                                    </div>
                                    <a-tag :color="approval.action === 'approved' ? 'success' : 'error'" class="uppercase m-0">
                                        {{ approval.action }}
                                    </a-tag>
                                </div>
                                
                                <div v-if="approval.notes" class="text-sm text-gray-600 bg-gray-50 p-3 rounded mt-3">
                                    {{ approval.notes }}
                                </div>
                                
                                <div v-if="approval.signature" class="mt-3">
                                    <img :src="'/storage/' + approval.signature.signature_image" alt="Signature" class="h-16 rounded border border-gray-200 bg-white p-1" />
                                </div>
                            </div>
                        </a-timeline-item>
                    </a-timeline>
                </a-card>
            </a-col>
        </a-row>
    </AuthenticatedLayout>
</template>
