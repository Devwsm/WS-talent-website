<?php

namespace Database\Seeders;

use App\Services\InnerCircle\AcaraService;
use Illuminate\Database\Seeder;

/**
 * Mengisi jadwal contoh WS Inner Circle (50 baris tentatif) untuk lokal/preview.
 * Hanya jalan bila IC_SEED_CONTOH=true; di produksi jadwal asli diisi lewat CMS atau impor CSV.
 */
class icEventContohSeeder extends Seeder
{
    public function run(AcaraService $acara): void
    {
        if (! config('inner_circle.seed_contoh')) {
            $this->command?->info('Jadwal contoh Inner Circle dilewati (IC_SEED_CONTOH bukan true).');

            return;
        }

        $hasil = $acara->imporCsv(database_path('data/ic_events_contoh.csv'));

        if ($hasil['galat']) {
            $this->command?->error(implode(PHP_EOL, $hasil['galat']));

            return;
        }

        $this->command?->info("Jadwal contoh: {$hasil['dibuat']} dibuat, {$hasil['diperbarui']} diperbarui.");
    }
}