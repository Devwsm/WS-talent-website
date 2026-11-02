<?php

use App\Services\InnerCircle\IcKonten;
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