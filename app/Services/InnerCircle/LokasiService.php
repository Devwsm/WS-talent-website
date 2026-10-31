<?php

namespace App\Services\InnerCircle;

use App\Models\ic_event;

/**
 * Aturan lokasi check-in: apakah HP anggota berada di dalam radius titik acara.
 *
 * Titik dan radius milik ACARA (kolom lokasi_* di ic_events), bukan milik akun CMS atau petugas yang mengaturnya.
 * Karena itu satu akun CMS boleh dipakai banyak petugas: HP petugas hanya dipakai sekali untuk menyimpan titik.
 *
 * Batas yang perlu diketahui: koordinat dikirim oleh browser anggota. Aplikasi "lokasi palsu" di Android masih bisa
 * mengelabuinya. Pemeriksaan ini menutup celah yang paling umum (QR difoto lalu dikirim ke orang yang tidak hadir),
 * bukan pemalsuan yang disengaja.
 */
class LokasiService
{
    private const RADIUS_BUMI_M = 6371008.8;

    /** Jarak dua titik di permukaan bumi dalam meter (rumus haversine). */
    public static function jarakMeter(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $p1 = deg2rad($lat1);
        $p2 = deg2rad($lat2);
        $dp = $p2 - $p1;
        $dl = deg2rad($lng2 - $lng1);

        $a = sin($dp / 2) ** 2 + cos($p1) * cos($p2) * sin($dl / 2) ** 2;

        return 2 * self::RADIUS_BUMI_M * asin(min(1.0, sqrt($a)));
    }

    public static function koordinatValid(mixed $lat, mixed $lng): bool
    {
        return is_numeric($lat) && is_numeric($lng)
            && is_finite((float) $lat) && is_finite((float) $lng)
            && abs((float) $lat) <= 90 && abs((float) $lng) <= 180;
    }

    /** 340 m, 1,2 km: dibulatkan supaya tidak terkesan presisi palsu. */
    public static function formatJarak(float $meter): string
    {
        if ($meter < 1000) {
            return max(10, (int) (round($meter / 10) * 10)) . ' m';
        }

        return number_format($meter / 1000, 1, ',', '.') . ' km';
    }

    /**
     * Periksa lokasi anggota terhadap titik acara. Acara tanpa titik lolos begitu saja (mengembalikan null).
     *
     * @param  array{lat?: mixed, lng?: mixed, akurasi?: mixed}|null  $lokasi  kiriman browser anggota
     * @return array{jarak: int, akurasi: int}|null  jarak ke titik acara dan akurasi GPS (meter), untuk dicatat
     *
     * @throws CheckinGagal
     */
    public function periksa(ic_event $event, ?array $lokasi): ?array
    {
        if (! $event->lokasi_aktif) {
            return null;
        }

        if (! $lokasi || ! self::koordinatValid($lokasi['lat'] ?? null, $lokasi['lng'] ?? null)) {
            throw new CheckinGagal('Acara ini memeriksa lokasi. Izinkan akses lokasi di browser, nyalakan GPS, lalu coba lagi.');
        }

        $cfg = config('inner_circle.lokasi');
        $akurasi = (float) ($lokasi['akurasi'] ?? 0);
        // HP yang tidak melaporkan akurasi dianggap kasar: tidak boleh jadi celah melewati batas akurasi.
        if (! is_numeric($lokasi['akurasi'] ?? null) || $akurasi <= 0 || $akurasi > (float) $cfg['akurasi_maks_m']) {
            throw new CheckinGagal(
                'Sinyal lokasi HP-mu kurang akurat' . ($akurasi > 0 ? ' (sekitar ' . self::formatJarak($akurasi) . ')' : '') .
                    '. Pindah ke area terbuka atau nyalakan GPS presisi tinggi, lalu coba lagi.'
            );
        }

        $jarak = self::jarakMeter((float) $event->lokasi_lat, (float) $event->lokasi_lng, (float) $lokasi['lat'], (float) $lokasi['lng']);
        $keringanan = min($akurasi, (float) $cfg['toleransi_akurasi_m']);

        if ($jarak - $keringanan > $event->radius_efektif) {
            throw new CheckinGagal(
                'Kamu berjarak sekitar ' . self::formatJarak($jarak) . ' dari lokasi acara (batas ' . $event->radius_efektif .
                    ' m). Check-in hanya bisa dilakukan di venue.'
            );
        }

        return ['jarak' => (int) round($jarak), 'akurasi' => (int) round($akurasi)];
    }
}