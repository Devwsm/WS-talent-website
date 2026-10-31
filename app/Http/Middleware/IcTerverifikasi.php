<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aktivitas anggota (check-in, unggah kenangan, suka) hanya untuk email yang sudah terverifikasi.
 *
 * Anggota yang belum terverifikasi tetap bisa masuk dan melihat-lihat. Pendaftaran dengan kata sandi tidak
 * membuktikan pemilik email, jadi akun yang dibuat dengan email orang lain tidak bisa mengumpulkan stempel atau
 * menayangkan konten. Akun Google sudah terverifikasi sejak masuk. Klaim hadiah punya pemeriksaan sendiri di ClaimService.
 */
class IcTerverifikasi
{
    public const PESAN = 'Verifikasi email dulu untuk memakai fitur ini. Buka tautan di email dari WS Inner Circle, atau kirim ulang dari halaman Beranda.';

    public function handle(Request $request, Closure $next): Response
    {
        $anggota = Auth::guard('member')->user();

        if ($anggota && $anggota->email_verified_at === null) {
            if ($request->expectsJson()) {
                return response()->json(['pesan' => self::PESAN], 403);
            }

            return redirect()->route('ic.beranda')->with('ic_toast_galat', self::PESAN);
        }

        return $next($request);
    }
}