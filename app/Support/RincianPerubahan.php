<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Menyusun rincian "apa yang berubah" untuk Log > Aktivitas: membandingkan nilai sebelum dan sesudah, lalu
 * menghasilkan satu kalimat pendek per perubahan.
 *
 * Aturan privasi: kolom `rahasia` (sandi) hanya ditulis "diganti" atau "dihapus", nilainya TIDAK PERNAH dicatat;
 * kolom `teks` (tulisan panjang) hanya ditulis "diubah" tanpa isinya.
 */
class RincianPerubahan
{
    /**
     * @param  array<string, mixed>  $sebelum  kolom => nilai lama
     * @param  array<string, mixed>  $sesudah  kolom => nilai baru
     * @param  array<string, string>  $label  kolom => nama yang dibaca manusia (urutan = urutan tampil)
     * @param  array<string, string>  $format  kolom => date | datetime | angka | meter (selain itu teks biasa)
     * @param  list<string>  $rahasia
     * @param  list<string>  $teks
     * @return list<string>
     */
    public static function bandingkan(array $sebelum, array $sesudah, array $label, array $format = [], array $rahasia = [], array $teks = []): array
    {
        $baris = [];

        foreach ($label as $kolom => $nama) {
            $lama = self::bersih($sebelum[$kolom] ?? null);
            $baru = self::bersih($sesudah[$kolom] ?? null);

            if (self::sama($lama, $baru, $format[$kolom] ?? null)) {
                continue;
            }

            if (in_array($kolom, $rahasia, true)) {
                $baris[] = $nama . ': ' . ($baru === null ? 'dihapus' : 'diganti');

                continue;
            }

            if (in_array($kolom, $teks, true)) {
                $baris[] = $nama . ': ' . ($baru === null ? 'dikosongkan' : ($lama === null ? 'diisi' : 'diubah'));

                continue;
            }

            $baris[] = $nama . ': ' . self::tulis($lama, $format[$kolom] ?? null) . ' → ' . self::tulis($baru, $format[$kolom] ?? null);
        }

        return $baris;
    }

    /** Gabungan baris menjadi satu teks untuk kolom `rincian` (maks. 2000 karakter). */
    public static function gabung(array $baris): ?string
    {
        $baris = array_values(array_filter(array_map('trim', $baris), fn($b) => $b !== ''));

        return $baris === [] ? null : mb_substr(implode("\n", $baris), 0, 2000);
    }

    /** Daftar nama kolom yang berubah, untuk kalimat ringkasan ("tanggal, kuota GL, poster"). */
    public static function ringkas(array $baris, int $maks = 6): string
    {
        $nama = array_map(fn($b) => Str::lower(Str::before($b, ':')), $baris);
        $lebih = count($nama) - $maks;
        $nama = array_slice($nama, 0, $maks);

        return implode(', ', $nama) . ($lebih > 0 ? " dan {$lebih} lainnya" : '');
    }

    private static function bersih(mixed $nilai): mixed
    {
        if (is_string($nilai)) {
            $nilai = trim($nilai);

            return $nilai === '' ? null : $nilai;
        }

        return $nilai;
    }

    private static function sama(mixed $a, mixed $b, ?string $format): bool
    {
        if ($a === null || $b === null) {
            return $a === $b;
        }

        if (in_array($format, ['date', 'datetime'], true)) {
            return self::waktu($a)?->format('Y-m-d H:i') === self::waktu($b)?->format('Y-m-d H:i');
        }

        if (in_array($format, ['angka', 'meter'], true) || (is_numeric($a) && is_numeric($b))) {
            return abs((float) $a - (float) $b) < 0.0000001;
        }

        return (string) $a === (string) $b;
    }

    private static function tulis(mixed $nilai, ?string $format): string
    {
        if ($nilai === null) {
            return 'kosong';
        }

        return match ($format) {
            'date' => self::waktu($nilai)?->locale('id')->translatedFormat('D, j M Y') ?? (string) $nilai,
            'datetime' => self::waktu($nilai)?->locale('id')->translatedFormat('j M Y, H.i') ?? (string) $nilai,
            'meter' => number_format((float) $nilai, 0, ',', '.') . ' m',
            'angka' => number_format((float) $nilai, 0, ',', '.'),
            default => Str::limit((string) $nilai, 60, '…'),
        };
    }

    private static function waktu(mixed $nilai): ?CarbonInterface
    {
        try {
            return $nilai instanceof CarbonInterface ? $nilai : Carbon::parse((string) $nilai);
        } catch (\Throwable) {
            return null;
        }
    }
}