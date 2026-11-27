<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $seedPassword = env('SEED_PASSWORD', '@Kompeten86') ?: '@Kompeten86';
        $password = Hash::make($seedPassword);

        // 1. Core System Administrators & Sample Accounts
        $coreUsers = [
            [
                'full_name' => 'Super Admin LSP Wisindo',
                'email' => 'superadmin@gmail.com',
                'phone' => '08111111111',
                'role' => 'SUPER_ADMIN',
                'status' => 'ACTIVE',
                'password' => $password,
            ],
            [
                'full_name' => 'Admin LSP Wisindo',
                'email' => 'adminlsp@gmail.com',
                'phone' => '08222222222',
                'role' => 'ADMIN_LSP',
                'status' => 'ACTIVE',
                'password' => $password,
            ],
            [
                'full_name' => 'Admin TUK Wisata Nusantara',
                'email' => 'tuk.wisindo@gmail.com',
                'phone' => '081234567890',
                'role' => 'TUK_ADMIN',
                'status' => 'ACTIVE',
                'password' => $password,
            ],
            [
                'full_name' => 'Asesi Ani Rahayu',
                'email' => 'asesi@gmail.com',
                'phone' => '081987654321',
                'role' => 'ASESI',
                'status' => 'ACTIVE',
                'password' => $password,
            ],
        ];

        foreach ($coreUsers as $uData) {
            $user = User::updateOrCreate(
                ['email' => $uData['email']],
                $uData
            );

            UserProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
                    'tempat_lahir' => 'Jakarta',
                    'tanggal_lahir' => ($uData['role'] === 'ASESI') ? '1998-05-12' : '1990-01-01',
                    'jenis_kelamin' => ($uData['role'] === 'ASESI') ? 'P' : 'L',
                    'alamat_rumah' => 'Jl. Wisata No. 1, Jakarta',
                ]
            );
        }

        // 2. Daftar 18 Asesor Resmi LSP P3 Wisata Indonesia (LSP Wisindo)
        // Sumber: Dokumen Asessor Penyusun dan validator-wisindo.xlsx
        $assessors = [
            [
                'name' => 'Panca Nugraha',
                'email' => 'asesor.panca@gmail.com',
                'met' => 'MET. 000.002973 2023',
                'phone' => '081230000001',
                'schemes' => ['SKM-CC-02', 'SKM-CDP-01'], // Commis Chef Hotel, Chef De Partie Hotel
            ],
            [
                'name' => 'Lulut Budiyono',
                'email' => 'asesor.lulut@gmail.com',
                'met' => 'MET. 000.002985 2023',
                'phone' => '081230000002',
                'schemes' => ['SKM-CC-02', 'SKM-CDP-01', 'SKM-FB-05'], // Commis Chef Hotel, Chef De Partie Hotel, Waiter Server
            ],
            [
                'name' => 'Hendryan Afrianto',
                'email' => 'asesor.hendryan@gmail.com',
                'met' => 'MET. 000.002975 2023',
                'phone' => '081230000003',
                'schemes' => ['SKM-CC-02', 'SKM-CDP-01'], // Commis Chef Hotel, Chef De Partie Hotel
            ],
            [
                'name' => 'George Edwin A',
                'email' => 'asesor.george@gmail.com',
                'met' => 'MET. 000.000683 2021',
                'phone' => '081230000004',
                'schemes' => ['SKM-CC-02', 'SKM-CDP-01'], // Commis Chef Hotel, Chef De Partie Hotel
            ],
            [
                'name' => 'Efnu Nugroho',
                'email' => 'asesor.efnu@gmail.com',
                'met' => 'MET. 000.000697 2021',
                'phone' => '081230000005',
                'schemes' => ['SKM-CC-02', 'SKM-CDP-01'], // Commis Chef Hotel, Chef De Partie Hotel
            ],
            [
                'name' => 'Rita Irawati P',
                'email' => 'asesor.rita@gmail.com',
                'met' => 'MET. 000.000699 2021',
                'phone' => '081230000006',
                'schemes' => ['SKM-HK-04', 'SKM-FO-03', 'SKM-FB-05'], // Room Attendant, Receptionist, Waiter Server
            ],
            [
                'name' => 'Dwi Agung Jayan Nurcahyo',
                'email' => 'asesor.dwiagung@gmail.com',
                'met' => 'MET. 000.002977 2023',
                'phone' => '081230000007',
                'schemes' => ['SKM-HK-04'], // Room Attendant
            ],
            [
                'name' => 'Ahmad Rosyid Rafiudin',
                'email' => 'asesor.ahmadrosyid@gmail.com',
                'met' => 'MET. 000.002980 2023',
                'phone' => '081230000008',
                'schemes' => ['SKM-HK-04'], // Room Attendant
            ],
            [
                'name' => 'Agus Setyawan',
                'email' => 'asesor.agussetyawan@gmail.com',
                'met' => 'MET. 000.002981 2023',
                'phone' => '081230000009',
                'schemes' => ['SKM-HK-04'], // Room Attendant
            ],
            [
                'name' => 'Agus Budiyono',
                'email' => 'asesor.agusbudiyono@gmail.com',
                'met' => 'MET. 000.002979 2023',
                'phone' => '081230000010',
                'schemes' => ['SKM-HK-04'], // Room Attendant
            ],
            [
                'name' => 'Supriono',
                'email' => 'asesor.supriono@gmail.com',
                'met' => 'MET. 000.002972 2023',
                'phone' => '081230000011',
                'schemes' => ['SKM-FO-03'], // Receptionist
            ],
            [
                'name' => 'Silvia Herliana Pramono',
                'email' => 'asesor.silvia@gmail.com',
                'met' => 'MET. 000.002970 2023',
                'phone' => '081230000012',
                'schemes' => ['SKM-FO-03'], // Receptionist
            ],
            [
                'name' => 'Deni Dwi Ken Hamam Nurrosyid',
                'email' => 'asesor.deni@gmail.com',
                'met' => 'MET. 000.002969 2023',
                'phone' => '081230000013',
                'schemes' => ['SKM-FO-03'], // Receptionist
            ],
            [
                'name' => 'Rubi',
                'email' => 'asesor.rubi@gmail.com',
                'met' => 'MET. 000.002971 2023',
                'phone' => '081230000014',
                'schemes' => ['SKM-FO-03'], // Receptionist
            ],
            [
                'name' => 'Stephanus Arry Briantoro Santoso',
                'email' => 'asesor.stephanus@gmail.com',
                'met' => 'MET. 000.000693 2021',
                'phone' => '081230000015',
                'schemes' => ['SKM-FB-05'], // Waiter Server
            ],
            [
                'name' => 'Agil Ardiansyah',
                'email' => 'asesor.agil@gmail.com',
                'met' => 'MET. 000.002986 2023',
                'phone' => '081230000016',
                'schemes' => ['SKM-FB-05'], // Waiter Server
            ],
            [
                'name' => 'Novi Primadani',
                'email' => 'asesor.novi@gmail.com',
                'met' => 'MET. 000.002964 2023',
                'phone' => '081230000017',
                'schemes' => ['SKM-FB-05'], // Waiter Server
            ],
            [
                'name' => 'Suwartono',
                'email' => 'asesor.suwartono@gmail.com',
                'met' => 'MET. 000.002976 2023',
                'phone' => '081230000018',
                'schemes' => ['SKM-FB-05'], // Waiter Server
            ],
        ];

        foreach ($assessors as $item) {
            $user = User::updateOrCreate(
                ['email' => $item['email']],
                [
                    'full_name' => $item['name'],
                    'phone' => $item['phone'],
                    'assessor_registration_number' => $item['met'],
                    'role' => 'ASESOR',
                    'status' => 'ACTIVE',
                    'password' => $password,
                ]
            );

            // Buat Profil Asesor & TTD Digital siap pakai
            UserProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
                    'tempat_lahir' => 'Jakarta',
                    'tanggal_lahir' => '1985-05-15',
                    'jenis_kelamin' => 'L',
                    'alamat_rumah' => 'Jl. Pariwisata Indonesia No. 10',
                    'pendidikan_terakhir' => 'S1 Pariwisata',
                    'nama_institusi_perusahaan' => 'LSP P3 Wisata Indonesia',
                    'jabatan' => 'Asesor Kompetensi BNSP',
                ]
            );
        }
    }
}
