<?php

namespace App\Http\Controllers\InnerCircle;

use App\Http\Controllers\Controller;
use App\Http\Requests\InnerCircle\simpanKontenRequest;
use App\Models\ic_claim;
use App\Services\InnerCircle\IcKonten;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** Tab Konten di CMS Inner Circle: ubah teks halaman anggota tanpa menyentuh kode. */
class kontenController extends Controller
{
    public function index(IcKonten $konten): View
    {
        return view('pages.dashboard-pages.inner-circle.konten', [
            'grup' => config('ic_konten.grup'),
            'kunci' => collect($konten->definisi()),
            'tersimpan' => $konten->tersimpan(),
            'jumlahKlaimMenunggu' => ic_claim::query()->whereIn('status', ic_claim::statusMenunggu())->count(),
        ]);
    }

    public function simpan(string $grup, simpanKontenRequest $request, IcKonten $konten): RedirectResponse
    {
        $berubah = $konten->simpan($grup, $request->nilai(), session('user'));

        return $this->kembali($grup)->with('success', $berubah > 0
            ? "Konten \"" . config("ic_konten.grup.$grup.label") . "\" disimpan ($berubah isian berubah)."
            : 'Tidak ada isian yang berubah.');
    }

    public function reset(string $grup, string $kunci, IcKonten $konten): RedirectResponse
    {
        $def = $konten->definisiKunci($kunci);
        abort_unless($def !== null && $def['grup'] === $grup, 404);

        $konten->reset($kunci);

        return $this->kembali($grup)->with('success', '"' . $def['label'] . '" dikembalikan ke teks bawaan.');
    }

    private function kembali(string $grup): RedirectResponse
    {
        return redirect(route('ic.admin.konten') . '#grup-' . $grup);
    }
}