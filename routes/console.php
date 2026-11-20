<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Jadwal Inner Circle ikut Bandsintown tiap pagi. Hanya jalan bila Inner Circle aktif dan BANDSINTOWN_APP_ID terisi,
// dan butuh cron `schedule:run` di server (lihat README, bagian Fase 9). Tanpa cron, pakai tombol sinkron di dashboard.
Schedule::command('ic:sinkron-bandsintown')
    ->dailyAt('04:00')
    ->when(fn() => config('inner_circle.aktif') && \App\Services\InnerCircle\BandsintownService::tersedia());

// Undian GL gratis Inner Circle: tiap menit memeriksa acara yang waktu undiannya (bawaan hari H 18.00 WIB) sudah tiba.
// Butuh cron `schedule:run` tiap menit di server (README bagian 5.4). Tanpa cron, undian tetap dipicu kunjungan anggota
// (UndianService::pengamanKunjungan) dan tombol "Undi sekarang" di CMS.
Schedule::command('ic:undi-gl')
    ->everyMinute()
    ->withoutOverlapping()
    ->when(fn() => config('inner_circle.aktif'));