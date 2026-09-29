<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const confirmingUserDeletion = ref(false);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => document.getElementById('profile-delete-password')?.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.reset();
};
</script>

<template>
    <section class="profile-delete-section">
        <header class="profile-form-header">
            <h2>Hapus Akun</h2>
            <p>Penghapusan akun akan menghapus seluruh data secara permanen dan tidak dapat dibatalkan.</p>
        </header>

        <a-button danger @click="confirmUserDeletion">Hapus Akun</a-button>

        <a-modal
            v-model:open="confirmingUserDeletion"
            wrap-class-name="profile-delete-modal"
            title="Konfirmasi Hapus Akun"
            ok-text="Hapus Akun"
            cancel-text="Batal"
            ok-type="danger"
            :confirm-loading="form.processing"
            @ok="deleteUser"
            @cancel="closeModal"
        >
            <p>Masukkan password untuk menghapus akun secara permanen.</p>
            <a-form-item
                label="Password"
                :validate-status="form.errors.password ? 'error' : ''"
                :help="form.errors.password"
            >
                <a-input-password
                    id="profile-delete-password"
                    v-model:value="form.password"
                    autocomplete="current-password"
                    @press-enter="deleteUser"
                />
            </a-form-item>
        </a-modal>
    </section>
</template>

<style>
.profile-delete-section {
    color: #e2e8f0;
}

.profile-delete-section .profile-form-header h2 {
    color: #f8fafc;
}

.profile-delete-section .profile-form-header p {
    color: #94a3b8;
}

html.theme-light .profile-delete-section {
    color: #334155;
}

html.theme-light .profile-delete-section .profile-form-header h2 {
    color: #0f172a;
}

html.theme-light .profile-delete-section .profile-form-header p {
    color: #64748b;
}

html:not(.theme-light) .profile-delete-modal .ant-modal-content {
    color: #e2e8f0;
    background: #1e293b;
}

html:not(.theme-light) .profile-delete-modal .ant-modal-header,
html:not(.theme-light) .profile-delete-modal .ant-modal-footer {
    background: #1e293b;
    border-color: #334155;
}

html:not(.theme-light) .profile-delete-modal .ant-modal-title,
html:not(.theme-light) .profile-delete-modal .ant-modal-close {
    color: #f8fafc;
}

html:not(.theme-light) .profile-delete-modal .ant-modal-body,
html:not(.theme-light) .profile-delete-modal .ant-form-item-label > label {
    color: #e2e8f0;
}

html:not(.theme-light) .profile-delete-modal .ant-input,
html:not(.theme-light) .profile-delete-modal .ant-input-affix-wrapper {
    color: #f8fafc;
    background: #0f172a;
    border-color: #475569;
}
</style>
