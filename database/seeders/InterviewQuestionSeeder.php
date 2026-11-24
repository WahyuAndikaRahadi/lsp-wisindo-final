<?php

namespace Database\Seeders;

use App\Models\InterviewQuestion;
use App\Models\Scheme;
use Illuminate\Database\Seeder;

class InterviewQuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Skema 1: Engine Junior Technician
        $scheme1 = Scheme::where('code', 'SKM-01-2026')
            ->orWhere('name', 'like', '%Engine Junior Technician%')
            ->first() ?? Scheme::find(1);

        if ($scheme1) {
            $questions1 = [
                1 => 'Sesuai dengan pengalaman saudara, ceritakan kecelakaan kerja akibat tidak menggunakan APD saat bekerja.',
                2 => 'Jelaskan akibat yang terjadi jika peralatan dan perlengkapan digunakan tidak sesuai dengan manual perbaikan.',
                3 => 'Jelaskan SOP menyimpan data informasi dan bukti komunikasi.',
                4 => 'Jelaskan alat apa saja yang Anda gunakan untuk memeriksa komponen sistem kelistrikan.',
                5 => 'Jelaskan SOP mematri komponen sesuai prosedur manual perbaikan.',
                6 => 'Jelaskan bagaimana memeriksa hasil pematrian secara visual.',
                7 => 'Jelaskan prosedur membaca gambar teknik sesuai fungsi dan cara kerjanya sesuai SOP.',
                8 => 'Jelaskan penggunaan alat ukur yang digunakan untuk mengukur tahanan Secunder Coil.',
                9 => 'Ceritakan bagaimana memeriksa sistem pengapian engine sesuai prosedur.',
                10 => 'Jelaskan mengapa oli dan komponen pada sistem pelumasan engine harus diperiksa sesuai prosedur.',
                11 => 'Apa yang Anda lakukan jika terjadi kebocoran pada sistem pendinginan engine?',
                12 => 'Apa yang Anda lakukan jika sistem bahan bakar bensin tidak normal?',
                13 => 'Jelaskan prosedur melakukan pemeliharaan sistem kontrol emisi sesuai dengan prosedur.',
                14 => 'Apa yang Anda lakukan jika berat jenis air baterai lebih tinggi dari standar?',
                15 => 'Jelaskan prosedur memasang baterai berdasarkan manual perbaikan.',
                16 => 'Jelaskan langkah-langkah pengerjaan jika horn (klakson) tidak berfungsi.',
                17 => 'Jelaskan mengapa komposisi air radiator terdiri dari air demineralisasi, glikol (ethylene glycol atau propylene glycol), dan zat aditif antikarat.',
                18 => 'Jelaskan prosedur pengujian air conditioner (AC).',
            ];

            foreach ($questions1 as $order => $text) {
                InterviewQuestion::updateOrCreate(
                    [
                        'scheme_id' => $scheme1->id,
                        'order' => $order,
                    ],
                    [
                        'evidence_context' => 'Sesuai dengan bukti no. …… yang Anda ajukan',
                        'question_text' => $text,
                        'expected_answer' => null,
                        'is_active' => true,
                    ]
                );
            }
        }

        // 2. Skema 2: Maintenance Gasoline Engine Injection System
        $scheme2 = Scheme::where('code', 'SKM-02-2026')
            ->orWhere('name', 'like', '%Gasoline Engine Injection%')
            ->first() ?? Scheme::find(2);

        if ($scheme2) {
            $questions2 = [
                1 => 'Sesuai dengan pengalaman saudara, ceritakan kecelakaan kerja akibat tidak menggunakan APD saat bekerja.',
                2 => 'Ceritakan tentang menggunakan peralatan dan perlengkapan sesuai jenis dan fungsi.',
                3 => 'Ceritakan tentang menjelaskan informasi yang disampaikan dengan data-data yang akurat dan terpercaya.',
                4 => 'Ceritakan tentang ketepatan memeriksa komponen dengan alat khusus/manual sesuai dengan prosedur.',
                5 => 'Ceritakan tentang menentukan jenis oli yang digunakan sesuai spesifikasi di manual perbaikan.',
                6 => 'Ceritakan tentang mengerjakan pematrian komponen sesuai prosedur manual perbaikan.',
                7 => 'Ceritakan tentang mengidentifikasi simbol dan kode gambar teknik.',
                8 => 'Ceritakan tentang menggunakan alat ukur sesuai fungsi dan jenis alat ukur.',
                9 => 'Ceritakan tentang hasil pemeriksaan engine diuji kinerjanya sesuai prosedur.',
                10 => 'Ceritakan tentang pemeliharaan sistem pelumasan dilakukan sesuai dengan prosedur.',
                11 => 'Ceritakan tentang kebocoran sistem pendinginan engine diperiksa, cairan sistem pendingin engine dicek, dan pemeliharaan sistem pendingin dilakukan sesuai prosedur.',
                12 => 'Ceritakan tentang pemeliharaan sistem bahan bakar bensin dilakukan sesuai dengan prosedur & hasil pemeliharaan sistem bahan bakar bensin diperiksa sesuai standar spesifikasi.',
                13 => 'Ceritakan tentang pemeliharaan sistem kontrol emisi dilakukan sesuai dengan prosedur & hasil pemeliharaan sistem kontrol emisi diperiksa sesuai standar spesifikasi.',
                14 => 'Ceritakan tentang tegangan baterai dan berat jenis air baterai diperiksa menggunakan alat ukur yang sesuai dengan manual perbaikan.',
                15 => 'Ceritakan tentang pelaksanaan mengganti baterai berdasarkan manual perbaikan.',
                16 => 'Ceritakan tentang penerapan persyaratan keselamatan dan kesehatan pekerjaan pada kelistrikan kendaraan.',
                17 => 'Ceritakan tentang cairan pendingin engine dikuras, diisi sesuai prosedur serta hasil penggantian cairan pada sistem pendinginan engine diuji sesuai standar spesifikasi.',
                18 => 'Ceritakan tentang ketepatan pemeliharaan sistem air conditioner berdasarkan manual perbaikan.',
            ];

            foreach ($questions2 as $order => $text) {
                InterviewQuestion::updateOrCreate(
                    [
                        'scheme_id' => $scheme2->id,
                        'order' => $order,
                    ],
                    [
                        'evidence_context' => 'Sesuai dengan bukti no. …… yang Anda ajukan',
                        'question_text' => $text,
                        'expected_answer' => null,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
