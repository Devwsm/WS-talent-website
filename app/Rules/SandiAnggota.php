<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Aturan kata sandi anggota WS Inner Circle yang membuat sandi dengan tangan (daftar manual dan atur ulang lewat
 * email). Dipakai bersama oleh daftarRequest dan aturUlangSandiRequest supaya aturannya tidak bisa dilewati lewat
 * salah satunya. Panjang (8 sampai 72) tetap dicek terpisah di request.
 *
 * - Hanya karakter keyboard biasa (ASCII yang tercetak): huruf A-Z, angka, spasi, dan simbol. Emoji dan karakter
 *   lain ditolak. Ini juga membuat batas bcrypt 72 byte sama dengan 72 karakter.
 * - Wajib memuat huruf besar, huruf kecil, dan angka. Simbol boleh, tidak diwajibkan.
 *
 * Hanya dipakai saat MEMBUAT sandi. Login tidak memeriksa aturan ini, supaya akun lama tetap bisa masuk.
 */
class SandiAnggota implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return; // aturan `string` yang melaporkannya
        }

        // \A ... \z (bukan ^ ... $): `$` ikut meloloskan baris baru di ujung.
        if (preg_match('/\A[\x20-\x7E]+\z/', $value) !== 1) {
            $fail('Kata sandi tidak boleh memakai emoji atau karakter di luar keyboard biasa. Pakai huruf, angka, dan simbol.');

            return;
        }

        if (! preg_match('/[a-z]/', $value) || ! preg_match('/[A-Z]/', $value) || ! preg_match('/[0-9]/', $value)) {
            $fail('Kata sandi harus memuat huruf besar, huruf kecil, dan angka.');
        }
    }
}