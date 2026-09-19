<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Profile" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-white leading-tight">Profil Saya</h1>
        </template>

        <div class="max-w-4xl space-y-6">
            <a-card :bordered="false" class="bg-slate-800/50 border border-white/5 shadow-none">
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    class="max-w-xl"
                />
            </a-card>

            <a-card :bordered="false" class="bg-slate-800/50 border border-white/5 shadow-none">
                <UpdatePasswordForm class="max-w-xl" />
            </a-card>

            <a-card :bordered="false" class="bg-slate-800/50 border border-white/5 shadow-none">
                <DeleteUserForm class="max-w-xl" />
            </a-card>
        </div>
    </AuthenticatedLayout>
</template>
