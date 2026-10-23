<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Satu kenangan di galeri. Properti turunan di bawah dipakai komponen `kartu-kenangan` (anggota dan
 * preview CMS) dengan nama yang sama seperti data contoh Fase 2-3.
 *
 * Muat relasi `member` lebih dulu (with()) saat menampilkan banyak baris. `jumlah_like` dan `disukai_saya`
 * diisi withCount()/withExists() di KenanganService; `milik_saya` diisi controller. Bila belum diisi,
 * accessor memberi nilai aman (0, false, false) supaya kartu tetap bisa dirender.
 */
class ic_memory extends Model
{
    public const TAYANG = 'tayang';
    public const DISEMBUNYIKAN = 'disembunyikan';

    protected $table = 'ic_memories';
    protected $primaryKey = 'id_memory';

    protected $fillable = [
        'member_id',
        'caption',
        'tempat',
        'kategori',
        'foto',
        'status',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(member::class, 'member_id', 'id_member');
    }

    /** Anggota yang menyukai kenangan ini (tabel ic_memory_likes). */
    public function penyuka(): BelongsToMany
    {
        return $this->belongsToMany(member::class, 'ic_memory_likes', 'memory_id', 'member_id', 'id_memory', 'id_member')
            ->withPivot('created_at');
    }

    public function scopeTayang(Builder $query): Builder
    {
        return $query->where('status', self::TAYANG);
    }

    protected function disembunyikan(): Attribute
    {
        return Attribute::get(fn() => $this->status === self::DISEMBUNYIKAN);
    }

    /**
     * Nama yang tampil ke sesama anggota: kata pertama + inisial kata kedua ("Ancha MK" jadi "Ancha M.").
     * Nama lengkap hanya dilihat admin lewat $kenangan->member->nama.
     */
    protected function namaMember(): Attribute
    {
        return Attribute::get(function () {
            $kata = preg_split('/\s+/u', trim((string) ($this->member->nama ?? '')), -1, PREG_SPLIT_NO_EMPTY);
            if (count($kata) === 0) {
                return 'Anggota';
            }
            if (count($kata) === 1) {
                return $kata[0];
            }

            return $kata[0] . ' ' . mb_strtoupper(mb_substr($kata[1], 0, 1)) . '.';
        });
    }

    /** Nama lain untuk created_at (tanggal tampil di kartu). */
    protected function tanggal(): Attribute
    {
        return Attribute::get(fn() => $this->created_at);
    }

    /** Ikon sampul kartu tanpa foto, dari kategorinya (config kategori_kenangan). */
    protected function ikon(): Attribute
    {
        return Attribute::get(fn() => config('inner_circle.kategori_kenangan.' . $this->kategori . '.sampul', 'sparkles'));
    }

    protected function warna(): Attribute
    {
        return Attribute::get(fn() => config('inner_circle.kategori_kenangan.' . $this->kategori . '.warna', 'kuning'));
    }

    protected function jumlahLike(): Attribute
    {
        return Attribute::get(fn($nilai, array $atribut) => array_key_exists('jumlah_like', $atribut)
            ? (int) $atribut['jumlah_like']
            : $this->penyuka()->count());
    }

    protected function disukaiSaya(): Attribute
    {
        return Attribute::get(fn($nilai, array $atribut) => (bool) ($atribut['disukai_saya'] ?? false));
    }

    protected function milikSaya(): Attribute
    {
        return Attribute::get(fn($nilai, array $atribut) => (bool) ($atribut['milik_saya'] ?? false));
    }
}