<?php

namespace App\Services;

use App\Mail\Admin\KodeLoginAdminMail;
use App\Models\account;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Kode OTP 6 digit untuk login admin (2FA lewat email). Aturannya:
 * - Hanya hash kode (HMAC dengan APP_KEY) yang disimpan di cache, berlaku admin_otp_menit menit, sekali pakai.
 * - Salah admin_otp_maks_salah kali: kode hangus, admin harus masuk ulang dengan sandi.
 * - Kirim ulang dibatasi admin_otp_maks_kirim per 10 menit per akun.
 * - kirim() melempar galat bila email gagal terkirim; pemanggil yang memutuskan pesan ke pengguna.
 */
class AdminOtpService
{
    public function aktif(): bool
    {
        return (bool) config('site.admin_2fa_aktif');
    }

    /** @return 'terkirim'|'terlalu_sering' */
    public function kirim(account $akun): string
    {
        $kunciBatas = 'admin-otp-kirim:' . $akun->getKey();
        $maks = (int) config('site.admin_otp_maks_kirim', 3);

        if (RateLimiter::tooManyAttempts($kunciBatas, $maks)) {
            return 'terlalu_sering';
        }

        $menit = (int) config('site.admin_otp_menit', 10);
        $kode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Kirim dulu; bila email gagal, kode lama tetap tidak tergantikan dan jatah kirim tidak terpakai.
        Mail::to($akun->email)->send(new KodeLoginAdminMail($akun->username, $kode, $menit));

        RateLimiter::hit($kunciBatas, 600);
        Cache::put($this->kunci($akun), [
            'hash' => $this->hash($kode),
            'salah' => 0,
            'berlaku_sampai' => now()->addMinutes($menit)->getTimestamp(),
        ], now()->addMinutes($menit));

        return 'terkirim';
    }

    /** @return 'ok'|'salah'|'habis'|'kedaluwarsa' */
    public function periksa(account $akun, string $kode): string
    {
        $kunci = $this->kunci($akun);
        $data = Cache::get($kunci);

        if (! is_array($data) || ($data['berlaku_sampai'] ?? 0) < now()->getTimestamp()) {
            Cache::forget($kunci);

            return 'kedaluwarsa';
        }

        if (hash_equals($data['hash'], $this->hash($kode))) {
            Cache::forget($kunci); // sekali pakai

            return 'ok';
        }

        $data['salah']++;
        if ($data['salah'] >= (int) config('site.admin_otp_maks_salah', 5)) {
            Cache::forget($kunci);

            return 'habis';
        }

        Cache::put($kunci, $data, \Illuminate\Support\Carbon::createFromTimestamp($data['berlaku_sampai']));

        return 'salah';
    }

    public function hapus(account $akun): void
    {
        Cache::forget($this->kunci($akun));
    }

    /** a***@gmail.com — untuk memberi tahu ke mana kode dikirim tanpa membuka alamat penuh. */
    public function samarkan(string $email): string
    {
        [$nama, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return Str::substr($nama, 0, min(2, max(1, mb_strlen($nama) - 1))) . '***@' . $domain;
    }

    private function kunci(account $akun): string
    {
        return 'admin-otp:' . $akun->getKey();
    }

    private function hash(string $kode): string
    {
        return hash_hmac('sha256', $kode, (string) config('app.key'));
    }
}