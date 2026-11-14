<?php

namespace App\Support;

/**
 * Menentukan "area" sebuah catatan error: CMS Artis (situs artis dan dashboard-nya) atau CMS Inner Circle
 * (komunitas /innercircle dan dashboard-nya). Dipakai supaya halaman Log di masing-masing CMS hanya menampilkan
 * catatan yang memang miliknya.
 *
 * Aktivitas admin tidak memakai kelas ini: area aktivitas sudah jelas dari nama route (ic.admin.* = Inner Circle).
 */
class AreaLog
{
    public const ARTIS = 'artis';

    public const INNER_CIRCLE = 'inner-circle';

    /** Awalan alamat yang termasuk Inner Circle (huruf kecil, tanpa garis miring di akhir). */
    private const AWALAN_IC = ['/innercircle', '/inner-circle', '/dashboard/inner-circle'];

    /** Area dari alamat halaman (jalur seperti "/innercircle/daftar" atau URL lengkap). */
    public static function dariUrl(?string $url, ?string $lokasi = null): string
    {
        $url = trim((string) $url);

        // Perintah artisan / jadwal otomatis tidak punya alamat halaman: lihat dari lokasi kodenya.
        if ($url === 'artisan') {
            return self::dariBerkas($lokasi);
        }

        $jalur = (string) parse_url($url, PHP_URL_PATH);
        $jalur = '/' . trim(strtolower($jalur), '/');

        foreach (self::AWALAN_IC as $awalan) {
            if ($jalur === $awalan || str_starts_with($jalur, $awalan . '/')) {
                return self::INNER_CIRCLE;
            }
        }

        return self::ARTIS;
    }

    /** Area dari lokasi kode (berkas:baris): kode di folder/layanan Inner Circle atau Bandsintown masuk Inner Circle. */
    public static function dariBerkas(?string $lokasi): string
    {
        $lokasi = (string) $lokasi;

        return str_contains($lokasi, 'InnerCircle') || str_contains($lokasi, 'Bandsintown')
            ? self::INNER_CIRCLE
            : self::ARTIS;
    }

    /** Nilai area yang pasti valid (selain dua area di atas dianggap CMS Artis). */
    public static function valid(?string $area): string
    {
        return $area === self::INNER_CIRCLE ? self::INNER_CIRCLE : self::ARTIS;
    }

    /**
     * Sidik jari baris error. Baris CMS Artis memakai rumus lama (sha1 dasar), baris Inner Circle ditambah area, jadi
     * masalah yang sama di dua area menjadi dua baris terpisah dan masing-masing CMS punya catatan sendiri.
     * Rumus yang sama dipakai migrasi 2026_11_14_090000 saat memindahkan baris lama.
     */
    public static function sidikJari(string $dasar, string $area): string
    {
        return $area === self::INNER_CIRCLE ? sha1($dasar . '|' . $area) : sha1($dasar);
    }
}