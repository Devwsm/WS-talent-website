<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ic_claim;
use App\Models\ic_pemeriksaan;
use App\Models\ic_penanda;
use Illuminate\Http\Request;

/**
 * Pembantu bersama controller halaman CMS Inner Circle (adminController, acaraController, acaraHariHController):
 * merender satu tab dengan angka lencana, peta ikon untuk preview, dan membaca parameter query sebagai teks.
 */
trait PembantuHalamanAdminIc
{
    /** Render satu tab; angka lencana di bar tab selalu ikut dikirim. */
    protected function tampil(string $tab, array $data)
    {
        return view('pages.dashboard-pages.inner-circle.' . $tab, $data + [
            'jumlahKlaimMenunggu' => ic_claim::query()->whereIn('status', ic_claim::statusMenunggu())->count(),
            // Lencana chip Acara > Pemeriksaan (Fase 5B): pengajuan menunggu + penanda yang belum ditinjau.
            'jumlahPemeriksaanMenunggu' => ic_pemeriksaan::query()->where('status', ic_pemeriksaan::MENUNGGU)->count()
                + ic_penanda::query()->where('status', ic_penanda::BARU)->count(),
        ]);
    }

    /**
     * Peta ikon -> alamat gambar (atau emoji bila berkas WebP belum ada) untuk JavaScript preview.
     * Logikanya sama dengan komponen <x-inner-circle.ikon>.
     *
     * @return array<string, array{url?: string, emoji?: string}>
     */
    protected function ikonPeta(): array
    {
        $peta = [];
        foreach (config('inner_circle.ikon') as $nama => $emoji) {
            $berkas = 'aset/inner-circle/ikon/' . $nama . '.webp';
            $peta[$nama] = file_exists(public_path($berkas))
                ? ['url' => asset($berkas)]
                : ['emoji' => $emoji];
        }

        return $peta;
    }

    /**
     * Alamat daftar jadwal acara tempat popup Atur acara dibuka (filter dan halaman yang sama, tanpa ?atur=),
     * dipakai sebagai tujuan setelah menyimpan. Hanya alamat di situs ini yang dipercaya; selain itu daftar polos.
     */
    protected function urlDaftarAcara(Request $request): string
    {
        $dasar = route('ic.admin.acara');
        $asal = parse_url((string) url()->previous($dasar));
        $dasarBagian = parse_url($dasar);

        if (($asal['host'] ?? null) !== ($dasarBagian['host'] ?? null) || ($asal['path'] ?? '') !== ($dasarBagian['path'] ?? '')) {
            return $dasar . '#jadwal';
        }

        parse_str((string) ($asal['query'] ?? ''), $query);
        unset($query['atur']);

        return $dasar . ($query ? '?' . http_build_query($query) : '') . '#jadwal';
    }

    /** Ambil parameter query sebagai teks; array atau nilai aneh dianggap kosong. */
    protected function teks(Request $request, string $kunci): string
    {
        $nilai = $request->query($kunci);

        return is_string($nilai) ? $nilai : '';
    }
}