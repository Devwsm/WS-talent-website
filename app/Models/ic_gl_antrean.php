<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu pendaftaran antrean GL gratis: unik per acara per anggota.
 *
 * status: `antri` (menunggu undian) | `menang` | `tidak_terpilih`. Baris yang menang menjadi dasar kartu GL
 * (nama anggota, nama acara, username dan sandi acara). Baris tidak dihapus setelah undian supaya hasilnya bisa diaudit.
 */
class ic_gl_antrean extends Model
{
    public const ANTRI = 'antri';
    public const MENANG = 'menang';
    public const TIDAK_TERPILIH = 'tidak_terpilih';

    protected $table = 'ic_gl_antrean';
    protected $primaryKey = 'id_antrean';

    protected $fillable = [
        'event_id',
        'member_id',
        'status',
        'urutan_undi',
        'kartu_dikirim_pada',
        'diterima_show_pada',
    ];

    protected function casts(): array
    {
        return [
            'urutan_undi' => 'integer',
            'kartu_dikirim_pada' => 'datetime',
            'diterima_show_pada' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(ic_event::class, 'event_id', 'id_event');
    }

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(member::class, 'member_id', 'id_member');
    }

    public function menang(): bool
    {
        return $this->status === self::MENANG;
    }
}