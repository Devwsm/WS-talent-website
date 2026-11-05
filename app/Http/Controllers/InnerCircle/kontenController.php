<?php

namespace App\Http\Controllers\InnerCircle;

use App\Http\Controllers\Controller;
use App\Http\Requests\InnerCircle\simpanKontenRequest;
use App\Models\ic_claim;
use App\Models\ic_pilihan;
use App\Services\InnerCircle\IcAturan;
use App\Services\InnerCircle\IcBagian;
use App\Services\InnerCircle\IcDaftar;
use App\Services\InnerCircle\IcKonten;
use App\Services\InnerCircle\IcPilihan;
use App\Services\InnerCircle\IcPrivasi;
use App\Services\InnerCircle\IcTampilan;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** Tab Konten di CMS Inner Circle: ubah teks halaman anggota tanpa menyentuh kode. */
class kontenController extends Controller
{
    /** `/konten` langsung membuka bagian pertama; pindah bagian lewat navbar atas (tanpa halaman pilihan). */
    public function index(IcKonten $konten, IcDaftar $daftar, IcPilihan $pilihan, IcAturan $aturan, IcTampilan $tampilan, IcPrivasi $privasi): View
    {
        return $this->bagian((string) array_key_first(IcBagian::semua()), $konten, $daftar, $pilihan, $aturan, $tampilan, $privasi);
    }

    /** Satu bagian: hanya formulir dan pratinjau topik itu, bukan seluruh tab. */
    public function bagian(string $bagian, IcKonten $konten, IcDaftar $daftar, IcPilihan $pilihan, IcAturan $aturan, IcTampilan $tampilan, IcPrivasi $privasi): View
    {
        $info = IcBagian::cari($bagian);
        abort_if($info === null, 404);

        // Jumlah isian yang sudah diubah per bagian teks, untuk angka kecil di navbar atas.
        $tersimpan = $konten->tersimpan();
        $diubah = [];
        foreach (IcBagian::semua() as $slug => $b) {
            if ($b['jenis'] === 'grup') {
                $diubah[$slug] = collect($konten->definisi())->where('grup', $b['kode'])->keys()->filter(fn($k) => isset($tersimpan[$k]))->count();
            }
        }

        return view('pages.dashboard-pages.inner-circle.konten-bagian', [
            'bagian' => $info,
            'semuaBagian' => IcBagian::semua(),
            'kelompokBagian' => IcBagian::berkelompok(),
            'diubahPerBagian' => $diubah,
            'modul' => $info['jenis'] === 'grup' ? IcBagian::modul($info['kode']) : [],
            // Tautan lama (#grup-x, #daftar-x, ...) dialihkan ke halaman bagiannya oleh skrip kecil di halaman.
            'petaJangkarLama' => IcBagian::petaJangkarLama(),
            'grup' => config('ic_konten.grup'),
            'kunci' => collect($konten->definisi()),
            'tersimpan' => $tersimpan,
            // Daftar berurutan (langkah, FAQ, poin teaser): definisi dan seluruh butirnya, termasuk yang disembunyikan.
            'daftarDef' => $daftar->definisi(),
            'butir' => collect($daftar->definisi())->map(fn($def, $kode) => $daftar->semua($kode))->all(),
            'ikonDaftar' => config('ic_konten.ikon_daftar'),
            // Pilihan (tipe acara, kategori kenangan) dan aturan bawaan check-in.
            'tipeBaris' => $pilihan->baris(ic_pilihan::TIPE_EVENT),
            'kategoriBaris' => $pilihan->baris(ic_pilihan::KATEGORI_KENANGAN),
            'pemakaianKategori' => $pilihan->pemakaianKategori(),
            'ikonKategori' => config('inner_circle.ikon_pilihan'),
            'ikonTipe' => config('inner_circle.ikon_pilihan') + config('inner_circle.ikon_tipe_tambahan'),
            'warnaKartu' => config('inner_circle.warna_kartu'),
            'warnaHex' => config('inner_circle.warna_kartu_hex'),
            'aturan' => $aturan->efektif(),
            'aturanBawaan' => $aturan->bawaan(),
            'aturanRingkasan' => $aturan->ringkasan(),
            // Kebijakan privasi: isi awal editor = isi kustom, atau teks bawaan.
            'privasiKustom' => $privasi->sudahKustom(),
            'privasiHtml' => $privasi->isi() ?? $privasi->bawaanHtml(),
            'privasiTanggal' => $privasi->diperbarui(),
            // Tampilan dan merek: nama program, warna, dan logo.
            'tampilanInfo' => config('ic_konten.tampilan'),
            'tampilanWarna' => $tampilan->warna(),
            'tampilanWarnaSimpan' => $tampilan->warnaTersimpan(),
            'tampilanProgram' => $tampilan->programTersimpan(),
            'tampilanProgramBawaan' => $tampilan->programBawaan(),
            'tampilanLogo' => $tampilan->logoUrl(),
            'jumlahKlaimMenunggu' => ic_claim::query()->whereIn('status', ic_claim::statusMenunggu())->count(),
        ]);
    }

    public function simpan(string $grup, simpanKontenRequest $request, IcKonten $konten): RedirectResponse
    {
        $berubah = $konten->simpan($grup, $request->nilai(), session('user'));

        return $this->kembali($grup, $request->input('modul'))->with('success', $berubah > 0
            ? "Konten \"" . config("ic_konten.grup.$grup.label") . "\" disimpan ($berubah isian berubah)."
            : 'Tidak ada isian yang berubah.');
    }

    public function reset(string $grup, string $kunci, IcKonten $konten, \Illuminate\Http\Request $request): RedirectResponse
    {
        $def = $konten->definisiKunci($kunci);
        abort_unless($def !== null && $def['grup'] === $grup, 404);

        $konten->reset($kunci);

        return $this->kembali($grup, $request->input('modul'))->with('success', '"' . $def['label'] . '" dikembalikan ke teks bawaan.');
    }

    /** Kembali ke bagian yang sama, dan ke modul yang baru disimpan bila namanya valid (hanya huruf kecil, angka, - dan _). */
    private function kembali(string $grup, mixed $modul = null): RedirectResponse
    {
        $jangkar = is_string($modul) && preg_match('/^[a-z0-9_-]{1,40}$/', $modul) ? 'modul-' . $grup . '-' . $modul : null;

        return redirect(IcBagian::url($grup, $jangkar));
    }
}