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