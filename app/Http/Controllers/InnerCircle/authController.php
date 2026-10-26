<?php

namespace App\Http\Controllers\InnerCircle;

use App\Http\Controllers\Controller;
use App\Http\Requests\InnerCircle\daftarRequest;
use App\Http\Requests\InnerCircle\masukRequest;
use App\Models\member;
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

    public function prosesDaftar(daftarRequest $request): RedirectResponse
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

        return redirect()->intended(route('ic.beranda'))->with('ic_toast', 'Selamat datang di WS Inner Circle!');
    }

    public function keluar(Request $request): RedirectResponse
    {
        Auth::guard('member')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('ic.masuk')->with('ic_toast', 'Kamu sudah keluar.');
    }
}