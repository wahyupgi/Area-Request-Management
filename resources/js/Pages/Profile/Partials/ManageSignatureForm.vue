<script setup>
import { useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { DeleteOutlined, SaveOutlined, UploadOutlined } from '@ant-design/icons-vue';
import { message, Modal } from 'ant-design-vue';

const props = defineProps({
    signature: {
        type: Object,
        default: null,
    },
});

const form = useForm({
    signature_image: null,
    certificate_no: props.signature?.certificate_no || '',
});
const preview = ref(null);

const beforeUpload = (file) => {
    const image = file.originFileObj || file;
    if (!['image/jpeg', 'image/png'].includes(image.type)) {
        message.error('Hanya bisa mengunggah file JPG atau PNG.');
        return false;
    }

    if (image.size / 1024 / 1024 >= 2) {
        message.error('Ukuran gambar harus lebih kecil dari 2MB.');
        return false;
    }

    form.signature_image = image;
    const reader = new FileReader();
    reader.onload = (event) => { preview.value = event.target.result; };
    reader.readAsDataURL(image);
    return false;
};

const clearPreview = () => {
    form.signature_image = null;
    preview.value = null;
};

const save = () => {
    if (!form.signature_image) {
        message.error('Silakan pilih gambar tanda tangan terlebih dahulu.');
        return;
    }

    form.post(route('signature.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => clearPreview(),
    });
};

const remove = () => {
    Modal.confirm({
        title: 'Hapus tanda tangan digital?',
        content: 'Tanda tangan ini akan dihapus dari akun Anda.',
        okText: 'Hapus',
        okType: 'danger',
        cancelText: 'Batal',
        onOk: () => router.delete(route('signature.destroy'), { preserveScroll: true }),
    });
};
</script>

<template>
    <a-card :bordered="false" class="profile-signature-card">
        <template #title>
            <span class="profile-signature-title font-medium">Tanda Tangan Digital</span>
        </template>

        <a-row v-if="signature" :gutter="[16, 16]" align="middle" class="profile-signature-current">
            <a-col :xs="24" :sm="8">
                <div class="profile-signature-image-wrap">
                    <img :src="`/storage/${signature.signature_image}`" alt="Tanda tangan digital saat ini" class="profile-signature-image" />
                </div>
            </a-col>
            <a-col :xs="24" :sm="16">
                <a-space align="center" size="middle">
                    <span class="profile-signature-status">Tanda tangan tersimpan</span>
                    <a-button danger @click="remove">
                        <template #icon><DeleteOutlined /></template>
                        Hapus
                    </a-button>
                </a-space>
            </a-col>
        </a-row>

        <a-form layout="vertical">
            <a-form-item
                label="Gambar Tanda Tangan"
                :validate-status="form.errors.signature_image ? 'error' : ''"
                :help="form.errors.signature_image || 'Format PNG/JPG, maksimal 2MB.'"
            >
                <a-upload-dragger
                    :multiple="false"
                    :before-upload="beforeUpload"
                    :show-upload-list="false"
                    class="profile-signature-dragger"
                >
                    <p class="ant-upload-drag-icon"><UploadOutlined /></p>
                    <p class="ant-upload-text">Klik atau seret gambar tanda tangan ke sini</p>
                </a-upload-dragger>
            </a-form-item>

            <a-row v-if="preview" align="middle" :gutter="[12, 12]" class="profile-signature-preview">
                <a-col>
                    <img :src="preview" alt="Pratinjau tanda tangan" class="profile-signature-preview-image" />
                </a-col>
                <a-col>
                    <a-button type="text" danger aria-label="Hapus pratinjau" @click="clearPreview">
                        <template #icon><DeleteOutlined /></template>
                    </a-button>
                </a-col>
            </a-row>

            <a-form-item label="Nomor Sertifikat (opsional)" :validate-status="form.errors.certificate_no ? 'error' : ''" :help="form.errors.certificate_no">
                <a-input v-model:value="form.certificate_no" placeholder="Contoh: CERT-JKT-001" />
            </a-form-item>

            <a-button
                type="primary"
                html-type="button"
                class="profile-signature-save"
                :loading="form.processing"
                :disabled="!form.signature_image"
                @click="save"
            >
                <template #icon><SaveOutlined /></template>
                Simpan Tanda Tangan
            </a-button>
        </a-form>
    </a-card>
</template>

<style>
.profile-signature-card {
    background-color: rgba(30, 41, 59, 0.5) !important;
    border: 1px solid rgba(255, 255, 255, 0.05) !important;
    box-shadow: none;
}

.profile-signature-current {
    margin-bottom: 24px;
}

.profile-signature-image-wrap {
    display: flex;
    min-height: 96px;
    min-width: 192px;
    align-items: center;
    justify-content: center;
    padding: 16px;
    border-radius: 8px;
    background: #ffffff;
}

.profile-signature-image,
.profile-signature-preview-image {
    max-width: 192px;
    height: 64px;
    object-fit: contain;
}

.profile-signature-preview {
    margin-bottom: 20px;
}

.profile-signature-preview-image {
    padding: 8px;
    border-radius: 6px;
    background: #ffffff;
}

.profile-signature-card .ant-card-head,
.profile-signature-card .profile-signature-title {
    color: #f8fafc !important;
    border-color: rgba(255, 255, 255, 0.08);
}

.profile-signature-card .profile-signature-status,
.profile-signature-card .ant-form-item-label > label {
    color: #e2e8f0 !important;
}

.profile-signature-card .ant-form-item-extra,
.profile-signature-card .ant-form-item-explain,
.profile-signature-card .profile-signature-dragger .ant-upload-hint {
    color: #94a3b8 !important;
}

.profile-signature-card .profile-signature-dragger.ant-upload-drag {
    background: rgba(30, 41, 59, 0.7) !important;
    border-color: #475569 !important;
}

.profile-signature-card .profile-signature-dragger .ant-upload-text {
    color: #e2e8f0 !important;
}

.profile-signature-card .profile-signature-dragger .ant-upload-drag-icon {
    color: #7dd3fc;
}

.profile-signature-card .ant-input {
    color: #f8fafc !important;
    background-color: #1e293b !important;
    border-color: #475569 !important;
}

.profile-signature-card .ant-input::placeholder {
    color: #94a3b8 !important;
}

.profile-signature-card .profile-signature-save.ant-btn-primary {
    color: #ffffff !important;
    background-color: #1677ff !important;
    border-color: #1677ff !important;
    font-family: inherit;
    font-size: 14px;
    font-weight: 600;
}

.profile-signature-card .profile-signature-save.ant-btn-primary:not(:disabled):hover {
    color: #ffffff !important;
    background-color: #4096ff !important;
    border-color: #4096ff !important;
}

html.theme-light .profile-signature-card {
    background-color: #ffffff !important;
    border-color: #e2e8f0 !important;
}

html.theme-light .profile-signature-card .ant-card-head,
html.theme-light .profile-signature-card .profile-signature-title {
    color: #0f172a !important;
    border-color: #e2e8f0;
}

html.theme-light .profile-signature-card .profile-signature-status,
html.theme-light .profile-signature-card .ant-form-item-label > label {
    color: #334155 !important;
}

html.theme-light .profile-signature-card .ant-form-item-extra,
html.theme-light .profile-signature-card .ant-form-item-explain,
html.theme-light .profile-signature-card .profile-signature-dragger .ant-upload-hint {
    color: #64748b !important;
}

html.theme-light .profile-signature-card .profile-signature-dragger.ant-upload-drag {
    background: #f8fafc !important;
    border-color: #cbd5e1 !important;
}

html.theme-light .profile-signature-card .profile-signature-dragger .ant-upload-text {
    color: #334155 !important;
}

html.theme-light .profile-signature-card .profile-signature-dragger .ant-upload-drag-icon {
    color: #1677ff;
}

html.theme-light .profile-signature-card .ant-input {
    color: #0f172a !important;
    background-color: #ffffff !important;
    border-color: #cbd5e1 !important;
}

html.theme-light .profile-signature-card .ant-input::placeholder {
    color: #64748b !important;
}
</style>