<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Collection;

/**
 * Data CMS Artis yang bisa dijadwalkan (berita dan album). `terbit_pada` kosong = langsung tampil.
 *
 * Tampil-tidaknya dinilai saat halaman dibuka (bukan oleh cron), sama seperti pengumuman Inner Circle. Karena
 * halaman publik disimpan sementara (CachePublik), data yang masih terjadwal ikut disimpan dan disaring ulang
 * di setiap kunjungan lewat `hanyaTayang()`, jadi data muncul tepat waktu tanpa menunggu cache habis.
 */
trait Terjadwal
{
    public function initializeTerjadwal(): void
    {
        $this->mergeCasts(['terbit_pada' => 'datetime']);
    }

    /** Yang sudah boleh dilihat pengunjung: tanpa jadwal, atau jadwalnya sudah tiba. */
    public function scopeTayang(Builder $query): Builder
    {
        return $query->where(fn(Builder $q) => $q->whereNull('terbit_pada')->orWhere('terbit_pada', '<=', now()));
    }

    /** Masih menunggu jadwal. */
    public function scopeBelumTayang(Builder $query): Builder
    {
        return $query->where('terbit_pada', '>', now());
    }

    /** Terbaru dulu menurut saat mulai tampilnya (jadwal bila ada, kalau tidak saat dibuat). */
    public function scopeTerbaruTayang(Builder $query): Builder
    {
        return $query->orderByRaw('COALESCE(terbit_pada, created_at) DESC')->orderByDesc($this->getKeyName());
    }

    protected function terjadwal(): Attribute
    {
        return Attribute::get(fn() => $this->terbit_pada !== null && $this->terbit_pada->isFuture());
    }

    /**
     * Saring daftar (mis. hasil cache) menjadi yang sudah tayang saat ini.
     *
     * @param  Collection<int, static>  $daftar
     * @return Collection<int, static>
     */
    public static function hanyaTayang(Collection $daftar): Collection
    {
        return $daftar->reject(fn($m) => $m->terjadwal)->values();
    }
}