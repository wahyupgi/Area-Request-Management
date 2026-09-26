<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MemoDocument from '@/Components/MemoDocument.vue';
import MemoAttachments from '@/Components/MemoAttachments.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    ArrowLeftOutlined,
    PrinterOutlined,
    DownloadOutlined,
    CheckCircleOutlined,
    CloseCircleOutlined,
    EditOutlined,
    SafetyCertificateOutlined,
    WarningOutlined,
    PaperClipOutlined
} from '@ant-design/icons-vue';

const props = defineProps({
    memo: Object,
    signature: Object,
});

const showRejectModal = ref(false);
const showApproveConfirm = ref(false);

const approveForm = useForm({
    notes: '',
    signature_image: null,
});

const rejectForm = useForm({
    notes: '',
});

const attachmentUrl = (attachment) => '/storage/' + attachment.file_path;

const handleApprove = () => {
    approveForm.post(route('approvals.approve', props.memo.id), {
        onSuccess: () => { showApproveConfirm.value = false; },
    });
};

const handleReject = () => {
    rejectForm.post(route('approvals.reject', props.memo.id), {
        onSuccess: () => { showRejectModal.value = false; },
    });
};

const printMemo = () => {
    const printWindow = window.open('', '_blank');
    if (!printWindow) return;

    const originalTitle = document.title;
    const memo = document.querySelector('#printable-memo')?.outerHTML || '';
    const attachments = document.querySelector('.memo-print-attachments')?.outerHTML || '';
    const stylesheetUrls = Array.from(document.querySelectorAll('link[rel="stylesheet"]')).map((link) => link.href);
    const activeStyles = Array.from(document.querySelectorAll('style')).map((style) => style.textContent).join('\n');

    const waitForDocx = new Promise((resolve) => {
        const startedAt = Date.now();
        const check = () => {
            const isReady = !document.querySelector('[data-attachment-loading]');
            if (isReady || Date.now() - startedAt > 5000) {
                resolve();
                return;
            }
            window.setTimeout(check, 100);
        };
        check();
    });

    waitForDocx.then(() => Promise.all(stylesheetUrls.map((url) => fetch(url).then((response) => response.text()).catch(() => '')))).then((styles) => {
        printWindow.document.write(`<!doctype html><html><head><title>${originalTitle}</title><style>${styles.join('\n')}\n${activeStyles}</style><style>
            @page { size: A4 portrait; margin: 0; }
            html, body { margin: 0 !important; padding: 0 !important; background: #fff !important; }
            body * { visibility: visible !important; }
            #printable-memo { position: relative !important; width: 210mm !important; min-height: 297mm !important; page-break-after: always !important; break-after: page !important; box-sizing: border-box !important; }
            .memo-print-attachments { display: block !important; width: 210mm !important; }
            .memo-print-attachment { display: block !important; width: 210mm !important; min-height: 297mm !important; page-break-before: always !important; break-before: page !important; box-sizing: border-box !important; }
        </style></head><body>${memo}${attachments}</body></html>`);
        printWindow.onload = () => {
            printWindow.onafterprint = () => printWindow.close();
            printWindow.focus();
            printWindow.print();
        };
        printWindow.document.close();
    });
};

const showSignerModal = ref(false);
const showMetaModal = ref(false);

const existingCustom = props.memo.field_values?.custom_signers || [];
const existingSlot3 = existingCustom[2] || {};
const existingSlot4 = existingCustom[3] || {};

const signersForm = useForm({
    slot3_enabled: !!(existingSlot3.name || existingSlot3.role),
    slot4_enabled: !!(existingSlot4.name || existingSlot4.role),
    slot3_name: existingSlot3.name || '',
    slot3_role: existingSlot3.role || '',
    slot4_name: existingSlot4.name || '',
    slot4_role: existingSlot4.role || '',
    footer_box_count: Number(props.memo.field_values?.footer_box_count || 2),
});

const existingMeta = props.memo.field_values?.meta || {};
const metaForm = useForm({
    code: props.memo.code || '',
    direktorat: existingMeta.direktorat || '',
    divisi: existingMeta.divisi || '',
    kepada: existingMeta.kepada || props.memo.area_manager?.name || '',
    kepada_jabatan: existingMeta.kepada_jabatan || 'Area Manager',
    perihal: existingMeta.perihal || '',
    lampiran: existingMeta.lampiran || '',
});

const saveMeta = () => {
    metaForm.post(route('approvals.updateMeta', props.memo.id), {
        preserveScroll: true,
        onSuccess: () => { showMetaModal.value = false; },
    });
};

