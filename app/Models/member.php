<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Anggota WS Inner Circle. Dipakai guard `member` (config/auth.php).
 * Akun admin CMS tetap di model `account` dan tidak berhubungan dengan model ini.
 */
class member extends Authenticatable
{
    protected $table = 'members';
    protected $primaryKey = 'id_member';

    protected $fillable = [
        'nama',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** Sama dengan default kolom: anggota yang baru dibuat langsung punya 0 stempel di memori, bukan null. */
    protected $attributes = [
        'stempel_total' => 0,
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'stempel_total' => 'integer',
            // Alias hasil withMax() di tab Anggota dashboard.
            'terakhir_checkin' => 'datetime',
        ];
    }

    /** Avatar bawaan selama anggota belum mengunggah foto (kolom `avatar`, Fase 6): emoji seperti di prototype. */
    protected function avatarEmoji(): Attribute
    {
        return Attribute::get(fn() => '😎');
    }

    /** Nama lain untuk created_at supaya view memakai istilah yang sama dengan data contoh. */
    protected function bergabungPada(): Attribute
    {
        return Attribute::get(fn() => $this->created_at);
    }

    public function stempel(): HasMany
    {
        return $this->hasMany(ic_stempel::class, 'member_id', 'id_member');
    }

    public function klaim(): HasMany
    {
        return $this->hasMany(ic_claim::class, 'member_id', 'id_member');
    }

    public function kenangan(): HasMany
    {
        return $this->hasMany(ic_memory::class, 'member_id', 'id_member');
    }
}
