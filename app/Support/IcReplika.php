<?php

namespace App\Support;

/**
 * Pembantu pratinjau replika di tab Konten (CMS Inner Circle).
 *
 * Pratinjau tidak lagi merender halaman asli di server. Tiap teks digambar ulang di browser memakai class asli area
 * anggota (ic-eyebrow, ic-judul, ic-tombol, ...) dan diperbarui langsung saat admin mengetik. Kelas ini hanya
 * menentukan bentuk (tipe) tiap kunci teks dan cara mengisi placeholder, tanpa database dan tanpa Laravel.
 */
class IcReplika
{
    /** Tombol bergaya garis (sekunder); sisanya tombol utama. */
    private const TOMBOL_GARIS = ['tentang.tombol.masuk', 'teaser.tombol.masuk', 'tentang.cta.masuk'];

    /** Judul besar (hero, teaser, banner, halaman masuk/daftar); judul lain berukuran judul bagian. */
    private const JUDUL_BESAR = [
        'tentang.hero.judul',
        'teaser.judul',
        'komunitas.banner.judul',
        'beranda.salam',
        'masuk.judul',
        'daftar.judul',
        'lupa.judul',
        'atur.judul',
    ];

    /**
     * Bentuk replika untuk satu kunci teks: meta-judul, meta-deskripsi, tombol, tombol-garis, tautan, eyebrow, judul,
     * judul-bagian, atau deskripsi (bawaan untuk teks biasa).
     */
    public static function tipe(string $kunci): string
    {
        $akhir = substr(strrchr('.' . $kunci, '.'), 1);

        if (str_starts_with($kunci, 'tentang.meta.')) {
            return $akhir === 'judul' ? 'meta-judul' : 'meta-deskripsi';
        }

        if (in_array($kunci, self::TOMBOL_GARIS, true)) {
            return 'tombol-garis';
        }

        if ($akhir === 'tombol' || str_contains($kunci, '.tombol.') || $kunci === 'beranda.stiker' || $kunci === 'tentang.cta.masuk') {
            return 'tombol';
        }

        if ($akhir === 'tautan') {
            return 'tautan';
        }

        if (in_array($akhir, ['eyebrow', 'label', 'pil', 'stiker'], true)) {
            return 'eyebrow';
        }

        if ($akhir === 'judul' || $kunci === 'beranda.salam') {
            return in_array($kunci, self::JUDUL_BESAR, true) ? 'judul' : 'judul-bagian';
        }

        return 'deskripsi';
    }

    /**
     * Peta placeholder => teks yang ditampilkan di pratinjau: {program} jadi nama program, {tautan} jadi labelnya.
     * Placeholder lain (mis. {nama}) dibiarkan apa adanya supaya admin melihat posisinya.
     *
     * @param  array<string, array<string, mixed>>  $tautan  config('ic_konten.tautan')
     * @return array<string, string>
     */
    public static function petaGanti(string $program, array $tautan = []): array
    {
        $peta = ['{program}' => $program];
        foreach ($tautan as $nama => $def) {
            $peta['{' . $nama . '}'] = (string) ($def['label'] ?? $nama);
        }

        return $peta;
    }

    /** @param  array<string, string>  $peta */
    public static function ganti(string $teks, array $peta): string
    {
        return $peta ? strtr($teks, $peta) : $teks;
    }
}