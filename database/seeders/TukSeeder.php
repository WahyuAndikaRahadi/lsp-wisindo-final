<?php

namespace Database\Seeders;

use App\Models\Tuk;
use Illuminate\Database\Seeder;

class TukSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Tuk::updateOrCreate(
            ['name' => 'TUK Cikarang'],
            [
                'address' => 'Cikarang, Bekasi, Jawa Barat',
                'is_active' => true,
            ]
        );
    }
}
