<?php

namespace App\Http\Controllers\InnerCircle;

use App\Http\Controllers\Controller;
use App\Services\InnerCircle\EmailService;
use App\Services\InnerCircle\GoogleAkun;
use App\Services\InnerCircle\GoogleGagal;
use App\Services\InnerCircle\GoogleOAuth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Masuk atau daftar anggota dengan akun Google (guard `member`). Hanya untuk anggota, login admin CMS tidak disentuh.
 */
class googleController extends Controller
{
    public function mulai(Request $request, GoogleOAuth $google): RedirectResponse
    {
        abort_unless(GoogleOAuth::tersedia(), 404);

        if (Auth::guard('member')->check()) {
            return redirect()->route('ic.beranda');
        }

        // Centang Kebijakan Privasi di halaman Daftar ikut sebagai ?setuju=1; tanpa itu akun baru tidak dibuat (lihat GoogleAkun).
        $request->session()->put('ic_privasi_setuju', $request->boolean('setuju'));

        return redirect()->away($google->mulai($request));
    }

    public function callback(Request $request, GoogleOAuth $google, GoogleAkun $akun, EmailService $email): RedirectResponse
    {
        abort_unless(GoogleOAuth::tersedia(), 404);

        if (Auth::guard('member')->check()) {
            return redirect()->route('ic.beranda');
        }

        try {
            [$anggota, $status] = $akun->masukkan($google->selesai($request), (bool) $request->session()->pull('ic_privasi_setuju', false));
        } catch (GoogleGagal $e) {
            return redirect()->route('ic.masuk')->withErrors(['google' => $e->getMessage()]);
        }

        // Identitas Google sudah terbukti; akun yang dinonaktifkan admin tetap tidak boleh masuk.
        if ($anggota->diblokir()) {
            return redirect()->route('ic.masuk')->withErrors(['google' => 'Akun ini dinonaktifkan. Hubungi tim WSM bila ini keliru.']);
        }

        Auth::guard('member')->login($anggota);
        // regenerate() mengganti ID sesi tanpa menghapus isinya (cegah session fixation, sesi admin tetap aman).
        $request->session()->regenerate();
        $request->session()->put('ic_sesi_versi', (int) $anggota->sesi_versi);

        // Sambutan hanya untuk anggota baru (emailnya sudah terverifikasi Google); gagal kirim tidak mengganggu login.
        if ($status === GoogleAkun::BARU) {
            $email->kirimSelamatDatang($anggota);
        }

        $pesan = match ($status) {
            GoogleAkun::BARU => 'Selamat datang di WS Inner Circle!',
            GoogleAkun::TERKAIT => 'Akunmu sudah terhubung ke Google. Mulai sekarang masuk lewat Google ya.',
            default => 'Selamat datang kembali!',
        };

        // Kembali ke tujuan semula (mis. tautan QR check-in) bila anggota tadi dialihkan dari sana.
        return redirect()->intended(route('ic.beranda'))->with('ic_toast', $pesan);
    }
}