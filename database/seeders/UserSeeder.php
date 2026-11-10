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

        $users = [
            // 1. Super Admin
            [
                'full_name' => 'Super Admin LSP',
                'email' => 'lsp.proindo@gmail.com',
                'phone' => '08111111111',
                'role' => 'SUPER_ADMIN',
                'status' => 'ACTIVE',
                'password' => $password,
            ],
            // 2. Admin LSP
            [
                'full_name' => 'Admin LSP Pro Indo',
                'email' => 'adminlsp@gmail.com',
                'phone' => '08222222222',
                'role' => 'ADMIN_LSP',
                'status' => 'ACTIVE',
                'password' => $password,
            ],
            // 3-16. 14 Asesors
            [
                'full_name' => 'Randi Ardiansyah',
                'email' => 'asesor.randi@gmail.com',
                'phone' => '08100000001',
                'assessor_registration_number' => 'MET.000.002498 2013',
                'role' => 'ASESOR',
                'status' => 'ACTIVE',
                'password' => $password,
            ],
            [
                'full_name' => 'Ari Susanto',
                'email' => 'asesor.ari@gmail.com',
                'phone' => '08100000002',
                'assessor_registration_number' => 'MET.000.004916 2018',
                'role' => 'ASESOR',
                'status' => 'ACTIVE',
                'password' => $password,
            ],
            [
                'full_name' => 'Imam Muslim',
                'email' => 'asesor.imam@gmail.com',
                'phone' => '08100000003',
                'assessor_registration_number' => 'MET.000.008273 2025',
                'role' => 'ASESOR',
                'status' => 'ACTIVE',
                'password' => $password,
            ],
            [
                'full_name' => 'Muhamad Rizik Abdillah',
                'email' => 'asesor.rizik@gmail.com',
                'phone' => '08100000004',
                'assessor_registration_number' => 'MET.000.008259 2025',
                'role' => 'ASESOR',
                'status' => 'ACTIVE',
                'password' => $password,
            ],
            [
                'full_name' => 'Aris Exwanto',
                'email' => 'asesor.aris@gmail.com',
                'phone' => '08100000005',
                'assessor_registration_number' => 'MET.000.001322 2019',
                'role' => 'ASESOR',
                'status' => 'ACTIVE',
                'password' => $password,
            ],
            [
                'full_name' => 'Ridwan',
                'email' => 'asesor.ridwan@gmail.com',
                'phone' => '08100000006',
                'assessor_registration_number' => 'MET.000.008265 2025',
                'role' => 'ASESOR',
                'status' => 'ACTIVE',
                'password' => $password,
            ],
            [
                'full_name' => 'Karyanto',
                'email' => 'asesor.karyanto@gmail.com',
                'phone' => '08100000007',
                'assessor_registration_number' => 'MET.000.008260 2025',
                'role' => 'ASESOR',
                'status' => 'ACTIVE',
                'password' => $password,
            ],
            [
                'full_name' => 'Ferry Susanto',
                'email' => 'asesor.ferry@gmail.com',
                'phone' => '08100000008',
                'assessor_registration_number' => 'MET.000.004004 2024',
                'role' => 'ASESOR',
                'status' => 'ACTIVE',
                'password' => $password,
            ],
            [
                'full_name' => 'Rendi Agung Purnomo',
                'email' => 'asesor.rendi@gmail.com',
                'phone' => '08100000009',
                'assessor_registration_number' => 'MET.000.011921 2019',
                'role' => 'ASESOR',
                'status' => 'ACTIVE',
                'password' => $password,
            ],
            [
                'full_name' => 'Suherman',
                'email' => 'asesor.suherman@gmail.com',
                'phone' => '08100000010',
                'assessor_registration_number' => 'MET.000.010256 2016',
                'role' => 'ASESOR',
                'status' => 'ACTIVE',
                'password' => $password,
            ],
            [
                'full_name' => 'Gunadi',
                'email' => 'asesor.gunadi@gmail.com',
                'phone' => '08100000011',
                'assessor_registration_number' => 'MET.000.000459 2007',
                'role' => 'ASESOR',
                'status' => 'ACTIVE',
                'password' => $password,
            ],
            [
                'full_name' => 'Sutarno',
                'email' => 'asesor.sutarno@gmail.com',
                'phone' => '08100000012',
                'assessor_registration_number' => 'MET.000.007040 2018',
                'role' => 'ASESOR',
                'status' => 'ACTIVE',
                'password' => $password,
            ],
            [
                'full_name' => 'Saptono',
                'email' => 'asesor.saptono@gmail.com',
                'phone' => '08100000013',
                'assessor_registration_number' => 'MET.000.003672 2016',
                'role' => 'ASESOR',
                'status' => 'ACTIVE',
                'password' => $password,
            ],
            [
                'full_name' => 'Alfian',
                'email' => 'asesor.alfian@gmail.com',
                'phone' => '08100000014',
                'assessor_registration_number' => 'MET.000.008269 2025',
                'role' => 'ASESOR',
                'status' => 'ACTIVE',
                'password' => $password,
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }

        // Test environment helper: seed a sample asesi for automated integration/feature tests
        if (app()->environment('testing')) {
            $testAsesi = User::updateOrCreate(
                ['email' => 'asesi1@gmail.com'],
                [
                    'full_name' => 'Asesi Test',
                    'phone' => '08555555555',
                    'role' => 'ASESI',
                    'status' => 'ACTIVE',
                    'password' => $password,
                ]
            );

            UserProfile::firstOrCreate(
                ['user_id' => $testAsesi->id],
                [
                    'nik' => '3216010000000001',
                    'tempat_lahir' => 'Bekasi',
                    'tanggal_lahir' => '2000-01-01',
                    'jenis_kelamin' => 'L',
                    'kebangsaan' => 'Indonesia',
                    'alamat_rumah' => 'Jl. Industri No. 1, Cikarang',
                    'kode_pos' => '17530',
                    'no_telepon_rumah' => '08555555555',
                    'pendidikan_terakhir' => 'SMK Otomotif',
                    'nama_institusi_perusahaan' => 'SMK Negeri 1 Cikarang',
                    'jabatan' => 'Siswa / Teknisi Junior',
                    'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
                ]
            );
        }
    }
}
