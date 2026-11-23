<?php

use App\Services\InnerCircle\PenjadwalService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// CATATAN HOSTING: Schedule::command() menjalankan tiap perintah lewat proses baru (proc_open), padahal hosting
// bersama (Rumahweb) menonaktifkannya sehingga cron gagal dengan "The Process class relies on proc_open".
// Karena itu semua jadwal ditulis sebagai Schedule::call() yang berjalan di dalam proses cron itu sendiri.
// Jangan diganti kembali ke Schedule::command() (dijaga tes UndianGlTest).
//
// Semua jadwal butuh satu cron di server (cPanel > Cron Jobs, tiap menit, lihat README bagian 5.4):
//   * * * * * /usr/local/bin/ea-php84 /home/USERNAME/laravel/artisan schedule:run >> /dev/null 2>&1
// Cron hidup terlihat di halaman Log (dashboard); kegagalan jadwal muncul sebagai error di Log, bukan di email cron.

// Detak tiap menit: bukti cron hidup. Dibaca halaman Log ("Cron: Normal").
Schedule::call(fn() => PenjadwalService::detak())
    ->name('cron:detak')
    ->everyMinute();

// Jadwal Inner Circle ikut Bandsintown tiap pagi. Hanya jalan bila Inner Circle aktif; bila BANDSINTOWN_APP_ID kosong,
// dilewati tanpa pesan. Kegagalan (App ID ditolak, Bandsintown tidak terjangkau) dilaporkan ke halaman Log.
// Kunci anti-tumpang-tindih kedaluwarsa dalam 30 menit supaya proses yang mati di tengah jalan tidak memblokir 24 jam.
Schedule::call(fn() => app(PenjadwalService::class)->sinkronBandsintown())
    ->name('ic:sinkron-bandsintown')
    ->dailyAt('04:00')
    ->withoutOverlapping(30)
    ->when(fn() => config('inner_circle.aktif'));

// Undian GL gratis Inner Circle: tiap menit memeriksa acara yang waktunya undiannya (bawaan hari H 18.00 WIB) sudah tiba.
// Tanpa cron, undian tetap dipicu kunjungan anggota (UndianService::pengamanKunjungan) dan tombol "Undi sekarang" di CMS.
// Kunci anti-tumpang-tindih hanya 10 menit: bila satu proses mati saat 18.00, menit-menit berikutnya tetap bisa mengundi.
Schedule::call(fn() => app(PenjadwalService::class)->undiGl())
    ->name('ic:undi-gl')
    ->everyMinute()
    ->withoutOverlapping(10)
    ->when(fn() => config('inner_circle.aktif'));