<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * Sidik perangkat untuk penanda check-in (Fase 5B).
 *
 * BUKAN sidik jari browser dan BUKAN alamat IP: perangkat dikenali lewat satu token acak di cookie `ic_perangkat`
 * (httpOnly, dibuat saat check-in pertama). Yang disimpan di database hanya hash HMAC token itu (kunci = APP_KEY),
 * jadi database yang bocor tidak bisa dicocokkan dengan cookie siapa pun. Dua akun yang check-in dari browser yang
 * sama di acara yang sama menghasilkan sidik yang sama.
 *
 * Batas: menghapus cookie atau memakai browser lain menghasilkan sidik baru. Ini isyarat untuk ditinjau manusia,
 * bukan bukti.
 */
class SidikPerangkat
{
    public const COOKIE = 'ic_perangkat';

    public static function untuk(Request $request): string
    {
        $token = $request->cookie(self::COOKIE);

        if (! is_string($token) || ! preg_match('/^[A-Za-z0-9]{40}$/', $token)) {
            $token = Str::random(40);
            Cookie::queue(Cookie::make(
                self::COOKIE,
                $token,
                (int) config('inner_circle.penanda.cookie_hari', 730) * 24 * 60,
                '/',
                null,
                $request->isSecure(),
                true,
                false,
                'lax',
            ));
        }

        return hash_hmac('sha256', $token, (string) config('app.key'));
    }
}