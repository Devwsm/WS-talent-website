<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class profileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        DB::table('profile_card')->insert([
            [
                'role'        => 'DJ & Music Producer',
                'name'        => 'Whisnu Santika',
                'description' => 'Pionir Indonesian Bounce —',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $genres =
            [
                'Indonesian Bounce',
                'EDM',
                'Dancehall',
                'Afrobeat',
                'Moombahton',
                'Bass Music',
            ];
        foreach ($genres as $genre) {
            DB::table('genre')->insert([
                'genre'      => $genre,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('bio')->insert([
            [
                'description' => 'Whisnu Santika adalah DJ dan produser rekaman asal Jakarta yang dikenal sebagai pionir Indonesian Bounce —',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);

        $statistik = [
            ['total' => '590K+', 'platform' => 'Instagram Followers'],
            ['total' => '2.9M+',  'platform' => 'Spotify Monthly Listeners'],
            ['total' => '436K+',  'platform' => 'YouTube Subscribers'],
        ];
        foreach ($statistik as $item) {
            DB::table('statistik')->insert([
                'total'      => $item['total'],
                'platform'   => $item['platform'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $highlights = [
            ['name' => 'Tomorrowland 2024', 'place' => 'Boom, Belgium', 'year' => '2024'],
            ['name' => 'Djakarta Warehouse Project', 'place' => 'Jakarta, Indonesia',    'year' => '2024'],
            ['name' => 'Sahara 1st Anniversary Tour', 'place' => 'Multishow, Indonesia', 'year' => '2023'],
            ['name' => 'Borderland Music Festival', 'place' => 'Malaysia & Asia', 'year' => '2025'],
        ];
        foreach ($highlights as $item) {
            DB::table('highlight')->insert([
                'name'       => $item['name'],
                'place'      => $item['place'],
                'year'       => $item['year'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $collabs = [
            ['name' => 'Dipha Barus', 'description' => 'DJ / Producer'],
            ['name' => 'Cinta Laura Kiehl', 'description' => 'Penyanyi'],
            ['name' => 'Souljah', 'description' => 'Band'],
            ['name' => 'Amy B', 'description' => 'Singer · Australia'],
        ];
        foreach ($collabs as $item) {
            DB::table('collab')->insert([
                'name'        => $item['name'],
                'description' => $item['description'],
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        $coverages = [
            ['platform' => 'Suara.com', 'link' => null],
            ['platform' => 'Spotify Editorial',     'link' => null],
            ['platform' => 'iNews.ID',    'link' => null],
            ['platform' => 'CNN Indonesia', 'link' => null],
            ['platform' => 'Kompas.com',    'link' => null],
            ['platform' => 'Wikipedia ID',     'link' => null],
        ];
        foreach ($coverages as $item) {
            DB::table('media_coverage')->insert([
                'platform'   => $item['platform'],
                'link'       => $item['link'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $bookings = [
            ['category' => 'Show & Touring', 'contact' => 'jeannita.kirana@gmail.com'],
            ['category' => 'Brand & Partnership',   'contact' => 'partnership@whisnusantika.com'],
            ['category' => 'Media & Press',    'contact' => 'management@whisnusantika.com'],
        ];
        foreach ($bookings as $item) {
            DB::table('booking')->insert([
                'category'   => $item['category'],
                'contact'    => $item['contact'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $mediaSosial = [
            ['platform' => 'Instagram', 'link' => ''],
            ['platform' => 'TikTok',    'link' => ''],
            ['platform' => 'YouTube',   'link' => ''],
            ['platform' => 'Spotify',   'link' => ''],
        ];
        foreach ($mediaSosial as $item) {
            DB::table('media_sosial')->insert([
                'platform'   => $item['platform'],
                'link'       => $item['link'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}