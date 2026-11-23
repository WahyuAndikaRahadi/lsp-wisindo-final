<?php

namespace Database\Seeders;

use App\Models\QuestionBank;
use App\Models\Scheme;
use Illuminate\Database\Seeder;

class CorrectedQuestionBankAnswerKeySeeder extends Seeder
{
    /**
     * Run the database seeds for FR.IA.05 question bank answer key corrections.
     */
    public function run(): void
    {
        $this->seedSkema2();
        $this->seedSkema3();
        $this->seedSkema5();
        $this->seedSkema6();
        $this->seedSkema7();

        $this->command?->info('FR.IA.05 Question Bank answer key corrections seeded successfully.');
    }

    /**
     * Skema 2: Maintenance Gasoline Engine Injection System (30 soal)
     */
    protected function seedSkema2(): void
    {
        $scheme = Scheme::where('code', 'SKM-02-2026')->first();
        if (! $scheme) {
            return;
        }

        $questions = QuestionBank::where('scheme_id', $scheme->id)->orderBy('id')->get();

        // No 3: C -> B (Urutan APAR: buka pin, arahkan selang, tekan handle)
        $q3 = $questions->get(2) ?? QuestionBank::where('scheme_id', $scheme->id)->where('question_text', 'like', '%APAR%')->first();
        $q3?->update(['correct_answer' => 'B']);

        // No 5: (tidak ada) -> D (Caddy portable: Jawaban a, b, dan c benar)
        $q5 = $questions->get(4) ?? QuestionBank::where('scheme_id', $scheme->id)->where('question_text', 'like', '%Caddy%')->first();
        $q5?->update(['correct_answer' => 'D']);

        // No 13: C -> B (Tekanan kompresi rendah disebabkan ring piston dan silinder aus)
        $q13 = $questions->get(12) ?? QuestionBank::where('scheme_id', $scheme->id)->where('question_text', 'like', '%kompresi rendah%')->first();
        $q13?->update(['correct_answer' => 'B']);

        // No 20: (tidak ada) -> A (Oktan tinggi berarti lebih sulit terbakar)
        $q20 = $questions->get(19) ?? QuestionBank::where('scheme_id', $scheme->id)->where('question_text', 'like', '%oktan bahan bakar%')->first();
        $q20?->update(['correct_answer' => 'A']);
    }

    /**
     * Skema 3: Perawatan Berkala Mobil Listrik (30 soal)
     */
    protected function seedSkema3(): void
    {
        $scheme = Scheme::where('code', 'SKM-03-2026')->first();
        if (! $scheme) {
            return;
        }

        $questions = QuestionBank::where('scheme_id', $scheme->id)->orderBy('id')->get();

        $correctedKeys = [
            1 => 'A', // Sekarang B
            2 => 'B', // Sekarang C
            3 => 'D', // Sekarang A
            4 => 'C', // Sekarang B
            5 => 'C', // Sekarang A
            6 => 'D', // Sekarang C
            7 => 'A', // Sudah benar
            8 => 'A', // Sekarang B
            9 => 'B', // Sudah benar
            10 => 'D', // Sekarang B
            11 => 'A', // Sekarang B
            12 => 'A', // Sekarang C
            13 => 'D', // Sekarang A
            14 => 'D', // Sekarang B
            15 => 'A', // Sekarang B
            16 => 'A', // Sekarang B
            17 => 'B', // Sudah benar
            18 => 'D', // Sekarang B
            19 => 'A', // Sekarang B
            20 => 'B', // Sudah benar
            21 => 'A', // Sekarang B
            22 => 'C', // Sekarang A
            23 => 'B', // Sudah benar
            24 => 'D', // Sekarang B
            25 => 'D', // Sekarang B
            26 => 'A', // Sekarang B
            27 => 'B', // Sekarang D
            28 => 'B', // Sudah benar
            29 => 'B', // Sekarang A
            30 => 'A', // Sekarang B
        ];

        foreach ($correctedKeys as $number => $key) {
            $q = $questions->get($number - 1);
            $q?->update(['correct_answer' => $key]);
        }
    }

    /**
     * Skema 5: Mekanik Junior (75 soal)
     */
    protected function seedSkema5(): void
    {
        $scheme = Scheme::where('code', 'SKM-05-2026')->first();
        if (! $scheme) {
            return;
        }

        $questions = QuestionBank::where('scheme_id', $scheme->id)->orderBy('id')->get();

        // No 61: Batas servis ukuran keolengan pelek roda -> B (2,0 mm)
        $q61 = $questions->get(60) ?? QuestionBank::where('scheme_id', $scheme->id)->where('question_text', 'like', '%keolengan pelek roda%')->first();
        $q61?->update(['correct_answer' => 'B']);
    }

    /**
     * Skema 6: Servis Sepeda Motor Listrik (40 soal)
     */
    protected function seedSkema6(): void
    {
        $scheme = Scheme::where('code', 'SKM-06-2026')->first();
        if (! $scheme) {
            return;
        }

        $questions = QuestionBank::where('scheme_id', $scheme->id)->orderBy('id')->get();

        // No 23: free play throttle -> C (stopscrew tidak berubah)
        $q23 = $questions->get(22) ?? QuestionBank::where('scheme_id', $scheme->id)->where('question_text', 'like', '%free play throttle gas%')->first();
        $q23?->update(['correct_answer' => 'C']);

        // No 31: menarik ke satu sisi -> A (bearing roda depan aus/rusak)
        $q31 = $questions->get(30) ?? QuestionBank::where('scheme_id', $scheme->id)->where('question_text', 'like', '%menarik ke satu sisi%')->first();
        $q31?->update(['correct_answer' => 'A']);
    }

    /**
     * Skema 7: Servis Sepeda Motor Injeksi (65 soal)
     */
    protected function seedSkema7(): void
    {
        $scheme = Scheme::where('code', 'SKM-07-2026')->first();
        if (! $scheme) {
            return;
        }

        $questions = QuestionBank::where('scheme_id', $scheme->id)->orderBy('id')->get();

        // No 15: Tanda Huruf "G" -> opsi Periksa, Penggantian, Perbaikan, Peringatan; Kunci B
        $q15 = $questions->get(14) ?? QuestionBank::where('scheme_id', $scheme->id)->where('question_text', 'like', '%Tanda Huruf "G"%')->first();
        $q15?->update([
            'option_a' => 'Periksa',
            'option_b' => 'Penggantian',
            'option_c' => 'Perbaikan',
            'option_d' => 'Peringatan',
            'correct_answer' => 'B',
        ]);

        // No 52: Batas servis ukuran keolengan pelek roda -> B (2,0 mm)
        $q52 = $questions->get(51) ?? QuestionBank::where('scheme_id', $scheme->id)->where('question_text', 'like', '%keolengan pelek roda%')->first();
        $q52?->update(['correct_answer' => 'B']);
    }
}
