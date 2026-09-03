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
            'banner_name'  => 'Map of Feelings',
            'link_banner'  => 'https://mapoffeelings.com/',
            'banner_cover' => 'Banner-mof.jpeg',
        ]);
    }
}