<?php

namespace App\Support;

/**
 * Menyusun header Content-Security-Policy dari config/keamanan.php.
 */
class KebijakanCsp
{
    public const MODE_LAPOR = 'report';
    public const MODE_PAKSA = 'enforce';

    /** Alamat penampung laporan pelanggaran (lihat cspController). */
    public const ALAMAT_LAPORAN = '/csp-report';

    /** Nama header dan isinya untuk mode saat ini, atau null bila CSP mati. @return array{0: string, 1: string}|null */
    public static function header(?string $mode = null): ?array
    {
        $mode ??= (string) config('keamanan.csp.mode');

        $nama = match ($mode) {
            self::MODE_LAPOR => 'Content-Security-Policy-Report-Only',
            self::MODE_PAKSA => 'Content-Security-Policy',
            default => null,
        };

        return $nama === null ? null : [$nama, self::nilai()];
    }

    public static function nilai(): string
    {
        $bagian = [];
        foreach ((array) config('keamanan.csp.direktif', []) as $direktif => $sumber) {
            $bagian[] = $direktif . ' ' . implode(' ', (array) $sumber);
        }
        $bagian[] = 'report-uri ' . self::ALAMAT_LAPORAN;

        return implode('; ', $bagian);
    }

    /** Apakah host (mis. widgetv3.bandsintown.com) diizinkan oleh salah satu direktif yang diberikan? */
    public static function hostDiizinkan(string $host, array $direktif): bool
    {
        foreach ($direktif as $nama) {
            foreach ((array) config("keamanan.csp.direktif.$nama", []) as $sumber) {
                if (! str_starts_with($sumber, 'https://')) {
                    continue;
                }

                $pola = substr($sumber, 8);
                if ($pola === $host) {
                    return true;
                }
                if (str_starts_with($pola, '*.') && str_ends_with($host, substr($pola, 1))) {
                    return true;
                }
            }
        }

        return false;
    }
}