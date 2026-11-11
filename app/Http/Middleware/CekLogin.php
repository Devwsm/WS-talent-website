<?php

namespace App\Http\Middleware;

use App\Models\account;
use App\Services\AktivitasService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class CekLogin
{
    public function handle(Request $request, Closure $next)
    {
        $sesi = $request->session();

        if (!$sesi->get('login')) {
            return redirect()->route('login');
        }

        // Sesi yang dibuat lewat login baru membawa id dan sidik sandi akun. Bila akunnya sudah dihapus atau
        // sandinya diganti, sesi dicabut. (Sesi lama sebelum pembaruan ini belum membawanya dan tetap diterima
        // sampai habis masa berlakunya; sesi baru selalu membawanya.)
        if ($sesi->has('admin_id')) {
            $akun = account::find($sesi->get('admin_id'));

            if (!$akun || !hash_equals($akun->sidikSesi(), (string) $sesi->get('admin_sidik'))) {
                app(AktivitasService::class)->catat(
                    'Sesi admin dicabut',
                    'Sandi akun diganti atau akun dihapus',
                    'gagal',
                    'akses',
                    (string) $sesi->get('user'),
                );

                $sesi->invalidate();
                $sesi->regenerateToken();

                return redirect()->route('login')
                    ->withErrors(['login' => 'Sesi berakhir karena data akun berubah. Silakan masuk lagi.']);
            }
        }

        return $next($request);
    }
}