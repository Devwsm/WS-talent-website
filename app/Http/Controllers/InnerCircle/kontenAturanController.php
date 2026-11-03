<?php

namespace App\Http\Controllers\InnerCircle;

use App\Http\Controllers\Controller;
use App\Http\Requests\InnerCircle\simpanAturanRequest;
use App\Services\InnerCircle\IcAturan;
use Illuminate\Http\RedirectResponse;

/** Tab Konten, bagian Aturan check-in: jendela, radius, dan akurasi bawaan. Logikanya ada di IcAturan. */
class kontenAturanController extends Controller
{
    public function simpan(simpanAturanRequest $request, IcAturan $aturan): RedirectResponse
    {
        return $this->selesai($aturan->simpan($request->nilai(), session('user')));
    }

    public function bawaan(IcAturan $aturan): RedirectResponse
    {
        return $this->selesai($aturan->kembalikanBawaan(session('user')));
    }

    /** @param  array{berubah: int, dibekukan: int}  $hasil */
    private function selesai(array $hasil): RedirectResponse
    {
        $pesan = $hasil['berubah'] > 0 ? 'Aturan check-in disimpan. Berlaku untuk acara yang dibuat berikutnya.' : 'Tidak ada aturan yang berubah.';
        if ($hasil['dibekukan'] > 0) {
            $pesan .= " {$hasil['dibekukan']} acara yang sudah ada tetap memakai aturan lamanya.";
        }

        return redirect(route('ic.admin.konten') . '#aturan-checkin')->with('success', $pesan);
    }
}