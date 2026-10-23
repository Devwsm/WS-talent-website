<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pengajuan satu hadiah oleh satu anggota. Status: diajukan, ditinjau, disetujui, selesai, ditolak
 * (daftar baku di config/inner_circle.php). Perubahan status lewat ClaimService, bukan langsung.
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
        'status',
        'catatan_admin',
        'diproses_oleh',
    ];

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

    /** Klaim yang masih menunggu tindakan admin. */
    public static function statusMenunggu(): array
    {
        return ['diajukan', 'ditinjau', 'disetujui'];
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

    protected function targetStempel(): Attribute
    {
        return Attribute::get(fn() => $this->reward->target_stempel);
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