<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { useSweetAlert } from '@/composables/useSweetAlert';
import { SaveOutlined, SendOutlined, PlusOutlined, DeleteOutlined } from '@ant-design/icons-vue';

const props = defineProps({
    branches: { type: Array, default: () => [] },
    formPengajuanOnly: { type: Boolean, default: false },
});

const page = usePage();
const { success } = useSweetAlert();
const documentLabels = {
    form_permohonan_pinjaman: 'Form Permohonan Pinjaman (FPP)',
    form_ijin_tidak_masuk_kerja: 'Form Ijin Tidak Masuk Kerja (FITMK)',
};

const beritaAcaraTemplates = [
    { value: 'permohonan_biaya_kost', label: 'Permohonan Biaya Kost' },
    { value: 'revisi_absensi', label: 'Permintaan Revisi Absensi' },
    { value: 'penghapusan_barang_sitaan', label: 'Penghapusan Barang Sitaan' },
    { value: 'lainnya', label: 'Lainnya' },
];
const formPengajuanTemplates = [
    { value: 'form_permohonan_pinjaman', label: 'Form Permohonan Pinjaman (FPP)' },
    { value: 'form_ijin_tidak_masuk_kerja', label: 'Form Ijin Tidak Masuk Kerja (FITMK)' },
];
const availableTemplates = computed(() => props.formPengajuanOnly
    ? formPengajuanTemplates
    : beritaAcaraTemplates
);

