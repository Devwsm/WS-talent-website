<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pengaduan satu anggota. Status: baru, diproses, selesai (daftar baku di config/inner_circle.php `pengaduan`).
 * Perubahan status lewat PengaduanService.
 */
class ic_pengaduan extends Model
{
    public const BARU = 'baru';
    public const DIPROSES = 'diproses';
    public const SELESAI = 'selesai';

    protected $table = 'ic_pengaduan';
    protected $primaryKey = 'id_pengaduan';

    protected $fillable = [
        'member_id',
        'kategori',
        'event_id',
        'isi',
        'status',
        'catatan_admin',
        'diproses_oleh',
    ];

    /** Pengaduan yang belum selesai (dasar angka di Ringkasan dan filter anggota). */
    public static function statusTerbuka(): array
    {
        return [self::BARU, self::DIPROSES];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(member::class, 'member_id', 'id_member');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(ic_event::class, 'event_id', 'id_event');
    }

    public function labelKategori(): string
    {
        return (string) config('inner_circle.pengaduan.kategori.' . $this->kategori, $this->kategori);
    }

    public function labelStatus(): string
    {
        return (string) config('inner_circle.pengaduan.status.' . $this->status . '.label', $this->status);
    }
}