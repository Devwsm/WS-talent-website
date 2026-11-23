<?php

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

// Jadwal Inner Circle ikut Bandsintown tiap pagi. Hanya jalan bila Inner Circle aktif dan BANDSINTOWN_APP_ID terisi,
// dan butuh cron `schedule:run` di server (lihat README, bagian 5.4). Tanpa cron, pakai tombol sinkron di dashboard.
Schedule::call(fn() => Artisan::call('ic:sinkron-bandsintown'))
    ->name('ic:sinkron-bandsintown')
    ->dailyAt('04:00')
    ->withoutOverlapping()
    ->when(fn() => config('inner_circle.aktif') && \App\Services\InnerCircle\BandsintownService::tersedia());

// Undian GL gratis Inner Circle: tiap menit memeriksa acara yang waktu undiannya (bawaan hari H 18.00 WIB) sudah tiba.
// Butuh cron `schedule:run` tiap menit di server (README bagian 5.4). Tanpa cron, undian tetap dipicu kunjungan anggota
// (UndianService::pengamanKunjungan) dan tombol "Undi sekarang" di CMS.
Schedule::call(fn() => Artisan::call('ic:undi-gl'))
    ->name('ic:undi-gl')
    ->everyMinute()
    ->withoutOverlapping()
    ->when(fn() => config('inner_circle.aktif'));