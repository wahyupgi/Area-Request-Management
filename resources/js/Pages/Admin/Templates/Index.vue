<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { PlusOutlined, EditOutlined, CheckCircleOutlined, CloseCircleOutlined } from '@ant-design/icons-vue';

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

        <div class="admin-master-page max-w-6xl">
            <a-card :bordered="false" class="bg-slate-800/50 border border-white/5">
                <template #title>
                    <span class="text-white font-medium">Daftar Template ({{ templates.length }})</span>
                </template>
                <template #extra>
                    <Link :href="route('admin.templates.create')">
                        <a-button type="primary">
                            <template #icon><PlusOutlined /></template>
                            Buat Template
                        </a-button>
                    </Link>
                </template>

                <a-list :data-source="templates" item-layout="vertical" class="custom-dark-list">
                    <template #renderItem="{ item }">
                        <a-list-item class="border-b border-white/5 last:border-0 py-4">
                            <template #actions>
                                <a-button type="link" class="admin-action-button" :class="item.is_active ? 'admin-action-delete' : 'admin-action-success'" @click="toggleActive(item)">
                                    <template #icon>
                                        <CloseCircleOutlined v-if="item.is_active" />
                                        <CheckCircleOutlined v-else />
                                    </template>
                                    {{ item.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </a-button>
                                <Link :href="route('admin.templates.edit', item.id)">
                                    <a-button type="link" class="admin-action-button admin-action-edit">
                                        <template #icon><EditOutlined /></template>
                                        Edit
                                    </a-button>
                                </Link>
                            </template>

                            <a-list-item-meta>
                                <template #title>
                                    <div class="flex items-center gap-3">
                                        <span class="text-white font-semibold text-base">{{ item.name }}</span>
                                        <a-tag v-if="item.category" color="blue">{{ item.category }}</a-tag>
                                        <a-tag :color="item.is_active ? 'success' : 'error'">
                                            {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                                        </a-tag>
                                    </div>
                                </template>
                                <template #description>
                                    <span class="text-slate-400">{{ item.field_schema?.length || 0 }} field · Dibuat oleh {{ item.creator?.name }}</span>
                                </template>
                            </a-list-item-meta>
                            
                            <div class="flex flex-wrap gap-2 mt-2">
                                <a-tag v-for="f in item.field_schema" :key="f.key" color="default" class="bg-slate-700/50 border-slate-600 text-slate-300">
                                    {{ f.label }} <span class="text-slate-500">({{ f.type }})</span>
                                    <span v-if="f.required" class="text-red-400 ml-1">*</span>
                                </a-tag>
                            </div>
                        </a-list-item>
                    </template>
                </a-list>
            </a-card>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
:deep(.custom-dark-list .ant-list-item-meta-title) {
    margin-bottom: 4px;
}
</style>
