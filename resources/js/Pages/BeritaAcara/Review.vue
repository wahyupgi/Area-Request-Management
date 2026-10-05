<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BeritaAcaraDocument from '@/Components/BeritaAcaraDocument.vue';
import MemoAttachments from '@/Components/MemoAttachments.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
    ArrowLeftOutlined,
    PrinterOutlined,
    CheckCircleOutlined,
    CloseCircleOutlined,
    PaperClipOutlined,
    DownloadOutlined,
    EditOutlined
} from '@ant-design/icons-vue';

const props = defineProps({
    beritaAcara: Object,
});

const showRejectModal = ref(false);
const showApproveConfirm = ref(false);
const showDocumentModal = ref(false);
const showSignersModal = ref(false);

const defaultSigners = () => {
    const creator = props.beritaAcara.creator;
    const manager = props.beritaAcara.area_manager || props.beritaAcara.areaManager;
    const template = props.beritaAcara.meta?.template;
    const isSeizedGoods = template === 'penghapusan_barang_sitaan';
    const isCostRequest = template === 'permohonan_biaya_kost';
    const executive = {
        name: props.beritaAcara.meta?.penyetuju_akhir || 'Bpk. Nugroho Samudra Sujatmiko, Ko',
        role: 'Senior Executive Vice President Bisnis dan Operasional',
    };
    const signers = [
        { name: creator?.name || '', role: 'Kepala Cabang' },
        { name: manager?.name || 'Bpk. Fathurrahman M', role: manager?.jabatan || 'Manager' },
    ];

    if (isSeizedGoods) {
        signers.push({ name: 'Bpk. Yudha', role: 'Legal' }, executive);
    } else {
        signers.push(executive);
        if (!isCostRequest) signers.push({ name: 'Ibu Ella Safitri', role: 'SPV HC Payroll' });
    }

    return signers;
};

const createSignerSettings = () => {
    const defaults = defaultSigners();
    const saved = props.beritaAcara.meta?.signature_signers || [];
    const isSeizedGoods = props.beritaAcara.meta?.template === 'penghapusan_barang_sitaan';

    return {
        signers: Array.from({ length: 4 }, (_, index) => ({
            ...(saved[index] || defaults[index] || { name: '', role: '' }),
        })),
        slot3_enabled: saved[2]?.enabled ?? defaults.length > 2,
        slot4_enabled: saved[3]?.enabled ?? defaults.length > 3,
        footer_box_count: Number(props.beritaAcara.meta?.footer_box_count ?? (isSeizedGoods ? 1 : 2)),
    };
};

const signatureForm = useForm(createSignerSettings());
const visibleSigners = computed(() => signatureForm.signers.filter((_, index) =>
    index < 2 || (index === 2 && signatureForm.slot3_enabled) || (index === 3 && signatureForm.slot4_enabled)
));

const approveForm = useForm({});
const documentForm = useForm({
    code: props.beritaAcara.code || '',
    title: props.beritaAcara.title || '',
    meta: {
        direktorat: props.beritaAcara.meta?.direktorat || '',
        divisi: props.beritaAcara.meta?.divisi || '',
        perihal: props.beritaAcara.meta?.perihal || '',
        kepada_nama: props.beritaAcara.meta?.kepada_nama || '',
        kepada_jabatan: props.beritaAcara.meta?.kepada_jabatan || '',
        penyetuju_akhir: props.beritaAcara.meta?.penyetuju_akhir || '',
        lampiran: props.beritaAcara.meta?.lampiran || '',
    },
});
const rejectForm = useForm({
    notes: '',
});

const saveDocumentInfo = () => {
    documentForm.post(route('approvals.ba.updateCode', props.beritaAcara.id), {
        preserveScroll: true,
        onSuccess: () => { showDocumentModal.value = false; },
    });
};

