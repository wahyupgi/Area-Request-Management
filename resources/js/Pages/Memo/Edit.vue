<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    memo: Object,
    templates: Array,
    signature: Object,
});

const form = useForm({
    title: props.memo.title || '',
    field_values: props.memo.field_values || {},
});

const fileInput = ref(null);
const uploading = ref(false);
const showSignatureDialog = ref(false);
const signatureFile = ref(null);
const signaturePreview = ref(null);
const submittingMemo = ref(false);

const save = () => {
    form.put(route('memos.update', props.memo.id));
};

const submitMemo = () => {
    showSignatureDialog.value = true;
};

const selectSignature = (event) => {
    const file = event.target.files[0];
    signatureFile.value = file || null;

    if (!file) {
        signaturePreview.value = null;
        return;
    }

    const reader = new FileReader();
    reader.onload = (event) => { signaturePreview.value = event.target.result; };
    reader.readAsDataURL(file);
};

const confirmSubmit = () => {
    if (!props.signature && !signatureFile.value) return;

    submittingMemo.value = true;
    const formData = new FormData();
    if (signatureFile.value) formData.append('signature_image', signatureFile.value);

    router.post(route('memos.submit', props.memo.id), formData, {
        forceFormData: true,
        onFinish: () => { submittingMemo.value = false; },
    });
};

const uploadFile = () => {
    const file = fileInput.value?.files[0];
    if (!file) return;

    uploading.value = true;
    const formData = new FormData();
    formData.append('file', file);

    router.post(route('memos.attachments.upload', props.memo.id), formData, {
        forceFormData: true,
        onFinish: () => {
            uploading.value = false;
            fileInput.value.value = '';
        },
    });
};

const deleteAttachment = (attachment) => {
    if (confirm('Hapus lampiran ini?')) {
        router.delete(route('memos.attachments.delete', [props.memo.id, attachment.id]));
    }
};

const deleteMemo = () => {
    if (confirm('Hapus memo ini? Tindakan ini tidak bisa dibatalkan.')) {
        router.delete(route('memos.destroy', props.memo.id));
    }
};
</script>

