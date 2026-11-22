<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    /** Komentar anggota pada kenangan ini, terlama di atas. */
    public function komentar(): HasMany
    {
        return $this->hasMany(ic_memory_comment::class, 'memory_id', 'id_memory')->orderBy('id_komentar');
    }

    public function scopeTayang(Builder $query): Builder
    {
        return $query->where('status', self::TAYANG);
    }

    protected function disembunyikan(): Attribute
    {
        return Attribute::get(fn() => $this->status === self::DISEMBUNYIKAN);
    }

    /** Nama yang tampil ke sesama anggota: nama lengkap dari profil (spasi ganda dirapikan), tidak disingkat. */
    protected function namaMember(): Attribute
    {
        return Attribute::get(function () {
            $nama = trim((string) preg_replace('/\s+/u', ' ', (string) ($this->member->nama ?? '')));

            return $nama !== '' ? $nama : 'Anggota';
        });
    }

    /** Nama lain untuk created_at (tanggal tampil di kartu). */
    protected function tanggal(): Attribute
    {
        return Attribute::get(fn() => $this->created_at);
    }

    /** Ikon sampul kartu tanpa foto, dari kategorinya (kategori kenangan, termasuk yang disembunyikan). */
    protected function ikon(): Attribute
    {
        return Attribute::get(fn() => ic_kategori_kenangan(true)[$this->kategori]['sampul'] ?? 'sparkles');
    }

    protected function warna(): Attribute
    {
        return Attribute::get(fn() => ic_kategori_kenangan(true)[$this->kategori]['warna'] ?? 'kuning');
    }

    protected function jumlahLike(): Attribute
    {
        return Attribute::get(fn($nilai, array $atribut) => array_key_exists('jumlah_like', $atribut)
            ? (int) $atribut['jumlah_like']
            : $this->penyuka()->count());
    }

    /** Diisi withCount('komentar as jumlah_komentar') di galeri; bila belum diisi dihitung langsung. */
    protected function jumlahKomentar(): Attribute
    {
        return Attribute::get(fn($nilai, array $atribut) => array_key_exists('jumlah_komentar', $atribut)
            ? (int) $atribut['jumlah_komentar']
            : $this->komentar()->count());
    }

    /**
     * Penyuka terbaru (sampai 3 anggota) untuk tumpukan foto ala IG. Diisi KenanganService::isiPenyuka() lewat
     * setRelation('penyuka_tampil'); bila belum diisi (mis. preview CMS) hasilnya kosong tanpa query.
     */
    protected function penyukaTampil(): Attribute
    {
        return Attribute::get(fn() => $this->relationLoaded('penyuka_tampil')
            ? $this->getRelation('penyuka_tampil')
            : collect());
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