const form = useForm({
    branch_id: null,
    title: '',
    meta: {
        template: undefined,
        direktorat: 'Operasional',
        divisi: 'Support',
        perihal: 'Berita Acara Permohonan',
        lampiran: '-',
        kepada_nama: '',
        kepada_jabatan: '',
        penyetuju_akhir: '',
        data: { rows: [], kronologi: '' },
        request_data: {},
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

watch(() => form.meta.template, (template) => {
    const selected = availableTemplates.value.find((item) => item.value === template);
    if (!selected) return;

    form.title = selected.label;
    form.meta.perihal = selected.label;
    form.meta.data = { rows: [], kronologi: '' };

    if (template === 'form_permohonan_pinjaman') {
        form.meta.request_data = {
            full_name: page.props.auth.user?.name || '',
            position: '',
            work_location: props.branches.find((branch) => branch.id === form.branch_id)?.name || '',
            employment_date: '',
            late_months: '',
            absence_months: '',
            request_number: '',
            salary_after_approval: '',
            minimum_salary: '',
            loan_amount: '',
            repayment_months: '',
            salary_deduction: 'Bersedia',
            loan_type: 'pinjaman_uang',
            reason: '',
        };
        form.meta.direktorat = '';
        form.meta.divisi = '';
        form.meta.kepada_nama = '';
        form.meta.kepada_jabatan = '';
        form.pengantar = '';
        form.rincian_data = [];
        form.keterangan_tambahan = '';
        form.penutup = '';
    } else if (template === 'form_ijin_tidak_masuk_kerja') {
        form.meta.request_data = {
            full_name: page.props.auth.user?.name || '',
            leave_type: 'cuti_tahunan',
            start_date: '',
            duration: '',
            reason: '',
            handover: '',
            substitute: '',
            phone: '',
        };
        form.meta.direktorat = '';
        form.meta.divisi = '';
        form.meta.kepada_nama = '';
        form.meta.kepada_jabatan = '';
        form.pengantar = '';
        form.rincian_data = [];
        form.keterangan_tambahan = '';
        form.penutup = '';
    } else if (template === 'permohonan_biaya_kost') {
        form.meta.direktorat = 'Regional Branch Office';
        form.meta.divisi = 'Branch Leader';
        form.meta.kepada_nama = 'Bpk. Nugroho Samudra Sujatmiko, Ko';
        form.meta.kepada_jabatan = 'Senior Executive Vice President Bisnis dan Operasional';
        form.pengantar = 'Sehubungan dengan kondisi yang mengharuskan saya untuk tinggal di luar kota, maka dengan ini saya mengajukan permohonan biaya kost dengan data sebagai berikut:';
        form.rincian_data = [
            { label: 'Nama', value: '' },
            { label: 'NIK', value: '' },
            { label: 'Nama Pemilik', value: '' },
            { label: 'Nama Kost', value: '' },
            { label: 'No. Tlp', value: '' },
            { label: 'Alamat Kost', value: '' },
            { label: 'Biaya Kost', value: '' },
        ];
        form.keterangan_tambahan = 'Berdasarkan data tersebut, saya mengajukan permohonan agar biaya kost untuk bulan-bulan berikutnya dapat ditransfer sesuai ketentuan yang berlaku.';
        form.penutup = 'Demikian internal memo ini dibuat agar dapat dipergunakan sebagaimana mestinya. Mohon dibantu pembayaran melalui rekening yang tertera. Terima kasih atas perhatian dan kerjasamanya.';
    } else if (template === 'revisi_absensi') {
        form.meta.direktorat = '';
        form.meta.divisi = 'HRD';
        form.meta.kepada_nama = 'Kepala Cabang';
        form.meta.kepada_jabatan = 'HRD';
        form.pengantar = 'Sehubungan dengan adanya kendala absensi, dengan ini kami mengajukan permohonan revisi absensi dengan data sebagai berikut:';
        form.rincian_data = [];
        form.meta.data.rows = [{ nama: '', nik: '', tanggal: '', absensi_in: '', absensi_out: '', ket: 'Revisi Absen' }];
        form.keterangan_tambahan = '';
        form.penutup = 'Demikian berita acara ini saya buat dengan sebenarnya. Terima kasih atas perhatian dan kerjasamanya, saya berharap dapat dibantu memakluminya.';
    } else if (template === 'penghapusan_barang_sitaan') {
        form.meta.direktorat = 'Regional Branch Office';
        form.meta.divisi = 'Branch Leader';
        form.meta.kepada_nama = 'Bpk. Nugroho Samudra Sujatmiko, Ko';
        form.meta.kepada_jabatan = 'Senior Executive Vice President Bisnis dan Operasional';
        form.pengantar = 'Sehubungan dengan adanya penyitaan barang gadai, bersama ini kami sampaikan data barang sebagai berikut:';
        form.rincian_data = [];
        form.meta.data.rows = [{ cabang: '', nama_nasabah: '', no_faktur: '', barang: '', nominal_pinjaman: '' }];
        form.keterangan_tambahan = '';
        form.penutup = 'Demikian berita acara ini dibuat agar dapat dipergunakan sebagaimana mestinya. Terima kasih atas perhatian dan kerjasamanya.';
    } else {
        form.title = '';
        form.meta.direktorat = '';
        form.meta.divisi = '';
        form.meta.perihal = '';
        form.meta.lampiran = '';
        form.meta.kepada_nama = '';
        form.meta.kepada_jabatan = '';
        form.meta.penyetuju_akhir = '';
        form.meta.data.rows = [{ uraian: '', keterangan: '' }];
        form.pengantar = '';
        form.rincian_data = [];
        form.keterangan_tambahan = '';
        form.penutup = '';
    }
});

watch(() => form.branch_id, (branchId) => {
    if (form.meta.template !== 'form_permohonan_pinjaman') return;
    form.meta.request_data.work_location = props.branches.find((branch) => branch.id === branchId)?.name || '';
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

const addTemplateRow = () => {
    const row = form.meta.template === 'revisi_absensi'
        ? { nama: '', nik: '', tanggal: '', absensi_in: '', absensi_out: '', ket: 'Revisi Absen' }
        : form.meta.template === 'penghapusan_barang_sitaan'
            ? { cabang: '', nama_nasabah: '', no_faktur: '', barang: '', nominal_pinjaman: '' }
            : { uraian: '', keterangan: '' };
    form.meta.data.rows.push(row);
};

const removeTemplateRow = (index) => {
    form.meta.data.rows.splice(index, 1);
};

const formatNominalInput = (value) => {
    if (value === undefined || value === null || value === '') return '';
    const digits = String(value).replace(/\D/g, '');
    if (!digits) return '';
    return `Rp ${Number(digits).toLocaleString('id-ID')}`;
};

const parseNominalInput = (value) => {
    const digits = String(value ?? '').replace(/\D/g, '');
    return digits ? Number(digits) : null;
};

const submit = () => {
    form.submit_after_save = false;
    form.transform(data => props.formPengajuanOnly
        ? ({
            branch_id: data.branch_id,
            title: documentLabels[data.meta.template],
            template: data.meta.template,
            meta: { request_data: data.meta.request_data },
            submit: false,
            attachment: form.attachment,
        })
        : ({ ...data, attachment: form.attachment }))
        .post(props.formPengajuanOnly ? route('form-pengajuan.store') : route('berita-acara.store'), {
            forceFormData: true,
            onSuccess: () => success(props.formPengajuanOnly
                ? 'Draft Form Pengajuan berhasil disimpan.'
                : 'Draft Berita Acara berhasil disimpan.'),
        });
};

const submitAndSign = () => {
    if (props.formPengajuanOnly) {
        form.submit_after_save = true;
        form.transform(data => ({
            branch_id: data.branch_id,
            title: documentLabels[data.meta.template],
            template: data.meta.template,
            meta: { request_data: data.meta.request_data },
            submit: true,
            attachment: form.attachment,
        })).post(route('form-pengajuan.store'), { forceFormData: true });
        return;
    }

    form.submit_after_save = true;
    form.transform(data => ({ ...data, attachment: form.attachment }))
        .post(route('berita-acara.store'), { forceFormData: true });
};
</script>


<template>
    <Head :title="formPengajuanOnly ? 'Buat Form Pengajuan' : 'Buat Berita Acara'" />
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-bold mb-0">{{ formPengajuanOnly ? 'Buat Form Pengajuan' : 'Buat Berita Acara Baru' }}</h1>
        </template>

        <a-form layout="vertical" @finish="submit" class="memo-create-page">
            <a-card :bordered="false" class="mb-6 rounded-lg shadow-sm">
                <h2 class="text-sm font-semibold mb-3">Cabang Pengajuan</h2>
                <a-alert v-if="!branches.length" type="warning" show-icon message="Tambahkan cabang di menu Cabang Saya sebelum membuat dokumen." />
                <a-form-item
                    v-else
                    label="Cabang"
                    :validateStatus="form.errors.branch_id ? 'error' : ''"
                    :help="form.errors.branch_id"
                    class="mb-0"
                >
                    <a-select v-model:value="form.branch_id" placeholder="Pilih cabang yang mengajukan" size="large">
                        <a-select-option v-for="branch in branches" :key="branch.id" :value="branch.id">
                            {{ branch.name }}
                        </a-select-option>
                    </a-select>
                </a-form-item>
            </a-card>

            <a-card :bordered="false" class="mb-6 rounded-lg shadow-sm">
                <h2 class="text-lg font-semibold mb-2">{{ formPengajuanOnly ? 'Pilih Template Form Pengajuan' : 'Pilih Template Berita Acara' }}</h2>
                <a-form-item
                    :validateStatus="form.errors['meta.template'] ? 'error' : ''"
                    :help="form.errors['meta.template']"
                    class="mb-0"
                >
                    <a-select
                        v-model:value="form.meta.template"
                        placeholder="Pilih template"
                        :options="availableTemplates"
                        size="large"
                    />
                </a-form-item>
            </a-card>

            <a-row :gutter="24" v-if="form.meta.template">
                <a-col :xs="24" :lg="10" class="mb-6">
                    <div class="flex flex-col gap-6">
                        <a-card v-if="!formPengajuanOnly" :bordered="false" class="rounded-lg shadow-sm">
                            <h2 class="text-sm font-semibold mb-1">Informasi Dokumen</h2>
                            <p class="text-xs text-gray-500 mb-4">Detail header yang tampil di dokumen cetak.</p>

                            <a-form-item label="Judul Berita Acara" class="mb-3"
                                :validateStatus="form.errors.title ? 'error' : ''"
                                :help="form.errors.title"
                            >
                                <a-input v-model:value="form.title" placeholder="Masukkan judul berita acara" size="large" />
                            </a-form-item>

                            <a-form-item label="Direktorat" class="mb-3">
                                <a-input v-model:value="form.meta.direktorat" placeholder="Contoh: Regional Branch Office" />
                            </a-form-item>

                            <a-form-item label="Divisi" class="mb-3">
                                <a-input v-model:value="form.meta.divisi" placeholder="Contoh: Branch Leader / HRD" />
                            </a-form-item>

                            <a-form-item label="Perihal" extra="Opsional, jika berbeda dari judul" class="mb-3">
                                <a-input v-model:value="form.meta.perihal" :placeholder="form.title || 'Mengikuti judul berita acara'" />
                            </a-form-item>

                            <a-form-item label="Kepada (Yth.)" class="mb-3">
                                <a-input v-model:value="form.meta.kepada_nama" placeholder="Nama penerima" />
                            </a-form-item>

                            <a-form-item label="Jabatan Penerima" class="mb-3">
                                <a-input v-model:value="form.meta.kepada_jabatan" placeholder="Contoh: Area Manager / SPV HC Payroll" />
                            </a-form-item>

                            <a-form-item label="Penyetuju Akhir" extra="Nama penyetuju akhir pada dokumen." class="mb-3">
                                <a-input v-model:value="form.meta.penyetuju_akhir" placeholder="Nama penyetuju akhir" />
                            </a-form-item>

                            <a-form-item label="Lampiran" extra="Keterangan lampiran yang tercetak pada dokumen." class="mb-3">
                                <a-input v-model:value="form.meta.lampiran" placeholder="Contoh: 1 Lembar, 3 Berkas, atau -" />
                            </a-form-item>

                        </a-card>

                        <!-- Lampiran Word -->
                        <a-card :bordered="false" class="rounded-lg shadow-sm">
                            <h2 class="text-sm font-semibold mb-1">Lampiran Pendukung</h2>
                            <p class="text-xs text-gray-400 mb-3">Unggah dokumen atau bukti pendukung (PDF, Word, atau gambar; maks. 10MB).</p>
                            <input
                                type="file"
                                @change="selectAttachment"
                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/png"
                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                            />
                            <p v-if="form.attachment" class="mt-2 text-xs text-green-600">✓ {{ form.attachment.name }}</p>
                        </a-card>
                    </div>
                </a-col>

                <a-col :xs="24" :lg="14">
                    <div class="flex flex-col gap-6">
                        <a-card :bordered="false" class="rounded-lg shadow-sm">
                            <h2 class="text-sm font-semibold mb-4">{{ formPengajuanOnly ? 'Isi Form Pengajuan' : 'Isi Berita Acara' }}</h2>
                            <template v-if="formPengajuanOnly && form.meta.template === 'form_permohonan_pinjaman'">
                                <a-alert class="mb-4" type="info" show-icon message="Form Permohonan Pinjaman (FPP)" description="Lengkapi data pemohon dan rincian pinjaman. Lampirkan bukti pendukung bila diperlukan." />
                                <a-row :gutter="12">
                                    <a-col :xs="24" :md="12"><a-form-item label="Nama Lengkap" :validateStatus="form.errors['meta.request_data.full_name'] ? 'error' : ''" :help="form.errors['meta.request_data.full_name']"><a-input v-model:value="form.meta.request_data.full_name" /></a-form-item></a-col>
                                    <a-col :xs="24" :md="12"><a-form-item label="Jabatan" :validateStatus="form.errors['meta.request_data.position'] ? 'error' : ''" :help="form.errors['meta.request_data.position']"><a-input v-model:value="form.meta.request_data.position" /></a-form-item></a-col>
                                    <a-col :xs="24" :md="12"><a-form-item label="Cabang / Lokasi Kerja" :validateStatus="form.errors['meta.request_data.work_location'] ? 'error' : ''" :help="form.errors['meta.request_data.work_location']"><a-input v-model:value="form.meta.request_data.work_location" /></a-form-item></a-col>
                                    <a-col :xs="24" :md="12"><a-form-item label="Tanggal Masuk Kerja" :validateStatus="form.errors['meta.request_data.employment_date'] ? 'error' : ''" :help="form.errors['meta.request_data.employment_date']"><a-input v-model:value="form.meta.request_data.employment_date" type="date" /></a-form-item></a-col>
                                    <a-col :xs="24" :md="8"><a-form-item label="Terlambat (bulan)"><a-input-number v-model:value="form.meta.request_data.late_months" :min="0" class="w-full" /></a-form-item></a-col>
                                    <a-col :xs="24" :md="8"><a-form-item label="Tidak Masuk (bulan)"><a-input-number v-model:value="form.meta.request_data.absence_months" :min="0" class="w-full" /></a-form-item></a-col>
                                    <a-col :xs="24" :md="8"><a-form-item label="Permohonan ke-"><a-input v-model:value="form.meta.request_data.request_number" /></a-form-item></a-col>
                                    <a-col :xs="24" :md="12"><a-form-item label="Gaji setelah disetujui"><a-input v-model:value="form.meta.request_data.salary_after_approval" placeholder="Contoh: Rp 6.000.000" /></a-form-item></a-col>
                                    <a-col :xs="24" :md="12"><a-form-item label="Gaji minimal"><a-input v-model:value="form.meta.request_data.minimum_salary" placeholder="Contoh: Rp 4.500.000" /></a-form-item></a-col>
                                    <a-col :xs="24" :md="12"><a-form-item label="Jumlah pinjaman yang diajukan" :validateStatus="form.errors['meta.request_data.loan_amount'] ? 'error' : ''" :help="form.errors['meta.request_data.loan_amount']"><a-input v-model:value="form.meta.request_data.loan_amount" placeholder="Contoh: Rp 5.000.000" /></a-form-item></a-col>
                                    <a-col :xs="24" :md="12"><a-form-item label="Lama pengembalian (bulan)" :validateStatus="form.errors['meta.request_data.repayment_months'] ? 'error' : ''" :help="form.errors['meta.request_data.repayment_months']"><a-input-number v-model:value="form.meta.request_data.repayment_months" :min="1" class="w-full" /></a-form-item></a-col>
                                    <a-col :xs="24" :md="12"><a-form-item label="Bersedia potong gaji setiap bulan"><a-radio-group v-model:value="form.meta.request_data.salary_deduction"><a-radio value="Bersedia">Bersedia</a-radio><a-radio value="Tidak bersedia">Tidak bersedia</a-radio></a-radio-group></a-form-item></a-col>
                                    <a-col :xs="24" :md="12"><a-form-item label="Jenis Pinjaman"><a-radio-group v-model:value="form.meta.request_data.loan_type"><a-radio value="pinjaman_uang">Pinjaman Uang</a-radio><a-radio value="pembelian_barang">Pembelian Barang</a-radio></a-radio-group></a-form-item></a-col>
                                    <a-col :span="24"><a-form-item label="Alasan Pinjaman" :validateStatus="form.errors['meta.request_data.reason'] ? 'error' : ''" :help="form.errors['meta.request_data.reason']"><a-textarea v-model:value="form.meta.request_data.reason" :rows="4" /></a-form-item></a-col>
                                </a-row>
                            </template>
                            <template v-else-if="formPengajuanOnly && form.meta.template === 'form_ijin_tidak_masuk_kerja'">
                                <a-alert class="mb-4" type="info" show-icon message="Form Ijin Tidak Masuk Kerja (FITMK)" description="Isi detail ketidakhadiran dan serah terima pekerjaan." />
                                <a-row :gutter="12">
                                    <a-col :xs="24" :md="12"><a-form-item label="Nama" :validateStatus="form.errors['meta.request_data.full_name'] ? 'error' : ''" :help="form.errors['meta.request_data.full_name']"><a-input v-model:value="form.meta.request_data.full_name" /></a-form-item></a-col>
                                    <a-col :xs="24" :md="12"><a-form-item label="Detail ijin tidak masuk kerja"><a-select v-model:value="form.meta.request_data.leave_type"><a-select-option value="cuti_tahunan">Cuti Tahunan</a-select-option><a-select-option value="ijin">Ijin (Belum Ada Hak Cuti)</a-select-option><a-select-option value="cuti_melahirkan">Cuti Melahirkan</a-select-option><a-select-option value="cuti_khusus">Cuti Khusus (Menikah/Kematian/Hari Raya)</a-select-option><a-select-option value="sakit">Sakit (Melampirkan Surat Dokter)</a-select-option><a-select-option value="lainnya">Lainnya</a-select-option></a-select></a-form-item></a-col>
                                    <a-col :xs="24" :md="12"><a-form-item label="Tanggal ijin tidak masuk kerja" :validateStatus="form.errors['meta.request_data.start_date'] ? 'error' : ''" :help="form.errors['meta.request_data.start_date']"><a-input v-model:value="form.meta.request_data.start_date" type="date" /></a-form-item></a-col>
                                    <a-col :xs="24" :md="12"><a-form-item label="Durasi (hari)" :validateStatus="form.errors['meta.request_data.duration'] ? 'error' : ''" :help="form.errors['meta.request_data.duration']"><a-input-number v-model:value="form.meta.request_data.duration" :min="1" class="w-full" /></a-form-item></a-col>
                                    <a-col :span="24"><a-form-item label="Alasan ijin tidak masuk kerja" :validateStatus="form.errors['meta.request_data.reason'] ? 'error' : ''" :help="form.errors['meta.request_data.reason']"><a-textarea v-model:value="form.meta.request_data.reason" :rows="3" /></a-form-item></a-col>
                                    <a-col :xs="24" :md="12"><a-form-item label="Pekerjaan selama tidak masuk dilimpahkan ke"><a-input v-model:value="form.meta.request_data.handover" /></a-form-item></a-col>
                                    <a-col :xs="24" :md="12"><a-form-item label="Nama pengganti"><a-input v-model:value="form.meta.request_data.substitute" /></a-form-item></a-col>
                                    <a-col :span="24"><a-form-item label="Kontak selama tidak masuk kerja (Telepon / HP)"><a-input v-model:value="form.meta.request_data.phone" /></a-form-item></a-col>
                                </a-row>
                            </template>
                            <template v-else>
                            <a-form-item label="Kalimat Pengantar (Sehubungan dengan...)" class="mb-4">
                                <a-textarea v-model:value="form.pengantar" :rows="3" />
                            </a-form-item>

                            <div v-if="form.meta.template === 'permohonan_biaya_kost'" class="border border-gray-200 rounded p-4 mb-4">
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

                            <div v-else class="border border-gray-200 rounded p-4 mb-4 overflow-x-auto">
                                <h3 class="font-semibold text-sm mb-3">{{ form.meta.template === 'revisi_absensi' ? 'Data Absensi' : form.meta.template === 'penghapusan_barang_sitaan' ? 'Data Barang Sitaan' : 'Daftar Item' }}</h3>
                                <template v-if="form.meta.template === 'lainnya'">
                                    <table class="w-full min-w-[520px] border-collapse text-sm">
                                        <thead>
                                            <tr class="bg-gray-50">
                                                <th class="border border-gray-200 px-3 py-2 text-center font-semibold">No.</th>
                                                <th class="border border-gray-200 px-3 py-2 text-left font-semibold">Uraian</th>
                                                <th class="border border-gray-200 px-3 py-2 text-left font-semibold">Keterangan</th>
                                                <th class="w-12 border border-gray-200 px-2 py-2"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(row, index) in form.meta.data.rows" :key="index">
                                                <td class="border border-gray-200 px-3 py-2 text-center">{{ index + 1 }}</td>
                                                <td class="border border-gray-200 px-2 py-2"><a-input v-model:value="row.uraian" placeholder="Uraian item" /></td>
                                                <td class="border border-gray-200 px-2 py-2"><a-input v-model:value="row.keterangan" placeholder="Keterangan" /></td>
                                                <td class="border border-gray-200 px-1 py-2 text-center">
                                                    <a-button type="text" danger aria-label="Hapus baris" @click="removeTemplateRow(index)">
                                                        <template #icon><delete-outlined /></template>
                                                    </a-button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <a-button type="dashed" block class="mt-3" @click="addTemplateRow">
                                        <template #icon><plus-outlined /></template>
                                        Tambah Baris
                                    </a-button>
                                </template>
                                <template v-else>
                                <div v-for="(row, index) in form.meta.data.rows" :key="index" class="border border-gray-200 rounded p-3 mb-3">
                                    <div class="flex justify-between items-center mb-2">
                                        <strong class="text-xs">Baris {{ index + 1 }}</strong>
                                        <a-button type="text" danger @click="removeTemplateRow(index)">
                                            <template #icon><delete-outlined /></template>
                                        </a-button>
                                    </div>
                                    <template v-if="form.meta.template === 'revisi_absensi'">
                                        <a-row :gutter="8">
                                            <a-col :xs="24" :sm="12" :md="8"><a-input v-model:value="row.nama" placeholder="Nama" class="mb-2" /></a-col>
                                            <a-col :xs="24" :sm="12" :md="8"><a-input v-model:value="row.nik" placeholder="NIK" class="mb-2" /></a-col>
                                            <a-col :xs="24" :sm="12" :md="8"><a-input v-model:value="row.tanggal" placeholder="Tanggal" class="mb-2" /></a-col>
                                            <a-col :xs="24" :sm="12" :md="8"><a-input v-model:value="row.absensi_in" placeholder="Absensi IN" class="mb-2" /></a-col>
                                            <a-col :xs="24" :sm="12" :md="8"><a-input v-model:value="row.absensi_out" placeholder="Absensi Out" class="mb-2" /></a-col>
                                            <a-col :xs="24" :sm="12" :md="8"><a-input v-model:value="row.ket" placeholder="Keterangan" class="mb-2" /></a-col>
                                        </a-row>
                                    </template>
                                    <template v-else>
                                        <a-row :gutter="8">
                                            <a-col :xs="24" :sm="12" :md="8"><a-input v-model:value="row.cabang" placeholder="Cabang" class="mb-2" /></a-col>
                                            <a-col :xs="24" :sm="12" :md="8"><a-input v-model:value="row.nama_nasabah" placeholder="Nama Nasabah" class="mb-2" /></a-col>
                                            <a-col :xs="24" :sm="12" :md="8"><a-input v-model:value="row.no_faktur" placeholder="No. Faktur" class="mb-2" /></a-col>
                                            <a-col :xs="24" :sm="12" :md="12"><a-input v-model:value="row.barang" placeholder="Barang" class="mb-2" /></a-col>
                                            <a-col :xs="24" :sm="12" :md="12"><a-input-number v-model:value="row.nominal_pinjaman" :precision="0" :formatter="formatNominalInput" :parser="parseNominalInput" placeholder="Nominal Pinjaman" class="mb-2 w-full" /></a-col>
                                        </a-row>
                                    </template>
                                </div>
                                <a-button type="dashed" block @click="addTemplateRow">
                                    <template #icon><plus-outlined /></template>
                                    Tambah Baris
                                </a-button>
                                <a-form-item v-if="form.meta.template === 'penghapusan_barang_sitaan'" label="Kronologi" class="mt-4 mb-0">
                                    <a-textarea v-model:value="form.meta.data.kronologi" :rows="5" />
                                </a-form-item>
                                </template>
                            </div>

                            <a-form-item v-if="form.meta.template !== 'penghapusan_barang_sitaan'" label="Keterangan Tambahan / Alasan" class="mb-4">
                                <a-textarea v-model:value="form.keterangan_tambahan" :rows="4" placeholder="Misal: Rincian pinalty, atau penjelasan lebih lanjut..." />
                            </a-form-item>
                            
                            <a-form-item label="Kalimat Penutup" class="mb-0">
                                <a-textarea v-model:value="form.penutup" :rows="2" />
                            </a-form-item>
                            </template>
                        </a-card>

                        <!-- Action Buttons -->
                        <div class="pt-2 flex flex-col sm:flex-row justify-end gap-3">
                            <a-button size="large" type="default" @click="submit">
                                <template #icon><save-outlined /></template>
                                Simpan Draft
                            </a-button>
                            <a-button size="large" type="primary" @click="submitAndSign" class="bg-green-600 hover:bg-green-500 border-green-600">
                                <template #icon><send-outlined /></template>
                                {{ formPengajuanOnly ? 'Kirim untuk Persetujuan' : 'Tanda Tangani & Kirim' }}
                            </a-button>
                        </div>
                    </div>
                </a-col>
            </a-row>
        </a-form>
    </AuthenticatedLayout>
</template>
