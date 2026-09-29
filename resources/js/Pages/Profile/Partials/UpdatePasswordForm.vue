<script setup>
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const focusField = (id) => document.getElementById(id)?.focus();

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                focusField('profile-new-password');
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                focusField('profile-current-password');
            }
        },
    });
};
</script>

<template>
    <section class="profile-form-section">
        <header class="profile-form-header">
            <h2>Ganti Password</h2>
            <p>Gunakan password yang kuat untuk menjaga keamanan akun Anda.</p>
        </header>

        <a-form layout="vertical" class="profile-ant-form">
            <a-form-item
                label="Password Saat Ini"
                :validate-status="form.errors.current_password ? 'error' : ''"
                :help="form.errors.current_password"
            >
                <a-input-password
                    id="profile-current-password"
                    v-model:value="form.current_password"
                    autocomplete="current-password"
                />
            </a-form-item>

            <a-form-item
                label="Password Baru"
                :validate-status="form.errors.password ? 'error' : ''"
                :help="form.errors.password"
            >
                <a-input-password
                    id="profile-new-password"
                    v-model:value="form.password"
                    autocomplete="new-password"
                />
            </a-form-item>

            <a-form-item
                label="Konfirmasi Password Baru"
                :validate-status="form.errors.password_confirmation ? 'error' : ''"
                :help="form.errors.password_confirmation"
            >
                <a-input-password
                    v-model:value="form.password_confirmation"
                    autocomplete="new-password"
                />
            </a-form-item>

            <div class="profile-form-actions">
                <a-button type="primary" :loading="form.processing" @click="updatePassword">
                    Simpan Password
                </a-button>
                <a-typography-text v-if="form.recentlySuccessful" type="success">
                    Password berhasil diubah.
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

html.theme-light .profile-form-section,
html.theme-light .profile-form-header h2 {
    color: #0f172a;
}

html.theme-light .profile-form-header p {
    color: #64748b;
}
</style>
