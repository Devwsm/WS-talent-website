<?php

namespace App\Http\Controllers\InnerCircle;

use App\Http\Controllers\Controller;
use App\Http\Requests\InnerCircle\aturUlangSandiRequest;
use App\Http\Requests\InnerCircle\lupaSandiRequest;
use App\Models\member;
use App\Services\InnerCircle\EmailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Lupa kata sandi anggota WS Inner Circle (guard `member`).
 *
 * - Jawaban SELALU sama, apa pun emailnya (terdaftar, tidak, atau akun Google): tidak membocorkan siapa yang punya akun.
 * - Email dikirim setelah respons terkirim (EmailService::tunda) supaya waktu respons pun tidak membedakan.
 * - Akun yang dibuat lewat Google tidak punya kata sandi: mereka menerima email yang mengarahkan masuk dengan Google.
 * - Memakai tautan atur ulang membuktikan pemilik email: email diverifikasi, sesi lama dikeluarkan (sesi_versi naik),
 *   dan token "ingat saya" diganti. Ini juga menutup celah orang lain yang lebih dulu mendaftarkan email itu.
 */
class lupaSandiController extends Controller
{
    public function form()
    {
        return Auth::guard('member')->check()
            ? redirect()->route('ic.beranda')
            : view('pages.inner-circle.lupa-sandi');
    }

    public function kirim(lupaSandiRequest $request, EmailService $email): RedirectResponse
    {
        $alamat = (string) $request->validated('email');

        $email->tunda(fn() => $email->kirimLupaSandi($alamat));

        return redirect()->route('ic.sandi.lupa')->with('lupa_terkirim', $alamat);
    }

    public function formAtur(Request $request, string $token): Response|RedirectResponse
    {
        if (Auth::guard('member')->check()) {
            return redirect()->route('ic.beranda');
        }

        $email = $request->query('email');

        // Tautan berisi token rahasia: jangan disimpan cache browser atau proxy.
        return response()
            ->view('pages.inner-circle.atur-ulang', ['token' => $token, 'email' => is_string($email) ? $email : ''])
            ->header('Cache-Control', 'no-store');
    }

    public function proses(aturUlangSandiRequest $request): RedirectResponse
    {
        $status = Password::broker('members')->reset(
            [
                'email' => $request->validated('email'),
                'password' => $request->validated('sandi'),
                'password_confirmation' => $request->input('sandi_confirmation'),
                'token' => $request->validated('token'),
            ],
            function (member $anggota, string $sandi) {
                // Kolom `password` memakai cast `hashed`, jadi cukup diisi teks biasa.
                $anggota->forceFill([
                    'password' => $sandi,
                    'remember_token' => Str::random(60),
                    'sesi_versi' => (int) $anggota->sesi_versi + 1,
                ]);
                $anggota->email_verified_at ??= now();
                $anggota->save();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()
                ->withErrors(['email' => 'Tautan ini sudah kedaluwarsa atau tidak valid. Minta tautan baru dari halaman Lupa kata sandi.'])
                ->onlyInput('email');
        }

        return redirect()->route('ic.masuk')->with('ic_toast', 'Kata sandi sudah diperbarui. Silakan masuk.');
    }
}