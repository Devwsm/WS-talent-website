<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Data CMS Artis yang urutan tampilnya bisa diatur admin (album, banner, header, merchandise).
 * Kolom `urutan` dimulai dari 1; urutan yang sama dipakai halaman publik dan daftar di CMS.
 */
trait Berurutan
{
    /** Dari paling depan ke belakang. Kunci utama sebagai pemutus supaya urutannya selalu pasti. */
    public function scopeUrut(Builder $query): Builder
    {
        return $query->orderBy('urutan')->orderBy($this->getKeyName());
    }

    /** Nomor urut untuk data baru: paling belakang. */
    public static function urutanBerikut(): int
    {
        return ((int) static::query()->max('urutan')) + 1;
    }
}