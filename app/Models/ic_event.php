<?php

namespace App\Models;

use App\Casts\TokenAcara;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Jadwal acara WS Inner Circle + token QR check-in.
 *
 * Properti turunan (`status_waktu`, `bisa_checkin`, `jendela_buka`, `jendela_tutup`) dihitung dari
 * `now()` saat dibaca, jadi tidak butuh cron. `sudah_checkin` bukan kolom: controller anggota
 * mengisinya per anggota lewat setAttribute().
 */
class ic_event extends Model
{
    public const HURUF_TOKEN = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    protected $table = 'ic_events';
    protected $primaryKey = 'id_event';

    protected $fillable = [
        'kode',
        'tanggal',
        'kota',
        'venue',
        'tipe',
        'sumber',
        'bit_id',
        'bit_url',
        'disinkron_pada',
        'token',
        'token_dibuat_pada',
        'jendela_mulai',
        'jendela_selesai',
        'lokasi_lat',
        'lokasi_lng',
        'lokasi_radius',
        'lokasi_sumber',
        'lokasi_diatur_oleh',
        'lokasi_diatur_pada',
    ];

    protected $hidden = ['token'];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'token' => TokenAcara::class,
            'disinkron_pada' => 'datetime',
            'token_dibuat_pada' => 'datetime',
            'jendela_mulai' => 'datetime',
            'jendela_selesai' => 'datetime',
            'lokasi_lat' => 'float',
            'lokasi_lng' => 'float',
            'lokasi_radius' => 'integer',
            'lokasi_diatur_pada' => 'datetime',
        ];
    }

    /** Acara ini punya titik lokasi, jadi check-in hanya sah dari dalam radiusnya. */
    protected function lokasiAktif(): Attribute
    {
        return Attribute::get(fn() => $this->lokasi_lat !== null && $this->lokasi_lng !== null);
    }

    /** Radius yang dipakai (meter): isian petugas, atau bawaan dari config; selalu di dalam batas config. */
    protected function radiusEfektif(): Attribute
    {
        return Attribute::get(function () {
            $cfg = config('inner_circle.lokasi');
            $radius = $this->lokasi_radius ?: (int) $cfg['radius_bawaan_m'];

            return max((int) $cfg['radius_min_m'], min((int) $cfg['radius_maks_m'], $radius));
        });
    }

    /** Token acak 12 karakter (tanpa huruf/angka yang mudah tertukar), mis. WS-7K2MQ9XH4TBD. */
    public static function buatToken(): string
    {
        $huruf = self::HURUF_TOKEN;
        $token = '';
        for ($i = 0; $i < 12; $i++) {
            $token .= $huruf[random_int(0, strlen($huruf) - 1)];
        }

        return 'WS-' . $token;
    }

    /**
     * Token tersimpan tetapi tidak bisa dibuka: hampir selalu karena APP_KEY di server berbeda dari saat token dibuat.
     * QR acara ini tidak bisa dibuat sampai tokennya diganti (dashboard: Acara & QR, tombol Perbaiki token).
     */
    public function tokenRusak(): bool
    {
        return filled($this->attributes['token'] ?? null) && $this->token === null;
    }

    /** Event privat dan event tanpa venue tampil di jadwal tetapi tidak bisa dipakai check-in. */
    protected function bisaCheckin(): Attribute
    {
        return Attribute::get(fn() => $this->tipe !== 'private' && filled($this->venue));
    }

    /** lewat | hari_ini | akan_datang, menurut tanggal show (zona Asia/Jakarta). */
    protected function statusWaktu(): Attribute
    {
        return Attribute::get(function () {
            if ($this->tanggal->isToday()) {
                return 'hari_ini';
            }

            return $this->tanggal->isPast() ? 'lewat' : 'akan_datang';
        });
    }

    /** Bawaan: tanggal show pukul 12.00 WIB. Bisa diganti per acara lewat kolom jendela_mulai. */
    protected function jendelaBuka(): Attribute
    {
        return Attribute::get(fn() => $this->jendela_mulai ?? $this->tanggal->copy()->setTime(12, 0));
    }

    /** Bawaan: H+1 pukul 06.00 WIB (show klub selesai lewat tengah malam). */
    protected function jendelaTutup(): Attribute
    {
        return Attribute::get(fn() => $this->jendela_selesai ?? $this->tanggal->copy()->addDay()->setTime(6, 0));
    }

    /**
     * Keadaan check-in untuk lembar check-in anggota: siap | belum | tutup | sudah.
     * `sudah` dibaca dari properti `sudah_checkin` yang diisi controller per anggota (bukan kolom).
     */
    public function statusCheckin(?Carbon $sekarang = null): string
    {
        $sekarang ??= now();

        if ($this->getAttribute('sudah_checkin')) {
            return 'sudah';
        }
        if ($sekarang->lessThan($this->jendela_buka)) {
            return 'belum';
        }

        return $sekarang->greaterThan($this->jendela_tutup) ? 'tutup' : 'siap';
    }

    /** Kalimat siap tampil untuk tiap keadaan check-in. */
    public function pesanCheckin(string $status): string
    {
        $format = fn(Carbon $w) => $w->locale('id')->translatedFormat('D, j M Y \p\u\k\u\l H.i') . ' WIB';

        return match ($status) {
            'sudah' => 'Kamu sudah check-in di acara ini. Satu acara hanya memberi satu stempel.',
            'belum' => 'Check-in belum dibuka. Dibuka ' . $format($this->jendela_buka) . '.',
            'tutup' => 'Check-in untuk acara ini sudah ditutup.',
            default => 'Check-in dibuka sampai ' . $format($this->jendela_tutup) . '.',
        };
    }

    /**
     * Atribut data-* yang dibaca JavaScript untuk mengisi lembar check-in tanpa memuat ulang halaman.
     * Titik lokasi acara sengaja TIDAK ikut: anggota hanya tahu apakah lokasinya diperiksa, bukan di mana titiknya.
     *
     * @return array<string, string>
     */
    public function atributCheckin(): array
    {
        $tipe = config('inner_circle.tipe_event.' . $this->tipe);

        return [
            'data-kode' => (string) $this->kode,
            'data-venue' => (string) $this->venue,
            'data-kota' => (string) $this->kota,
            'data-tanggal' => $this->tanggal->locale('id')->translatedFormat('D, j M Y'),
            'data-tipe' => (string) ($tipe['label'] ?? ''),
            'data-status' => $this->statusCheckin(),
            'data-pesan' => $this->pesanCheckin($this->statusCheckin()),
            'data-lokasi' => $this->lokasi_aktif ? '1' : '0',
        ];
    }

    /** Apakah jendela check-in sedang terbuka pada waktu $sekarang (bawaan: saat ini). */
    public function dalamJendela(?Carbon $sekarang = null): bool
    {
        $sekarang ??= now();

        return $sekarang->greaterThanOrEqualTo($this->jendela_buka)
            && $sekarang->lessThanOrEqualTo($this->jendela_tutup);
    }

    public function stempel(): HasMany
    {
        return $this->hasMany(ic_stempel::class, 'event_id', 'id_event');
    }
}