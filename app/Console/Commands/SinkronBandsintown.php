<?php

namespace App\Console\Commands;

use App\Services\InnerCircle\BandsintownService;
use App\Services\InnerCircle\SinkronGagal;
use Illuminate\Console\Command;

class SinkronBandsintown extends Command
{
    protected $signature = 'ic:sinkron-bandsintown';

    protected $description = 'Tarik jadwal tour dari Bandsintown ke jadwal acara WS Inner Circle';

    public function handle(BandsintownService $bit): int
    {
        if (! BandsintownService::tersedia()) {
            $this->warn('BANDSINTOWN_APP_ID belum diisi di .env, sinkron dilewati.');

            return self::SUCCESS;
        }

        try {
            $hasil = $bit->sinkron();
        } catch (SinkronGagal $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Bandsintown: {$hasil['total']} acara dibaca, {$hasil['dibuat']} baru, {$hasil['diperbarui']} diperbarui, {$hasil['terhubung']} ditautkan ke acara manual.");

        foreach ($hasil['hilang'] as $baris) {
            $this->warn("Tidak lagi ada di Bandsintown (cek manual): {$baris}");
        }

        return self::SUCCESS;
    }
}