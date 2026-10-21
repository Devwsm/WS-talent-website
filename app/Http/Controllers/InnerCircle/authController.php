<?php

namespace App\Http\Controllers\InnerCircle;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Halaman Masuk dan Daftar anggota WS Inner Circle.
 *
 * Fase 2 (UI dulu): form hanya divalidasi supaya tampilan pesan galatnya bisa ditinjau.
 * Belum ada akun, sesi, atau guard `member`: setelah lolos validasi anggota langsung
 * diarahkan ke Beranda yang berisi data contoh. Fase 4 mengganti isi dua method proses
 * di bawah dengan Form Request, tabel `members`, dan guard `member`.
 */
class authController extends Controller
{
    public function masuk()
    {
        return view('pages.inner-circle.masuk');
    }

    public function daftar()
    {
        return view('pages.inner-circle.daftar');
    }

    public function prosesMasuk(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'max:150'],
            'sandi' => ['required', 'string'],
        ], $this->pesan(), $this->atribut());

        if ($validator->fails()) {
            return $this->kembaliDenganGalat($request, $validator);
        }

        return redirect()->route('ic.beranda')->with('ic_toast', 'Selamat datang kembali!');
    }

    public function prosesDaftar(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'nama' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'sandi' => ['required', 'string', 'min:8', 'confirmed'],
        ], $this->pesan(), $this->atribut());

        if ($validator->fails()) {
            return $this->kembaliDenganGalat($request, $validator);
        }

        return redirect()->route('ic.beranda')->with('ic_toast', 'Selamat datang di WS Inner Circle!');
    }

    /** Kata sandi sengaja tidak ikut disimpan ke sesi sebagai input lama. */
    private function kembaliDenganGalat(Request $request, $validator): RedirectResponse
    {
        return redirect()->back()
            ->withErrors($validator)
            ->withInput($request->except(['sandi', 'sandi_confirmation']));
    }

    private function atribut(): array
    {
        return [
            'nama' => 'Nama lengkap',
            'email' => 'Email',
            'sandi' => 'Kata sandi',
        ];
    }

    private function pesan(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'email' => 'Format email belum benar.',
            'max' => ':attribute terlalu panjang (maksimal :max karakter).',
            'sandi.min' => 'Kata sandi minimal :min karakter.',
            'sandi.confirmed' => 'Ulangi kata sandi belum sama.',
        ];
    }
}