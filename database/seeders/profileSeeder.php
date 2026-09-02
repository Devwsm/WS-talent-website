<?php

namespace Database\Seeders;

use App\Models\highlight;
use App\Models\statistik;
use Illuminate\Database\Seeder;

class profileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statistik = [
            ['total' => '593K+', 'platform' => 'YouTube Subscribers'],
            ['total' => '3.3M+', 'platform' => 'Spotify Monthly Listeners'],
            ['total' => '443K+', 'platform' => 'Instagram Followers'],
        ];
        foreach ($statistik as $item) {
            statistik::create($item);
        }

        // Catatan: field 'place' menyimpan nama event, 'description' menyimpan lokasi
        // (mengikuti urutan tampil di profile-full.blade.php)
        $highlights = [
            ['place' => 'Tomorrowland 2026', 'description' => 'Boom, Belgium', 'year' => '2026'],
            ['place' => 'Djakarta Warehouse Project', 'description' => 'Bali, Indonesia', 'year' => '2025'],
            ['place' => 'Sahara 1st Anniversary Tour', 'description' => 'Multishow, Indonesia', 'year' => '2023'],
            ['place' => 'Borderland Music Festival', 'description' => 'Kuching, Sarawak', 'year' => '2025'],
        ];
        foreach ($highlights as $item) {
            highlight::create($item);
        }
    }
}