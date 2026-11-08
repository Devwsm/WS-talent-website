<?php

namespace App\Services\InnerCircle;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Membatasi "oracle email" di pendaftaran.
 *
 * Pesan "Email ini sudah terdaftar" memang perlu (orang sering lupa sudah punya akun), tapi tanpa batas ia bisa
 * dipakai untuk menebak email mana saja yang jadi anggota. Pembatas umum `ic-daftar` (30 per menit per IP) terlalu
 * longgar untuk itu, jadi di sini yang dihitung HANYA percobaan yang berujung "email sudah terdaftar".
 *
 * Setelah MAKS kali dalam JENDELA detik dari satu IP, pendaftaran dari IP itu ditahan sementara. Selama ditahan,
 * pemeriksaan email kembar TIDAK dijalankan dan jawabannya sama untuk email apa pun (terdaftar atau tidak),
 * jadi tidak ada lagi petunjuk yang bisa dibaca. Pendaftar sah tidak terpengaruh: ia butuh paling banyak satu atau
 * dua percobaan. Pembatasnya per IP, jadi IP lain (misalnya jaringan penonton lain) tidak ikut tertahan.
 */
class BatasEmailKembar
{
    public const MAKS = 10;
    public const JENDELA = 600; // detik

    public const PESAN_DITAHAN = 'Terlalu banyak percobaan daftar dengan email yang sudah terdaftar dari jaringan ini. Tunggu beberapa menit. Kalau kamu sudah punya akun, silakan masuk atau atur ulang kata sandi.';

    public function ditahan(Request $request): bool
    {
        return RateLimiter::tooManyAttempts($this->kunci($request), self::MAKS);
    }

    /** Catat satu percobaan yang berujung "email sudah terdaftar". Jendela dihitung sejak percobaan pertama. */
    public function catat(Request $request): void
    {
        RateLimiter::hit($this->kunci($request), self::JENDELA);
    }

    private function kunci(Request $request): string
    {
        return 'daftar-email-kembar:' . $request->ip();
    }
}