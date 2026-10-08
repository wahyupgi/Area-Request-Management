<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { useSweetAlert } from '@/composables/useSweetAlert';
import { preventNonDigitPaste, restrictToDigits } from '@/composables/numericInput';
import { SaveOutlined, SendOutlined, PlusOutlined, DeleteOutlined } from '@ant-design/icons-vue';

const props = defineProps({
    formPengajuanOnly: { type: Boolean, default: false },
    branches: { type: Array, default: () => [] },
    templates: { type: Array, default: () => [] },
});

const page = usePage();
const { success } = useSweetAlert();
const availableTemplates = computed(() => {
    return (props.templates || []).map(t => ({ value: t.name, label: t.name, templateData: t }));
});

const formatRupiahAmount = (value) => {
    const digits = String(value ?? '').replace(/\D/g, '');
    return digits ? `Rp ${digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.')}` : '';
};
const requestAmountModel = (field) => computed({
    get: () => formatRupiahAmount(form.meta.request_data[field]),
    set: (value) => {
        const digits = String(value ?? '').replace(/\D/g, '');
        form.meta.request_data[field] = digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    },
});
const loanAmount = requestAmountModel('loan_amount');
const salaryAfterApproval = requestAmountModel('salary_after_approval');
const minimumSalary = requestAmountModel('minimum_salary');

