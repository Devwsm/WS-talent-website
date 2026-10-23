<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Satu baris jejak perubahan status klaim. Hanya ditambah, tidak pernah diubah. */
class ic_claim_log extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'ic_claim_logs';
    protected $primaryKey = 'id_log';

    protected $fillable = [
        'claim_id',
        'status',
        'oleh',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function klaim(): BelongsTo
    {
        return $this->belongsTo(ic_claim::class, 'claim_id', 'id_claim');
    }
}