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

        return redirect()->away($google->mulai($request));
    }

    public function callback(Request $request, GoogleOAuth $google, GoogleAkun $akun, EmailService $email): RedirectResponse
    {
        abort_unless(GoogleOAuth::tersedia(), 404);

        if (Auth::guard('member')->check()) {
            return redirect()->route('ic.beranda');
        }

        try {
            [$anggota, $status] = $akun->masukkan($google->selesai($request));
        } catch (GoogleGagal $e) {
            return redirect()->route('ic.masuk')->withErrors(['google' => $e->getMessage()]);
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