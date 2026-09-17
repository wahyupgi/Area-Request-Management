<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useSweetAlert } from '@/composables/useSweetAlert';

const props = defineProps({ signature: Object });

const form = useForm({
    signature_image: null,
    certificate_no: '',
});

const preview = ref(null);
const { confirm } = useSweetAlert();

const onFileChange = (e) => {
    const file = e.target.files[0];
    form.signature_image = file;
    if (file) {
        const reader = new FileReader();
        reader.onload = (ev) => { preview.value = ev.target.result; };
        reader.readAsDataURL(file);
    }
};

const save = () => {
    form.post(route('signature.store'), {
        forceFormData: true,
    });
};

const remove = async () => {
    if (await confirm('Hapus tanda tangan digital Anda?', 'Tanda tangan digital akan dihapus dari akun Anda.')) {
        router.delete(route('signature.destroy'));
    }
};
</script>

<template>
    <Head title="Tanda Tangan Digital" />
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-bold text-white">Tanda Tangan Digital</h1>
        </template>

        <div class="max-w-2xl space-y-6">
            <!-- Current Signature -->
            <div v-if="signature" class="bg-slate-800/50 border border-white/5 rounded-2xl p-6">
                <h2 class="text-lg font-semibold text-white mb-4">Tanda Tangan Saat Ini</h2>
                <div class="flex items-center gap-6">
                    <div class="bg-white/10 rounded-2xl p-6">
                        <img :src="'/storage/' + signature.signature_image" alt="Signature" class="h-24 object-contain" />
                    </div>
                    <div>
                        <p class="text-sm text-emerald-400 font-medium">✓ Terdaftar</p>
                        <p v-if="signature.certificate_no" class="text-sm text-slate-400 mt-1">No. Sertifikat: {{ signature.certificate_no }}</p>
                        <p class="text-xs text-slate-500 mt-1">Dibuat: {{ new Date(signature.created_at).toLocaleDateString('id-ID') }}</p>
                        <button @click="remove" class="mt-3 text-sm text-red-400 hover:text-red-300 transition-colors">Hapus Tanda Tangan</button>
                    </div>
                </div>
            </div>

            <!-- Upload New -->
            <div class="bg-slate-800/50 border border-white/5 rounded-2xl p-6">
                <h2 class="text-lg font-semibold text-white mb-4">{{ signature ? 'Ganti Tanda Tangan' : 'Upload Tanda Tangan' }}</h2>
                <form @submit.prevent="save" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">File Gambar Tanda Tangan</label>
                        <input type="file" @change="onFileChange" accept="image/png,image/jpeg" class="text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-indigo-600 file:text-white hover:file:bg-indigo-500 file:cursor-pointer" />
                        <p class="text-xs text-slate-500 mt-1">Format: PNG atau JPG. Maksimal 2MB. Disarankan background transparan (PNG).</p>
                        <p v-if="form.errors.signature_image" class="text-red-400 text-sm mt-1">{{ form.errors.signature_image }}</p>
                    </div>

                    <div v-if="preview" class="bg-white/10 rounded-xl p-4 inline-block">
                        <img :src="preview" alt="Preview" class="h-20 object-contain" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Nomor Sertifikat (opsional)</label>
                        <input v-model="form.certificate_no" type="text" placeholder="Contoh: CERT-JKT-001" class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors" />
                    </div>

                    <button type="submit" :disabled="form.processing || !form.signature_image" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white text-sm font-medium rounded-xl transition-colors shadow-lg shadow-indigo-500/25">
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Tanda Tangan' }}
                    </button>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
