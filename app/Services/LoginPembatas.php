<?php

namespace App\Services;

use App\Support\SidikPerangkat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Pembatas login admin CMS: kunci sementara bertingkat bagi perangkat yang terus salah sandi.
 *
 * Pembatas per menit (`throttle:admin-login`) hanya menahan serangan yang cepat. Penebak yang pelan (beberapa kali
 * sehari, seperti yang terlihat di Log) lolos darinya. Karena itu kegagalan dihitung dalam jendela panjang
 * (bawaan 72 jam) dan, begitu melewati ambang, perangkat dikunci dengan durasi yang makin lama:
 * mis. 5 gagal = 15 menit, 8 gagal = 1 jam, 12 gagal = 24 jam (ambang ada di config/keamanan.php).
 *
 * "Perangkat" dikenali dari tiga kunci sekaligus, karena tidak ada cara pasti mengenali satu perangkat dari server:
 *  - perangkat : token acak di cookie `ic_perangkat` (cookie yang sama dengan penanda check-in). Paling tepat,
 *                tapi bot tanpa cookie selalu dianggap perangkat baru.
 *  - ip-ua     : gabungan alamat IP + User-Agent. Menutup bot tanpa cookie, dengan ambang lebih longgar.
 *  - ip        : alamat IP saja, ambang paling longgar karena satu Wi-Fi bisa dipakai banyak orang.
 * Kunci mana pun yang sedang terkunci menolak percobaan, bahkan dengan sandi yang benar.
 *
 * Kunci disimpan sebagai hash di cache (tidak ada tabel baru). Login berhasil menghapus hitungan gagal milik
 * kunci perangkat dan ip-ua (bukan hitungan IP), jadi admin yang sekali salah ketik tidak terbawa-bawa.
 */
class LoginPembatas
{
    private const JENIS = ['perangkat', 'ip-ua', 'ip'];

    /** Sisa detik kunci terlama di antara ketiga kunci; 0 bila tidak ada yang terkunci. */
    public function sisaDetik(Request $request): int
    {
        $sisa = 0;

        foreach ($this->kunci($request) as $jenis => $id) {
            $sampai = (int) Cache::get($this->kunciKunci($jenis, $id), 0);
            $sisa = max($sisa, $sampai - now()->timestamp);
        }

        return max(0, $sisa);
    }

    /**
     * Catat satu login gagal. Mengembalikan lama kunci BARU dalam detik bila percobaan ini membuat salah satu kunci
     * melewati ambang, atau null bila belum ada yang terkunci.
     */
    public function catatGagal(Request $request): ?int
    {
        $jendela = max(1, (int) config('keamanan.login_admin.jendela_jam', 72)) * 3600;
        $kunciBaru = null;

        foreach ($this->kunci($request) as $jenis => $id) {
            $hitung = $this->kunciHitung($jenis, $id);
            Cache::add($hitung, 0, $jendela);
            $jumlah = (int) Cache::increment($hitung);

            $menit = $this->menitKunci($jenis, $jumlah);
            if ($menit === null) {
                continue;
            }

            $detik = $menit * 60;
            Cache::put($this->kunciKunci($jenis, $id), now()->timestamp + $detik, $detik);
            $kunciBaru = max((int) $kunciBaru, $detik);
        }

        return $kunciBaru;
    }

    /** Login berhasil: hitungan gagal perangkat ini dibersihkan (hitungan IP tetap, supaya penebak tak bisa menghapusnya). */
    public function bersihkan(Request $request): void
    {
        foreach (['perangkat', 'ip-ua'] as $jenis) {
            Cache::forget($this->kunciHitung($jenis, $this->kunci($request)[$jenis]));
        }
    }

    /** Teks lama tunggu yang mudah dibaca, mis. "15 menit" atau "2 jam". */
    public static function teksDurasi(int $detik): string
    {
        $menit = max(1, (int) ceil($detik / 60));

        return $menit >= 120 ? (int) ceil($menit / 60) . ' jam' : $menit . ' menit';
    }

    /** @return array<string, string> jenis => pengenal (sudah berupa hash atau token terhash) */
    private function kunci(Request $request): array
    {
        $ip = (string) $request->ip();

        return [
            'perangkat' => SidikPerangkat::untuk($request),
            'ip-ua' => hash('sha256', $ip . '|' . $request->userAgent()),
            'ip' => hash('sha256', $ip),
        ];
    }

    /** Menit kunci untuk jumlah gagal ini (ambang tertinggi yang terlewati), atau null bila belum mencapai ambang. */
    private function menitKunci(string $jenis, int $jumlah): ?int
    {
        $tingkat = (array) config('keamanan.login_admin.' . str_replace('-', '_', $jenis), []);
        ksort($tingkat);

        $menit = null;
        foreach ($tingkat as $ambang => $lama) {
            if ($jumlah >= (int) $ambang) {
                $menit = (int) $lama;
            }
        }

        return $menit;
    }

    private function kunciHitung(string $jenis, string $id): string
    {
        return 'login-admin:gagal:' . $jenis . ':' . $id;
    }

    private function kunciKunci(string $jenis, string $id): string
    {
        return 'login-admin:kunci:' . $jenis . ':' . $id;
    }
}