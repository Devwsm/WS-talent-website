<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mengeluarkan sesi anggota yang "ketinggalan zaman". `members.sesi_versi` dinaikkan saat akun dikaitkan ke Google
 * (lihat GoogleAkun), sehingga sesi yang masuk sebelumnya (mis. dengan sandi lama milik orang lain) langsung berakhir.
 * Dipasang setelah `auth:member`.
 */
class IcSesiVersi
{
    public function handle(Request $request, Closure $next): Response
    {
        $anggota = Auth::guard('member')->user();

        if ($anggota && (int) $request->session()->get('ic_sesi_versi', 0) !== (int) $anggota->sesi_versi) {
            Auth::guard('member')->logout();

            return redirect()->route('ic.masuk')->with('ic_toast', 'Sesimu sudah berakhir. Silakan masuk lagi.');
        }

        return $next($request);
    }
}