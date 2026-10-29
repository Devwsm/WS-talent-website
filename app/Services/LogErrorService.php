<?php

namespace App\Services;

use App\Mail\Admin\PeringatanErrorMail;
use App\Models\log_error;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

/**
 * Menyimpan error yang tidak tertangani ke tabel log_errors (dilihat di dashboard, halaman Log) dan, bila
 * LOG_ALERT_EMAIL terisi, mengirim email peringatan.
 *
 * Aturan penting:
 * - Dipanggil dari Exceptions::report() di bootstrap/app.php. Hanya error tak terduga yang sampai ke sini
 *   (validasi, 404, login, CSRF, abort() sengaja tidak dilaporkan oleh Laravel).
 * - TIDAK PERNAH melempar galat: kalau database atau email sedang rusak, laporan tetap ada di storage/logs
 *   dan aplikasi tidak jatuh dua kali.
 * - Error yang sama (kelas + lokasi kode) digabung jadi satu baris dengan penghitung.
 * - Email: satu per jenis error per jam, dan paling banyak 10 per jam di seluruh situs (melindungi kuota Gmail).
 */
class LogErrorService
{
    private static bool $sedangMencatat = false;

    public function catat(Throwable $e): void
    {
        if (self::$sedangMencatat) {
            return;
        }

        self::$sedangMencatat = true;

        try {
            [$log, $baru] = $this->simpan($e);
            $this->kabari($log, $baru);

            if (random_int(1, 40) === 1) {
                log_error::query()
                    ->where('terakhir_pada', '<', now()->subDays((int) config('log_admin.simpan_error_hari', 90)))
                    ->delete();
            }
        } catch (Throwable) {
            // Sengaja diam (lihat catatan kelas).
        } finally {
            self::$sedangMencatat = false;
        }
    }

    /** @return array{0: log_error, 1: bool} [baris, apakah baru dibuat atau dibuka lagi] */
    private function simpan(Throwable $e): array
    {
        $lokasi = ltrim(str_replace([base_path(), '\\'], ['', '/'], $e->getFile()), '/') . ':' . $e->getLine();
        $fingerprint = sha1(get_class($e) . '|' . $lokasi);
        $sekarang = now();

        $data = [
            'pesan' => $this->bersihkan($e->getMessage()),
            'url' => $this->url(),
            'metode' => $this->konsol() ? 'CLI' : request()->method(),
            'ip' => $this->konsol() ? null : request()->ip(),
            'pengguna' => $this->pengguna(),
            'terakhir_pada' => $sekarang,
        ];

        $log = log_error::query()->where('fingerprint', $fingerprint)->first();

        if ($log) {
            $dibukaLagi = $log->selesai;
            $log->update($data + ['jumlah' => $log->jumlah + 1, 'selesai' => false]);

            return [$log, $dibukaLagi];
        }

        $log = log_error::create($data + [
            'fingerprint' => $fingerprint,
            'kelas' => get_class($e),
            'lokasi' => $lokasi,
            'jejak' => $this->jejak($e),
            'jumlah' => 1,
            'selesai' => false,
            'pertama_pada' => $sekarang,
        ]);

        return [$log, true];
    }

    private function kabari(log_error $log, bool $baru): void
    {
        $tujuan = trim((string) config('site.peringatan_email'));
        if ($tujuan === '' || ! filter_var($tujuan, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        // Error yang masih terbuka dan sudah dilaporkan lewat email tidak diulang dalam satu jam.
        $menit = (int) config('log_admin.email_per_error_menit', 60);
        if (! Cache::add('log-error-email:' . $log->fingerprint, 1, now()->addMinutes($menit))) {
            return;
        }

        // Batas keseluruhan, supaya badai error berbeda tidak menghabiskan kuota kirim Gmail.
        $maks = (int) config('log_admin.email_maks_per_jam', 10);
        if (RateLimiter::tooManyAttempts('log-error-email-global', $maks)) {
            return;
        }
        RateLimiter::hit('log-error-email-global', 3600);

        $surat = new PeringatanErrorMail(
            kelas: $log->kelas,
            pesan: $log->pesan,
            lokasi: (string) $log->lokasi,
            url: $log->url,
            waktu: $log->terakhir_pada->locale('id')->translatedFormat('j M Y, H.i') . ' WIB',
            jumlah: (int) $log->jumlah,
            urlLog: route('log.index'),
        );

        $kirim = function () use ($tujuan, $surat) {
            try {
                Mail::to($tujuan)->send($surat);
            } catch (Throwable) {
                // Email peringatan gagal: cukup tetap ada di halaman Log.
            }
        };

        // Setelah respons terkirim (di web) supaya halaman error tidak menunggu server email.
        app()->runningInConsole() ? $kirim() : app()->terminating($kirim);
    }

    /** Dua belas langkah pertama, tanpa argumen fungsi (argumen bisa memuat sandi atau token). */
    private function jejak(Throwable $e): string
    {
        $baris = [];
        foreach (array_slice($e->getTrace(), 0, 12) as $i => $langkah) {
            $berkas = isset($langkah['file'])
                ? ltrim(str_replace([base_path(), '\\'], ['', '/'], $langkah['file']), '/') . ':' . ($langkah['line'] ?? '?')
                : '[internal]';
            $fungsi = ($langkah['class'] ?? '') . ($langkah['type'] ?? '') . ($langkah['function'] ?? '');
            $baris[] = '#' . $i . ' ' . $berkas . ' ' . $fungsi . '()';
        }

        return implode("\n", $baris);
    }

    /** Buang pola yang tampak seperti rahasia, lalu batasi panjang. */
    private function bersihkan(string $pesan): string
    {
        $pesan = preg_replace('/\b(password|passwd|sandi|secret|token|api[_-]?key|authorization)(["\']?\s*[=:]\s*["\']?)[^\s"\',;)]+/i', '$1$2***', $pesan) ?? $pesan;
        $pesan = preg_replace('/\bBearer\s+[A-Za-z0-9._~+\/=-]+/i', 'Bearer ***', $pesan) ?? $pesan;

        return mb_substr($pesan, 0, 1000);
    }

    /** Jalan lewat artisan/queue (bukan request web): tidak ada rute yang ter-resolve. */
    private function konsol(): bool
    {
        return app()->runningInConsole() && ! request()->route();
    }

    /** Alamat tanpa query string (query bisa memuat token reset atau kode OAuth). */
    private function url(): ?string
    {
        if ($this->konsol()) {
            return 'artisan';
        }

        return mb_substr(request()->url(), 0, 500);
    }

    private function pengguna(): ?string
    {
        try {
            if ($this->konsol() || ! request()->hasSession()) {
                return null;
            }
            if (request()->session()->get('login')) {
                return 'admin:' . request()->session()->get('user');
            }
            if (Auth::guard('member')->check()) {
                return 'anggota:' . Auth::guard('member')->id();
            }
        } catch (Throwable) {
            // Sesi belum siap: lewati.
        }

        return null;
    }
}