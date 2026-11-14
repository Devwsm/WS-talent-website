<?php

namespace App\Support;

/**
 * Meringkas User-Agent browser menjadi satu kalimat pendek yang mudah dibaca, mis. "HP Android · Chrome" atau
 * "Komputer Windows · Edge". Yang disimpan di Log hanya ringkasan ini, BUKAN teks User-Agent mentah.
 */
class PerangkatPengguna
{
    public const TIDAK_DIKENAL = 'Perangkat tidak dikenal';

    public static function ringkas(?string $userAgent): string
    {
        $ua = trim((string) $userAgent);
        if ($ua === '') {
            return self::TIDAK_DIKENAL;
        }

        $perangkat = self::perangkat($ua);
        $browser = self::browser($ua);

        $hasil = implode(' · ', array_filter([$perangkat, $browser]));

        return $hasil === '' ? self::TIDAK_DIKENAL : mb_substr($hasil, 0, 100);
    }

    private static function perangkat(string $ua): string
    {
        if (preg_match('/iPhone|iPod/i', $ua)) {
            return 'iPhone';
        }
        if (preg_match('/iPad/i', $ua)) {
            return 'iPad';
        }
        if (preg_match('/Android/i', $ua)) {
            return preg_match('/Mobile/i', $ua) ? 'HP Android' : 'Tablet Android';
        }
        if (preg_match('/CrOS/i', $ua)) {
            return 'Komputer Chromebook';
        }
        if (preg_match('/Windows/i', $ua)) {
            return 'Komputer Windows';
        }
        if (preg_match('/Macintosh|Mac OS X/i', $ua)) {
            return 'Komputer Mac';
        }
        if (preg_match('/Linux|X11/i', $ua)) {
            return 'Komputer Linux';
        }

        return '';
    }

    /** Urutan pengecekan penting: banyak browser mengaku "Chrome" dan "Safari" sekaligus. */
    private static function browser(string $ua): string
    {
        return match (true) {
            (bool) preg_match('/Edg(e|A|iOS)?\//i', $ua) => 'Edge',
            (bool) preg_match('/OPR\/|Opera/i', $ua) => 'Opera',
            (bool) preg_match('/SamsungBrowser/i', $ua) => 'Samsung Internet',
            (bool) preg_match('/Firefox|FxiOS/i', $ua) => 'Firefox',
            (bool) preg_match('/Chrome|CriOS|Chromium/i', $ua) => 'Chrome',
            (bool) preg_match('/Safari/i', $ua) => 'Safari',
            default => '',
        };
    }
}