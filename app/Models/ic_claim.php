<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Penukaran satu hadiah oleh satu anggota. Status: diajukan, ditinjau, disetujui, selesai, ditolak, dibatalkan
 * (daftar baku di config/inner_circle.php). Perubahan status lewat ClaimService, bukan langsung.
 *
 * `poin_ditahan` = poin yang ditahan dari saldo anggota saat mengajukan (harga hadiah saat itu). Poin tetap ditahan selama
 * statusnya menunggu, habis dipakai saat `selesai`, dan dikembalikan saat `ditolak` atau `dibatalkan`. Klaim lama sebelum
 * Fase 8 bernilai 0 (tidak pernah memotong saldo).
 *
 * Properti turunan di bawah dipakai view anggota dan dashboard (nama sama dengan data contoh Fase 2-3).
 * Muat relasi `reward` dan `member` lebih dulu (with()) saat menampilkan banyak baris.
 */
class ic_claim extends Model
{
    protected $table = 'ic_claims';
    protected $primaryKey = 'id_claim';

    protected $fillable = [
        'member_id',
        'reward_id',
        'opsi_dipilih',
        'poin_ditahan',
        'status',
        'catatan_admin',
        'diproses_oleh',
    ];

    protected function casts(): array
    {
        return [
            'poin_ditahan' => 'integer',
        ];
    }

    public function reward(): BelongsTo
    {
        return $this->belongsTo(ic_reward::class, 'reward_id', 'id_reward');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(member::class, 'member_id', 'id_member');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ic_claim_log::class, 'claim_id', 'id_claim')->orderBy('id_log');
    }

    /** Klaim yang masih menunggu tindakan admin (poinnya masih ditahan bila `poin_ditahan` > 0). */
    public static function statusMenunggu(): array
    {
        return ['diajukan', 'ditinjau', 'disetujui'];
    }

    /** Status akhir yang mengembalikan poin yang ditahan (dan stok hadiah). */
    public static function statusBatal(): array
    {
        return ['ditolak', 'dibatalkan'];
    }

    /** Penukaran ini memakai alur poin baru (poin ditahan saat mengajukan), bukan klaim lama tanpa pemotongan. */
    public function memakaiPoin(): bool
    {
        return $this->poin_ditahan > 0;
    }

    protected function diajukanPada(): Attribute
    {
        return Attribute::get(fn() => $this->created_at);
    }

    /** Nama lain untuk opsi_dipilih (Profil anggota). */
    protected function opsi(): Attribute
    {
        return Attribute::get(fn() => $this->opsi_dipilih);
    }

    protected function judul(): Attribute
    {
        return Attribute::get(fn() => $this->reward->judul);
    }

    protected function hargaPoin(): Attribute
    {
        return Attribute::get(fn() => $this->reward->harga_poin);
    }

    protected function ikon(): Attribute
    {
        return Attribute::get(fn() => $this->reward->ikon);
    }

    protected function warna(): Attribute
    {
        return Attribute::get(fn() => $this->reward->warna);
    }

    protected function namaMember(): Attribute
    {
        return Attribute::get(fn() => $this->member->nama);
    }

    protected function kodeMember(): Attribute
    {
        return Attribute::get(fn() => $this->member->kode_member);
    }
}