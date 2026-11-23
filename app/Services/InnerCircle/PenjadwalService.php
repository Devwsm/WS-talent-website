<?php

namespace App\Services\InnerCircle;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Isi jadwal otomatis di routes/console.php (dijalankan cron `schedule:run` tiap menit) sekaligus mencatat jejaknya,
 * supaya admin bisa melihat di halaman Log apakah cron benar-benar jalan dan apa hasil terakhirnya.
 *
 * - Detak: dicatat tiap menit oleh jadwal `cron:detak`. Tidak ada detak = cron belum terpasang atau berhenti.
 * - Status: hasil terakhir sinkron Bandsintown dan undian GL otomatis (ok | peringatan | gagal | dilewati).
 * - Kegagalan dilaporkan lewat report() (halaman Log + email), tidak hanya disimpan sebagai status.
 * Semua disimpan di cache (tidak butuh migrasi). Pencatatan tidak pernah melempar galat.
 */
class PenjadwalService
{
    private const DETAK = 'penjadwal:detak';

    private const STATUS = 'penjadwal:status:';

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

    /** Tarik jadwal tour dari Bandsintown. Kegagalan dilaporkan; App ID kosong hanya dicatat sebagai "dilewati". */
    public function sinkronBandsintown(): void
    {
        if (! BandsintownService::tersedia()) {
            $this->catat('bandsintown', 'dilewati', 'BANDSINTOWN_APP_ID belum diisi di .env server, jadi jadwal belum ditarik otomatis.');

            return;
        }

        try {
            $h = app(BandsintownService::class)->sinkron();
        } catch (SinkronGagal $e) {
            $this->catat('bandsintown', 'gagal', $e->getMessage());
            report(new JadwalGagal('Sinkron Bandsintown otomatis gagal. ' . $e->getMessage(), 0, $e));

            return;
        }

        $teks = "{$h['total']} acara dibaca, {$h['dibuat']} baru, {$h['diperbarui']} diperbarui, {$h['terhubung']} ditautkan ke acara manual.";
        if ($h['hilang'] !== []) {
            $teks .= ' Tidak lagi ada di Bandsintown (cek manual): ' . implode('; ', $h['hilang']) . '.';
        }

        $this->catat('bandsintown', $h['hilang'] === [] ? 'ok' : 'peringatan', $teks);
    }

    /**
     * Undi GL acara yang waktunya tiba (bawaan hari H 18.00 WIB). Galat tiap acara sudah dilaporkan oleh UndianService;
     * di sini hanya jejak hasilnya. Tidak ada acara jatuh tempo = status sebelumnya dibiarkan.
     */
    public function undiGl(): void
    {
        $hasil = app(UndianService::class)->jalankanJatuhTempo();

        if ($hasil === []) {
            return;
        }

        $tingkat = match (true) {
            in_array('gagal', $hasil, true) => 'gagal',
            in_array('tertunda', $hasil, true) => 'peringatan',
            default => 'ok',
        };
        $teks = collect($hasil)->map(fn(string $status, string $kode) => "{$kode}: {$status}")->implode('; ');

        if ($tingkat === 'peringatan') {
            $teks .= '. Tertunda = kuota, username, atau sandi acara belum lengkap saat 18.00; lengkapi lalu pakai Undi sekarang.';
        }

        $this->catat('undian', $tingkat, $teks);
    }

    /**
     * Baris status untuk halaman Log. Baris cron tampil di dua CMS; baris Bandsintown dan undian hanya di CMS Inner Circle.
     *
     * @return array<int, array{label: string, tingkat: string, teks: string}>
     */
    public static function ringkasan(bool $denganInnerCircle): array
    {
        $baris = [];

        try {
            $detak = Cache::get(self::DETAK);

            if (! $detak) {
                $baris[] = ['label' => 'Cron', 'tingkat' => 'peringatan', 'teks' => 'Belum pernah terdeteksi jalan. Pasang cron schedule:run tiap menit di cPanel, atau tunggu satu menit bila baru dipasang.'];
            } elseif (now()->getTimestamp() - (int) $detak > self::BATAS_DETAK_DETIK) {
                $baris[] = ['label' => 'Cron', 'tingkat' => 'gagal', 'teks' => 'Terakhir jalan ' . self::waktu((int) $detak) . '. Seharusnya tiap menit: cek cron di cPanel.'];
            } else {
                $baris[] = ['label' => 'Cron', 'tingkat' => 'ok', 'teks' => 'Jalan normal (terakhir ' . self::waktu((int) $detak) . ').'];
            }

            if ($denganInnerCircle) {
                foreach (['bandsintown' => 'Sinkron Bandsintown (04.00)', 'undian' => 'Undian GL otomatis (18.00)'] as $kunci => $label) {
                    $s = Cache::get(self::STATUS . $kunci);

                    $baris[] = is_array($s)
                        ? ['label' => $label, 'tingkat' => (string) $s['tingkat'], 'teks' => self::waktu((int) $s['waktu']) . ': ' . $s['teks']]
                        : ['label' => $label, 'tingkat' => 'netral', 'teks' => 'Belum pernah jalan.'];
                }
            }
        } catch (Throwable) {
            // Halaman Log tetap terbuka walau cache bermasalah.
        }

        return $baris;
    }

    private function catat(string $kunci, string $tingkat, string $teks): void
    {
        try {
            Cache::forever(self::STATUS . $kunci, ['waktu' => now()->getTimestamp(), 'tingkat' => $tingkat, 'teks' => mb_substr($teks, 0, 500)]);
        } catch (Throwable) {
            // Lihat catatan kelas.
        }
    }

    private static function waktu(int $detik): string
    {
        $t = Carbon::createFromTimestamp($detik, config('app.timezone'))->locale('id');

        return $t->diffForHumans() . ' (' . $t->translatedFormat('j M Y, H.i') . ' WIB)';
    }
}