<?php

namespace App\Http\Controllers\InnerCircle;

use App\Http\Controllers\Controller;
use App\Http\Requests\InnerCircle\daftarRequest;
use App\Http\Requests\InnerCircle\masukRequest;
use App\Models\member;
use App\Services\InnerCircle\EmailService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Masuk, Daftar, dan Keluar anggota WS Inner Circle (guard `member`).
 *
 * Login admin CMS (`session('login')`, tabel `accounts`) tidak disentuh. Karena itu logout anggota
 * TIDAK memakai session()->invalidate(): sesi admin di browser yang sama harus tetap hidup.
 */
class authController extends Controller
{
    public function masuk()
    {
        return Auth::guard('member')->check()
            ? redirect()->route('ic.beranda')
            : view('pages.inner-circle.masuk');
    }

    public function daftar()
    {
        return Auth::guard('member')->check()
            ? redirect()->route('ic.beranda')
            : view('pages.inner-circle.daftar');
    }

    public function prosesMasuk(masukRequest $request): RedirectResponse
    {
        $berhasil = Auth::guard('member')->attempt([
            'email' => $request->validated('email'),
            'password' => $request->validated('sandi'),
        ]);

        if (! $berhasil) {
            // Pesan sengaja generik: tidak membocorkan apakah emailnya terdaftar (atau hanya memakai Google).
            return back()
                ->withErrors(['email' => 'Email atau kata sandi salah.'])
                ->onlyInput('email');
        }

        // regenerate() mengganti ID sesi tanpa menghapus isinya (cegah session fixation, sesi admin aman).
        $request->session()->regenerate();
        $request->session()->put('ic_sesi_versi', (int) Auth::guard('member')->user()->sesi_versi);

        // Kalau anggota tadi diarahkan ke Masuk dari tautan QR check-in, kembalikan ke sana.
        return redirect()->intended(route('ic.beranda'))->with('ic_toast', 'Selamat datang kembali!');
    }

    public function prosesDaftar(daftarRequest $request, EmailService $email): RedirectResponse
    {
        try {
            $anggota = DB::transaction(function () use ($request) {
                $baru = member::create([
                    'nama' => $request->validated('nama'),
                    'email' => $request->validated('email'),
                    'password' => $request->validated('sandi'),
                ]);

                // IC-00001: nomor urut dari ID, diisi di transaksi yang sama dengan pembuatan akunnya.
                $baru->kode_member = sprintf('IC-%05d', $baru->id_member);
                $baru->save();

                return $baru;
            });
        } catch (UniqueConstraintViolationException) {
            // Dua pendaftaran dengan email sama persis bersamaan: yang kedua kalah di batasan unik database.
            return back()
                ->withErrors(['email' => 'Email ini sudah terdaftar. Silakan masuk.'])
                ->withInput($request->except(['sandi', 'sandi_confirmation']));
        }

        Auth::guard('member')->login($anggota);
        $request->session()->regenerate();
        $request->session()->put('ic_sesi_versi', (int) $anggota->sesi_versi);

        // Email belum terbukti milik pendaftar: kirim tautan verifikasi (hadiah baru bisa diajukan setelah terverifikasi).
        // Gagal kirim tidak menggagalkan pendaftaran; anggota bisa meminta kirim ulang dari halaman Hadiah.
        $email->kirimVerifikasi($anggota);

        return redirect()->intended(route('ic.beranda'))
            ->with('ic_toast', 'Selamat datang di WS Inner Circle! Cek emailmu untuk verifikasi sebelum mengajukan hadiah.');
    }

    /**
     * Menyelesaikan verifikasi dari tautan email. Hanya sah bila tanda tangan dan masa berlakunya benar DAN tautan itu
     * milik anggota yang sedang masuk. Rute ini di belakang `auth:member`, jadi orang yang mendaftarkan email orang
     * lain tidak bisa mengesahkannya: kata sandi yang ia pasang tidak diketahui pemilik email, dan pemilik email tidak
     * bisa masuk ke akun itu untuk membuka tautannya.
     */
    public function verifikasiEmail(Request $request, int $id, string $hash): RedirectResponse
    {
        $anggota = Auth::guard('member')->user();

        if (! $request->hasValidSignature()) {
            return redirect()->route('ic.hadiah')
                ->with('ic_toast_galat', 'Tautan verifikasi sudah kedaluwarsa atau tidak valid. Kirim ulang email verifikasi dari halaman ini.');
        }

        if ((int) $anggota->getKey() !== $id || ! hash_equals(sha1(mb_strtolower((string) $anggota->email)), $hash)) {
            return redirect()->route('ic.hadiah')
                ->with('ic_toast_galat', 'Tautan ini bukan untuk akun yang sedang masuk. Masuk dengan akun yang benar, lalu buka tautannya lagi.');
        }

        if ($anggota->email_verified_at !== null) {
            return redirect()->route('ic.hadiah')->with('ic_toast', 'Emailmu sudah terverifikasi.');
        }

        $anggota->forceFill(['email_verified_at' => now()])->save();

        return redirect(route('ic.hadiah') . '#klaim')
            ->with('ic_toast', 'Email terverifikasi. Sekarang kamu bisa mengajukan hadiah.');
    }

    /** Kirim ulang email verifikasi (tombol di halaman Hadiah). Dibatasi per anggota lewat pembatas `ic-verifikasi`. */
    public function kirimUlangVerifikasi(EmailService $email): RedirectResponse
    {
        $anggota = Auth::guard('member')->user();

        if ($anggota->email_verified_at !== null) {
            return redirect()->route('ic.hadiah')->with('ic_toast', 'Emailmu sudah terverifikasi.');
        }

        if (! $email->kirimVerifikasi($anggota)) {
            return redirect()->route('ic.hadiah')
                ->with('ic_toast_galat', 'Layanan email verifikasi sedang padat hari ini. Coba lagi besok, atau hubungi admin.');
        }

        return redirect()->route('ic.hadiah')
            ->with('ic_toast', 'Email verifikasi dikirim ke ' . $anggota->email . '. Cek juga folder spam.');
    }

    public function keluar(Request $request): RedirectResponse
    {
        Auth::guard('member')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('ic.masuk')->with('ic_toast', 'Kamu sudah keluar.');
    }
}