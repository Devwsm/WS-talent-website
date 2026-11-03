<?php

use App\Services\InnerCircle\IcKonten;
use App\Services\InnerCircle\IcPilihan;
use App\Services\InnerCircle\IcTampilan;
use Illuminate\Support\Collection;

/*
 * Fungsi bantu untuk view. Dimuat oleh AppServiceProvider (bukan lewat composer.json), jadi tidak perlu
 * `composer dump-autoload` setiap ada perubahan.
 */

if (! function_exists('ic_teks')) {
    /** Teks konten Inner Circle (dari CMS, atau teks bawaan). Tampilkan selalu dengan {{ }} supaya di-escape. */
    function ic_teks(string $kunci, array $isi = []): string
    {
        return app(IcKonten::class)->teks($kunci, $isi);
    }
}

if (! function_exists('ic_daftar')) {
    /** Daftar item aktif satu grup konten, urut. */
    function ic_daftar(string $grup): Collection
    {
        return app(IcKonten::class)->daftar($grup);
    }
}

if (! function_exists('ic_html')) {
    /**
     * Teks konten Inner Circle sebagai HTML aman (baris baru jadi <br>, {tautan} jadi tautan resmi). Hasilnya sudah
     * di-escape, jadi tetap ditulis {{ ic_html('kunci') }} (tidak perlu {!! !!}).
     */
    function ic_html(string $kunci, array $isi = []): Illuminate\Support\HtmlString
    {
        return app(IcKonten::class)->html($kunci, $isi);
    }
}

if (! function_exists('ic_html_daftar')) {
    /** Isi satu butir daftar (mis. jawaban FAQ) sebagai HTML aman; tulis {{ ic_html_daftar('faq', $butir->isi) }}. */
    function ic_html_daftar(string $grup, ?string $teks): Illuminate\Support\HtmlString
    {
        return app(IcKonten::class)->htmlDaftar($grup, $teks);
    }
}

if (! function_exists('ic_tipe_event')) {
    /** Tipe acara (kunci => label, ikon, warna) dari CMS atau config. */
    function ic_tipe_event(): array
    {
        return app(IcPilihan::class)->tipeEvent();
    }
}

if (! function_exists('ic_kategori_kenangan')) {
    /** Kategori kenangan (kunci => label, ikon, sampul, warna). $semua = termasuk yang disembunyikan (untuk kenangan lama). */
    function ic_kategori_kenangan(bool $semua = false): array
    {
        return app(IcPilihan::class)->kategoriKenangan($semua);
    }
}

if (! function_exists('ic_tampilan')) {
    /** Tampilan dan merek Inner Circle (warna, logo kustom) dari CMS atau bawaan. */
    function ic_tampilan(): IcTampilan
    {
        return app(IcTampilan::class);
    }
}

if (! function_exists('ic_program')) {
    /** Nama program (bawaan "WS Inner Circle") dari CMS. Tampilkan dengan {{ }} supaya di-escape. */
    function ic_program(): string
    {
        return app(IcKonten::class)->namaProgram();
    }
}