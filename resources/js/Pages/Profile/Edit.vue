<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import ManageSignatureForm from './Partials/ManageSignatureForm.vue';
import { Head, usePage } from '@inertiajs/vue3';

const page = usePage();

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
            <h1 class="profile-page-title">Profil Saya</h1>
        </template>

        <div class="profile-page-content">
            <a-space direction="vertical" size="large" class="profile-page-sections">
            <a-card :bordered="false" class="profile-page-card">
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                />
            </a-card>

            <ManageSignatureForm
                v-if="['AM', 'KC'].includes(page.props.auth.user.role)"
                :signature="page.props.signature"
            />

            <a-card :bordered="false" class="profile-page-card">
                <UpdatePasswordForm />
            </a-card>

            <a-card :bordered="false" class="profile-page-card">
                <DeleteUserForm />
            </a-card>
            </a-space>
        </div>
    </AuthenticatedLayout>
</template>

<style>
.profile-page-title {
    margin: 0;
    color: #ffffff;
    font-size: 20px;
    font-weight: 600;
    line-height: 1.4;
}

.profile-page-content {
    width: 100%;
    max-width: 900px;
    margin: 0 auto;
}

.profile-page-sections {
    width: 100%;
}

.profile-page-card {
    width: 100%;
    color: #e2e8f0;
    background: rgba(30, 41, 59, 0.5) !important;
    border: 1px solid rgba(255, 255, 255, 0.05);
}

.profile-page-card .ant-card-body {
    padding: 24px;
}

html.theme-light .profile-page-title {
    color: #0f172a;
}

html.theme-light .profile-page-card {
    color: #334155;
    background: #ffffff !important;
    border-color: #e2e8f0;
}
</style>
