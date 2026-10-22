<?php

namespace App\Models;

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
        'token',
        'token_dibuat_pada',
        'jendela_mulai',
        'jendela_selesai',
    ];

    protected $hidden = ['token'];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'token' => 'encrypted',
            'token_dibuat_pada' => 'datetime',
            'jendela_mulai' => 'datetime',
            'jendela_selesai' => 'datetime',
        ];
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