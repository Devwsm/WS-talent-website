<?php

namespace App\Http\Controllers\InnerCircle;

use App\Http\Controllers\Controller;
use App\Http\Requests\InnerCircle\simpanItemRequest;
use App\Services\InnerCircle\IcDaftar;
use App\Services\InnerCircle\KontenGagal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Tab Konten, bagian daftar berurutan: langkah "Cara kerjanya", FAQ, dan poin teaser. Logikanya ada di IcDaftar. */
class kontenDaftarController extends Controller
{
    public function tambah(string $daftar, simpanItemRequest $request, IcDaftar $layanan): RedirectResponse
    {
        return $this->jalankan($daftar, function () use ($daftar, $request, $layanan) {
            $layanan->tambah($daftar, $request->butir());

            return 'Ditambahkan ke daftar "' . $layanan->def($daftar)['label'] . '".';
        });
    }

    public function ubah(string $daftar, int $id, simpanItemRequest $request, IcDaftar $layanan): RedirectResponse
    {
        return $this->jalankan($daftar, function () use ($daftar, $id, $request, $layanan) {
            $layanan->ubah($daftar, $id, $request->butir());

            return 'Perubahan disimpan.';
        });
    }

    public function geser(string $daftar, int $id, Request $request, IcDaftar $layanan): RedirectResponse
    {
        $arah = $request->validate(['arah' => ['required', 'in:naik,turun']])['arah'];

        return $this->jalankan($daftar, function () use ($daftar, $id, $arah, $layanan) {
            return $layanan->geser($daftar, $id, $arah)
                ? 'Urutan diperbarui.'
                : 'Sudah di ' . ($arah === 'naik' ? 'paling atas' : 'paling bawah') . '.';
        });
    }

    public function aktif(string $daftar, int $id, Request $request, IcDaftar $layanan): RedirectResponse
    {
        $aktif = $request->boolean('aktif');

        return $this->jalankan($daftar, function () use ($daftar, $id, $aktif, $layanan) {
            $layanan->aktifkan($daftar, $id, $aktif);

            return $aktif ? 'Ditampilkan lagi di halaman.' : 'Disembunyikan dari halaman.';
        });
    }

    public function hapus(string $daftar, int $id, IcDaftar $layanan): RedirectResponse
    {
        return $this->jalankan($daftar, function () use ($daftar, $id, $layanan) {
            $layanan->hapus($daftar, $id);

            return 'Dihapus dari daftar.';
        });
    }

    public function bawaan(string $daftar, IcDaftar $layanan): RedirectResponse
    {
        return $this->jalankan($daftar, function () use ($daftar, $layanan) {
            $jumlah = $layanan->kembalikanBawaan($daftar);

            return "Daftar \"{$layanan->def($daftar)['label']}\" dikembalikan ke bawaan ($jumlah butir).";
        });
    }

    /** Jalankan aksi; daftar tidak dikenal = 404, aturan yang menolak = pesan galat di halaman Konten. */
    private function jalankan(string $daftar, callable $aksi): RedirectResponse
    {
        abort_unless(is_array(config("ic_konten.daftar.$daftar")), 404);

        $kembali = redirect(route('ic.admin.konten') . '#daftar-' . $daftar);

        try {
            return $kembali->with('success', $aksi());
        } catch (KontenGagal $e) {
            return $kembali->with('error', $e->getMessage());
        }
    }
}