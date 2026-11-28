<?php

namespace App\Http\Controllers\InnerCircle;

use App\Http\Controllers\Controller;
use App\Http\Requests\InnerCircle\simpanGameRequest;
use App\Services\InnerCircle\IcBagian;
use App\Services\InnerCircle\IcGame;
use Illuminate\Http\RedirectResponse;

/** Tab Konten, bagian Aturan game: status, jatah ronde, dan batas poin. Logikanya ada di IcGame. */
class kontenGameController extends Controller
{
    public function simpan(simpanGameRequest $request, IcGame $game): RedirectResponse
    {
        return $this->selesai($game->simpan($request->nilai(), session('user')));
    }

    public function bawaan(IcGame $game): RedirectResponse
    {
        return $this->selesai($game->kembalikanBawaan(session('user')));
    }

    private function selesai(int $berubah): RedirectResponse
    {
        $pesan = $berubah > 0 ? 'Aturan game disimpan. Berlaku untuk ronde berikutnya.' : 'Tidak ada aturan game yang berubah.';

        return redirect(IcBagian::url('game'))->with('success', $pesan);
    }
}