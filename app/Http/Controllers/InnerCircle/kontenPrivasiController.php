<?php

namespace App\Http\Controllers\InnerCircle;

use App\Services\InnerCircle\IcBagian;
use App\Http\Controllers\Controller;
use App\Http\Requests\InnerCircle\simpanPrivasiRequest;
use App\Services\InnerCircle\IcPrivasi;
use Illuminate\Http\RedirectResponse;

/** Tab Konten, bagian Kebijakan privasi (/privasi). Logikanya ada di IcPrivasi. */
class kontenPrivasiController extends Controller
{
    public function simpan(simpanPrivasiRequest $request, IcPrivasi $privasi): RedirectResponse
    {
        $privasi->simpan($request->isi(), session('user'));

        return redirect(IcBagian::url('privasi'))
            ->with('success', 'Kebijakan privasi disimpan. Tanggal "Diperbarui" di halaman ikut berubah.');
    }

    public function bawaan(IcPrivasi $privasi): RedirectResponse
    {
        $berubah = $privasi->kembalikanBawaan(session('user'));

        return redirect(IcBagian::url('privasi'))
            ->with('success', $berubah > 0 ? 'Kebijakan privasi dikembalikan ke teks bawaan.' : 'Kebijakan privasi sudah memakai teks bawaan.');
    }
}