<?php

namespace App\Http\Controllers\InnerCircle;

use App\Http\Controllers\Controller;
use App\Http\Requests\InnerCircle\simpanTampilanRequest;
use App\Services\InnerCircle\IcTampilan;
use App\Support\GambarGagal;
use Illuminate\Http\RedirectResponse;

/** Tab Konten, bagian Tampilan: nama program, warna, dan logo. Logikanya ada di IcTampilan. */
class kontenTampilanController extends Controller
{
    public function simpan(simpanTampilanRequest $request, IcTampilan $tampilan): RedirectResponse
    {
        try {
            $berubah = $tampilan->simpan(
                $request->nilai(),
                $request->file('logo'),
                $request->boolean('hapus_logo'),
                session('user'),
            );
        } catch (GambarGagal $e) {
            return $this->kembali()->withInput()->withErrors(['logo' => $e->getMessage()], 'tampilan');
        }

        return $this->kembali()->with('success', $berubah > 0 ? "Tampilan disimpan ($berubah pengaturan berubah)." : 'Tidak ada pengaturan yang berubah.');
    }

    public function bawaan(IcTampilan $tampilan): RedirectResponse
    {
        $berubah = $tampilan->kembalikanBawaan(session('user'));

        return $this->kembali()->with('success', $berubah > 0 ? 'Tampilan dikembalikan ke bawaan.' : 'Tampilan sudah memakai bawaan.');
    }

    private function kembali(): RedirectResponse
    {
        return redirect(route('ic.admin.konten') . '#tampilan');
    }
}