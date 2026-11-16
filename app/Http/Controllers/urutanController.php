<?php

namespace App\Http\Controllers;

use App\Services\Cms\UrutanTampil;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Tombol geser naik/turun dan "jadikan pertama" di daftar CMS Artis (album, banner, header, merchandise). */
class urutanController extends Controller
{
    /** Nama route daftar tiap jenis data dan kata untuk pesan. */
    private const DAFTAR = [
        'albums' => ['route' => 'albums', 'objek' => 'Album', 'paginasi' => true],
        'banner' => ['route' => 'banner', 'objek' => 'Banner', 'paginasi' => false],
        'headers' => ['route' => 'headers', 'objek' => 'Header', 'paginasi' => false],
        'merchandise' => ['route' => 'merchandise', 'objek' => 'Merchandise', 'paginasi' => true],
    ];

    public function geser(Request $request, UrutanTampil $urutan, int $id): RedirectResponse
    {
        $modul = (string) $request->route('modul');
        $arah = $request->validate(['arah' => ['required', 'in:' . implode(',', UrutanTampil::ARAH)]])['arah'];
        $d = self::DAFTAR[$modul] ?? abort(404);

        try {
            $hasil = $urutan->geser($modul, $id, $arah);
        } catch (ModelNotFoundException) {
            return redirect()->route($d['route'])->withErrors($d['objek'] . ' tidak ditemukan.');
        }

        // Kembali ke halaman tempat data itu sekarang berada, lengkap dengan penanda supaya langsung terlihat.
        $parameter = $d['paginasi'] ? ['page' => UrutanTampil::halamanUntuk($hasil['posisi'])] : [];
        $tujuan = route($d['route'], $parameter) . '#item-' . $id;

        if (! $hasil['bergeser']) {
            return redirect($tujuan)->with('success', $arah === 'turun'
                ? $d['objek'] . ' ini sudah paling belakang.'
                : $d['objek'] . ' ini sudah paling depan.');
        }

        return redirect($tujuan)->with('success', $arah === 'awal'
            ? $d['objek'] . ' dijadikan pertama di halaman utama.'
            : 'Urutan ' . strtolower($d['objek']) . ' diperbarui: sekarang nomor ' . $hasil['posisi'] . '.');
    }
}