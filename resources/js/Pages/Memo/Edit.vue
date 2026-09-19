<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useSweetAlert } from '@/composables/useSweetAlert';

const props = defineProps({
    memo: Object,
    templates: Array,
    signature: Object,
    openSignature: Boolean,
});

const initialItems = Array.isArray(props.memo.field_values?.items)
    ? props.memo.field_values.items
    : [props.memo.field_values || {}];

const form = useForm({
    code: props.memo.code || '',
    template_id: props.memo.template_id,
    title: props.memo.title || '',
    field_values: {
        ...(props.memo.field_values || {}),
        pengantar: props.memo.field_values?.pengantar || `Sehubungan dengan pengajuan ${props.memo.template?.name || props.memo.title || 'memo ini'}, saya ingin mengajukan permintaan dengan rincian sebagai berikut:`,
        items: initialItems,
        meta: {
            direktorat: props.memo.field_values?.meta?.direktorat || 'Regional Branch Office',
            divisi: props.memo.field_values?.meta?.divisi || 'Branch Leader',
            perihal: props.memo.field_values?.meta?.perihal || '',
            lampiran: props.memo.field_values?.meta?.lampiran || '',
        },
    },
});

const fileInput = ref(null);
const uploading = ref(false);
const showSignatureDialog = ref(props.openSignature);
const signatureFile = ref(null);
const signaturePreview = ref(null);
const submittingMemo = ref(false);
const { confirm } = useSweetAlert();

const save = () => {
    form.put(route('memos.update', props.memo.id));
};

const submitMemo = () => {
    showSignatureDialog.value = true;
};

const addItem = () => {
    form.field_values.items.push({});
};

const removeItem = (index) => {
    if (form.field_values.items.length > 1) {
        form.field_values.items.splice(index, 1);
    }
};

const addTableRow = (item, fieldKey, columns) => {
    if (!Array.isArray(item[fieldKey])) {
        item[fieldKey] = [];
    }
    const emptyRow = {};
    columns.forEach(col => { emptyRow[col.key] = ''; });
    item[fieldKey].push(emptyRow);
};

const removeTableRow = (item, fieldKey, rowIndex) => {
    if (Array.isArray(item[fieldKey]) && item[fieldKey].length > 1) {
        item[fieldKey].splice(rowIndex, 1);
    }
};

