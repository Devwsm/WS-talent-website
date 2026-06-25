<?php

namespace Database\Seeders;

use App\Models\account;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class accountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
            account::create([
            'username' => 'staff_ws',
            'password' => Hash::make('dashboard_ws_2026'),
        ]);
    }
}