<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lima tahapan hadiah WS Inner Circle dari prototype (10, 25, 50, 100, 250 stempel lama = 10.000 sampai 250.000 poin).
 *
 * Aman dijalankan berulang: tahap yang sudah ada tidak ditimpa, jadi teks yang sudah diedit di CMS
 * tidak hilang. Dipanggil dari migration `ic_rewards` dan dari DatabaseSeeder.
 *
 * Peka skema: migration `ic_rewards` (Oktober) memanggilnya saat kolomnya masih `target_stempel`, sedangkan setelah
 * migration `stempel_jadi_poin` kolomnya `harga_poin` dan nilainya dikali 1.000. Karena itu seeder memakai query
 * langsung dengan nama kolom yang sedang ada, bukan model.
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

        $baru = Schema::hasColumn('ic_rewards', 'harga_poin');
        $kolom = $baru ? 'harga_poin' : 'target_stempel';
        $pengali = $baru ? 1000 : 1;

        foreach ($data as $urutan => [$target, $judul, $deskripsi, $ikon, $warna, $opsi]) {
            if (DB::table('ic_rewards')->where($kolom, $target * $pengali)->exists()) {
                continue;
            }

            DB::table('ic_rewards')->insert([
                $kolom => $target * $pengali,
                'judul' => $judul,
                'deskripsi' => $deskripsi,
                'ikon' => $ikon,
                'warna' => $warna,
                'opsi' => $opsi === null ? null : json_encode($opsi),
                'urutan' => $urutan + 1,
                'aktif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}