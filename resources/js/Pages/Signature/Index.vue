<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { UploadOutlined, DeleteOutlined, SaveOutlined } from '@ant-design/icons-vue';
import { Modal, message } from 'ant-design-vue';

const props = defineProps({ signature: Object });

const form = useForm({
    signature_image: null,
    certificate_no: '',
});

const preview = ref(null);
const selectedFile = ref(null);

const assignSignatureFile = (file) => {
    if (!file) {
        selectedFile.value = null;
        form.signature_image = null;
        preview.value = null;
        return;
    }

    const isJpgOrPng = file.type === 'image/jpeg' || file.type === 'image/png';
    if (!isJpgOrPng) {
        message.error('Hanya bisa mengunggah file JPG/PNG!');
        return;
    }

    const isLt2M = file.size / 1024 / 1024 < 2;
    if (!isLt2M) {
        message.error('Gambar harus lebih kecil dari 2MB!');
        return;
    }

    selectedFile.value = file;
    form.signature_image = file;

    const reader = new FileReader();
    reader.onload = (e) => {
        preview.value = e.target.result;
    };
    reader.readAsDataURL(file);
};

const beforeUpload = (file) => {
    assignSignatureFile(file);
    return false;
};

const handleRemoveImage = () => {
    selectedFile.value = null;
    form.signature_image = null;
    preview.value = null;
};

const save = () => {
    if (!selectedFile.value && !form.signature_image) {
        message.error('Silakan unggah gambar tanda tangan terlebih dahulu.');
        return;
    }

    const payload = new FormData();
    payload.append('signature_image', selectedFile.value || form.signature_image);
    if (form.certificate_no) {
        payload.append('certificate_no', form.certificate_no);
    }

    router.post(route('signature.store'), payload, {
        forceFormData: true,
        onSuccess: () => {
            message.success('Tanda tangan berhasil disimpan.');
            preview.value = null;
            selectedFile.value = null;
            form.reset();
        },
        onError: (errors) => {
            const firstError = Array.isArray(errors?.signature_image) ? errors.signature_image[0] : null;
            if (firstError) {
                message.error(firstError);
            }
        },
    });
};

const remove = () => {
    Modal.confirm({
        title: 'Hapus tanda tangan digital Anda?',
        content: 'Tanda tangan digital akan dihapus dari akun Anda secara permanen.',
        okText: 'Hapus',
        okType: 'danger',
        cancelText: 'Batal',
        onOk() {
            router.delete(route('signature.destroy'), {
                onSuccess: () => message.success('Tanda tangan berhasil dihapus.')
            });
        }
    });
};
</script>

<template>
    <Head title="Tanda Tangan Digital" />
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-bold text-white">Tanda Tangan Digital</h1>
        </template>

        <div class="am-signature-page signature-page max-w-3xl space-y-6">
            <!-- Current Signature -->
            <a-card v-if="signature" :bordered="false" class="bg-slate-800/50 border border-white/5">
                <template #title>
                    <span class="text-white font-medium">Tanda Tangan Saat Ini</span>
                </template>
                <div class="flex flex-col sm:flex-row items-center gap-6">
                    <div class="bg-white/10 rounded-2xl p-6 flex items-center justify-center min-w-[200px]">
                        <img :src="'/storage/' + signature.signature_image" alt="Signature" class="h-24 object-contain" />
                    </div>
                    <div class="flex-1 w-full">
                        <a-tag color="success" class="mb-2">✓ Terdaftar</a-tag>
                        <a-descriptions :column="1" size="small" class="custom-dark-descriptions">
                            <a-descriptions-item v-if="signature.certificate_no" label="No. Sertifikat">
                                {{ signature.certificate_no }}
                            </a-descriptions-item>
                            <a-descriptions-item label="Dibuat Pada">
                                {{ new Date(signature.created_at).toLocaleDateString('id-ID') }}
                            </a-descriptions-item>
                        </a-descriptions>
                        <div class="mt-4">
                            <a-button danger @click="remove">
                                <template #icon><DeleteOutlined /></template>
                                Hapus Tanda Tangan
                            </a-button>
                        </div>
                    </div>
                </div>
            </a-card>

            <!-- Upload New -->
            <a-card :bordered="false" class="bg-slate-800/50 border border-white/5">
                <template #title>
                    <span class="text-white font-medium">{{ signature ? 'Ganti Tanda Tangan' : 'Upload Tanda Tangan' }}</span>
                </template>
                
                <a-form layout="vertical" @finish="save">
                    <a-form-item 
                        label="File Gambar Tanda Tangan" 
                        :validateStatus="form.errors.signature_image ? 'error' : ''" 
                        :help="form.errors.signature_image || 'Format: PNG atau JPG. Maksimal 2MB. Disarankan background transparan (PNG).'"
                    >
                        <a-upload-dragger
                            name="file"
                            :multiple="false"
                            :before-upload="beforeUpload"
                            :show-upload-list="false"
                            class="custom-dragger"
                        >
                            <p class="ant-upload-drag-icon">
                                <UploadOutlined style="color: #6366f1;" />
                            </p>
                            <p class="ant-upload-text" style="color: #cbd5e1;">Klik atau seret file ke area ini</p>
                            <p class="ant-upload-hint" style="color: #94a3b8;">
                                Mendukung file tunggal upload. Hindari mengunggah data rahasia tanpa enkripsi.
                            </p>
                        </a-upload-dragger>
                    </a-form-item>

                    <!-- Preview -->
                    <div v-if="preview" class="mb-6">
                        <p class="text-sm text-slate-300 mb-2">Pratinjau:</p>
                        <div class="bg-white/10 rounded-xl p-4 inline-block relative border border-white/10">
                            <img :src="preview" alt="Preview" class="h-20 object-contain" />
                            <a-button 
                                type="primary" 
                                danger 
                                shape="circle" 
                                size="small" 
                                class="absolute -top-2 -right-2"
                                @click="handleRemoveImage"
                            >
                                <template #icon><DeleteOutlined /></template>
                            </a-button>
                        </div>
                    </div>

                    <a-form-item label="Nomor Sertifikat (opsional)">
                        <a-input v-model:value="form.certificate_no" placeholder="Contoh: CERT-JKT-001" size="large" />
                    </a-form-item>

                    <div class="pt-2">
                        <a-button type="primary" html-type="button" :loading="form.processing" size="large" :disabled="!selectedFile && !form.signature_image" @click.prevent="save">
                            <template #icon><SaveOutlined /></template>
                            Simpan Tanda Tangan
                        </a-button>
                    </div>
                </a-form>
            </a-card>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
:deep(.custom-dark-descriptions .ant-descriptions-item-label) {
    color: #94a3b8;
}
:deep(.custom-dark-descriptions .ant-descriptions-item-content) {
    color: #f1f5f9;
    font-weight: 500;
}
:deep(.custom-dragger.ant-upload-drag) {
    background-color: rgba(30, 41, 59, 0.5) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
}
:deep(.custom-dragger.ant-upload-drag:hover) {
    border-color: #6366f1 !important;
}
</style>
