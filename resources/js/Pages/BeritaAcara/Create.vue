<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { useSweetAlert } from '@/composables/useSweetAlert';
import { SaveOutlined, SendOutlined, PlusOutlined, DeleteOutlined } from '@ant-design/icons-vue';

const { success } = useSweetAlert();

const form = useForm({
    title: '',
    meta: {
        direktorat: 'Operasional',
        divisi: 'Support',
        perihal: 'Berita Acara Permohonan',
        lampiran: '-',
        kepada_nama: '',
        kepada_jabatan: '',
    },
    pengantar: 'Sehubungan dengan adanya berita acara ini, saya ingin memberitahukan bahwa...',
    rincian_data: [
        { label: 'Nama', value: '' },
        { label: 'NIK', value: '' },
        { label: 'Cabang', value: '' },
        { label: 'Jabatan', value: '' }
    ],
    keterangan_tambahan: 'Adapun alasan mengajukan...',
    penutup: 'Demikian berita acara ini dibuat agar dapat dipergunakan sebagai mana mestinya. Terima kasih atas perhatiannya dan kerjasamanya.',
    attachment: null,
    submit_after_save: false,
});

const selectAttachment = (event) => {
    form.attachment = event.target.files[0] || null;
};

const addRincian = () => {
    form.rincian_data.push({ label: '', value: '' });
};

const removeRincian = (index) => {
    form.rincian_data.splice(index, 1);
};

const submit = () => {
    form.submit_after_save = false;
    form.transform(data => ({ ...data, attachment: form.attachment }))
        .post(route('berita-acara.store'), {
            forceFormData: true,
            onSuccess: () => success('Draft Berita Acara berhasil disimpan.'),
        });
};

const submitAndSign = () => {
    form.submit_after_save = true;
    form.transform(data => ({ ...data, attachment: form.attachment }))
        .post(route('berita-acara.store'), { forceFormData: true });
};
</script>


<template>
    <Head title="Buat Berita Acara" />
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-bold mb-0">Buat Berita Acara Baru</h1>
        </template>

        <a-form layout="vertical" @finish="submit" class="memo-create-page">
            <a-row :gutter="24">
                <a-col :xs="24" :lg="10" class="mb-6">
                    <div class="flex flex-col gap-6">
                        <!-- Informasi Header -->
                        <a-card :bordered="false" class="rounded-lg shadow-sm">
                            <h2 class="text-sm font-semibold mb-4">Informasi Dokumen</h2>

                            <a-form-item label="Judul / Perihal Utama" class="mb-3"
                                :validateStatus="form.errors.title ? 'error' : ''"
                                :help="form.errors.title"
                            >
                                <a-input v-model:value="form.title" placeholder="Contoh: Permohonan Resign Karyawan" size="large" />
                            </a-form-item>
                            
                            <a-form-item label="Direktorat" class="mb-3">
                                <a-input v-model:value="form.meta.direktorat" />
                            </a-form-item>
                            
                            <a-form-item label="Divisi" class="mb-3">
                                <a-input v-model:value="form.meta.divisi" />
                            </a-form-item>
                            
                            <a-form-item label="Perihal" class="mb-3">
                                <a-input v-model:value="form.meta.perihal" />
                            </a-form-item>
                            
                            <a-form-item label="Lampiran" class="mb-3">
                                <a-input v-model:value="form.meta.lampiran" />
                            </a-form-item>

                            <div class="border-t border-gray-100 my-4"></div>
                            
                            <h2 class="text-sm font-semibold mb-4">Penerima Dokumen (Kepada Yth)</h2>
                            
                            <a-form-item label="Nama Penerima" class="mb-3">
                                <a-input v-model:value="form.meta.kepada_nama" placeholder="Contoh: Ibu Ella Safitri" />
                            </a-form-item>
                            
                            <a-form-item label="Jabatan Penerima" class="mb-0">
                                <a-input v-model:value="form.meta.kepada_jabatan" placeholder="Contoh: SPV HC Payroll" />
                            </a-form-item>
                        </a-card>

                        <!-- Lampiran Word -->
                        <a-card :bordered="false" class="rounded-lg shadow-sm">
                            <h2 class="text-sm font-semibold mb-1">Lampiran Dokumen Word</h2>
                            <p class="text-xs text-gray-400 mb-3">Unggah berkas pendukung dalam format .doc atau .docx (Maks. 10MB).</p>
                            <input
                                type="file"
                                @change="selectAttachment"
                                accept=".doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                            />
                            <p v-if="form.attachment" class="mt-2 text-xs text-green-600">✓ {{ form.attachment.name }}</p>
                        </a-card>
                    </div>
                </a-col>

                <a-col :xs="24" :lg="14">
                    <div class="flex flex-col gap-6">
                        <a-card :bordered="false" class="rounded-lg shadow-sm">
                            <h2 class="text-sm font-semibold mb-4">Isi Berita Acara</h2>
                            
                            <a-form-item label="Kalimat Pengantar (Sehubungan dengan...)" class="mb-4">
                                <a-textarea v-model:value="form.pengantar" :rows="3" />
                            </a-form-item>

                            <div class="border border-gray-200 rounded p-4 mb-4">
                                <h3 class="font-semibold text-sm mb-3">Data Rincian</h3>
                                <div v-for="(item, index) in form.rincian_data" :key="index" class="flex gap-3 mb-2 items-center">
                                    <a-input v-model:value="item.label" placeholder="Label (Misal: NIK)" style="width: 35%;" />
                                    <span class="text-gray-400">:</span>
                                    <a-input v-model:value="item.value" placeholder="Isi Data" class="flex-1" />
                                    <a-button type="text" danger @click="removeRincian(index)" class="flex-shrink-0">
                                        <template #icon><delete-outlined /></template>
                                    </a-button>
                                </div>
                                <a-button type="dashed" block class="mt-2" @click="addRincian">
                                    <template #icon><plus-outlined /></template>
                                    Tambah Baris Data
                                </a-button>
                            </div>

                            <a-form-item label="Keterangan Tambahan / Alasan" class="mb-4">
                                <a-textarea v-model:value="form.keterangan_tambahan" :rows="4" placeholder="Misal: Rincian pinalty, atau penjelasan lebih lanjut..." />
                            </a-form-item>
                            
                            <a-form-item label="Kalimat Penutup" class="mb-0">
                                <a-textarea v-model:value="form.penutup" :rows="2" />
                            </a-form-item>
                        </a-card>

                        <!-- Action Buttons -->
                        <div class="pt-2 flex flex-col sm:flex-row justify-end gap-3">
                            <a-button size="large" type="default" @click="submit">
                                <template #icon><save-outlined /></template>
                                Simpan Draft
                            </a-button>
                            <a-button size="large" type="primary" @click="submitAndSign" class="bg-green-600 hover:bg-green-500 border-green-600">
                                <template #icon><send-outlined /></template>
                                Tanda Tangani & Kirim
                            </a-button>
                        </div>
                    </div>
                </a-col>
            </a-row>
        </a-form>
    </AuthenticatedLayout>
</template>
