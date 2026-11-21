<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Anggota yang menunggu hadiah yang stoknya habis. Satu baris per anggota per hadiah; tidak ada updated_at. */
class ic_reward_tunggu extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'ic_reward_tunggu';
    protected $primaryKey = 'id_tunggu';

    protected $fillable = [
        'member_id',
        'reward_id',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(member::class, 'member_id', 'id_member');
    }

    public function reward(): BelongsTo
    {
        return $this->belongsTo(ic_reward::class, 'reward_id', 'id_reward');
    }
}