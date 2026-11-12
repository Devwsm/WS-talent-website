<?php

namespace App\Http\Middleware;

use App\Support\KebijakanCsp;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header keamanan dasar untuk semua halaman web.
 *
 * - kamera (pemindai QR) dan lokasi (check-in di venue) hanya boleh dipakai halaman sendiri, mikrofon/dll dimatikan
 * - X-Frame-Options mencegah clickjacking (situs ini tidak pernah di-embed di situs lain)
 * - HSTS hanya dikirim lewat HTTPS dan di produksi (jangan di lokal: browser akan "mengunci" localhost ke HTTPS)
 *
 * Content-Security-Policy dikirim menurut config/keamanan.php (mode off / report / enforce). Di production bawaannya
 * `report`: tidak memblokir apa pun, hanya melaporkan ke /csp-report (tampil di halaman Log). Lihat catatan di
 * config/keamanan.php dan README bagian 4.8.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        // Halaman yang membawa token rahasia di URL (atur ulang sandi) menetapkan `no-referrer` sendiri; jangan ditimpa.
        if (! $response->headers->has('Referrer-Policy')) {
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        }
        $response->headers->set(
            'Permissions-Policy',
            'camera=(self), microphone=(), geolocation=(self), payment=(), usb=(), interest-cohort=()'
        );
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');

        if ($csp = KebijakanCsp::header()) {
            $response->headers->set($csp[0], $csp[1]);
        }

        if ($request->isSecure() && app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        return $response;
    }
}