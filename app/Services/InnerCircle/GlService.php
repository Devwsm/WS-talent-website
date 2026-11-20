<?php

namespace App\Services\InnerCircle;

use App\Models\ic_event;
use App\Models\ic_gl_antrean;
use App\Models\member;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Sisi anggota untuk GL gratis: ikut antrean, keluar antrean, keadaan tombol, dan kartu GL pemenang.
 *
 * Satu pendaftaran per anggota per acara (kunci unik event_id + member_id). Antrean ditutup oleh waktu undian
 * (bawaan hari H pukul 18.00 WIB) atau oleh hasil undian; mengundi sendiri ada di UndianService.
 */
class GlService
{
    /** Nama acara untuk kartu dan email: venue, ditambah kota bila ada. */
    public static function namaAcara(ic_event $event): string
    {
        return trim((string) $event->venue . (filled($event->kota) ? ', ' . $event->kota : '')) ?: (string) $event->kode;
    }

    /**
     * Antrean sudah tertutup? Setelah undian selesai atau tertunda: tertutup. Ditunda admin: tetap terbuka (belum ada
     * undian yang akan jalan). Selain itu menurut waktu undian acara.
     */
    public function antreanTutup(ic_event $event, ?Carbon $sekarang = null): bool
    {
        $sekarang ??= now();

        return match ($event->status_undian) {
            ic_event::UNDIAN_SELESAI, ic_event::UNDIAN_TERTUNDA => true,
            ic_event::UNDIAN_DITUNDA => false,
            default => $sekarang->greaterThanOrEqualTo($event->undian_efektif),
        };
    }

    /** Alasan anggota ini belum bisa ikut antrean acara itu, atau null bila boleh. */
    public function alasanTidakBisaAntri(member $anggota, ic_event $event, ?Carbon $sekarang = null): ?string
    {
        if (! $event->punya_gl) {
            return 'Acara ini tidak punya antrean GL gratis.';
        }
        if ($anggota->diblokir()) {
            return 'Akunmu sedang dinonaktifkan, jadi belum bisa ikut antrean GL.';
        }
        if ($anggota->email_verified_at === null) {
            return 'Verifikasi emailmu dulu untuk ikut antrean GL.';
        }
        if ($this->antreanTutup($event, $sekarang)) {
            return 'Antrean GL untuk acara ini sudah ditutup.';
        }

        return null;
    }

    /** Ikut antrean. Mengulang tidak membuat pendaftaran kedua. */
    public function antri(member $anggota, ic_event $event, ?Carbon $sekarang = null): ic_gl_antrean
    {
        if ($alasan = $this->alasanTidakBisaAntri($anggota, $event, $sekarang)) {
            throw new GlGagal($alasan);
        }

        $kunci = ['event_id' => $event->getKey(), 'member_id' => $anggota->getKey()];

        try {
            return ic_gl_antrean::query()->firstOrCreate($kunci, ['status' => ic_gl_antrean::ANTRI]);
        } catch (UniqueConstraintViolationException) {
            // Dua ketukan hampir bersamaan: yang kedua cukup memakai baris yang sudah dibuat yang pertama.
            return ic_gl_antrean::query()->where($kunci)->firstOrFail();
        }
    }

    /** Keluar antrean selama antrean belum ditutup. Pemenang dan yang sudah diundi tidak bisa keluar. */
    public function keluar(member $anggota, ic_event $event, ?Carbon $sekarang = null): void
    {
        if ($this->antreanTutup($event, $sekarang)) {
            throw new GlGagal('Antrean GL sudah ditutup, jadi tidak bisa keluar lagi.');
        }

        ic_gl_antrean::query()
            ->where('event_id', $event->getKey())
            ->where('member_id', $anggota->getKey())
            ->where('status', ic_gl_antrean::ANTRI)
            ->delete();
    }

    /**
     * Keadaan GL anggota di acara itu, untuk tombol dan kartu di halaman detail acara:
     * tidak_ada | buka | antri | ditutup | tertunda | menang | tidak_terpilih.
     */
    public function statusAnggota(member $anggota, ic_event $event, ?Carbon $sekarang = null): string
    {
        if (! $event->punya_gl) {
            return 'tidak_ada';
        }

        $baris = ic_gl_antrean::query()
            ->where('event_id', $event->getKey())
            ->where('member_id', $anggota->getKey())
            ->first();
        $tutup = $this->antreanTutup($event, $sekarang);

        if ($baris === null) {
            return $tutup ? 'ditutup' : 'buka';
        }

        return match ($baris->status) {
            ic_gl_antrean::MENANG => 'menang',
            ic_gl_antrean::TIDAK_TERPILIH => 'tidak_terpilih',
            default => $tutup ? 'tertunda' : 'antri',
        };
    }

    /**
     * Isi kartu GL, hanya untuk pemenang setelah undian selesai (selain itu null). Sandi acara hanya keluar lewat
     * sini, jadi tidak pernah dikirim ke anggota yang belum berhak.
     *
     * @return array{anggota: string, kode_member: string, acara: string, tanggal: string, username: string, sandi: string, petunjuk: ?string}|null
     */
    public function kartu(member $anggota, ic_event $event): ?array
    {
        if ($event->status_undian !== ic_event::UNDIAN_SELESAI) {
            return null;
        }

        $menang = ic_gl_antrean::query()
            ->where('event_id', $event->getKey())
            ->where('member_id', $anggota->getKey())
            ->where('status', ic_gl_antrean::MENANG)
            ->exists();

        if (! $menang || ! filled($event->username_acara) || ! filled($event->sandi_acara)) {
            return null;
        }

        return [
            'anggota' => (string) $anggota->nama,
            'kode_member' => (string) $anggota->kode_member,
            'acara' => self::namaAcara($event),
            'tanggal' => $event->tanggal->locale('id')->translatedFormat('l, j F Y'),
            'username' => (string) $event->username_acara,
            'sandi' => (string) $event->sandi_acara,
            'petunjuk' => $event->petunjuk_masuk,
        ];
    }
}