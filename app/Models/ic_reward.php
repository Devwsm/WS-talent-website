<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Satu tahapan hadiah WS Inner Circle. `status_member` dan `catatan_admin` bukan kolom: ClaimService
 * mengisinya per anggota lewat setAttribute() (terkunci | terbuka | status klaim).
 */
class ic_reward extends Model
{
    protected $table = 'ic_rewards';
    protected $primaryKey = 'id_reward';

    protected $fillable = [
        'target_stempel',
        'judul',
        'deskripsi',
        'ikon',
        'warna',
        'opsi',
        'urutan',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'target_stempel' => 'integer',
            'opsi' => 'array',
            'aktif' => 'boolean',
        ];
    }

    public function klaim(): HasMany
    {
        return $this->hasMany(ic_claim::class, 'reward_id', 'id_reward');
    }
}