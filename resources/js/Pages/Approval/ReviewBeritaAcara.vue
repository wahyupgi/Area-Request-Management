<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BeritaAcaraDocument from '@/Components/BeritaAcaraDocument.vue';
import MemoAttachments from '@/Components/MemoAttachments.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useSweetAlert } from '@/composables/useSweetAlert';
import {
    ArrowLeftOutlined,
    EditOutlined,
    SafetyCertificateOutlined
} from '@ant-design/icons-vue';

const props = defineProps({
    beritaAcara: Object,
    signature: { type: Object, default: null },
});

const showRejectModal = ref(false);
const showApproveConfirm = ref(false);
const { success } = useSweetAlert();
const showDocumentModal = ref(false);
const showSignersModal = ref(false);

const defaultSigners = () => {
    const creator = props.beritaAcara.creator;
    const manager = props.beritaAcara.area_manager || props.beritaAcara.areaManager;
    const template = props.beritaAcara.meta?.template;
    const isSeizedGoods = template === 'penghapusan_barang_sitaan';
    const isCostRequest = template === 'permohonan_biaya_kost' || template === 'template_1';
    const executive = {
        name: props.beritaAcara.meta?.penyetuju_akhir || 'Bpk. Nugroho Samudra Sujatmiko, Ko',
        role: 'Senior Executive Vice President Bisnis dan Operasional',
    };
    const signers = [
        { name: creator?.name || '', role: 'Kepala Cabang' },
        { name: manager?.name || '', role: manager?.jabatan || 'Manager' },
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
        onSuccess: () => {
            showApproveConfirm.value = false;
            success('Berita Acara berhasil disetujui.');
        },
    });
};

const requestApprove = () => {
    showApproveConfirm.value = true;
};

const handleReject = () => {
    rejectForm.post(route('approvals.ba.reject', props.beritaAcara.id), {
        onSuccess: () => { showRejectModal.value = false; },
    });
};

const printAttachments = [
    ...(props.beritaAcara.attachments || []),
    ...(props.beritaAcara.attachment_path ? [{
        id: `legacy-ba-${props.beritaAcara.id}`,
        legacy: true,
        file_path: props.beritaAcara.attachment_path,
        original_name: props.beritaAcara.attachment_name,
    }] : []),
];

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
                    <Link :href="route('approvals.pending', { tab: 'masuk' })">
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

            </div>
        </template>

        <div class="max-w-7xl mx-auto py-2 px-4">
            <a-row :gutter="[24, 24]">
                <a-col :xs="24" :lg="16" class="flex flex-col items-center justify-start">
                    <div class="document-preview-scroll w-full">
                        <div class="document-preview-paper shadow-md bg-white">
                            <BeritaAcaraDocument :berita-acara="beritaAcara" />
                        </div>
                    </div>
                    <MemoAttachments :attachments="printAttachments" :subject="beritaAcara.meta?.perihal || beritaAcara.title" />
                </a-col>

                <a-col :xs="24" :lg="8">
                    <!-- Status Card -->
                    <a-card title="Keputusan Persetujuan" :bordered="false" class="rounded-lg shadow-sm mb-6" size="small">
                        <template #extra>
                            <a-tag v-if="beritaAcara.status === 'approved'" color="success">Sudah Disetujui</a-tag>
                            <a-tag v-else-if="beritaAcara.status === 'rejected'" color="error">Ditolak</a-tag>
                            <a-tag v-else color="warning">Perlu Keputusan</a-tag>
                        </template>

                        <div v-if="beritaAcara.status === 'approved'" class="space-y-4">
                            <a-alert type="success" show-icon message="Berita Acara ini telah resmi disetujui dan tanda tangan digital telah dibubuhkan pada dokumen." />
                            <Link :href="route('approvals.pending', { tab: 'masuk' })">
                                <a-button block>&larr; Kembali ke Daftar Antrean</a-button>
                            </Link>
                        </div>
                        <div v-else-if="beritaAcara.status === 'rejected'" class="space-y-4">
                            <a-alert type="error" show-icon message="Berita Acara ini telah ditolak untuk revisi cabang." />
                            <Link :href="route('approvals.pending', { tab: 'masuk' })">
                                <a-button block>&larr; Kembali ke Daftar Antrean</a-button>
                            </Link>
                        </div>
                        <div v-else class="space-y-3">
                            <p class="text-sm text-gray-500 mb-4">
                                Periksa kelayakan rincian Berita Acara. Keputusan Anda akan dibubuhkan tanda tangan digital dan tercatat pada riwayat sistem.
                            </p>

                            <a-button type="primary" block size="large" class="bg-green-600 hover:bg-green-500 border-green-600" @click="requestApprove">
                                Setujui & Tanda Tangani Berita Acara
                            </a-button>

                            <a-button danger block size="large" @click="showRejectModal = true">
                                Tolak / Minta Revisi Cabang
                            </a-button>
                            <Link :href="route('approvals.pending', { tab: 'masuk' })" class="block text-center mt-2 text-sm text-gray-400 hover:text-gray-600">
                                &larr; Kembali ke Daftar Antrean
                            </Link>
                        </div>
                    </a-card>

                    <a-card title="Tanda Tangan Digital Anda" :bordered="false" class="rounded-lg shadow-sm mb-6" size="small">
                        <template #extra>
                            <Link :href="route('profile.edit')" class="text-blue-500 text-xs hover:underline">Ubah</Link>
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
                            <template #description>Daftarkan tanda tangan melalui menu Profil sebelum menyetujui dokumen.</template>
                        </a-alert>
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

                    <a-card title="Informasi Permohonan" :bordered="false" class="rounded-lg shadow-sm mt-6" size="small">
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between gap-3"><span class="text-gray-500">Pemohon</span><span class="font-medium text-right">{{ beritaAcara.creator?.name || '-' }}</span></div>
                            <div class="flex justify-between gap-3"><span class="text-gray-500">Unit Cabang</span><span class="font-medium text-right">{{ beritaAcara.branch?.name || '-' }}</span></div>
                            <div class="flex justify-between gap-3"><span class="text-gray-500">Diajukan</span><span class="text-right">{{ formatDate(beritaAcara.submitted_at || beritaAcara.created_at) }}</span></div>
                        </div>
                    </a-card>
                </a-col>
            </a-row>
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