const saveSigners = () => {
    signatureForm.transform((data) => ({
        signers: data.signers.map((signer, index) => ({
            ...signer,
            enabled: index < 2 || (index === 2 ? data.slot3_enabled : data.slot4_enabled),
        })),
        footer_box_count: data.footer_box_count,
    })).post(route('approvals.ba.updateSigners', props.beritaAcara.id), {
        preserveScroll: true,
        onSuccess: () => { showSignersModal.value = false; },
    });
};

const cancelSignerEdits = () => {
    Object.assign(signatureForm, createSignerSettings());
    showSignersModal.value = false;
};

const handleApprove = () => {
    approveForm.post(route('approvals.ba.approve', props.beritaAcara.id), {
        onSuccess: () => { showApproveConfirm.value = false; },
    });
};

const handleReject = () => {
    rejectForm.post(route('approvals.ba.reject', props.beritaAcara.id), {
        onSuccess: () => { showRejectModal.value = false; },
    });
};

const attachmentUrl = () => '/storage/' + props.beritaAcara.attachment_path;
const printAttachments = props.beritaAcara.attachment_path ? [{
    id: props.beritaAcara.id,
    file_path: props.beritaAcara.attachment_path,
    original_name: props.beritaAcara.attachment_name,
}] : [];

const printBA = () => {
    const printWindow = window.open('', '_blank');
    if (!printWindow) return;

    const originalTitle = document.title;
    const beritaAcara = document.querySelector('#printable-ba')?.outerHTML || '';
    const stylesheetUrls = Array.from(document.querySelectorAll('link[rel="stylesheet"]')).map((link) => link.href);
    const activeStyles = Array.from(document.querySelectorAll('style')).map((style) => style.textContent).join('\n');

    const waitForDocx = new Promise((resolve) => {
        const startedAt = Date.now();
        const check = () => {
            if (!document.querySelector('[data-attachment-loading]') || Date.now() - startedAt > 5000) {
                resolve();
                return;
            }
            window.setTimeout(check, 100);
        };
        check();
    });

    waitForDocx.then(() => Promise.all(stylesheetUrls.map((url) => fetch(url).then((response) => response.text()).catch(() => '')))).then((styles) => {
        const attachments = document.querySelector('.memo-print-attachments')?.outerHTML || '';
        printWindow.document.write(`<!doctype html><html><head><title>${originalTitle}</title><style>${styles.join('\n')}\n${activeStyles}</style><style>
            @page { size: A4 portrait; margin: 0; }
            html, body { margin: 0 !important; padding: 0 !important; background: #fff !important; }
            body * { visibility: visible !important; }
            #printable-ba { position: relative !important; inset: auto !important; width: 210mm !important; min-height: 297mm !important; page-break-after: always !important; break-after: page !important; box-sizing: border-box !important; }
            .memo-print-attachments { display: block !important; width: 210mm !important; }
            .memo-print-attachment { display: block !important; width: 210mm !important; min-height: 297mm !important; page-break-before: always !important; break-before: page !important; box-sizing: border-box !important; }
        </style></head><body>${beritaAcara}${attachments}</body></html>`);
        printWindow.onload = () => {
            printWindow.onafterprint = () => printWindow.close();
            printWindow.focus();
            printWindow.print();
        };
        printWindow.document.close();
    });
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};
</script>