const form = useForm({
    branch_id: undefined,
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

watch(() => form.meta.template, (templateName) => {
    const selected = availableTemplates.value.find((item) => item.value === templateName);
    if (!selected) return;

    const t = selected.templateData;
    form.title = t.name;
    form.meta.perihal = t.document_defaults?.perihal || t.name;
    form.meta.data = { rows: [], kronologi: '' };

    if (t.type === 'form') {
        const requestData = {};
        (t.field_schema || []).forEach(field => {
            if (field.key === 'full_name') requestData[field.key] = page.props.auth.user?.name || '';
            else requestData[field.key] = '';
        });
        
        if (t.name === 'Form Permohonan Pinjaman (FPP)') {
            requestData.salary_deduction = 'Bersedia';
            requestData.loan_type = 'pinjaman_uang';
        } else if (t.name === 'Form Ijin Tidak Masuk Kerja (FITMK)') {
            requestData.leave_type = 'cuti_tahunan';
            requestData.substitute_position = '';
        }
        form.meta.request_data = requestData;
        
        form.meta.direktorat = '';
        form.meta.divisi = '';
        form.meta.kepada_nama = '';
        form.meta.kepada_jabatan = '';
        form.pengantar = '';
        form.rincian_data = [];
        form.keterangan_tambahan = '';
        form.penutup = '';
    } else {
        form.meta.direktorat = t.document_defaults?.direktorat || '';
        form.meta.divisi = t.document_defaults?.divisi || '';
        form.meta.kepada_nama = t.document_defaults?.kepada || '';
        form.meta.kepada_jabatan = t.document_defaults?.kepada_jabatan || '';
        form.meta.lampiran = t.document_defaults?.lampiran || '';
        form.pengantar = t.document_defaults?.pengantar || '';
        form.meta.penyetuju_akhir = t.document_defaults?.penyetuju_akhir || '';
        
        if (t.name === 'Permintaan Revisi Absensi') {
            form.rincian_data = [];
            form.meta.data.rows = [{ nama: '', nik: '', tanggal: '', absensi_in: '', absensi_out: '', ket: 'Revisi Absen' }];
        } else if (t.name === 'Penghapusan Barang Sitaan') {
            form.rincian_data = [];
            form.meta.data.rows = [{ cabang: '', nama_nasabah: '', no_faktur: '', barang: '', nominal_pinjaman: '' }];
        } else if (t.name === 'Berita Acara Lainnya') {
            form.rincian_data = [];
            form.meta.data.rows = [{ uraian: '', keterangan: '' }];
        } else {
            form.rincian_data = (t.field_schema || []).map(f => ({ label: f.label, value: '' }));
            form.meta.data.rows = [];
        }

        form.keterangan_tambahan = t.document_defaults?.keterangan_tambahan || '';
        form.penutup = t.document_defaults?.penutup || '';
    }
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
    const row = form.meta.template === 'Permintaan Revisi Absensi'
        ? { nama: '', nik: '', tanggal: '', absensi_in: '', absensi_out: '', ket: 'Revisi Absen' }
        : form.meta.template === 'Penghapusan Barang Sitaan'
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

const restrictDetailDigits = (event, label) => {
    if (/nik|nomor telepon|no\.?\s*tlp|biaya kost/i.test(label || '')) {
        restrictToDigits(event);
    }
};

const preventNonDigitDetailPaste = (event, label) => {
    if (/nik|nomor telepon|no\.?\s*tlp|biaya kost/i.test(label || '')) {
        preventNonDigitPaste(event);
    }
};

const isRupiahDetail = (label) => /biaya kost|nominal|rupiah|pinjaman|harga/i.test(label || '');
const updateDetailValue = (item, value) => {
    item.value = isRupiahDetail(item.label)
        ? formatRupiahAmount(value)
        : value;
};
const formattedDetailValue = (item) => isRupiahDetail(item.label)
    ? formatRupiahAmount(item.value)
    : item.value;

const submit = () => {
    form.submit_after_save = false;
    form.transform(data => props.formPengajuanOnly
        ? ({
            title: data.title,
            branch_id: data.branch_id,
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
            title: data.title,
            branch_id: data.branch_id,
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
                <h2 class="text-lg font-semibold mb-2">{{ formPengajuanOnly ? 'Pilih Cabang dan Jenis Form Pengajuan' : 'Pilih Cabang dan Jenis Berita Acara' }}</h2>
                <a-form-item
                    label="Cabang"
                    :validateStatus="form.errors.branch_id ? 'error' : ''"
                    :help="form.errors.branch_id"
                >
                    <a-select
                        v-model:value="form.branch_id"
                        placeholder="Pilih cabang"
                        :options="branches.map(branch => ({ value: branch.id, label: branch.area?.name ? `${branch.name} — ${branch.area.name}` : branch.name }))"
                        size="large"
                    />
                </a-form-item>
                <a-form-item
                    :label="formPengajuanOnly ? 'Jenis Form Pengajuan' : 'Jenis Berita Acara'"
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
                            <template v-if="formPengajuanOnly && form.meta.template === 'Form Permohonan Pinjaman (FPP)'">
                                <a-alert class="mb-4" type="info" show-icon message="Form Permohonan Pinjaman (FPP)" description="Lengkapi data pemohon dan rincian pinjaman. Lampirkan bukti pendukung bila diperlukan." />
                                <a-row :gutter="12">
                                    <a-col :xs="24" :md="12"><a-form-item label="Nama Lengkap" :validateStatus="form.errors['meta.request_data.full_name'] ? 'error' : ''" :help="form.errors['meta.request_data.full_name']"><a-input v-model:value="form.meta.request_data.full_name" /></a-form-item></a-col>
                                    <a-col :xs="24" :md="12"><a-form-item label="Jabatan" :validateStatus="form.errors['meta.request_data.position'] ? 'error' : ''" :help="form.errors['meta.request_data.position']"><a-input v-model:value="form.meta.request_data.position" /></a-form-item></a-col>
                                    <a-col :xs="24" :md="12"><a-form-item label="Cabang / Lokasi Kerja" :validateStatus="form.errors['meta.request_data.work_location'] ? 'error' : ''" :help="form.errors['meta.request_data.work_location']"><a-input v-model:value="form.meta.request_data.work_location" /></a-form-item></a-col>
                                    <a-col :xs="24" :md="12"><a-form-item label="Tanggal Masuk Kerja" :validateStatus="form.errors['meta.request_data.employment_date'] ? 'error' : ''" :help="form.errors['meta.request_data.employment_date']"><a-input v-model:value="form.meta.request_data.employment_date" type="date" /></a-form-item></a-col>
                                    <a-col :xs="24" :md="8"><a-form-item label="Terlambat (bulan)"><a-input-number v-model:value="form.meta.request_data.late_months" :min="0" @keydown="restrictToDigits" @paste="preventNonDigitPaste" class="w-full" /></a-form-item></a-col>
                                    <a-col :xs="24" :md="8"><a-form-item label="Tidak Masuk (bulan)"><a-input-number v-model:value="form.meta.request_data.absence_months" :min="0" @keydown="restrictToDigits" @paste="preventNonDigitPaste" class="w-full" /></a-form-item></a-col>
                                    <a-col :xs="24" :md="8"><a-form-item label="Permohonan ke-"><a-input v-model:value="form.meta.request_data.request_number" @keydown="restrictToDigits" @paste="preventNonDigitPaste" /></a-form-item></a-col>
                                    <a-col :xs="24" :md="12"><a-form-item label="Gaji setelah disetujui"><a-input v-model:value="salaryAfterApproval" placeholder="Contoh: 6.000.000" /></a-form-item></a-col>
                                    <a-col :xs="24" :md="12"><a-form-item label="Gaji minimal"><a-input v-model:value="minimumSalary" placeholder="Contoh: 4.500.000" /></a-form-item></a-col>
                                    <a-col :xs="24" :md="12"><a-form-item label="Jumlah pinjaman yang diajukan" :validateStatus="form.errors['meta.request_data.loan_amount'] ? 'error' : ''" :help="form.errors['meta.request_data.loan_amount']"><a-input v-model:value="loanAmount" placeholder="Contoh: 5.000.000" /></a-form-item></a-col>
                                    <a-col :xs="24" :md="12"><a-form-item label="Lama pengembalian (bulan)" :validateStatus="form.errors['meta.request_data.repayment_months'] ? 'error' : ''" :help="form.errors['meta.request_data.repayment_months']"><a-input-number v-model:value="form.meta.request_data.repayment_months" :min="1" @keydown="restrictToDigits" @paste="preventNonDigitPaste" class="w-full" /></a-form-item></a-col>
                                    <a-col :xs="24" :md="12"><a-form-item label="Bersedia potong gaji setiap bulan"><a-radio-group v-model:value="form.meta.request_data.salary_deduction"><a-radio value="Bersedia">Bersedia</a-radio><a-radio value="Tidak bersedia">Tidak bersedia</a-radio></a-radio-group></a-form-item></a-col>
                                    <a-col :xs="24" :md="12"><a-form-item label="Jenis Pinjaman"><a-radio-group v-model:value="form.meta.request_data.loan_type"><a-radio value="pinjaman_uang">Pinjaman Uang</a-radio><a-radio value="pembelian_barang">Pembelian Barang</a-radio></a-radio-group></a-form-item></a-col>
                                    <a-col :span="24"><a-form-item label="Alasan Pinjaman" :validateStatus="form.errors['meta.request_data.reason'] ? 'error' : ''" :help="form.errors['meta.request_data.reason']"><a-textarea v-model:value="form.meta.request_data.reason" :rows="4" /></a-form-item></a-col>
                                </a-row>
                            </template>
                            <template v-else-if="formPengajuanOnly && form.meta.template === 'Form Ijin Tidak Masuk Kerja (FITMK)'">
                                <a-alert class="mb-4" type="info" show-icon message="Form Ijin Tidak Masuk Kerja (FITMK)" description="Isi detail ketidakhadiran dan serah terima pekerjaan." />
                                <a-row :gutter="12">
                                    <a-col :xs="24" :md="12"><a-form-item label="Nama" :validateStatus="form.errors['meta.request_data.full_name'] ? 'error' : ''" :help="form.errors['meta.request_data.full_name']"><a-input v-model:value="form.meta.request_data.full_name" /></a-form-item></a-col>
                                    <a-col :xs="24" :md="12"><a-form-item label="Detail ijin tidak masuk kerja"><a-select v-model:value="form.meta.request_data.leave_type"><a-select-option value="cuti_tahunan">Cuti Tahunan</a-select-option><a-select-option value="ijin">Ijin (Belum Ada Hak Cuti)</a-select-option><a-select-option value="cuti_melahirkan">Cuti Melahirkan</a-select-option><a-select-option value="cuti_khusus">Cuti Khusus (Menikah/Kematian/Hari Raya)</a-select-option><a-select-option value="sakit">Sakit (Melampirkan Surat Dokter)</a-select-option><a-select-option value="lainnya">Lainnya</a-select-option></a-select></a-form-item></a-col>
                                    <a-col :xs="24" :md="12"><a-form-item label="Tanggal ijin tidak masuk kerja" :validateStatus="form.errors['meta.request_data.start_date'] ? 'error' : ''" :help="form.errors['meta.request_data.start_date']"><a-input v-model:value="form.meta.request_data.start_date" type="date" /></a-form-item></a-col>
                                    <a-col :xs="24" :md="12"><a-form-item label="Durasi (hari)" :validateStatus="form.errors['meta.request_data.duration'] ? 'error' : ''" :help="form.errors['meta.request_data.duration']"><a-input-number v-model:value="form.meta.request_data.duration" :min="1" @keydown="restrictToDigits" @paste="preventNonDigitPaste" class="w-full" /></a-form-item></a-col>
                                    <a-col :span="24"><a-form-item label="Alasan ijin tidak masuk kerja" :validateStatus="form.errors['meta.request_data.reason'] ? 'error' : ''" :help="form.errors['meta.request_data.reason']"><a-textarea v-model:value="form.meta.request_data.reason" :rows="3" /></a-form-item></a-col>
                                    <a-col :xs="24" :md="12"><a-form-item label="Pekerjaan selama tidak masuk dilimpahkan ke"><a-input v-model:value="form.meta.request_data.handover" /></a-form-item></a-col>
                                    <a-col :xs="24" :md="12"><a-form-item label="Nama pengganti"><a-input v-model:value="form.meta.request_data.substitute" /></a-form-item></a-col>
                                    <a-col :xs="24" :md="12"><a-form-item label="Jabatan pengganti"><a-input v-model:value="form.meta.request_data.substitute_position" placeholder="Contoh: Kepala Unit" /></a-form-item></a-col>
                                    <a-col :span="24"><a-form-item label="Kontak selama tidak masuk kerja (Telepon / HP)"><a-input v-model:value="form.meta.request_data.phone" @keydown="restrictToDigits" @paste="preventNonDigitPaste" /></a-form-item></a-col>
                                </a-row>
                            </template>
                            <template v-else>
                            <a-form-item label="Kalimat Pengantar (Sehubungan dengan...)" class="mb-4">
                                <a-textarea v-model:value="form.pengantar" :rows="3" />
                            </a-form-item>

                            <div v-if="!['Permintaan Revisi Absensi', 'Penghapusan Barang Sitaan', 'Berita Acara Lainnya'].includes(form.meta.template)" class="border border-gray-200 rounded p-4 mb-4">
                                <h3 class="font-semibold text-sm mb-3">Data Rincian</h3>
                                <template v-if="form.meta.template === 'template_1'">
                                    <div v-for="(label, index) in ['Cabang', 'Faktur', 'Tujuan', 'Nominal']" :key="label" class="flex gap-3 mb-2 items-center">
                                        <span class="w-[35%] flex-shrink-0">{{ label }}</span>
                                        <span class="text-gray-400">:</span>
                                        <a-input
                                            :value="formattedDetailValue(form.rincian_data[index])"
                                            placeholder="Isi Data"
                                            class="flex-1"
                                            @update:value="value => updateDetailValue(form.rincian_data[index], value)"
                                            @keydown="event => restrictDetailDigits(event, label)"
                                            @paste="event => preventNonDigitDetailPaste(event, label)"
                                        />
                                    </div>
                                </template>
                                <template v-else>
                                    <div v-for="(item, index) in form.rincian_data" :key="index" class="flex gap-3 mb-2 items-center">
                                        <a-input v-model:value="item.label" placeholder="Label (Misal: NIK)" style="width: 35%;" />
                                        <span class="text-gray-400">:</span>
                                        <a-input :value="formattedDetailValue(item)" placeholder="Isi Data" class="flex-1" @update:value="value => updateDetailValue(item, value)" @keydown="event => restrictDetailDigits(event, item.label)" @paste="event => preventNonDigitDetailPaste(event, item.label)" />
                                        <a-button type="text" danger @click="removeRincian(index)" class="flex-shrink-0">
                                            <template #icon><delete-outlined /></template>
                                        </a-button>
                                    </div>
                                    <a-button type="dashed" block class="mt-2" @click="addRincian">
                                        <template #icon><plus-outlined /></template>
                                        Tambah Baris Data
                                    </a-button>
                                </template>
                            </div>

                            <div v-else class="border border-gray-200 rounded p-4 mb-4 overflow-x-auto">
                                <h3 class="font-semibold text-sm mb-3">{{ form.meta.template === 'Permintaan Revisi Absensi' ? 'Data Absensi' : form.meta.template === 'Penghapusan Barang Sitaan' ? 'Data Barang Sitaan' : 'Daftar Item' }}</h3>
                                <template v-if="form.meta.template === 'Berita Acara Lainnya'">
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
                                    <template v-if="form.meta.template === 'Permintaan Revisi Absensi'">
                                        <a-row :gutter="8">
                                            <a-col :xs="24" :sm="12" :md="8"><a-input v-model:value="row.nama" placeholder="Nama" class="mb-2" /></a-col>
                                            <a-col :xs="24" :sm="12" :md="8"><a-input v-model:value="row.nik" placeholder="NIK" @keydown="restrictToDigits" @paste="preventNonDigitPaste" class="mb-2" /></a-col>
                                            <a-col :xs="24" :sm="12" :md="8"><a-input v-model:value="row.tanggal" placeholder="Tanggal" class="mb-2" /></a-col>
                                            <a-col :xs="24" :sm="12" :md="8"><a-input v-model:value="row.absensi_in" placeholder="Absensi IN" class="mb-2" /></a-col>
                                            <a-col :xs="24" :sm="12" :md="8"><a-input v-model:value="row.absensi_out" placeholder="Absensi Out" class="mb-2" /></a-col>
                                            <a-col :xs="24" :sm="12" :md="8"><a-input v-model:value="row.ket" placeholder="Keterangan" class="mb-2" /></a-col>
                                        </a-row>
                                    </template>
                                    <template v-else-if="form.meta.template === 'Penghapusan Barang Sitaan'">
                                        <div class="flex items-center gap-3 mb-2">
                                            <span class="w-[35%] flex-shrink-0">Cabang</span>
                                            <span class="text-gray-400">:</span>
                                            <a-input v-model:value="row.cabang" placeholder="Isi Data" class="flex-1" />
                                        </div>
                                        <div class="flex items-center gap-3 mb-2">
                                            <span class="w-[35%] flex-shrink-0">Nama Nasabah</span>
                                            <span class="text-gray-400">:</span>
                                            <a-input v-model:value="row.nama_nasabah" placeholder="Isi Data" class="flex-1" />
                                        </div>
                                        <div class="flex items-center gap-3 mb-2">
                                            <span class="w-[35%] flex-shrink-0">No. Faktur</span>
                                            <span class="text-gray-400">:</span>
                                            <a-input v-model:value="row.no_faktur" placeholder="Isi Data" class="flex-1" />
                                        </div>
                                        <div class="flex items-center gap-3 mb-2">
                                            <span class="w-[35%] flex-shrink-0">Barang</span>
                                            <span class="text-gray-400">:</span>
                                            <a-input v-model:value="row.barang" placeholder="Isi Data" class="flex-1" />
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="w-[35%] flex-shrink-0">Nominal Pinjaman</span>
                                            <span class="text-gray-400">:</span>
                                            <a-input-number v-model:value="row.nominal_pinjaman" :precision="0" :formatter="formatNominalInput" :parser="parseNominalInput" @keydown="restrictToDigits" @paste="preventNonDigitPaste" placeholder="Isi Data" class="flex-1 w-full" />
                                        </div>
                                    </template>
                                    <template v-else>
                                        <a-row :gutter="8">
                                            <a-col :span="24"><a-input v-model:value="row.cabang" placeholder="Cabang" class="mb-2" /></a-col>
                                            <a-col :span="24"><a-input v-model:value="row.nama_nasabah" placeholder="Nama Nasabah" class="mb-2" /></a-col>
                                            <a-col :span="24"><a-input v-model:value="row.no_faktur" placeholder="No. Faktur" class="mb-2" /></a-col>
                                            <a-col :span="24"><a-input v-model:value="row.barang" placeholder="Barang" class="mb-2" /></a-col>
                                            <a-col :span="24"><a-input-number v-model:value="row.nominal_pinjaman" :precision="0" :formatter="formatNominalInput" :parser="parseNominalInput" @keydown="restrictToDigits" @paste="preventNonDigitPaste" placeholder="Nominal Pinjaman" class="mb-2 w-full" /></a-col>
                                        </a-row>
                                    </template>
                                </div>
                                <a-button type="dashed" block @click="addTemplateRow">
                                    <template #icon><plus-outlined /></template>
                                    Tambah Baris
                                </a-button>
                                <a-form-item v-if="form.meta.template === 'Penghapusan Barang Sitaan'" label="Kronologi" class="mt-4 mb-0">
                                    <a-textarea v-model:value="form.meta.data.kronologi" :rows="5" />
                                </a-form-item>
                                </template>
                            </div>

                            <a-form-item v-if="form.meta.template !== 'Penghapusan Barang Sitaan'" label="Keterangan Tambahan / Alasan" class="mb-4">
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
