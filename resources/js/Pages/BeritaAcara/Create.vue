<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { watch } from 'vue';
import { useSweetAlert } from '@/composables/useSweetAlert';
import { SaveOutlined, SendOutlined, PlusOutlined, DeleteOutlined } from '@ant-design/icons-vue';

const { success } = useSweetAlert();

const templates = [
    { value: 'permohonan_biaya_kost', label: 'Permohonan Biaya Kost' },
    { value: 'revisi_absensi', label: 'Permintaan Revisi Absensi' },
    { value: 'penghapusan_barang_sitaan', label: 'Penghapusan Barang Sitaan' },
    { value: 'lainnya', label: 'Lainnya' },
];

const form = useForm({
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
    const selected = templates.find((item) => item.value === template);
    if (!selected) return;

    form.title = selected.label;
    form.meta.perihal = selected.label;
    form.meta.data = { rows: [], kronologi: '' };

    if (template === 'permohonan_biaya_kost') {
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
            <a-card :bordered="false" class="mb-6 rounded-lg shadow-sm">
                <h2 class="text-lg font-semibold mb-2">Pilih Template Berita Acara</h2>
                <a-form-item
                    :validateStatus="form.errors['meta.template'] ? 'error' : ''"
                    :help="form.errors['meta.template']"
                    class="mb-0"
                >
                    <a-select
                        v-model:value="form.meta.template"
                        placeholder="Pilih kategori dan template Berita Acara"
                        :options="templates"
                        size="large"
                    />
                </a-form-item>
            </a-card>

            <a-row :gutter="24" v-if="form.meta.template">
                <a-col :xs="24" :lg="10" class="mb-6">
                    <div class="flex flex-col gap-6">
                        <a-card :bordered="false" class="rounded-lg shadow-sm">
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
