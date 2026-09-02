<?php

namespace Database\Seeders;

use App\Models\color_pages;
use Illuminate\Database\Seeder;

class colorPagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        color_pages::create([
            'color' => '#5E0006',
        ]);
    }
}