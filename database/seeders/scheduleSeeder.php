<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class scheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        DB::table('schedule')->insert([
            [
                'tanggal' => '2026-04-01',
                'nama_tempat' => 'livehouse',
                'daerah' => 'pekanbaru',
            ],
            [
                'tanggal' => '2026-04-02',
                'nama_tempat' => 'livehouse',
                'daerah' => 'batam',
            ],
            [
                'tanggal' => '2026-04-03',
                'nama_tempat' => 'gold dragon',
                'daerah' => 'palembang',
            ],
            [
                'tanggal' => '2026-04-04',
                'nama_tempat' => 'bllack owl',
                'daerah' => 'merak',
            ],
            [
                'tanggal' => '2026-04-08',
                'nama_tempat' => 'helen`s night mart',
                'daerah' => 'solo',
            ],
            [
                'tanggal' => '2026-04-09',
                'nama_tempat' => 'gold tiger',
                'daerah' => 'semarang',
            ],
            [
                'tanggal' => '2026-04-10',
                'nama_tempat' => 'gold dragon',
                'daerah' => 'yogyakarta',
            ],
            [
                'tanggal' => '2026-04-11',
                'nama_tempat' => 'the nine',
                'daerah' => 'mmalang',
            ],
            [
                'tanggal' => '2026-04-16',
                'nama_tempat' => 'angle`s wing',
                'daerah' => 'samarinda',
            ],
            [
                'tanggal' => '2026-04-17',
                'nama_tempat' => 'angle`s wing',
                'daerah' => 'balikpapan',
            ],
            [
                'tanggal' => '2026-04-23',
                'nama_tempat' => 'd`liquid',
                'daerah' => 'kendari',
            ],
            [
                'tanggal' => '2026-04-24',
                'nama_tempat' => 'elite',
                'daerah' => 'makassar',
            ],
            [
                'tanggal' => '2026-04-25',
                'nama_tempat' => 'noya',
                'daerah' => 'pik',
            ],
        ]);
    }
}