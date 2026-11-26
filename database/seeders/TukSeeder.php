<?php

namespace Database\Seeders;

use App\Models\Scheme;
use App\Models\Tuk;
use App\Models\User;
use Illuminate\Database\Seeder;

class TukSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schemes = Scheme::all();

        // 1. TUK Utama (Terverifikasi untuk seluruh 5 skema AJJ)
        $tuk1 = Tuk::updateOrCreate(
            ['code' => 'TUK-WIS-001'],
            [
                'name' => 'TUK Mandiri Wisata Nusantara',
                'type' => 'MANDIRI',
                'address' => 'Jl. Pariwisata Raya No. 88, Menteng, Jakarta Pusat',
                'postal_code' => '10310',
                'kode_provinsi' => '31',
                'nama_provinsi' => 'DKI JAKARTA',
                'kode_kota' => '3171',
                'nama_kota' => 'KOTA JAKARTA PUSAT',
                'phone' => '021-3901234',
                'email' => 'tuk.wisata@gmail.com',
                'pic_name' => 'Budi Santoso, S.Par',
                'pic_phone' => '081234567890',
                'pic_position' => 'Manajer Operasional TUK',
                'is_active' => true,
                'verification_status' => 'VERIFIED',
                'verification_notes' => 'Telah memenuhi standar kelayakan asesmen jarak jauh (AJJ) sesuai SOP Verifikasi TUK Jarak Jauh LSP P3 Wisata Indonesia.',
                'verified_at' => now(),
                'room_photos' => ['samples/room_1.jpg', 'samples/room_2.jpg'],
                'internet_data_photos' => ['samples/internet_data.jpg'],
                'internet_connection_photos' => ['samples/speedtest.jpg'],
                'equipment_photos' => ['samples/equipment_1.jpg', 'samples/equipment_2.jpg'],
                'room_video_path' => 'samples/video_ruang_tuk.mp4',
            ]
        );

        if ($schemes->isNotEmpty()) {
            $syncData = [];
            foreach ($schemes as $s) {
                $syncData[$s->id] = ['is_verified' => true];
            }
            $tuk1->schemes()->sync($syncData);
        }

        // Attach Admin TUK account to this TUK
        $adminTuk = User::where('email', 'tuk.wisindo@gmail.com')->first();
        if ($adminTuk) {
            $adminTuk->update(['tuk_id' => $tuk1->id]);
        }

        // 2. TUK Baru (Belum Verifikasi untuk simulasi workflow TUK)
        Tuk::updateOrCreate(
            ['code' => 'TUK-WIS-002'],
            [
                'name' => 'TUK Sewaktu Hotel Grand Cikarang',
                'type' => 'SEWAKTU',
                'address' => 'Kawasan Industri Jababeka, Cikarang, Jawa Barat',
                'postal_code' => '17530',
                'kode_provinsi' => '32',
                'nama_provinsi' => 'JAWA BARAT',
                'kode_kota' => '3216',
                'nama_kota' => 'KABUPATEN BEKASI',
                'phone' => '021-8987654',
                'email' => 'tuk.cikarang@gmail.com',
                'pic_name' => 'Hendri Wijaya',
                'pic_phone' => '081399887766',
                'pic_position' => 'Koordinator TUK',
                'is_active' => true,
                'verification_status' => 'UNSUBMITTED',
            ]
        );
    }
}
