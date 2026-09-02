<?php

namespace Database\Seeders;

use App\Models\albums;
use Illuminate\Database\Seeder;

class albumsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'albums_name'  => 'VAMOS! - Whisnu Santika, hbrp, KEEBO, MC SPYDER',
                'link_spotify' => 'https://ffm.to/vamosmusic',
                'albums_cover' => 'vamos.png',
            ],
            [
                'albums_name'  => 'Cartel - Whisnu Santika, hbrp, KEEBO',
                'link_spotify' => 'https://ffm.to/cartelmusic',
                'albums_cover' => 'cartel.png',
            ],
            [
                'albums_name'  => 'Mambo Jambo - Whisnu Santika, Adnan Veron, Dub It, Liquid Silva',
                'link_spotify' => 'https://ffm.to/mambojambomusic',
                'albums_cover' => 'mambo-jambo.png',
            ],
            [
                'albums_name'  => 'Yalla Habibi - Whisnu Santika',
                'link_spotify' => 'https://ffm.to/yallahabibi',
                'albums_cover' => 'yalla-habibi.png',
            ],
            [
                'albums_name'  => 'Tequila - Whisnu Santika, East Blake, Adnan Veron',
                'link_spotify' => 'https://ffm.to/tequilayouranthem',
                'albums_cover' => 'tequila.png',
            ],
            [
                'albums_name'  => "I'll be Yours - Whisnu Santika, Rey Putra, Cosmo Kent",
                'link_spotify' => 'https://whisnusantika.ffm.to/illbeyours',
                'albums_cover' => 'be-yours.png',
            ],
            [
                'albums_name'  => 'J-Town - Whisnu Santika, Rey Putra, MC DRWE',
                'link_spotify' => 'https://ffm.to/j-town',
                'albums_cover' => 'jTown.png',
            ],
        ];

        foreach ($data as $item) {
            albums::create($item);
        }
    }
}