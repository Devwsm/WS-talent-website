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