<?php

namespace Database\Seeders;

use App\Models\banner;
use Illuminate\Database\Seeder;

class bannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        banner::create([
            'banner_name'  => 'Official Merchandise - Indonesian Bounce',
            'link_banner'  => 'https://mapoffeelings.com/',
            'banner_cover' => 'Banner.jpg',
        ]);
    }
}