const saveSigners = () => {
    const signers = [
        { name: props.memo.creator?.name || '', role: 'Kepala Cabang', location: 'document' },
        { name: props.memo.area_manager?.name || '', role: 'Area Manager', location: 'document' },
    ];

    if (signersForm.slot3_enabled) {
        signers.push({ name: signersForm.slot3_name, role: signersForm.slot3_role, location: 'document' });
    }

    if (signersForm.slot4_enabled) {
        signers.push({ name: signersForm.slot4_name, role: signersForm.slot4_role, location: 'document' });
    }

    signersForm.transform(() => ({ signers, footer_box_count: signersForm.footer_box_count })).post(route('approvals.updateSigners', props.memo.id), {
        preserveScroll: true,
        onSuccess: () => { showSignerModal.value = false; },
    });
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};
</script>

<template>
    <Head :title="'Review Memo: ' + memo.code" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-3">
                    <Link :href="route('approvals.pending')">
                        <a-button type="text" shape="circle" title="Kembali ke Daftar Antrean">
                            <template #icon><arrow-left-outlined /></template>
                        </a-button>
                    </Link>
                    <div>
                        <h1 class="text-base font-bold text-gray-800 mb-0 tracking-tight">Review Memo Pengajuan</h1>
                        <div class="flex items-center gap-2 text-xs text-gray-500">
                            <span class="font-mono font-semibold text-blue-600">{{ memo.code }}</span>
                            <span>•</span>
                            <span>{{ memo.branch?.name || 'Cabang' }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <a-tag v-if="memo.status === 'approved'" color="success" class="font-semibold">
                        <template #icon><check-circle-outlined /></template> Disetujui Resmi
                    </a-tag>
                    <a-tag v-else-if="memo.status === 'rejected'" color="error" class="font-semibold">
                        <template #icon><close-circle-outlined /></template> Ditolak
                    </a-tag>
                    <a-tag v-else color="warning" class="font-semibold">
                        Menunggu Persetujuan
                    </a-tag>

                    <a-button class="hidden sm:inline-flex print:hidden" @click="printMemo">
                        <template #icon><printer-outlined /></template> Cetak Dokumen
                    </a-button>
                    <a-button class="hidden sm:inline-flex print:hidden" @click="printMemo">
                        <template #icon><download-outlined /></template> Download / Simpan PDF
                    </a-button>
                </div>
            </div>
        </template>

        <div :class="['memo-print-root max-w-7xl mx-auto py-2 print:p-0 print:m-0 print:max-w-none print:w-full', { 'has-memo-attachments': memo.attachments?.length }]">
            <a-row :gutter="[24, 24]" class="print:block print:w-full print:m-0 print:p-0">
                
                <!-- Left: Document Preview Stage -->
                <a-col :xs="24" :lg="16" class="flex flex-col items-center justify-start print:block print:w-full print:m-0 print:p-0">
                    <div class="w-full max-w-[210mm] print:max-w-none print:w-full shadow-md print:shadow-none bg-white">
                        <MemoDocument :memo="memo" :show-am-signature="memo.status === 'approved'" />
                    </div>
                </a-col>

                <!-- Right: Action & Inspector Sidebar -->
                <a-col :xs="24" :lg="8" class="print:hidden">
                    
                    <!-- Card 1: Decision Action Panel -->
                    <a-card title="Status Keputusan AM" :bordered="false" class="rounded-lg shadow-sm mb-6" size="small">
                        <template #extra>
                            <a-tag v-if="memo.status === 'approved'" color="success">Sudah Disetujui</a-tag>
                            <a-tag v-else-if="memo.status === 'rejected'" color="error">Ditolak</a-tag>
                            <a-tag v-else color="warning">Perlu Tindakan</a-tag>
                        </template>

                        <div v-if="memo.status === 'approved'" class="space-y-4">
                            <a-alert type="success" show-icon message="Memo ini telah resmi disetujui dan tanda tangan digital telah dibubuhkan pada dokumen." />
                            <Link :href="route('approvals.pending')">
                                <a-button block>&larr; Kembali ke Daftar Antrean</a-button>
                            </Link>
                        </div>

                        <div v-else-if="memo.status === 'rejected'" class="space-y-4">
                            <a-alert type="error" show-icon message="Memo ini telah ditolak untuk revisi cabang." />
                            <Link :href="route('approvals.pending')">
                                <a-button block>&larr; Kembali ke Daftar Antrean</a-button>
                            </Link>
                        </div>

                        <div v-else class="space-y-3">
                            <p class="text-sm text-gray-500 mb-4">
                                Periksa kelayakan rincian permohonan memo. Keputusan Anda akan dibubuhkan tanda tangan digital dan tercatat pada riwayat audit sistem.
                            </p>

                            <a-button type="primary" block size="large" class="bg-green-600 hover:bg-green-500 border-green-600" @click="showApproveConfirm = true">
                                <template #icon></template> Setujui & Tanda Tangani Memo
                            </a-button>

                            <a-button danger block size="large" @click="showRejectModal = true">
                                <template #icon></template> Tolak / Minta Revisi Cabang
                            </a-button>

                            <Link :href="route('approvals.pending')" class="block text-center mt-2 text-sm text-gray-400 hover:text-gray-600">
                                &larr; Kembali ke Daftar Antrean
                            </Link>
                        </div>
                    </a-card>

                    <!-- Card 2: Digital Signature Status -->
                    <a-card title="Tanda Tangan Digital Anda" :bordered="false" class="rounded-lg shadow-sm mb-6" size="small">
                        <template #extra>
                            <Link :href="route('signature.index')" class="text-blue-500 text-xs hover:underline">Ubah</Link>
                        </template>

                        <div v-if="signature" class="flex items-center gap-4">
                            <div class="h-16 w-28 rounded-lg bg-gray-50 border border-gray-200 flex items-center justify-center p-2">
                                <img :src="'/storage/' + signature.signature_image" alt="TTD Anda" class="max-h-full object-contain" />
                            </div>
                            <div>
                                <div class="flex items-center gap-1 text-green-600 text-sm font-semibold mb-1">
                                    <safety-certificate-outlined /> Siap Dibubuhkan
                                </div>
                                <p class="text-xs text-gray-400 m-0">Sertifikat: {{ signature.certificate_no || 'PGI-DS-AUTO' }}</p>
                            </div>
                        </div>

                        <a-alert v-else type="warning" show-icon class="mt-2">
                            <template #message><span class="font-semibold">Tanda Tangan Belum Didaftarkan</span></template>
                            <template #description>Anda dapat mengunggah tanda tangan saat mengonfirmasi approval atau melalui menu Tanda Tangan.</template>
                        </a-alert>
                    </a-card>

                    <!-- Card 3: Penandatangan Dokumen -->
                    <a-card title="Penandatangan Memo" :bordered="false" class="rounded-lg shadow-sm mb-6" size="small">
                        <template #extra>
                            <a-button type="link" size="small" @click="showSignerModal = true">
                                <template #icon><edit-outlined /></template> Edit
                            </a-button>
                        </template>

                        <a-list item-layout="horizontal" size="small" class="bg-gray-50 rounded-lg p-2 border border-gray-100">
                            <a-list-item>
                                <a-list-item-meta :title="memo.creator?.name || '-'" description="Kepala Cabang (Dibuat)">
                                </a-list-item-meta>
                            </a-list-item>
                            <a-list-item>
                                <a-list-item-meta :title="memo.area_manager?.name || '-'" description="Area Manager (Disetujui)">
                                </a-list-item-meta>
                            </a-list-item>
                            <a-list-item v-if="signersForm.slot3_enabled">
                                <a-list-item-meta :title="signersForm.slot3_name || '(Belum diisi)'" :description="signersForm.slot3_role || '-'">
                                </a-list-item-meta>
                            </a-list-item>
                            <a-list-item v-if="signersForm.slot4_enabled" class="border-0">
                                <a-list-item-meta :title="signersForm.slot4_name || '(Belum diisi)'" :description="signersForm.slot4_role || '-'">
                                </a-list-item-meta>
                            </a-list-item>
                        </a-list>
                    </a-card>

                    <!-- Card 4: Informasi Dokumen (Meta) -->
                    <a-card title="Informasi Dokumen" :bordered="false" class="rounded-lg shadow-sm mb-6" size="small">
                        <template #extra>
                            <a-button type="link" size="small" @click="showMetaModal = true">
                                <template #icon><edit-outlined /></template> Edit
                            </a-button>
                        </template>

                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between"><span class="text-gray-500">Direktorat</span><span class="font-medium truncate max-w-[150px]">{{ metaForm.direktorat || '(default)' }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Divisi</span><span class="font-medium truncate max-w-[150px]">{{ metaForm.divisi || '(default)' }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Perihal</span><span class="font-medium truncate max-w-[150px]">{{ metaForm.perihal || memo.title }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Lampiran</span><span class="font-medium truncate max-w-[150px]">{{ metaForm.lampiran || '(auto)' }}</span></div>
                        </div>
                    </a-card>

                    <!-- Card 5: Information Summary -->
                    <a-card title="Informasi Permohonan" :bordered="false" class="rounded-lg shadow-sm mb-6" size="small">
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between"><span class="text-gray-500">Pemohon</span><span class="font-medium">{{ memo.creator?.name || '-' }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Unit Cabang</span><span class="font-medium">{{ memo.branch?.name || '-' }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Template</span><span class="font-medium text-blue-600 truncate max-w-[150px]">{{ memo.template?.name || '-' }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Diajukan</span><span>{{ formatDate(memo.submitted_at || memo.created_at) }}</span></div>
                        </div>
                    </a-card>

                    <!-- Attachments -->
                    <a-card v-if="memo.attachments?.length > 0" :title="'Lampiran (' + memo.attachments.length + ')'" :bordered="false" class="rounded-lg shadow-sm mb-6" size="small">
                        <a-list item-layout="horizontal" :data-source="memo.attachments" size="small">
                            <template #renderItem="{ item }">
                                <a-list-item>
                                    <a-list-item-meta>
                                        <template #title>
                                            <a :href="attachmentUrl(item)" target="_blank" class="text-blue-600 hover:underline text-sm truncate block max-w-[200px]">
                                                {{ item.original_name || item.file_path }}
                                            </a>
                                        </template>
                                        <template #avatar><paper-clip-outlined class="text-gray-400" /></template>
                                    </a-list-item-meta>
                                </a-list-item>
                            </template>
                        </a-list>
                    </a-card>

                    <!-- Previous Approvals -->
                    <a-card v-if="memo.approvals?.length > 0" title="Riwayat Review Sebelumnya" :bordered="false" class="rounded-lg shadow-sm" size="small">
                        <div class="space-y-3">
                            <div v-for="a in memo.approvals" :key="a.id" class="p-3 bg-gray-50 rounded-lg border border-gray-100 text-sm">
                                <div class="flex justify-between mb-1">
                                    <a-tag :color="a.action === 'approved' ? 'success' : 'error'" class="uppercase m-0">{{ a.action }}</a-tag>
                                    <span class="text-xs text-gray-500">{{ formatDate(a.created_at) }}</span>
                                </div>
                                <p v-if="a.notes" class="text-gray-600 text-xs mt-2 mb-0">{{ a.notes }}</p>
                            </div>
                        </div>
                    </a-card>
                </a-col>
            </a-row>
            <MemoAttachments :attachments="memo.attachments" :subject="memo.field_values?.meta?.perihal || memo.title" />
        </div>

        <!-- Approve Confirm Modal -->
        <a-modal
            v-model:open="showApproveConfirm"
            title="Konfirmasi Persetujuan Memo"
            :confirmLoading="approveForm.processing"
            @ok="handleApprove"
            okText="Ya, Setujui Memo"
            cancelText="Batal"
            okType="primary"
            :okButtonProps="{ class: 'bg-green-600 hover:bg-green-500 border-green-600' }"
            centered
        >
            <p class="text-sm text-gray-500 mb-4">Memo ini akan disetujui dan tanda tangan digital Anda akan dibubuhkan pada dokumen resmi.</p>

            <a-form layout="vertical">
                <div v-if="!signature" class="mb-4">
                    <a-form-item label="Upload Tanda Tangan" required :help="approveForm.errors.signature" :validateStatus="approveForm.errors.signature ? 'error' : ''">
                        <input type="file" @change="approveForm.signature_image = $event.target.files[0]" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                        <p class="text-xs text-gray-400 mt-1 mb-0">Upload gambar tanda tangan Anda (PNG/JPG, maks 2MB)</p>
                    </a-form-item>
                </div>
                <div v-else class="mb-4">
                    <a-alert type="success" show-icon class="mb-2">
                        <template #message>Tanda tangan terdaftar akan digunakan</template>
                    </a-alert>
                    <img :src="'/storage/' + signature.signature_image" alt="TTD Anda" class="h-12 object-contain bg-gray-50 border rounded p-1" />
                </div>

                <a-form-item label="Catatan Persetujuan (opsional)">
                    <a-textarea v-model:value="approveForm.notes" :rows="3" placeholder="Tambahkan catatan jika diperlukan..." />
                </a-form-item>
            </a-form>
        </a-modal>

        <!-- Reject Modal -->
        <a-modal
            v-model:open="showRejectModal"
            title="Tolak Memo Pengajuan"
            :confirmLoading="rejectForm.processing"
            @ok="handleReject"
            okText="Ya, Tolak Memo"
            cancelText="Batal"
            okType="danger"
            centered
        >
            <p class="text-sm text-gray-500 mb-4">Berikan alasan penolakan agar Kepala Cabang dapat melakukan revisi permohonan.</p>
            <a-form layout="vertical">
                <a-form-item label="Alasan Penolakan" required :help="rejectForm.errors.notes" :validateStatus="rejectForm.errors.notes ? 'error' : ''">
                    <a-textarea v-model:value="rejectForm.notes" :rows="4" placeholder="Tuliskan alasan penolakan secara jelas..." />
                </a-form-item>
            </a-form>
        </a-modal>

        <!-- Edit Signers Modal -->
        <a-modal
            v-model:open="showSignerModal"
            title="Sesuaikan Penandatangan Memo"
            :confirmLoading="signersForm.processing"
            @ok="saveSigners"
            okText="Simpan Penandatangan"
            cancelText="Batal"
            centered
        >
            <p class="text-sm text-gray-500 mb-4">Atur nama dan jabatan untuk kolom penandatangan ke-3 dan ke-4 yang tampil pada dokumen cetak memo ini.</p>
            
            <a-form layout="vertical">
                <a-form-item label="Jumlah kotak persegi di footer">
                    <a-select v-model:value="signersForm.footer_box_count">
                        <a-select-option :value="1">1 kotak</a-select-option>
                        <a-select-option :value="2">2 kotak</a-select-option>
                    </a-select>
                </a-form-item>

                <a-card title="Penandatangan 3" size="small" class="mb-4 bg-gray-50">
                    <template #extra>
                        <a-switch v-model:checked="signersForm.slot3_enabled" size="small" />
                    </template>
                    <div v-if="signersForm.slot3_enabled">
                        <a-form-item label="Nama Lengkap" class="mb-2">
                            <a-input v-model:value="signersForm.slot3_name" placeholder="Contoh: Nama Pejabat / Pimpinan" />
                        </a-form-item>
                        <a-form-item label="Jabatan" class="mb-0">
                            <a-input v-model:value="signersForm.slot3_role" placeholder="Contoh: General Manager / Operational Director" />
                        </a-form-item>
                    </div>
                </a-card>

                <a-card title="Penandatangan 4" size="small" class="mb-0 bg-gray-50">
                    <template #extra>
                        <a-switch v-model:checked="signersForm.slot4_enabled" size="small" />
                    </template>
                    <div v-if="signersForm.slot4_enabled">
                        <a-form-item label="Nama Lengkap" class="mb-2">
                            <a-input v-model:value="signersForm.slot4_name" placeholder="Contoh: Nama Pejabat / Pimpinan" />
                        </a-form-item>
                        <a-form-item label="Jabatan" class="mb-0">
                            <a-input v-model:value="signersForm.slot4_role" placeholder="Contoh: Direktur Utama" />
                        </a-form-item>
                    </div>
                </a-card>
            </a-form>
        </a-modal>

        <!-- Edit Meta Modal -->
        <a-modal
            v-model:open="showMetaModal"
            title="Edit Informasi Dokumen"
            :confirmLoading="metaForm.processing"
            @ok="saveMeta"
            okText="Simpan Perubahan"
            cancelText="Batal"
            centered
        >
            <p class="text-sm text-gray-500 mb-4">Nilai ini akan tampil di header dokumen cetak. Kosongkan untuk menggunakan nilai default.</p>
            <a-form layout="vertical">
                <a-form-item label="Nomor Memo" class="mb-3">
                    <a-input v-model:value="metaForm.code" placeholder="Masukkan nomor memo..." />
                </a-form-item>
                <a-form-item label="Direktorat" class="mb-3">
                    <a-input v-model:value="metaForm.direktorat" placeholder="contoh: Operasional" />
                </a-form-item>
                <a-form-item label="Divisi" class="mb-3">
                    <a-input v-model:value="metaForm.divisi" placeholder="contoh: GA / Ma-Link" />
                </a-form-item>
                <a-form-item label="Perihal" extra="default: judul memo" class="mb-3">
                    <a-input v-model:value="metaForm.perihal" :placeholder="memo.title" />
                </a-form-item>
                <a-form-item label="Tujuan / Kepada Yth" class="mb-3">
                    <a-input v-model:value="metaForm.kepada" placeholder="contoh: Bpk. / Ibu. Area Manager" />
                </a-form-item>
                <a-form-item label="Jabatan / Divisi Tujuan" class="mb-3">
                    <a-input v-model:value="metaForm.kepada_jabatan" placeholder="contoh: Area Manager / Kepala Divisi" />
                </a-form-item>
                <a-form-item label="Lampiran" extra="default: jumlah file" class="mb-0">
                    <a-input v-model:value="metaForm.lampiran" placeholder="contoh: 1 Lembar, 3 Berkas" />
                </a-form-item>
            </a-form>
        </a-modal>
    </AuthenticatedLayout>
</template>
