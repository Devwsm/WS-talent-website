<?php

namespace Database\Seeders;

use App\Models\merchandise;
use Illuminate\Database\Seeder;

class merchandiseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'merchandise_name'  => 'Yalla Habibi Tee - Gold (Depan)',
                'link_merchandise'  => 'https://whisnusantika.com/yalla',
                'merchandise_cover' => 'Yalla-Front.webp',
            ],
            [
                'merchandise_name'  => 'Yalla Habibi Tee - Gold (Belakang)',
                'link_merchandise'  => 'https://whisnusantika.com/yalla',
                'merchandise_cover' => 'Yalla-Back.webp',
            ],
            [
                'merchandise_name'  => 'Yalla Habibi Tee - Merah (Depan)',
                'link_merchandise'  => 'https://whisnusantika.com/habibi',
                'merchandise_cover' => 'Habibi-Front.webp',
            ],
            [
                'merchandise_name'  => 'Yalla Habibi Tee - Merah (Belakang)',
                'link_merchandise'  => 'https://whisnusantika.com/habibi',
                'merchandise_cover' => 'Habibi-Back.webp',
            ],
        ];

        foreach ($data as $item) {
            merchandise::create($item);
        }
    }
}