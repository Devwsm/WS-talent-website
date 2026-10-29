<?php

namespace App\Http\Middleware;

use App\Services\AktivitasService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Mencatat aksi tulis admin di dashboard (tambah/ubah/hapus) ke halaman Log > Aktivitas. Dipasang di grup /dashboard.
 *
 * - Hanya request POST/PUT/PATCH/DELETE. Membuka halaman tidak dicatat.
 * - Isi form tidak dicatat (hanya nama aksi, id di URL, dan beberapa nilai pendek yang tidak sensitif).
 * - Gagal validasi (salah ketik) tidak dicatat; yang gagal karena aturan atau galat server dicatat sebagai "gagal".
 */
class CatatAktivitas
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $response;
        }

        try {
            $sesi = $request->hasSession() ? $request->session() : null;

            // `_flash.new` = kunci yang di-flash oleh request INI; flash sisa dari request sebelumnya tidak dihitung.
            $baru = $sesi ? (array) $sesi->get('_flash.new', []) : [];

            if (in_array('errors', $baru, true)) {
                return $response; // validasi form gagal
            }

            $gagal = $response->getStatusCode() >= 400
                || in_array('error', $baru, true)
                || in_array('ic_toast_galat', $baru, true);

            app(AktivitasService::class)->catatRequest($request, $gagal ? 'gagal' : 'berhasil');
        } catch (Throwable) {
            // Pencatatan tidak boleh mengganggu aksi admin.
        }

        return $response;
    }
}