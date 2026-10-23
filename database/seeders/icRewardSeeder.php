<?php

namespace Database\Seeders;

use App\Models\ic_reward;
use Illuminate\Database\Seeder;

/**
 * Lima tahapan hadiah WS Inner Circle dari prototype (10, 25, 50, 100, 250 stempel).
 *
 * Aman dijalankan berulang: tahap yang sudah ada tidak ditimpa, jadi teks yang sudah diedit di CMS
 * tidak hilang. Dipanggil dari migration `ic_rewards` dan dari DatabaseSeeder.
 */
class icRewardSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [10, 'Hadiah Pertama', 'Diskon merchandise resmi + stiker & gantungan kunci', 'gift', 'koral', null],
            [25, 'Akses Eksklusif', 'Akses awal merchandise + edisi khusus', 'cap', 'kuning', null],
            [50, 'Interaksi Personal', 'Panggilan video ATAU ucapan video khusus', 'love-letter', 'biru', ['Panggilan video', 'Ucapan video khusus']],
            [100, 'Di Balik Panggung', 'Akses belakang panggung festival + cek suara + foto Instax bertanda tangan', 'camera', 'lilac', null],
            [250, 'Legenda Inner Circle', 'Tiket pertunjukan khusus + belakang panggung + koleksi eksklusif', 'ticket', 'pink', null],
        ];

        foreach ($data as $urutan => [$target, $judul, $deskripsi, $ikon, $warna, $opsi]) {
            ic_reward::query()->firstOrCreate(
                ['target_stempel' => $target],
                [
                    'judul' => $judul,
                    'deskripsi' => $deskripsi,
                    'ikon' => $ikon,
                    'warna' => $warna,
                    'opsi' => $opsi,
                    'urutan' => $urutan + 1,
                    'aktif' => true,
                ],
            );
        }
    }
}