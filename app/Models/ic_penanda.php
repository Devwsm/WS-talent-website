<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Penanda mencurigakan pada satu check-in (Fase 5B). Check-in tidak ditahan; penanda hanya masuk antrean tinjauan admin.
 * Unik per anggota, acara, dan jenis, jadi pemicu yang sama tidak membuat baris ganda.
 */
class ic_penanda extends Model
{
    public const BARU = 'baru';
    public const AMAN = 'aman';
    public const DITINDAK = 'ditindak';

    public const PERANGKAT_SAMA = 'perangkat_sama';
    public const LOKASI_JANGGAL = 'lokasi_janggal';
    public const PERJALANAN_MUSTAHIL = 'perjalanan_mustahil';
    public const BANYAK_GAGAL = 'banyak_gagal';

    /** Jenis penanda: kode => label singkat untuk admin. */
    public const JENIS = [
        self::PERANGKAT_SAMA => 'Perangkat sama dipakai beberapa akun',
        self::LOKASI_JANGGAL => 'Lokasi terlalu sempurna',
        self::PERJALANAN_MUSTAHIL => 'Perjalanan tidak masuk akal',
        self::BANYAK_GAGAL => 'Banyak percobaan lokasi gagal',
    ];

    protected $table = 'ic_penanda';
    protected $primaryKey = 'id_penanda';

    protected $fillable = [
        'member_id',
        'event_id',
        'poin_id',
        'jenis',
        'detail',
        'status',
        'catatan_admin',
        'ditinjau_oleh',
        'ditinjau_pada',
    ];

    protected function casts(): array
    {
        return [
            'ditinjau_pada' => 'datetime',
        ];
    }

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(member::class, 'member_id', 'id_member');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(ic_event::class, 'event_id', 'id_event');
    }

    public function labelJenis(): string
    {
        return self::JENIS[$this->jenis] ?? $this->jenis;
    }
}