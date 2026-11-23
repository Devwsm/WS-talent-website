<?php

namespace App\Services;

use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Anggaran email harian seluruh situs (lihat config/email_anggaran.php).
 *
 * Setiap email yang benar-benar terkirim dihitung otomatis lewat event MessageSent (AppServiceProvider), jadi semua
 * jenis email terhitung tanpa harus diubah satu per satu. Pengirim yang tidak mendesak bertanya dulu lewat
 * izinkan(): bila anggaran hampir habis, email berprioritas rendah berhenti dulu, lalu yang biasa, sedangkan email
 * penting (kode login, atur ulang sandi, verifikasi) selalu lewat.
 *
 * Hitungan per jam disimpan di berkas JSON dengan kunci jam, dijumlahkan untuk 24 jam terakhir (bergulir seperti
 * hitungan Gmail). Galat baca/tulis tidak pernah menghentikan pengiriman: gagal membaca dianggap anggaran longgar.
 */
class AnggaranEmail
{
    public const PENTING = 'penting';

    public const BIASA = 'biasa';

    public const RENDAH = 'rendah';

    public static function berkas(): string
    {
        return (string) (config('email_anggaran.berkas') ?: storage_path('app/anggaran-email.json'));
    }

    /** Jumlah email terkirim dalam 24 jam terakhir. */
    public static function terpakai(): int
    {
        $batas = now()->subHours(24)->format('Y-m-d H');
        $total = 0;

        foreach (self::baca() as $jam => $jumlah) {
            if ((string) $jam > $batas) {
                $total += (int) $jumlah;
            }
        }

        return $total;
    }

    /** Apakah email berprioritas $tingkat masih boleh dikirim sekarang. */
    public static function tersedia(string $tingkat): bool
    {
        if ($tingkat === self::PENTING) {
            return true;
        }

        $maks = max(1, (int) config('email_anggaran.harian', 450));
        $bagian = $tingkat === self::RENDAH
            ? (float) config('email_anggaran.batas_rendah', 0.6)
            : (float) config('email_anggaran.batas_biasa', 0.9);

        return self::terpakai() < (int) floor($maks * $bagian);
    }

    /** Seperti tersedia(), tetapi mencatat peringatan (sekali per jam per jenis) saat email ditahan. */
    public static function izinkan(string $tingkat, string $jenis): bool
    {
        if (self::tersedia($tingkat)) {
            return true;
        }

        try {
            if (Cache::add('anggaran-email-tahan:' . $tingkat . ':' . $jenis, 1, now()->addHour())) {
                Log::warning('Email ditahan: anggaran email harian hampir habis', [
                    'jenis' => $jenis,
                    'tingkat' => $tingkat,
                    'terpakai' => self::terpakai(),
                    'anggaran' => (int) config('email_anggaran.harian', 450),
                ]);
            }
        } catch (Throwable) {
            // Pencatatan peringatan tidak boleh mengganggu.
        }

        return false;
    }

    /** Catat email yang sudah terkirim. Dipanggil oleh listener MessageSent. */
    public static function catat(int $jumlah = 1): void
    {
        try {
            $berkas = self::berkas();
            $dir = dirname($berkas);
            if (! is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }

            $fp = fopen($berkas, 'c+');
            if ($fp === false) {
                return;
            }

            try {
                if (flock($fp, LOCK_EX)) {
                    $data = json_decode((string) stream_get_contents($fp), true);
                    $data = is_array($data) ? $data : [];

                    $potong = now()->subHours(48)->format('Y-m-d H');
                    $data = array_filter($data, fn($n, $jam) => (string) $jam > $potong, ARRAY_FILTER_USE_BOTH);

                    $jam = now()->format('Y-m-d H');
                    $data[$jam] = (int) ($data[$jam] ?? 0) + max(1, $jumlah);

                    ftruncate($fp, 0);
                    rewind($fp);
                    fwrite($fp, (string) json_encode($data));
                    fflush($fp);
                    flock($fp, LOCK_UN);
                }
            } finally {
                fclose($fp);
            }
        } catch (Throwable) {
            // Pencatatan tidak boleh menggagalkan email.
        }
    }

    public static function dariEvent(MessageSent $event): void
    {
        try {
            $jumlah = count($event->sent->getEnvelope()->getRecipients());
        } catch (Throwable) {
            $jumlah = 1;
        }

        self::catat($jumlah);
    }

    /** @return array<string, int> */
    private static function baca(): array
    {
        $isi = @file_get_contents(self::berkas());
        $data = $isi === false ? null : json_decode($isi, true);

        return is_array($data) ? $data : [];
    }
}