<template>
    <Head :title="`Review Berita Acara: ${beritaAcara.code}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-3">
                    <Link :href="route('approvals.pending', { tab: 'ba-masuk' })">
                        <a-button type="text" shape="circle" title="Kembali ke Daftar Antrean">
                            <template #icon><arrow-left-outlined /></template>
                        </a-button>
                    </Link>
                    <div>
                        <h1 class="text-base font-bold text-gray-800 mb-0 tracking-tight">Review Berita Acara</h1>
                        <div class="flex items-center gap-2 text-xs text-gray-500">
                            <span class="font-mono font-semibold text-blue-600">{{ beritaAcara.code || 'BA Baru' }}</span>
                            <span>•</span>
                            <span>{{ beritaAcara.branch?.name || 'Cabang' }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <a-button class="print:hidden" @click="printBA">
                        <template #icon><printer-outlined /></template> Cetak BA
                    </a-button>
                    <a-button class="print:hidden" @click="printBA">
                        <template #icon><download-outlined /></template> Download / Simpan PDF
                    </a-button>
                    <a-tag v-if="beritaAcara.status === 'approved'" color="success" class="font-semibold">
                        <template #icon><check-circle-outlined /></template> Disetujui
                    </a-tag>
                    <a-tag v-else-if="beritaAcara.status === 'rejected'" color="error" class="font-semibold">
                        <template #icon><close-circle-outlined /></template> Ditolak
                    </a-tag>
                    <a-tag v-else color="warning" class="font-semibold">
                        Menunggu Persetujuan
                    </a-tag>
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto py-6 px-4">
            <a-row :gutter="[24, 24]">
                <a-col :xs="24" :lg="18">
                    <a-card :bordered="false" class="rounded-lg shadow-sm mb-6">
                        <h2 class="text-lg font-bold border-b pb-2 mb-4">Dokumen Berita Acara</h2>
                        <BeritaAcaraDocument :berita-acara="beritaAcara" />
                    </a-card>

                    <a-card v-if="beritaAcara.attachment_path" title="Lampiran Pendukung" :bordered="false" class="rounded-lg shadow-sm">
                        <a-list item-layout="horizontal" size="small">
                            <a-list-item>
                                <a-list-item-meta>
                                    <template #title>
                                        <a :href="attachmentUrl()" target="_blank" class="text-blue-600 hover:underline flex items-center gap-2">
                                            <paper-clip-outlined />
                                            {{ beritaAcara.attachment_name || 'Lampiran Dokumen' }}
                                        </a>
                                    </template>
                                </a-list-item-meta>
                                <template #actions>
                                    <a :href="attachmentUrl()" target="_blank">
                                        <a-button type="link" size="small">
                                            <template #icon><download-outlined /></template> Unduh
                                        </a-button>
                                    </a>
                                </template>
                            </a-list-item>
                        </a-list>
                    </a-card>
                </a-col>

                <a-col :xs="24" :lg="6">
                    <!-- Status Card -->
                    <a-card title="Keputusan Persetujuan" :bordered="false" class="rounded-lg shadow-sm mb-6" size="small">
                        <template #extra>
                            <a-tag v-if="beritaAcara.status === 'approved'" color="success">Sudah Disetujui</a-tag>
                            <a-tag v-else-if="beritaAcara.status === 'rejected'" color="error">Ditolak</a-tag>
                            <a-tag v-else color="warning">Perlu Keputusan</a-tag>
                        </template>

                        <div v-if="beritaAcara.status === 'approved'" class="space-y-4">
                            <a-alert type="success" show-icon message="Pengajuan ini telah disetujui." />
                        </div>
                        <div v-else-if="beritaAcara.status === 'rejected'" class="space-y-4">
                            <a-alert type="error" show-icon message="Pengajuan ini telah ditolak." />
                        </div>
                        <div v-else class="space-y-3">
                            <p class="text-sm text-gray-500 mb-4">
                                Silakan periksa rincian Berita Acara yang diajukan. Keputusan Anda akan dicatat dalam sistem.
                            </p>

                            <a-button type="primary" block size="large" class="bg-green-600 hover:bg-green-500 border-green-600" @click="showApproveConfirm = true">
                                <template #icon><check-circle-outlined /></template> Setujui
                            </a-button>

                            <a-button danger block size="large" @click="showRejectModal = true">
                                <template #icon><close-circle-outlined /></template> Tolak
                            </a-button>
                        </div>
                    </a-card>

                    <!-- Signatories -->
                    <a-card title="Penandatangan Berita Acara" :bordered="false" class="rounded-lg shadow-sm mb-6" size="small">
                        <template #extra>
                            <a-button v-if="beritaAcara.status === 'submitted'" type="link" size="small" @click="showSignersModal = true">
                                <template #icon><edit-outlined /></template> Edit
                            </a-button>
                        </template>
                        <a-list item-layout="horizontal" size="small">
                            <a-list-item v-for="(signer, index) in visibleSigners" :key="index">
                                <a-list-item-meta :title="signer.name || '(Belum diisi)'" :description="signer.role || '-'" />
                            </a-list-item>
                        </a-list>
                    </a-card>

                    <!-- Meta Data -->
                    <a-card title="Informasi Dokumen" :bordered="false" class="rounded-lg shadow-sm" size="small">
                        <template #extra>
                            <a-button v-if="beritaAcara.status === 'submitted'" type="link" size="small" @click="showDocumentModal = true">
                                <template #icon><edit-outlined /></template> Edit
                            </a-button>
                        </template>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between"><span class="text-gray-500">Direktorat</span><span class="font-medium truncate max-w-[150px]">{{ beritaAcara.meta?.direktorat || '(default)' }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Divisi</span><span class="font-medium truncate max-w-[150px]">{{ beritaAcara.meta?.divisi || '(default)' }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Perihal</span><span class="font-medium truncate max-w-[150px]">{{ beritaAcara.meta?.perihal || beritaAcara.title }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Lampiran</span><span class="font-medium truncate max-w-[150px]">{{ beritaAcara.meta?.lampiran || '(default)' }}</span></div>
                        </div>
                    </a-card>
                </a-col>
            </a-row>
            <MemoAttachments :attachments="printAttachments" :subject="beritaAcara.meta?.perihal || beritaAcara.title" />
        </div>

        <a-modal
            v-model:open="showDocumentModal"
            title="Edit Informasi Dokumen"
            :confirmLoading="documentForm.processing"
            @ok="saveDocumentInfo"
            okText="Simpan Perubahan"
            cancelText="Batal"
            centered
        >
            <p class="text-sm text-gray-500 mb-4">Perubahan ini akan tampil di header dokumen BA.</p>
            <a-form layout="vertical">
                <a-form-item label="Nomor BA" :help="documentForm.errors.code" :validateStatus="documentForm.errors.code ? 'error' : ''" class="mb-3">
                    <a-input v-model:value="documentForm.code" placeholder="Masukkan nomor BA..." />
                </a-form-item>
                <a-form-item label="Judul" :help="documentForm.errors.title" :validateStatus="documentForm.errors.title ? 'error' : ''" class="mb-3">
                    <a-input v-model:value="documentForm.title" placeholder="Masukkan judul BA..." />
                </a-form-item>
                <a-form-item label="Direktorat" :help="documentForm.errors['meta.direktorat']" :validateStatus="documentForm.errors['meta.direktorat'] ? 'error' : ''" class="mb-3">
                    <a-input v-model:value="documentForm.meta.direktorat" placeholder="Contoh: Regional Branch Office" />
                </a-form-item>
                <a-form-item label="Divisi" :help="documentForm.errors['meta.divisi']" :validateStatus="documentForm.errors['meta.divisi'] ? 'error' : ''" class="mb-3">
                    <a-input v-model:value="documentForm.meta.divisi" placeholder="Contoh: Branch Leader / HRD" />
                </a-form-item>
                <a-form-item label="Perihal" :help="documentForm.errors['meta.perihal']" :validateStatus="documentForm.errors['meta.perihal'] ? 'error' : ''" class="mb-3">
                    <a-input v-model:value="documentForm.meta.perihal" :placeholder="documentForm.title" />
                </a-form-item>
                <a-form-item label="Kepada (Yth.)" :help="documentForm.errors['meta.kepada_nama']" :validateStatus="documentForm.errors['meta.kepada_nama'] ? 'error' : ''" class="mb-3">
                    <a-input v-model:value="documentForm.meta.kepada_nama" placeholder="Nama penerima" />
                </a-form-item>
                <a-form-item label="Jabatan Penerima" :help="documentForm.errors['meta.kepada_jabatan']" :validateStatus="documentForm.errors['meta.kepada_jabatan'] ? 'error' : ''" class="mb-3">
                    <a-input v-model:value="documentForm.meta.kepada_jabatan" placeholder="Jabatan penerima" />
                </a-form-item>
                <a-form-item label="Penyetuju Akhir" :help="documentForm.errors['meta.penyetuju_akhir']" :validateStatus="documentForm.errors['meta.penyetuju_akhir'] ? 'error' : ''" class="mb-3">
                    <a-input v-model:value="documentForm.meta.penyetuju_akhir" placeholder="Nama penyetuju akhir" />
                </a-form-item>
                <a-form-item label="Lampiran" :help="documentForm.errors['meta.lampiran']" :validateStatus="documentForm.errors['meta.lampiran'] ? 'error' : ''" class="mb-0">
                    <a-input v-model:value="documentForm.meta.lampiran" placeholder="Contoh: 1 Lembar, 3 Berkas" />
                </a-form-item>
            </a-form>
        </a-modal>

        <a-modal
            v-model:open="showSignersModal"
            title="Sesuaikan Penandatangan Berita Acara"
            :confirmLoading="signatureForm.processing"
            @ok="saveSigners"
            @cancel="cancelSignerEdits"
            okText="Simpan Penandatangan"
            cancelText="Batal"
            centered
        >
            <p class="text-sm text-gray-500 mb-4">Atur nama dan jabatan yang tampil pada setiap kolom tanda tangan dokumen BA ini.</p>
            <a-form layout="vertical">
                <a-form-item label="Jumlah kotak persegi di footer">
                    <a-select v-model:value="signatureForm.footer_box_count">
                        <a-select-option :value="1">1 kotak</a-select-option>
                        <a-select-option :value="2">2 kotak</a-select-option>
                    </a-select>
                </a-form-item>

                <a-card v-for="(signer, index) in signatureForm.signers" :key="index" :title="`Penandatangan ${index + 1}`" size="small" class="mb-3 bg-gray-50">
                    <template v-if="index >= 2" #extra>
                        <a-switch v-model:checked="signatureForm[index === 2 ? 'slot3_enabled' : 'slot4_enabled']" size="small" />
                    </template>
                    <template v-if="index < 2 || signatureForm[index === 2 ? 'slot3_enabled' : 'slot4_enabled']">
                    <a-form-item label="Nama Lengkap" class="mb-2">
                        <a-input v-model:value="signer.name" placeholder="Nama penandatangan" />
                    </a-form-item>
                    <a-form-item label="Jabatan" class="mb-0">
                        <a-input v-model:value="signer.role" placeholder="Jabatan penandatangan" />
                    </a-form-item>
                    </template>
                </a-card>
            </a-form>
        </a-modal>

        <!-- Approve Modal -->
        <a-modal
            v-model:open="showApproveConfirm"
            title="Konfirmasi Persetujuan Berita Acara"
            :confirmLoading="approveForm.processing"
            @ok="handleApprove"
            okText="Ya, Setujui"
            cancelText="Batal"
            okType="primary"
            :okButtonProps="{ class: 'bg-green-600 hover:bg-green-500 border-green-600' }"
            centered
        >
            <p class="text-gray-600">Apakah Anda yakin ingin menyetujui pengajuan ini?</p>
            <a-alert
                v-if="approveForm.errors.signature"
                class="mt-3"
                type="error"
                show-icon
                :message="approveForm.errors.signature"
            />
        </a-modal>

        <!-- Reject Modal -->
        <a-modal
            v-model:open="showRejectModal"
            title="Tolak Berita Acara"
            :confirmLoading="rejectForm.processing"
            @ok="handleReject"
            okText="Ya, Tolak"
            cancelText="Batal"
            okType="danger"
            centered
        >
            <p class="text-sm text-gray-500 mb-4">Berikan alasan penolakan.</p>
            <a-form layout="vertical">
                <a-form-item label="Alasan Penolakan" required :help="rejectForm.errors.notes" :validateStatus="rejectForm.errors.notes ? 'error' : ''">
                    <a-textarea v-model:value="rejectForm.notes" :rows="4" placeholder="Tuliskan alasan penolakan secara jelas..." />
                </a-form-item>
            </a-form>
        </a-modal>
    </AuthenticatedLayout>
</template>
