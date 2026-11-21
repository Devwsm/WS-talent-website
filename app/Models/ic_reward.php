<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Satu hadiah di katalog WS Inner Circle yang ditukar dengan poin (Meet & Greet, Merchandise, dst).
 *
 * `stok` kosong = tak terbatas; angkanya berkurang saat anggota mengajukan penukaran dan kembali bila penukaran ditolak
 * atau dibatalkan (ClaimService). `harga_poin` boleh diubah admin: penukaran yang sudah masuk menyimpan harganya sendiri
 * (`ic_claims.poin_ditahan`). Hadiah tidak dihapus, hanya dinonaktifkan, supaya riwayat penukaran tetap utuh.
 *
 * `status_member`, `sisa_poin`, `persen`, `stok_habis`, dan `di_tunggu` bukan kolom: ClaimService mengisinya per anggota
 * lewat setAttribute() untuk halaman Hadiah.
 */
class ic_reward extends Model
{
    protected $table = 'ic_rewards';
    protected $primaryKey = 'id_reward';

    protected $fillable = [
        'harga_poin',
        'judul',
        'deskripsi',
        'ikon',
        'warna',
        'opsi',
        'stok',
        'urutan',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'harga_poin' => 'integer',
            'opsi' => 'array',
            'stok' => 'integer',
            'aktif' => 'boolean',
        ];
    }

    /** Stok terbatas dan sudah habis? (stok kosong = tak terbatas, jadi tidak pernah habis). */
    public function stokHabis(): bool
    {
        return $this->stok !== null && $this->stok < 1;
    }

    public function klaim(): HasMany
    {
        return $this->hasMany(ic_claim::class, 'reward_id', 'id_reward');
    }

    public function tunggu(): HasMany
    {
        return $this->hasMany(ic_reward_tunggu::class, 'reward_id', 'id_reward');
    }
}