const ensureTableRows = (item, fieldKey, columns) => {
    if (!Array.isArray(item[fieldKey]) || item[fieldKey].length === 0) {
        const emptyRow = {};
        columns.forEach(col => { emptyRow[col.key] = ''; });
        item[fieldKey] = [emptyRow];
    }
    return item[fieldKey];
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

const deleteAttachment = async (attachment) => {
    if (await confirm('Hapus lampiran ini?', 'Lampiran akan dihapus dari memo.')) {
        router.delete(route('memos.attachments.delete', [props.memo.id, attachment.id]));
    }
};

const deleteMemo = async () => {
    if (await confirm('Hapus memo ini?')) {
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

        <div class="w-full max-w-none space-y-6">
            <div class="space-y-6">
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
            <form @submit.prevent="save" class="space-y-4">
                <div class="grid w-full grid-cols-1 lg:grid-cols-[minmax(260px,0.7fr)_minmax(0,1.3fr)] gap-4 items-start">
                    <div class="flex flex-col gap-4 self-start">
                        <!-- Nomor Memo -->
                        <div class="bg-slate-800/50 border border-white/5 rounded-xl p-4">
                            <label class="block text-sm font-medium text-slate-300 mb-2">Nomor Memo</label>
                            <input v-model="form.code" type="text" placeholder="Masukkan nomor memo..." class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-slate-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors" />
                            <p v-if="form.errors.code" class="text-red-400 text-sm mt-2">{{ form.errors.code }}</p>
                        </div>

                        <!-- Title -->
                        <div class="bg-slate-800/50 border border-white/5 rounded-xl p-4">
                            <label class="block text-sm font-medium text-slate-300 mb-2">Judul Memo</label>
                            <input v-model="form.title" type="text" placeholder="Masukkan judul memo..." class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-slate-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors" />
                            <p v-if="form.errors.title" class="text-red-400 text-sm mt-2">{{ form.errors.title }}</p>
                        </div>

                        <div v-if="memo.template" class="bg-slate-800/50 border border-white/5 rounded-xl p-4">
                            <label class="block text-sm font-medium text-slate-300 mb-2">Isi Memo</label>
                            <textarea v-model="form.field_values.pengantar" rows="5" class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-slate-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors resize-y"></textarea>
                            <p class="text-xs text-slate-500 mt-2">Teks ini akan tampil pada bagian “Sehubungan dengan” dan dapat diubah sesuai kebutuhan pengajuan.</p>
                        </div>

                        <div class="bg-slate-800/50 border border-white/5 rounded-xl p-4">
                            <h2 class="text-sm font-semibold text-white mb-1">Informasi Dokumen</h2>
                            <p class="text-xs text-slate-400 mb-4">Detail header yang tampil di dokumen cetak.</p>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-medium text-slate-400 mb-1.5">Direktorat</label>
                                    <input v-model="form.field_values.meta.direktorat" type="text" placeholder="contoh: Operasional" class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm placeholder-slate-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-400 mb-1.5">Divisi</label>
                                    <input v-model="form.field_values.meta.divisi" type="text" :placeholder="memo.template?.category || 'contoh: GA / Ma-Link'" class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm placeholder-slate-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-400 mb-1.5">Perihal <span class="text-slate-600">(opsional, jika beda dari judul)</span></label>
                                    <input v-model="form.field_values.meta.perihal" type="text" :placeholder="form.title || 'Mengikuti judul memo'" class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm placeholder-slate-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-400 mb-1.5">Lampiran <span class="text-slate-600">(keterangan teks)</span></label>
                                    <input v-model="form.field_values.meta.lampiran" type="text" placeholder="contoh: 1 Lembar, 3 Berkas" class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm placeholder-slate-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors" />
                                </div>
                            </div>
                        </div>

                        <div class="bg-slate-800/50 border border-white/5 rounded-xl p-4">
                            <h2 class="text-lg font-semibold text-white mb-4">Lampiran</h2>
                            <div v-if="memo.attachments?.length > 0" class="space-y-2 mb-4">
                                <div v-for="att in memo.attachments" :key="att.id" class="flex items-center justify-between bg-slate-700/30 rounded-xl px-4 py-3">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <svg class="w-5 h-5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                        <span class="text-sm text-slate-300 truncate">{{ att.original_name || att.file_path }}</span>
                                    </div>
                                    <button type="button" @click="deleteAttachment(att)" class="text-red-400 hover:text-red-300 text-sm flex-shrink-0">Hapus</button>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <input ref="fileInput" type="file" @change="uploadFile" class="max-w-full text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-indigo-600 file:text-white hover:file:bg-indigo-500 file:cursor-pointer" />
                                <span v-if="uploading" class="text-sm text-slate-400">Mengupload...</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="memo.template" class="bg-slate-800/50 border border-white/5 rounded-xl p-4 min-w-0 self-start">
                        <div v-for="(item, itemIndex) in form.field_values.items" :key="itemIndex" class="border border-white/10 p-4 space-y-4 mb-4 last:mb-0">
                            <div class="flex items-center justify-between border-b border-white/10 pb-3">
                                <h3 class="text-sm font-semibold text-white">Item {{ itemIndex + 1 }}</h3>
                                <button v-if="form.field_values.items.length > 1" type="button" class="text-sm text-red-400 hover:text-red-300" @click="removeItem(itemIndex)">Hapus item</button>
                            </div>
                                <div v-for="field in memo.template.field_schema" :key="field.key">
                                    <label class="block text-sm font-medium text-slate-300 mb-2">
                                        {{ field.label }}
                                        <span v-if="field.required" class="text-red-400">*</span>
                                    </label>
                                    <input v-if="field.type === 'text'" v-model="item[field.key]" type="text" class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors" />
                                    <input v-else-if="field.type === 'number'" v-model="item[field.key]" type="number" class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors" />
                                    <input v-else-if="field.type === 'date'" v-model="item[field.key]" type="date" class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors" />
                                    <textarea v-else-if="field.type === 'textarea'" v-model="item[field.key]" rows="4" class="w-full bg-slate-700/50 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors resize-none"></textarea>
                                    <!-- Table field: multiple sub-rows (e.g. area + qty) -->
                                    <div v-else-if="field.type === 'table'" class="space-y-2">
                                        <div v-for="(subRow, subIndex) in ensureTableRows(item, field.key, field.columns)" :key="'sub-' + subIndex" class="flex items-start gap-2 bg-slate-700/30 border border-white/10 rounded-xl p-3">
                                            <div class="flex-1 grid gap-2" :class="field.columns.length > 1 ? 'grid-cols-2' : 'grid-cols-1'">
                                                <div v-for="col in field.columns" :key="col.key">
                                                    <label class="block text-xs text-slate-400 mb-1">{{ col.label }}</label>
                                                    <input v-model="subRow[col.key]" :type="col.type === 'number' ? 'number' : 'text'" class="w-full bg-slate-800/60 border border-white/10 rounded-lg px-3 py-2 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors" />
                                                </div>
                                            </div>
                                            <button v-if="item[field.key].length > 1" type="button" @click="removeTableRow(item, field.key, subIndex)" class="mt-5 text-red-400 hover:text-red-300 text-lg leading-none flex-shrink-0">&times;</button>
                                        </div>
                                        <button type="button" @click="addTableRow(item, field.key, field.columns)" class="w-full px-3 py-2 border border-dashed border-indigo-400/40 text-indigo-300 hover:bg-indigo-500/10 text-xs font-medium rounded-xl transition-colors">+ Tambah Baris</button>
                                    </div>
                                    <p v-if="form.errors['field_values.items.' + itemIndex + '.' + field.key]" class="text-red-400 text-sm mt-1">{{ form.errors['field_values.items.' + itemIndex + '.' + field.key] }}</p>
                                </div>
                        </div>
                        <button type="button" class="w-full mt-4 px-4 py-2.5 border border-indigo-400/40 text-indigo-300 hover:bg-indigo-500/10 text-sm font-medium" @click="addItem">+ Tambah Item</button>
                        <div class="hidden mt-6 border-t border-white/10 pt-5 lg:block">                   
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                                <button type="submit" :disabled="form.processing" class="w-full px-6 py-3.5 bg-blue-600 hover:bg-blue-500 disabled:opacity-50 text-white text-sm font-semibold rounded-xl transition-colors shadow-lg shadow-blue-500/20 sm:w-auto">
                                    {{ form.processing ? 'Menyimpan...' : 'Simpan Draft' }}
                                </button>
                                <button type="button" @click="submitMemo" :disabled="form.processing" class="w-full px-6 py-3.5 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white text-sm font-semibold rounded-xl transition-colors shadow-lg shadow-emerald-500/25 sm:w-auto">
                                    Tanda Tangani &amp; Kirim ke AM
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-800/50 border border-white/5 rounded-2xl p-5 lg:hidden">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                        <button type="submit" :disabled="form.processing" class="order-2 w-full px-6 py-3.5 bg-blue-600 hover:bg-blue-500 disabled:opacity-50 text-white text-sm font-semibold rounded-xl transition-colors shadow-lg shadow-blue-500/20 sm:order-1 sm:w-auto">
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Draft' }}
                        </button>
                        <button type="button" @click="submitMemo" :disabled="form.processing" class="order-1 w-full px-6 py-3.5 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white text-sm font-semibold rounded-xl transition-colors shadow-lg shadow-emerald-500/25 sm:order-2 sm:w-auto">
                            Tanda Tangani &amp; Kirim ke AM
                        </button>
                    </div>
                </div>
            </form>

            </div>

            <!-- Digital signature confirmation before submission -->
            <Teleport to="body">
            <div v-if="showSignatureDialog" class="signature-submit-overlay fixed inset-0 z-[999] flex items-start justify-center p-4 pt-20 pb-6 sm:items-center sm:pt-4">
                <div class="signature-submit-modal w-full max-w-lg max-h-[88vh] overflow-y-auto rounded-2xl p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-white">Tanda Tangan Digital KC</h2>
                        <p class="signature-submit-subtext mt-1 text-sm">Masukkan tanda tangan terlebih dahulu sebelum memo dikirim ke Area Manager.</p>
                    </div>
                    <button type="button" @click="showSignatureDialog = false" class="signature-submit-close text-xl leading-none" aria-label="Tutup">&times;</button>
                </div>

                <div v-if="signature && !signatureFile" class="signature-submit-preview-box flex items-center gap-4 mb-4 p-3 rounded-xl border">
                    <img :src="'/storage/' + signature.signature_image" alt="Tanda tangan digital KC" class="h-14 w-32 object-contain bg-white/10 rounded-lg p-1" />
                    <div>
                        <p class="text-sm text-blue-400 font-medium">Tanda tangan terdaftar</p>
                        <p class="signature-submit-subtext mt-1 text-xs">Tanda tangan ini akan digunakan untuk memo.</p>
                    </div>
                </div>

                <label class="signature-submit-label mb-2 block text-sm font-medium">{{ signature ? 'Ganti tanda tangan (opsional)' : 'Upload tanda tangan' }}</label>
                <input type="file" @change="selectSignature" accept="image/png,image/jpeg" class="text-sm" />
                <p class="signature-submit-subtext mt-1 text-xs">Format PNG/JPG, maksimal 2MB.</p>
                <img v-if="signaturePreview" :src="signaturePreview" alt="Preview tanda tangan digital KC" class="h-16 mt-4 object-contain bg-white/10 rounded-lg p-1" />
                <p v-if="$page.props.errors?.signature" class="text-red-400 text-sm mt-2">{{ $page.props.errors.signature }}</p>

                <div class="flex gap-3 mt-5">
                    <button type="button" @click="showSignatureDialog = false" class="signature-submit-cancel px-5 py-2.5 text-sm font-medium rounded-xl transition-colors">Batal</button>
                    <button type="button" @click="confirmSubmit" :disabled="submittingMemo || (!signature && !signatureFile)" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-500 disabled:opacity-50 text-white text-sm font-medium rounded-xl transition-colors">{{ submittingMemo ? 'Mengirim...' : 'Tanda Tangani & Kirim ke AM' }}</button>
                </div>
                </div>
            </div>
            </Teleport>

            <!-- Delete -->
            <div v-if="memo.status === 'draft'" class="flex justify-end">
                <button @click="deleteMemo" class="px-4 py-2 text-sm text-red-400 hover:text-red-300 hover:bg-red-500/10 rounded-xl transition-colors">
                    Hapus Memo
                </button>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
