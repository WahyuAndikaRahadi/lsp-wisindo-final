<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            TukSeeder::class,
            UserSeeder::class,
            SchemeSeeder::class,
        ]);
    }
}
