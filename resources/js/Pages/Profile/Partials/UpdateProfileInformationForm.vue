<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: Boolean,
    status: String,
});

const user = usePage().props.auth.user;
const form = useForm({
    username: user.username,
    email: user.email,
});

const updateProfile = () => {
    form.patch(route('profile.update'));
};
</script>

<template>
    <section class="profile-form-section">
        <header class="profile-form-header">
            <h2>Informasi Profil</h2>
            <p>Ubah username dan alamat email akun Anda.</p>
        </header>

        <a-form layout="vertical" class="profile-ant-form">
            <a-form-item
                label="Username"
                :validate-status="form.errors.username ? 'error' : ''"
                :help="form.errors.username"
            >
                <a-input
                    v-model:value="form.username"
                    autocomplete="username"
                    autofocus
                    required
                />
            </a-form-item>

            <a-form-item
                label="Email"
                :validate-status="form.errors.email ? 'error' : ''"
                :help="form.errors.email"
            >
                <a-input
                    v-model:value="form.email"
                    type="email"
                    autocomplete="email"
                    required
                />
            </a-form-item>

            <a-alert
                v-if="mustVerifyEmail && user.email_verified_at === null"
                type="warning"
                show-icon
                class="profile-verification-alert"
            >
                <template #message>Email Anda belum diverifikasi.</template>
                <template #description>
                    <Link :href="route('verification.send')" method="post" as="button" class="profile-verification-link">
                        Kirim ulang tautan verifikasi
                    </Link>
                    <span v-if="status === 'verification-link-sent'" class="profile-verification-success">
                        Tautan verifikasi baru telah dikirim.
                    </span>
                </template>
            </a-alert>

            <div class="profile-form-actions">
                <a-button type="primary" :loading="form.processing" @click="updateProfile">
                    Simpan Perubahan
                </a-button>
                <a-typography-text v-if="form.recentlySuccessful" type="success">
                    Perubahan tersimpan.
                </a-typography-text>
            </div>
        </a-form>
    </section>
</template>

<style>
.profile-form-section {
    color: #e2e8f0;
}

.profile-form-header {
    margin-bottom: 24px;
}

.profile-form-header h2 {
    margin: 0;
    color: #f8fafc;
    font-size: 18px;
    font-weight: 600;
}

.profile-form-header p {
    margin: 6px 0 0;
    color: #94a3b8;
}

.profile-form-actions {
    display: flex;
    align-items: center;
    gap: 16px;
}

.profile-verification-alert {
    margin-bottom: 24px;
}

.profile-verification-link {
    padding: 0;
    color: #60a5fa;
    background: none;
    border: 0;
    cursor: pointer;
    text-decoration: underline;
}

.profile-verification-success {
    display: block;
    margin-top: 8px;
    color: #22c55e;
}

html.theme-light .profile-form-section,
html.theme-light .profile-form-header h2 {
    color: #0f172a;
}

html.theme-light .profile-form-header p {
    color: #64748b;
}

html.theme-light .profile-verification-link {
    color: #1677ff;
}
</style>