<?php

namespace Database\Seeders;

use App\Models\header;
use Illuminate\Database\Seeder;

class headerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'header_color'       => '#191970',
                'header_title'       => 'Aku Harus pergi',
                'header_img'         => 'Whisnu-Santika_Logo-2025-2-White.png',
                'header_name'        => 'Whisnu Santika (Official Lyrics Video)',
                'header_description' => 'Whisnu Santika, Ari Lesmana',
                'link_header'        => 'https://youtu.be/PqTRMsd8uRQ?si=nSkFJRWGTxL71F5V',
                'header_background'  => 'Whisnu Santika Cartel DWP.mp4',
            ],
            [
                'header_color'       => '#5E0006',
                'header_title'       => 'New Release',
                'header_img'         => 'Whisnu-Santika_Logo-2025-2-White.png',
                'header_name'        => 'Whisnu Santika',
                'header_description' => 'Pionir Indonesian Bounce',
                'link_header'        => 'https://www.youtube.com/watch?v=aTXY-jlYXfo',
                'header_background'  => 'Whisnu Santika Cartel DWP.mp4',
            ],
        ];

        foreach ($data as $item) {
            header::create($item);
        }
    }
}