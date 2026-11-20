<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pengajuan pemeriksaan kehadiran (Fase 5B): anggota yang GPS-nya gagal meminta tim WSM memeriksa kehadirannya.
 * Satu pengajuan per anggota per acara. Disetujui = poin check-in masuk lewat buku besar (`poin_id`); ditolak = ada alasan.
 */
class ic_pemeriksaan extends Model
{
    public const MENUNGGU = 'menunggu';
    public const DISETUJUI = 'disetujui';
    public const DITOLAK = 'ditolak';

    /** Alasan yang boleh diajukan: kode => teks untuk anggota dan admin. */
    public const ALASAN = [
        'izin_ditolak' => 'Izin lokasi ditolak',
        'tidak_tersedia' => 'GPS atau lokasi tidak tersedia di HP',
        'sinyal_lemah' => 'Sinyal GPS terlalu lemah, lokasi tidak terbaca',
        'akurasi_kasar' => 'Akurasi GPS terlalu kasar',
        'di_luar_radius' => 'Terbaca di luar radius venue',
    ];

    protected $table = 'ic_pemeriksaan';
    protected $primaryKey = 'id_pemeriksaan';

    protected $fillable = [
        'member_id',
        'event_id',
        'alasan',
        'jarak_m',
        'akurasi_m',
        'catatan_anggota',
        'status',
        'catatan_admin',
        'ditinjau_oleh',
        'ditinjau_pada',
        'poin_id',
        'sidik_perangkat',
    ];

    protected function casts(): array
    {
        return [
            'jarak_m' => 'integer',
            'akurasi_m' => 'integer',
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

    public function menunggu(): bool
    {
        return $this->status === self::MENUNGGU;
    }

    public function labelAlasan(): string
    {
        return self::ALASAN[$this->alasan] ?? 'Lokasi bermasalah';
    }
}