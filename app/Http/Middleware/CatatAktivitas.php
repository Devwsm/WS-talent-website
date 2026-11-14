<?php

namespace App\Http\Middleware;

use App\Services\AktivitasService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Mencatat aksi tulis admin di dashboard (tambah/ubah/hapus) ke halaman Log > Aktivitas. Dipasang di grup /dashboard,
 * jadi berlaku untuk CMS Artis dan CMS Inner Circle.
 *
 * - Hanya request POST/PUT/PATCH/DELETE. Membuka halaman tidak dicatat.
 * - Isi form tidak dicatat: hanya nama aksi, nama butir yang diubah, dan beberapa nilai pendek yang tidak sensitif.
 * - Yang dicatat HANYA aksi yang berhasil, atau yang ditolak aturan dengan pesan yang tampil ke admin (mis. "tahap
 *   hadiah terakhir tidak boleh disembunyikan"). Salah ketik di form tidak dicatat, dan galat (kode 4xx/5xx) juga tidak
 *   dicatat di sini: galat server sudah masuk tab Error, jadi mencatatnya lagi hanya membuat catatan ganda.
 */
class CatatAktivitas
{
    public function handle(Request $request, Closure $next): Response
    {
        $tulis = ! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true);

        // Nama butir harus dibaca sebelum aksi berjalan (sesudah dihapus namanya hilang).
        $konteks = $tulis ? app(AktivitasService::class)->siapkan($request) : [];

        $response = $next($request);

        if (! $tulis || $response->getStatusCode() >= 400) {
            return $response;
        }

        try {
            $sesi = $request->hasSession() ? $request->session() : null;

            // `_flash.new` = kunci yang di-flash oleh request INI; flash sisa dari request sebelumnya tidak dihitung.
            $baru = $sesi ? (array) $sesi->get('_flash.new', []) : [];

            if (in_array('errors', $baru, true)) {
                return $response; // validasi form gagal
            }

            $pesan = null;
            $gagal = false;
            foreach (['error', 'ic_toast_galat'] as $kunci) {
                if ($sesi && in_array($kunci, $baru, true)) {
                    $gagal = true;
                    $nilai = $sesi->get($kunci);
                    $pesan ??= is_string($nilai) ? $nilai : null;
                }
            }

            app(AktivitasService::class)->catatRequest($request, $gagal ? 'gagal' : 'berhasil', $konteks, $pesan);
        } catch (Throwable) {
            // Pencatatan tidak boleh mengganggu aksi admin.
        }

        return $response;
    }
}