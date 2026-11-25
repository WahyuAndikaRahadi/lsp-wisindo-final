<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class FinishedJadwalWith20AssesseesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Delegates to FinishedScheduleSeeder for comprehensive finished schedule seeding.
     */
    public function run(): void
    {
        $this->call(FinishedScheduleSeeder::class);
    }
}
