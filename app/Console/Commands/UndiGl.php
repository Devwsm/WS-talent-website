<?php

namespace App\Console\Commands;

use App\Services\InnerCircle\UndianService;
use Illuminate\Console\Command;

/**
 * Undi GL gratis untuk acara yang waktunya sudah tiba (bawaan hari H pukul 18.00 WIB).
 * Dijadwalkan tiap menit di routes/console.php; aman dijalankan berulang karena satu acara hanya diundi sekali.
 */
class UndiGl extends Command
{
    protected $signature = 'ic:undi-gl';

    protected $description = 'Undi GL gratis Inner Circle untuk acara yang waktu undiannya sudah tiba';

    public function handle(UndianService $undian): int
    {
        $hasil = $undian->jalankanJatuhTempo();

        foreach ($hasil as $kode => $status) {
            $this->line("{$kode}: {$status}");
        }
        if ($hasil === []) {
            $this->line('Tidak ada undian yang jatuh tempo.');
        }

        return in_array('gagal', $hasil, true) ? self::FAILURE : self::SUCCESS;
    }
}