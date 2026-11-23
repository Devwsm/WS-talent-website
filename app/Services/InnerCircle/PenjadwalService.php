<?php

namespace App\Services\InnerCircle;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Isi jadwal otomatis di routes/console.php (dijalankan cron `schedule:run` tiap menit).
 *
 * - Detak: dicatat tiap menit oleh jadwal `cron:detak`; halaman Log menampilkannya sebagai tanda cron hidup.
 *   Detak disimpan di cache, jadi hilang saat deploy (optimize:clear) dan pulih dalam semenit.
 * - Kegagalan sinkron Bandsintown dan undian GL dilaporkan lewat report() (halaman Log CMS Inner Circle + email).
 * Pencatatan detak tidak pernah melempar galat.
 */
class PenjadwalService
{
    private const DETAK = 'penjadwal:detak';

    /** Cron dianggap berhenti bila detak terakhir lebih tua dari ini (detik); idealnya tiap 60 detik. */
    private const BATAS_DETAK_DETIK = 180;

    public static function detak(): void
    {
        try {
            Cache::forever(self::DETAK, now()->getTimestamp());
        } catch (Throwable) {
            // Pencatatan detak tidak boleh mengganggu jadwal lain.
        }
    }

    /** Tarik jadwal tour dari Bandsintown. Kegagalan dilaporkan; App ID kosong dilewati tanpa suara. */
    public function sinkronBandsintown(): void
    {
        if (! BandsintownService::tersedia()) {
            return;
        }

        try {
            app(BandsintownService::class)->sinkron();
        } catch (SinkronGagal $e) {
            report(new JadwalGagal('Sinkron Bandsintown otomatis gagal. ' . $e->getMessage(), 0, $e));
        }
    }

    /** Undi GL acara yang waktunya tiba (bawaan hari H 18.00 WIB). Galat tiap acara dilaporkan oleh UndianService. */
    public function undiGl(): void
    {
        app(UndianService::class)->jalankanJatuhTempo();
    }

    /**
     * Baris status cron untuk halaman Log (tampil di kedua CMS).
     *
     * @return array<int, array{label: string, tingkat: string, teks: string}>
     */
    public static function ringkasan(): array
    {
        try {
            $detak = Cache::get(self::DETAK);

            if (! $detak) {
                return [['label' => 'Cron', 'tingkat' => 'peringatan', 'teks' => 'Belum terdeteksi jalan. Bila baru deploy, tunggu satu menit; bila tetap begini, cek cron schedule:run tiap menit di cPanel.']];
            }

            $lalu = now()->getTimestamp() - (int) $detak;
            $t = Carbon::createFromTimestamp((int) $detak, config('app.timezone'))->locale('id');
            $waktu = $t->diffForHumans() . ' (' . $t->translatedFormat('j M Y, H.i') . ' WIB)';

            return $lalu > self::BATAS_DETAK_DETIK
                ? [['label' => 'Cron', 'tingkat' => 'gagal', 'teks' => "Terakhir jalan {$waktu}. Seharusnya tiap menit: cek cron di cPanel."]]
                : [['label' => 'Cron', 'tingkat' => 'ok', 'teks' => "Jalan normal (terakhir {$waktu})."]];
        } catch (Throwable) {
            return []; // Halaman Log tetap terbuka walau cache bermasalah.
        }
    }
}