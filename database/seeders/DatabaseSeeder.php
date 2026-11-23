<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            TukSeeder::class,
            SchemeSeeder::class,
            CorrectedQuestionBankAnswerKeySeeder::class,
            CompleteMissingAk03FeedbackSeeder::class,
        ]);

        Setting::firstOrCreate(
            ['key' => 'appeal_window_days'],
            ['value' => '3', 'description' => 'Batas waktu pengajuan banding dalam hari kerja']
        );

        Setting::firstOrCreate(
            ['key' => 'cbt_passing_grade'],
            ['value' => '75.0', 'description' => 'Nilai passing grade ujian teori pilihan ganda CBT (FR.IA.05)']
        );
    }
}
