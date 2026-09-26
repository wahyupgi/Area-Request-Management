<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    ArrowLeftOutlined,
    CheckCircleOutlined,
    CloseCircleOutlined,
    PaperClipOutlined,
    DownloadOutlined
} from '@ant-design/icons-vue';

const props = defineProps({
    beritaAcara: Object,
});

const showRejectModal = ref(false);
const showApproveConfirm = ref(false);

const approveForm = useForm({});
const rejectForm = useForm({
    notes: '',
});

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

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};
</script>

<template>
    <Head :title="'Review Berita Acara: ' + beritaAcara.code" />
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

        <div class="max-w-5xl mx-auto py-6">
            <a-row :gutter="24">
                <a-col :xs="24" :lg="16">
                    <a-card :bordered="false" class="rounded-lg shadow-sm mb-6">
                        <h2 class="text-lg font-bold border-b pb-2 mb-4">Draft Dokumen Berita Acara</h2>
                        
                        <div class="bg-gray-50 p-6 rounded border border-gray-200 text-gray-800">
                            <h3 class="text-center font-bold text-xl uppercase mb-1">BERITA ACARA</h3>
                            <p class="text-center font-semibold mb-6">NO: {{ beritaAcara.code || '_________________' }}</p>

                            <p class="mb-4">{{ beritaAcara.pengantar }}</p>

                            <table class="w-full mb-6">
                                <tbody>
                                    <tr v-for="(item, idx) in beritaAcara.rincian_data" :key="idx">
                                        <td class="py-1 w-1/3 font-semibold">{{ item.label }}</td>
                                        <td class="py-1 w-4 text-center">:</td>
                                        <td class="py-1">{{ item.value }}</td>
                                    </tr>
                                </tbody>
                            </table>

                            <div v-if="beritaAcara.keterangan_tambahan" class="mb-4">
                                <p class="whitespace-pre-line">{{ beritaAcara.keterangan_tambahan }}</p>
                            </div>

                            <p class="mb-8">{{ beritaAcara.penutup }}</p>

                            <div class="flex justify-between mt-12">
                                <div class="text-center">
                                    <p class="mb-16">Dibuat Oleh,</p>
                                    <p class="font-bold underline mb-0">{{ beritaAcara.creator?.name || '_________________' }}</p>
                                    <p class="text-sm">Kepala Cabang</p>
                                </div>
                                <div class="text-center">
                                    <p class="mb-16">Mengetahui,</p>
                                    <p class="font-bold underline mb-0">{{ beritaAcara.area_manager?.name || '_________________' }}</p>
                                    <p class="text-sm">Area Manager</p>
                                </div>
                            </div>
                        </div>
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

                <a-col :xs="24" :lg="8">
                    <!-- Status Card -->
                    <a-card title="Keputusan Persetujuan" :bordered="false" class="rounded-lg shadow-sm mb-6" size="small">
                        <template #extra>
                            <a-tag v-if="beritaAcara.status === 'approved'" color="success">Sudah Disetujui</a-tag>
                            <a-tag v-else-if="beritaAcara.status === 'rejected'" color="error">Ditolak</a-tag>
                            <a-tag v-else color="warning">Perlu Keputusan</a-tag>
                        </template>

                        <div v-if="beritaAcara.status === 'approved'" class="space-y-4">
                            <a-alert type="success" show-icon message="Berita Acara ini telah disetujui." />
                        </div>
                        <div v-else-if="beritaAcara.status === 'rejected'" class="space-y-4">
                            <a-alert type="error" show-icon message="Berita Acara ini telah ditolak." />
                        </div>
                        <div v-else class="space-y-3">
                            <p class="text-sm text-gray-500 mb-4">
                                Silakan periksa rincian Berita Acara yang diajukan. Keputusan Anda akan dicatat dalam sistem.
                            </p>

                            <a-button type="primary" block size="large" class="bg-green-600 hover:bg-green-500 border-green-600" @click="showApproveConfirm = true">
                                <template #icon><check-circle-outlined /></template> Setujui BA
                            </a-button>

                            <a-button danger block size="large" @click="showRejectModal = true">
                                <template #icon><close-circle-outlined /></template> Tolak BA
                            </a-button>
                        </div>
                    </a-card>

                    <!-- Meta Data -->
                    <a-card title="Informasi Meta Dokumen" :bordered="false" class="rounded-lg shadow-sm" size="small">
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between"><span class="text-gray-500">Judul</span><span class="font-medium text-right max-w-[150px] truncate" :title="beritaAcara.title">{{ beritaAcara.title }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Direktorat</span><span class="font-medium">{{ beritaAcara.meta?.direktorat || '-' }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Divisi</span><span class="font-medium">{{ beritaAcara.meta?.divisi || '-' }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Kepada</span><span class="font-medium truncate">{{ beritaAcara.meta?.kepada_nama || '-' }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Jabatan</span><span class="font-medium">{{ beritaAcara.meta?.kepada_jabatan || '-' }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Tanggal Buat</span><span>{{ formatDate(beritaAcara.created_at) }}</span></div>
                        </div>
                    </a-card>
                </a-col>
            </a-row>
        </div>

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
            <p class="text-gray-600">Apakah Anda yakin ingin menyetujui Berita Acara ini?</p>
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
