<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

/**
 * Satu pengumuman resmi WSM untuk anggota. Bentuk propertinya sama dengan data contoh Fase 2-3,
 * jadi komponen `pengumuman-unggulan` dan `item-pengumuman` dipakai apa adanya (anggota dan preview CMS).
 */
class ic_broadcast extends Model
{
    protected $table = 'ic_broadcasts';
    protected $primaryKey = 'id_broadcast';

    protected $fillable = [
        'judul',
        'isi',
        'tag',
        'ikon',
        'warna',
        'cta_label',
        'cta_tujuan',
        'unggulan',
        'terbit_pada',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'unggulan' => 'boolean',
            'aktif' => 'boolean',
            'terbit_pada' => 'date',
        ];
    }

    /**
     * Yang sudah boleh dilihat anggota: aktif dan tanggal terbitnya sudah tiba (zona Asia/Jakarta).
     * whereDate, bukan perbandingan teks, supaya benar untuk MySQL maupun SQLite (tes).
     */
    public function scopeTayang(Builder $query): Builder
    {
        return $query->where('aktif', true)->whereDate('terbit_pada', '<=', today()->toDateString());
    }

    /** Terjadwal: tanggal terbitnya belum tiba, jadi belum tampil ke anggota. */
    protected function terjadwal(): Attribute
    {
        return Attribute::get(fn() => $this->terbit_pada !== null && $this->terbit_pada->gt(today()));
    }

    /** Tujuan tombol; route yang sudah tidak ada (config berubah) jatuh ke halaman Komunitas, bukan error 500. */
    protected function ctaUrl(): Attribute
    {
        return Attribute::get(fn() => Route::has($this->cta_tujuan)
            ? route($this->cta_tujuan)
            : route('ic.komunitas'));
    }
}