<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu ronde mini game Mambo Jambo Pineapple Catcher (lihat GameService). Dibuat saat ronde dimulai (`berjalan`),
 * lalu diselesaikan sekali: `selesai` (skor dihitung server), `ditolak` (alasan di kolom `alasan`), atau `ditinggalkan`
 * (anggota memulai ronde baru sebelum yang ini dikirim). Tidak ada updated_at: waktunya `mulai_pada` dan `selesai_pada`.
 */
class ic_game extends Model
{
    public const BERJALAN = 'berjalan';
    public const SELESAI = 'selesai';
    public const DITOLAK = 'ditolak';
    public const DITINGGALKAN = 'ditinggalkan';

    public $timestamps = false;

    protected $table = 'ic_game';
    protected $primaryKey = 'id_game';

    protected $fillable = [
        'member_id',
        'kode',
        'seed',
        'tanggal',
        'status',
        'alasan',
        'skor',
        'skor_klaim',
        'level',
        'langkah',
        'nanas',
        'bir',
        'lewat',
        'poin',
        'poin_id',
        'mulai_pada',
        'selesai_pada',
    ];

    protected function casts(): array
    {
        return [
            'seed' => 'integer',
            'skor' => 'integer',
            'skor_klaim' => 'integer',
            'poin' => 'integer',
            'tanggal' => 'date:Y-m-d',
            'mulai_pada' => 'datetime',
            'selesai_pada' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(member::class, 'member_id', 'id_member');
    }
}