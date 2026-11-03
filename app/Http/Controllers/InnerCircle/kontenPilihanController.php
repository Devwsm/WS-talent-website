<?php

namespace App\Http\Controllers\InnerCircle;

use App\Http\Controllers\Controller;
use App\Http\Requests\InnerCircle\simpanPilihanRequest;
use App\Models\ic_pilihan;
use App\Services\InnerCircle\IcPilihan;
use App\Services\InnerCircle\KontenGagal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Tab Konten, bagian Pilihan: tipe acara dan kategori kenangan. Logikanya ada di IcPilihan. */
class kontenPilihanController extends Controller
{
    public function ubahTipe(string $kunci, simpanPilihanRequest $request, IcPilihan $pilihan): RedirectResponse
    {
        return $this->jalankan('tipe', function () use ($kunci, $request, $pilihan) {
            $baris = $pilihan->ubahTipe($kunci, $request->isian());

            return "Tipe acara \"{$baris->label}\" disimpan.";
        });
    }

    public function bawaanTipe(string $kunci, IcPilihan $pilihan): RedirectResponse
    {
        return $this->jalankan('tipe', fn() => $this->bawaan($pilihan, ic_pilihan::TIPE_EVENT, $kunci));
    }

    public function tambahKategori(simpanPilihanRequest $request, IcPilihan $pilihan): RedirectResponse
    {
        return $this->jalankan('kategori', function () use ($request, $pilihan) {
            $baris = $pilihan->tambahKategori($request->isian());

            return "Kategori \"{$baris->label}\" ditambahkan.";
        });
    }

    public function ubahKategori(string $kunci, simpanPilihanRequest $request, IcPilihan $pilihan): RedirectResponse
    {
        return $this->jalankan('kategori', function () use ($kunci, $request, $pilihan) {
            $baris = $pilihan->ubahKategori($kunci, $request->isian());

            return "Kategori \"{$baris->label}\" disimpan.";
        });
    }

    public function geserKategori(string $kunci, Request $request, IcPilihan $pilihan): RedirectResponse
    {
        $arah = $request->validate(['arah' => ['required', 'in:naik,turun']])['arah'];

        return $this->jalankan('kategori', fn() => $pilihan->geserKategori($kunci, $arah)
            ? 'Urutan diperbarui.'
            : 'Sudah di ' . ($arah === 'naik' ? 'paling atas' : 'paling bawah') . '.');
    }

    public function aktifKategori(string $kunci, Request $request, IcPilihan $pilihan): RedirectResponse
    {
        $aktif = $request->boolean('aktif');

        return $this->jalankan('kategori', function () use ($kunci, $aktif, $pilihan) {
            $pilihan->aktifkanKategori($kunci, $aktif);

            return $aktif ? 'Kategori ditampilkan lagi.' : 'Kategori disembunyikan: tidak bisa dipilih lagi, kenangan lamanya tetap tampil.';
        });
    }

    public function hapusKategori(string $kunci, IcPilihan $pilihan): RedirectResponse
    {
        return $this->jalankan('kategori', function () use ($kunci, $pilihan) {
            $pilihan->hapusKategori($kunci);

            return 'Kategori dihapus.';
        });
    }

    public function bawaanKategori(string $kunci, IcPilihan $pilihan): RedirectResponse
    {
        return $this->jalankan('kategori', fn() => $this->bawaan($pilihan, ic_pilihan::KATEGORI_KENANGAN, $kunci));
    }

    private function bawaan(IcPilihan $pilihan, string $jenis, string $kunci): string
    {
        if (! $pilihan->kembalikanBawaan($jenis, $kunci)) {
            throw new KontenGagal('Pilihan ini bukan pilihan bawaan, jadi tidak ada yang dikembalikan.');
        }

        return 'Dikembalikan ke bawaan.';
    }

    /** Jalankan aksi; aturan yang menolak = pesan galat di halaman Konten, tepat di bagiannya. */
    private function jalankan(string $bagian, callable $aksi): RedirectResponse
    {
        $kembali = redirect(route('ic.admin.konten') . '#pilihan-' . $bagian);

        try {
            return $kembali->with('success', $aksi());
        } catch (KontenGagal $e) {
            return $kembali->with('error', $e->getMessage());
        }
    }
}