<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris buku besar stempel. Hanya ditambah, tidak pernah diubah (tidak ada updated_at).
 * Check-in = sumber `checkin` + event_id terisi; stempel dari admin = sumber `manual`.
 */
class ic_stempel extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'ic_stempel';
    protected $primaryKey = 'id_stempel';

    protected $fillable = [
        'member_id',
        'jumlah',
        'sumber',
        'event_id',
        'judul',
        'ikon',
        'catatan',
        'dicatat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /** Nama yang dipakai komponen <x-inner-circle.baris-aktivitas> (sama dengan data contoh). */
    protected function tanggal(): Attribute
    {
        return Attribute::get(fn() => $this->created_at);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(member::class, 'member_id', 'id_member');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(ic_event::class, 'event_id', 'id_event');
    }
}