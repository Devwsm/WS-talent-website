<?php

namespace App\Services;

use App\Models\log_error;
use Throwable;

/**
 * Menyimpan laporan pelanggaran Content-Security-Policy ke tabel log_errors (halaman Log di dashboard), supaya
 * terlihat tanpa membuka berkas log di server. Laporan yang sama (aturan + sumber yang dilaporkan) digabung jadi
 * satu baris dengan penghitung. Tidak mengirim email, dan TIDAK PERNAH melempar galat.
 *
 * Data dari browser tidak dipercaya: semua bidang dipotong, query string dan fragmen dibuang (bisa memuat token),
 * dan laporan dari ekstensi browser (sumber derau terbesar) diabaikan.
 */
class CspLaporanService
{
    private const DIABAIKAN = ['chrome-extension', 'moz-extension', 'safari-extension', 'safari-web-extension', 'ms-browser-extension', 'about', 'resource', 'chrome', 'webviewprogressproxy', 'android-webview-video-poster'];

    /** @param array<string, mixed> $laporan bidang CSP (document-uri, effective-directive, blocked-uri, ...) */
    public function catat(array $laporan, ?string $ip = null): void
    {
        try {
            $direktif = $this->direktif($laporan);
            $diblokir = $this->diblokir((string) ($laporan['blocked-uri'] ?? ''));
            if ($diblokir === null) {
                return;
            }

            $halaman = $this->jalur((string) ($laporan['document-uri'] ?? ''));
            $fingerprint = sha1('csp|' . $direktif . '|' . $diblokir);
            $sekarang = now();
            $disposisi = ($laporan['disposition'] ?? 'report') === 'enforce' ? 'diblokir' : 'hanya dilaporkan';

            $sumber = $this->jalur((string) ($laporan['source-file'] ?? ''), true);
            $baris = (int) ($laporan['line-number'] ?? 0);

            $data = [
                'pesan' => "CSP {$direktif}: {$diblokir} ({$disposisi}) di halaman {$halaman}",
                'url' => mb_substr($halaman, 0, 500),
                'metode' => 'CSP',
                'ip' => $ip,
                'pengguna' => null,
                'terakhir_pada' => $sekarang,
            ];

            $ada = log_error::query()->where('fingerprint', $fingerprint)->first();
            if ($ada) {
                $ada->update($data + ['jumlah' => $ada->jumlah + 1, 'selesai' => false]);

                return;
            }

            $maks = (int) config('keamanan.csp.laporan_maks', 300);
            if (log_error::query()->where('kelas', 'like', 'CSP:%')->count() >= $maks) {
                return;
            }

            log_error::create($data + [
                'fingerprint' => $fingerprint,
                'kelas' => 'CSP:' . $direktif,
                'lokasi' => mb_substr($diblokir . ($sumber !== '' ? " (dari {$sumber}" . ($baris > 0 ? ":{$baris}" : '') . ')' : ''), 0, 255),
                'jejak' => null,
                'jumlah' => 1,
                'selesai' => false,
                'pertama_pada' => $sekarang,
            ]);

            if (random_int(1, 40) === 1) {
                log_error::query()
                    ->where('kelas', 'like', 'CSP:%')
                    ->where('terakhir_pada', '<', now()->subDays((int) config('log_admin.simpan_error_hari', 90)))
                    ->delete();
            }
        } catch (Throwable) {
            // Sengaja diam: laporan CSP tidak boleh membuat halaman atau database bermasalah.
        }
    }

    private function direktif(array $laporan): string
    {
        $nama = (string) ($laporan['effective-directive'] ?? '');
        if ($nama === '') {
            $nama = strtok((string) ($laporan['violated-directive'] ?? ''), ' ') ?: '';
        }
        $nama = strtolower($nama);

        return preg_match('/^[a-z-]{3,40}$/', $nama) ? $nama : 'tidak-diketahui';
    }

    /** Host (untuk URL http/https) atau kata kunci (inline, eval, data, blob). Null bila harus diabaikan. */
    private function diblokir(string $uri): ?string
    {
        $uri = trim($uri);
        if ($uri === '' || in_array($uri, ['inline', 'eval', 'data', 'blob', 'self'], true)) {
            return $uri === '' ? 'tidak-diketahui' : $uri;
        }

        $skema = strtolower((string) parse_url($uri, PHP_URL_SCHEME));
        if (in_array($skema, self::DIABAIKAN, true)) {
            return null;
        }
        if (in_array($skema, ['http', 'https', 'ws', 'wss'], true)) {
            $host = strtolower((string) parse_url($uri, PHP_URL_HOST));

            return $host !== '' ? mb_substr($host, 0, 120) : 'tidak-diketahui';
        }

        return in_array($skema, ['data', 'blob'], true) ? $skema : 'tidak-diketahui';
    }

    /** Jalur tanpa query dan fragmen. $denganHost menyertakan host (untuk sumber berkas). */
    private function jalur(string $uri, bool $denganHost = false): string
    {
        $bagian = parse_url(trim($uri));
        if ($bagian === false) {
            return '';
        }

        $jalur = (string) ($bagian['path'] ?? '/');
        $hasil = $denganHost ? ((string) ($bagian['host'] ?? '')) . $jalur : $jalur;

        return mb_substr($hasil, 0, 200);
    }
}