<template>
    <Head :title="'Edit: ' + memo.title" />
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-bold text-white">Edit Memo</h1>
        </template>

        <div class="max-w-3xl space-y-6">
            <!-- Rejection Notes -->
            <div v-if="memo.status === 'rejected' && memo.approvals?.length > 0" class="bg-red-500/10 border border-red-500/20 rounded-2xl p-5">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    <div>
                        <h3 class="text-sm font-semibold text-red-400">Memo Ditolak</h3>
                        <p class="text-sm text-red-300/70 mt-1">{{ memo.approvals[0]?.notes }}</p>
                        <p class="text-xs text-red-400/50 mt-2">Oleh: {{ memo.approvals[0]?.approver?.name }}</p>
                    </div>
                </div>
            </div>

            <!-- Memo Info -->
            <div class="bg-slate-800/50 border border-white/5 rounded-2xl p-6">
                <div class="flex items-center gap-3 mb-4">
                    <span class="text-xs font-mono text-slate-500 bg-slate-700/50 px-3 py-1 rounded-lg">{{ memo.code }}</span>
                    <span :class="[memo.status === 'rejected' ? 'bg-red-500/20 text-red-400' : 'bg-slate-500/20 text-slate-400', 'text-xs font-semibold px-3 py-1 rounded-full']">
                        {{ memo.status }}
                    </span>
                </div>
                <p class="text-sm text-slate-400">Template: <span class="text-slate-300">{{ memo.template?.name }}</span></p>
            </div>

            <!-- Edit Form -->
            <form @submit.prevent="save" class="space-y-6">
                <div class="bg-slate-800/50 border border-white/5 rounded-2xl p-6">
                    <label class="block text-sm font-medium text-slate-300 mb-2">Judul Memo</label>
                    <input v-model="form.title" type="text" class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors" />
                </div>

                <div v-if="memo.template" class="bg-slate-800/50 border border-white/5 rounded-2xl p-6">
                    <h2 class="text-lg font-semibold text-white mb-4">Detail Memo</h2>
                    <div class="space-y-4">
                        <div v-for="field in memo.template.field_schema" :key="field.key">
                            <label class="block text-sm font-medium text-slate-300 mb-2">
                                {{ field.label }}
                                <span v-if="field.required" class="text-red-400">*</span>
                            </label>
                            <input v-if="field.type === 'text'" v-model="form.field_values[field.key]" type="text" class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors" />
                            <input v-else-if="field.type === 'number'" v-model="form.field_values[field.key]" type="number" class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors" />
                            <input v-else-if="field.type === 'date'" v-model="form.field_values[field.key]" type="date" class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors" />
                            <textarea v-else-if="field.type === 'textarea'" v-model="form.field_values[field.key]" rows="4" class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors resize-none"></textarea>
                            <p v-if="form.errors['field_values.' + field.key]" class="text-red-400 text-sm mt-1">{{ form.errors['field_values.' + field.key] }}</p>
                        </div>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button type="submit" :disabled="form.processing" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white text-sm font-medium rounded-xl transition-colors">
                        Simpan Draft
                    </button>
                    <button type="button" @click="submitMemo" class="px-6 py-3 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-medium rounded-xl transition-colors shadow-lg shadow-emerald-500/25">
                        Submit ke AM
                    </button>
                </div>
            </form>

            <!-- Digital signature confirmation before submission -->
            <div v-if="showSignatureDialog" class="bg-slate-800/70 border border-emerald-500/20 rounded-2xl p-6 shadow-sm">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-white">Tanda Tangan Digital KC</h2>
                        <p class="text-sm text-slate-400 mt-1">Masukkan tanda tangan terlebih dahulu sebelum memo dikirim ke Area Manager.</p>
                    </div>
                    <button type="button" @click="showSignatureDialog = false" class="text-slate-400 hover:text-white text-xl leading-none" aria-label="Tutup">&times;</button>
                </div>

                <div v-if="signature && !signatureFile" class="flex items-center gap-4 mb-4 p-3 rounded-xl bg-slate-900/40 border border-white/5">
                    <img :src="'/storage/' + signature.signature_image" alt="Tanda tangan digital KC" class="h-14 w-32 object-contain bg-white/10 rounded-lg p-1" />
                    <div>
                        <p class="text-sm text-emerald-400 font-medium">Tanda tangan terdaftar</p>
                        <p class="text-xs text-slate-400 mt-1">Tanda tangan ini akan digunakan untuk memo.</p>
                    </div>
                </div>

                <label class="block text-sm font-medium text-slate-300 mb-2">{{ signature ? 'Ganti tanda tangan (opsional)' : 'Upload tanda tangan' }}</label>
                <input type="file" @change="selectSignature" accept="image/png,image/jpeg" class="text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-indigo-600 file:text-white hover:file:bg-indigo-500 file:cursor-pointer" />
                <p class="text-xs text-slate-500 mt-1">Format PNG/JPG, maksimal 2MB.</p>
                <img v-if="signaturePreview" :src="signaturePreview" alt="Preview tanda tangan digital KC" class="h-16 mt-4 object-contain bg-white/10 rounded-lg p-1" />
                <p v-if="$page.props.errors?.signature" class="text-red-400 text-sm mt-2">{{ $page.props.errors.signature }}</p>

                <div class="flex gap-3 mt-5">
                    <button type="button" @click="showSignatureDialog = false" class="px-5 py-2.5 bg-slate-700 hover:bg-slate-600 text-slate-200 text-sm font-medium rounded-xl transition-colors">Batal</button>
                    <button type="button" @click="confirmSubmit" :disabled="submittingMemo || (!signature && !signatureFile)" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white text-sm font-medium rounded-xl transition-colors">{{ submittingMemo ? 'Mengirim...' : 'Tanda Tangani & Kirim ke AM' }}</button>
                </div>
            </div>

            <!-- Attachments -->
            <div class="bg-slate-800/50 border border-white/5 rounded-2xl p-6">
                <h2 class="text-lg font-semibold text-white mb-4">Lampiran</h2>
                <div v-if="memo.attachments?.length > 0" class="space-y-2 mb-4">
                    <div v-for="att in memo.attachments" :key="att.id" class="flex items-center justify-between bg-slate-700/30 rounded-xl px-4 py-3">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                            <span class="text-sm text-slate-300">{{ att.original_name || att.file_path }}</span>
                        </div>
                        <button @click="deleteAttachment(att)" class="text-red-400 hover:text-red-300 text-sm">Hapus</button>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <input ref="fileInput" type="file" @change="uploadFile" class="text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-indigo-600 file:text-white hover:file:bg-indigo-500 file:cursor-pointer" />
                    <span v-if="uploading" class="text-sm text-slate-400">Mengupload...</span>
                </div>
            </div>

            <!-- Delete -->
            <div v-if="memo.status === 'draft'" class="flex justify-end">
                <button @click="deleteMemo" class="px-4 py-2 text-sm text-red-400 hover:text-red-300 hover:bg-red-500/10 rounded-xl transition-colors">
                    Hapus Memo
                </button>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
