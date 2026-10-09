<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KcMappingSeeder extends Seeder
{
    public function run(): void
    {
        // =====================================================================
        // Data dari file "Daftar Mapping KC dan wilayah.xlsx"
        // Struktur: KC Name => [ City => [ Branch codes ] ]
        // Semua berada di Provinsi "Jawa Tengah"
        // =====================================================================

        $mappingData = [
            'Abdul Fatah Fadilah' => [
                'Kab. Grobogan' => ['PWD001', 'PWD002', 'PWD003', 'PWD004', 'PWD005', 'PWD006', 'PWD007'],
            ],
            'Abdurohman' => [
                'Kab. Temanggung' => ['TMG001', 'TMG002', 'TMG003', 'TMG004', 'TMG005', 'TMG006'],
            ],
            'Afif Agus Pratama' => [
                'Kab. Banyumas' => ['PWT005', 'PWT007', 'PWT009', 'PWT010', 'PWT013'],
            ],
            'Ageng Hartanto' => [
                'Kab. Boyolali' => ['BYL003', 'BYL004', 'BYL006', 'BYL008', 'BYL009', 'BYL010', 'BYL011', 'BYL012', 'BYL013'],
            ],
            'Agung Wiranata' => [
                'Kab. Semarang' => ['UNR004', 'UNR006', 'UNR011', 'UNR014'],
            ],
            'Aldo Ramadhan' => [
                'Kab. Jepara' => ['JPA001', 'JPA002', 'JPA003', 'JPA004', 'JPA005', 'JPA006'],
            ],
            'Alya Shaffira Adzani' => [
                'Kab. Purbalingga' => ['PBG006', 'PBG007', 'PBG008', 'PBG009', 'PBG011'],
            ],
            'Ana Mahbubah' => [
                'Kab. Pekalongan' => ['PKL001', 'PKL002', 'PKL003', 'PKL004', 'PKL005', 'PKL006'],
            ],
            'Andreas Setia Pratama' => [
                'Kab. Sukoharjo' => ['SKH001', 'SKH002', 'SKH004', 'SKH005', 'SKH009', 'SKH012', 'SKH013'],
            ],
            'Anggie Lidya Putri' => [
                'Kab. Semarang' => ['UNR003', 'UNR007', 'UNR009', 'UNR010', 'UNR012', 'UNR013'],
            ],
            'Aqbil Rizki' => [
                'Kab. Sragen' => ['SGN001', 'SGN002', 'SGN003', 'SGN004', 'SGN005', 'SGN006', 'SGN007', 'SGN008', 'SGN009', 'SGN010', 'SGN011'],
            ],
            'Asep Tio Wahyudi' => [
                'Kab. Klaten' => ['KLN002', 'KLN003', 'KLN004', 'KLN006', 'KLN010', 'KLN012', 'KLN013', 'KLN014'],
            ],
            'Cempaka Rizkiana' => [
                'Kab. Brebes' => ['BBS002', 'BBS006', 'BBS007', 'BBS010', 'BBS016', 'BBS023', 'BBS025'],
            ],
            'Deri Sutiawan' => [
                'Kab. Magelang' => ['MKD002', 'MKD003', 'MKD004', 'MKD006', 'MKD007'],
            ],
            'Dewi Widyani' => [
                'Kab. Brebes' => ['BBS008', 'BBS009', 'BBS011', 'BBS017', 'BBS018', 'BBS019', 'BBS024'],
            ],
            'Dinda Wulan Suciana' => [
                'Kab. Batang' => ['BTG003', 'BTG004', 'BTG007', 'BTG008', 'BTG009'],
                'Kab. Kendal' => ['KDL001', 'KDL002', 'KDL003', 'KDL004', 'KDL005'],
            ],
            'Doni Robyansyah' => [
                'Kab. Pekalongan' => ['KJN001', 'KJN002', 'KJN003', 'KJN004', 'KJN005', 'KJN006'],
            ],
            'Fauziah Mulyaningrum' => [
                'Kab. Brebes' => ['BBS001', 'BBS003', 'BBS013', 'BBS014', 'BBS015', 'BBS022', 'BBS026'],
            ],
            'Fidiana Haritza Hilalia' => [
                'Kab. Tegal' => ['SLW004', 'SLW006', 'SLW009', 'SLW012', 'SLW014'],
            ],
            'Firman Nur Diansyah' => [
                'Kab. Wonogiri' => ['WNG001', 'WNG002', 'WNG003', 'WNG004', 'WNG005', 'WNG006', 'WNG007', 'WNG008'],
            ],
            'Hadi Putra Permana' => [
                'Kab. Demak' => ['DMK001', 'DMK002', 'DMK003', 'DMK004', 'DMK005', 'DMK006', 'DMK007'],
            ],
            'Intan Nuraeni' => [
                'Kab. Batang' => ['BTG001', 'BTG002', 'BTG005', 'BTG006', 'BTG010', 'BTG011'],
            ],
            'Irgi Akmall Agustian' => [
                'Kab. Pemalang' => ['PML004', 'PML006', 'PML009', 'PML011', 'PML014', 'PML016'],
            ],
            'Jihan Auliana Fitri' => [
                'Kab. Pemalang' => ['PML002', 'PML005', 'PML008', 'PML010', 'PML012', 'PML015'],
            ],
            'Kevin Aldiada' => [
                'Kab. Tegal' => ['SLW002', 'SLW007', 'SLW008', 'SLW010', 'SLW013', 'SLW015', 'SLW016', 'SLW019'],
            ],
            'Meilina Putri' => [
                'Kab. Purbalingga' => ['PBG001', 'PBG002', 'PBG003', 'PBG004', 'PBG005', 'PBG010'],
            ],
            'Muhamad Khafidz Baharudin' => [
                'Kab. Banyumas' => ['PWT014', 'PWT015', 'PWT016', 'PWT018', 'PWT020', 'PWT023', 'PWT025'],
            ],
            'Muhammad Iqbal Ramadhan' => [
                'Kab. Karanganyar' => ['KRG001', 'KRG002', 'KRG003', 'KRG004', 'KRG005', 'KRG006', 'KRG007', 'KRG008', 'KRG009'],
            ],
            'Muhammad Ridwan' => [
                'Kab. Pemalang' => ['PML001', 'PML003', 'PML007', 'PML013'],
            ],
            'Mutiah Sabrina' => [
                'Kab. Cilacap' => ['CLP002', 'CLP007', 'CLP008', 'CLP009', 'CLP010', 'CLP011', 'CLP013'],
            ],
            'Nurhalimah' => [
                'Kab. Tegal' => ['SLW001', 'SLW003', 'SLW005', 'SLW011', 'SLW017', 'SLW018'],
            ],
            'Pitri Nopita Sari' => [
                'Kab. Banjarnegara' => ['BNR001', 'BNR002', 'BNR003'],
                'Kab. Wonosobo' => ['WSB001', 'WSB002', 'WSB003', 'WSB004', 'WSB005', 'WSB006', 'WSB007', 'WSB008'],
            ],
            'Putri Retnosari' => [
                'Kab. Banyumas' => ['PWT001', 'PWT002', 'PWT012', 'PWT017'],
            ],
            'Rachel Audyta' => [
                'Kab. Sukoharjo' => ['SKH003', 'SKH006', 'SKH007', 'SKH008', 'SKH010', 'SKH011'],
            ],
            'Reza Dikki Wahyudi' => [
                'Kab. Blora' => ['BLA001', 'BLA002', 'BLA003', 'BLA004', 'BLA005', 'BLA006', 'BLA007'],
            ],
            'Reza Triyani Herera' => [
                'Kab. Salatiga' => ['SLT001', 'SLT002', 'SLT003', 'SLT004'],
                'Kab. Semarang' => ['UNR001', 'UNR002', 'UNR005', 'UNR008', 'UNR015'],
            ],
            'Rina Dwi Tania' => [
                'Kab. Tegal' => ['TGL001', 'TGL002', 'TGL003', 'TGL004', 'TGL005', 'TGL006', 'TGL007'],
            ],
            'Rizky Al Azhar' => [
                'Kab. Kebumen' => ['KBM001', 'KBM002', 'KBM003', 'KBM004', 'KBM005'],
                'Kab. Purworejo' => ['PWR001', 'PWR002', 'PWR003'],
            ],
            'Salbia Nugraha' => [
                'Kab. Cilacap' => ['CLP001', 'CLP003', 'CLP004', 'CLP005', 'CLP006', 'CLP012', 'CLP014'],
            ],
            'Septiyana Anjarwati' => [
                'Kota Surakarta' => ['SKT001', 'SKT002', 'SKT003', 'SKT004', 'SKT005', 'SKT006', 'SKT007', 'SKT008', 'SKT009', 'SKT010', 'SKT011'],
            ],
            'Siti Nurdiniah' => [
                'Kab. Brebes' => ['BBS004', 'BBS005', 'BBS012', 'BBS020', 'BBS021', 'BBS027'],
            ],
            'Sovi Yanti' => [
                'Kab. Pati' => ['PTI001', 'PTI002', 'PTI003', 'PTI004', 'PTI005'],
            ],
            'Susi Rodiah' => [
                'Kab. Rembang' => ['RBG001', 'RBG002', 'RBG003', 'RBG004', 'RBG005'],
            ],
            'Syaebri Khamdani Putra' => [
                'Kab. Klaten' => ['KLN001', 'KLN005', 'KLN007', 'KLN008', 'KLN009', 'KLN011'],
            ],
            'Tia Alia Ananda' => [
                'Kab. Boyolali' => ['BYL001', 'BYL002', 'BYL005', 'BYL007'],
            ],
            'Wulan Dwi Cahyani' => [
                'Kab. Magelang' => ['MGG001', 'MGG002', 'MGG003', 'MGG004', 'MKD001', 'MKD005'],
            ],
            'Yoga Aprianto' => [
                'Kab. Kudus' => ['KDS001', 'KDS002', 'KDS003', 'KDS004', 'KDS005'],
            ],
            'Yohan Prahara' => [
                'Kab. Banyumas' => ['PWT003', 'PWT004', 'PWT006', 'PWT008', 'PWT011', 'PWT019', 'PWT021', 'PWT022', 'PWT024'],
            ],
        ];

        DB::transaction(function () use ($mappingData) {
            // 1. Buat / ambil Provinsi "Jawa Tengah"
            $province = Area::query()->firstOrCreate(
                ['name' => 'Jawa Tengah', 'parent_id' => null]
            );
            $this->command->info("Provinsi: {$province->name} (ID: {$province->id})");

            // 2. Cache kota yang sudah ada
            $cityCache = [];

            // 3. Proses tiap KC
            $kcCount = 0;
            $branchCount = 0;

            foreach ($mappingData as $kcName => $cityBranches) {
                // Buat username dari nama KC (lowercase, tanpa spasi)
                $username = Str::slug($kcName, '');
                $email = Str::slug($kcName, '.') . '@pgi.co.id';

                // Ambil semua city untuk KC ini (untuk menentukan area_id)
                $cityNames = array_keys($cityBranches);
                $firstCity = $cityNames[0];

                // Buat / ambil semua kota untuk KC ini
                $kcCityIds = [];
                foreach ($cityNames as $cityName) {
                    if (!isset($cityCache[$cityName])) {
                        $city = Area::query()->firstOrCreate(
                            ['name' => $cityName, 'parent_id' => $province->id]
                        );
                        $cityCache[$cityName] = $city;
                    }
                    $kcCityIds[] = $cityCache[$cityName]->id;
                }

                // area_id KC = kota pertama
                $primaryCityId = $kcCityIds[0];

                // Buat / ambil user KC
                $kcUser = User::query()->firstOrCreate(
                    ['username' => $username],
                    [
                        'name' => $kcName,
                        'email' => $email,
                        'password' => bcrypt('password123'),
                        'role' => 'KC',
                        'area_id' => $primaryCityId,
                    ]
                );

                if ($kcUser->wasRecentlyCreated) {
                    $kcCount++;
                    $this->command->info("  KC dibuat: {$kcName} (username: {$username})");
                } else {
                    $this->command->warn("  KC sudah ada: {$kcName} (username: {$username})");
                }

                // Buat branches dan assign ke KC
                foreach ($cityBranches as $cityName => $branchCodes) {
                    $city = $cityCache[$cityName];

                    foreach ($branchCodes as $branchCode) {
                        $branch = Branch::query()->firstOrCreate(
                            ['name' => $branchCode],
                            [
                                'area_id' => $city->id,
                                'kc_user_id' => $kcUser->id,
                            ]
                        );

                        if ($branch->wasRecentlyCreated) {
                            $branchCount++;
                        } else {
                            // Update kc_user_id jika branch sudah ada tapi belum di-assign
                            if ($branch->kc_user_id === null) {
                                $branch->update(['kc_user_id' => $kcUser->id]);
                            }
                        }
                    }
                }
            }

            $this->command->info('');
            $this->command->info("=== SELESAI ===");
            $this->command->info("Provinsi: 1 (Jawa Tengah)");
            $this->command->info("Kota: " . count($cityCache) . " kota dibuat/diverifikasi");
            $this->command->info("KC baru: {$kcCount} user");
            $this->command->info("Branch baru: {$branchCount} cabang");
        });
    }
}
