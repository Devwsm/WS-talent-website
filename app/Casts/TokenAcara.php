<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Token QR acara: tersimpan terenkripsi di database, sama seperti cast bawaan `encrypted`.
 *
 * Bedanya: bila token tidak bisa dibuka (APP_KEY di server berbeda dari saat token dibuat, mis. setelah
 * `key:generate` ulang atau database dipindah dari komputer lain), hasilnya NULL, bukan galat "The MAC is invalid".
 * Dengan begitu satu token rusak tidak menjatuhkan seluruh halaman Acara maupun check-in; admin cukup memakai
 * tombol "Perbaiki token" di dashboard (lihat adminController@perbaikiToken).
 */
class TokenAcara implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString((string) $value);
        } catch (DecryptException) {
            return null;
        }
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null ? null : Crypt::encryptString((string) $value);
    }
}