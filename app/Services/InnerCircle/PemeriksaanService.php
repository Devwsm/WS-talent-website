<?php

namespace App\Services\InnerCircle;

use App\Models\ic_event;
use App\Models\ic_pemeriksaan;
use App\Models\ic_poin;
use App\Models\member;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Pemeriksaan kehadiran (Fase 5B): jalan keluar bagi anggota yang berada di venue tetapi GPS-nya gagal.
 *
 * Anggota mengajukan SAAT check-in sedang dibuka; tim WSM menyetujui atau menolak dari CMS (Acara > Pemeriksaan).
 * Persetujuan memberi poin lewat PoinService, sumber `checkin`, dengan nilai yang sama seperti check-in biasa
 * (poin GL untuk pemenang GL, poin mandiri untuk yang lain). Kunci unik (anggota, acara) di buku besar membuat
 * kehadiran tidak mungkin tercatat dua kali, apa pun urutan check-in dan persetujuannya.
 */
class PemeriksaanService
{
    /** Umur catatan kegagalan lokasi di sesi (menit). */
    private const MENIT_SESI = 30;

    public function __construct(private CheckinService $checkin, private PoinService $poin) {}

    /**
     * Ajukan pemeriksaan. `$ukuran` (jarak, akurasi) hanya berasal dari pengukuran server, bukan kiriman form.
     *
     * @param  array{jarak?: int|null, akurasi?: int|null}|null  $ukuran
     *
     * @throws PemeriksaanGagal
     */
    public function ajukan(member $anggota, ic_event $event, string $alasan, ?string $catatan, ?array $ukuran = null, ?string $sidik = null): ic_pemeriksaan
    {
        if (! array_key_exists($alasan, ic_pemeriksaan::ALASAN)) {
            throw new PemeriksaanGagal('Pilih alasan pengajuan yang sesuai.');
        }

        if (! $event->bisa_checkin) {
            throw new PemeriksaanGagal('Acara ini tidak memakai check-in.');
        }

        if (! $event->lokasi_aktif) {
            throw new PemeriksaanGagal('Check-in acara ini belum bisa dibuka karena titik lokasi venue belum disiapkan tim WSM. Coba lagi nanti atau tanyakan ke panitia di venue.');
        }

        $sekarang = now();
        if ($sekarang->lessThan($event->jendela_buka) || $sekarang->greaterThan($event->jendela_tutup)) {
            throw new PemeriksaanGagal('Pengajuan hanya bisa dibuat selama check-in acara ini dibuka.');
        }

        $catatan = $catatan !== null ? trim($catatan) : null;
        $catatan = $catatan === '' ? null : mb_substr($catatan, 0, (int) config('inner_circle.pemeriksaan.catatan_maks', 300));

        try {
            return DB::transaction(function () use ($anggota, $event, $alasan, $catatan, $ukuran, $sidik) {
                $terkunci = member::query()->whereKey($anggota->getKey())->lockForUpdate()->firstOrFail();

                if (ic_poin::query()->where('member_id', $terkunci->getKey())->where('event_id', $event->getKey())->exists()) {
                    throw new PemeriksaanGagal('Kamu sudah check-in di acara ini, jadi tidak perlu pemeriksaan.');
                }

                if (ic_pemeriksaan::query()->where('member_id', $terkunci->getKey())->where('event_id', $event->getKey())->exists()) {
                    throw new PemeriksaanGagal('Kamu sudah mengajukan pemeriksaan untuk acara ini. Satu acara hanya bisa diajukan satu kali.');
                }

                return ic_pemeriksaan::query()->create([
                    'member_id' => $terkunci->getKey(),
                    'event_id' => $event->getKey(),
                    'alasan' => $alasan,
                    'jarak_m' => $ukuran['jarak'] ?? null,
                    'akurasi_m' => $ukuran['akurasi'] ?? null,
                    'catatan_anggota' => $catatan,
                    'status' => ic_pemeriksaan::MENUNGGU,
                    'sidik_perangkat' => $sidik,
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            throw new PemeriksaanGagal('Kamu sudah mengajukan pemeriksaan untuk acara ini. Satu acara hanya bisa diajukan satu kali.');
        }
    }

    /**
     * Setujui: poin check-in masuk ke buku besar. Gagal bila anggota ternyata sudah tercatat hadir di acara itu
     * (mis. check-in berhasil sesudah mengajukan); pengajuan seperti itu cukup ditolak dengan alasan.
     *
     * @throws PemeriksaanGagal
     */
    public function setujui(int $id, ?string $admin, ?string $catatan = null): ic_poin
    {
        $catatan = $this->bersihkan($catatan, 300);

        try {
            return DB::transaction(function () use ($id, $admin, $catatan) {
                $p = ic_pemeriksaan::query()->whereKey($id)->lockForUpdate()->first();
                if (! $p) {
                    throw new PemeriksaanGagal('Pengajuan tidak ditemukan. Mungkin akunnya sudah dihapus.');
                }
                if (! $p->menunggu()) {
                    throw new PemeriksaanGagal('Pengajuan ini sudah ditinjau.');
                }

                $anggota = member::query()->whereKey($p->member_id)->lockForUpdate()->firstOrFail();
                $event = ic_event::query()->findOrFail($p->event_id);

                if (ic_poin::query()->where('member_id', $anggota->getKey())->where('event_id', $event->getKey())->exists()) {
                    throw new PemeriksaanGagal('Anggota ini sudah tercatat hadir di acara itu, jadi poinnya tidak bisa diberikan lagi. Tolak saja pengajuannya dengan alasan.');
                }

                $tipe = app(IcPilihan::class)->tipeEvent()[$event->tipe] ?? null;

                $poin = $this->poin->tambah(
                    $anggota,
                    $this->checkin->poinUntuk($event, $this->checkin->pemenangGl($anggota, $event)),
                    'checkin',
                    'Check-in di ' . $event->venue,
                    $tipe['ikon'] ?? 'ticket',
                    $event,
                    catatan: 'Disetujui lewat pemeriksaan kehadiran',
                    dicatatOleh: $admin,
                    jarakM: $p->jarak_m,
                    akurasiM: $p->akurasi_m,
                    sidikPerangkat: $p->sidik_perangkat,
                );

                $p->update([
                    'status' => ic_pemeriksaan::DISETUJUI,
                    'catatan_admin' => $catatan,
                    'ditinjau_oleh' => $this->namaAdmin($admin),
                    'ditinjau_pada' => now(),
                    'poin_id' => $poin->getKey(),
                ]);

                return $poin;
            });
        } catch (UniqueConstraintViolationException) {
            throw new PemeriksaanGagal('Anggota ini sudah tercatat hadir di acara itu, jadi poinnya tidak bisa diberikan lagi.');
        }
    }

    /** @throws PemeriksaanGagal */
    public function tolak(int $id, ?string $admin, string $alasan): ic_pemeriksaan
    {
        $alasan = $this->bersihkan($alasan, 300);
        if ($alasan === null || mb_strlen($alasan) < 5) {
            throw new PemeriksaanGagal('Isi alasan penolakan (minimal 5 huruf) supaya anggota tahu sebabnya.');
        }

        return DB::transaction(function () use ($id, $admin, $alasan) {
            $p = ic_pemeriksaan::query()->whereKey($id)->lockForUpdate()->first();
            if (! $p) {
                throw new PemeriksaanGagal('Pengajuan tidak ditemukan. Mungkin akunnya sudah dihapus.');
            }
            if (! $p->menunggu()) {
                throw new PemeriksaanGagal('Pengajuan ini sudah ditinjau.');
            }

            $p->update([
                'status' => ic_pemeriksaan::DITOLAK,
                'catatan_admin' => $alasan,
                'ditinjau_oleh' => $this->namaAdmin($admin),
                'ditinjau_pada' => now(),
            ]);

            return $p;
        });
    }

    /**
     * Ingat kegagalan lokasi terakhir di sesi anggota (hasil ukur SERVER: alasan, jarak, akurasi), supaya pengajuan
     * sesudahnya memakai angka itu dan bukan isian form. Hanya berlaku `MENIT_SESI` menit dan per acara.
     */
    public function simpanGagalSesi(CheckinGagal $gagal): void
    {
        if ($gagal->alasan === null || $gagal->kodeAcara === null) {
            return;
        }

        session()->put('ic_gps_gagal.' . $gagal->kodeAcara, [
            'alasan' => $gagal->alasan,
            'jarak' => $gagal->jarak,
            'akurasi' => $gagal->akurasi,
            'waktu' => now()->timestamp,
        ]);
    }

    /** @return array{alasan: string, jarak: int|null, akurasi: int|null, waktu: int}|null */
    public function gagalSesi(string $kodeAcara): ?array
    {
        $catatan = session('ic_gps_gagal.' . $kodeAcara);
        if (! is_array($catatan) || ! isset($catatan['alasan'], $catatan['waktu'])) {
            return null;
        }

        if (now()->timestamp - (int) $catatan['waktu'] > self::MENIT_SESI * 60) {
            return null;
        }

        return $catatan;
    }

    public function bersihkanGagalSesi(string $kodeAcara): void
    {
        session()->forget('ic_gps_gagal.' . $kodeAcara);
    }

    /**
     * Apa yang ditawarkan ke anggota di lembar check-in untuk acara ini.
     * `tampil` = formulir ajukan dibuka dari server: check-in barusan gagal karena lokasi (alasan hasil ukur server),
     * belum ada pengajuan, belum tercatat hadir, dan check-in sedang dibuka. Bila GPS gagal terbaca di browser,
     * JavaScript yang memunculkan formulirnya (server tidak ikut dilewati).
     *
     * @return array{tampil: bool, alasan: string|null, pengajuan: ic_pemeriksaan|null}
     */
    public function tawaran(member $anggota, ?ic_event $event): array
    {
        if (! $event) {
            return ['tampil' => false, 'alasan' => null, 'pengajuan' => null];
        }

        $pengajuan = $this->milik($anggota, $event);
        $gagal = $this->gagalSesi($event->kode);
        $siap = $event->statusCheckin() === 'siap' && $event->lokasi_aktif;

        return [
            'tampil' => $pengajuan === null && $gagal !== null && $siap,
            'alasan' => $gagal['alasan'] ?? null,
            'pengajuan' => $pengajuan,
        ];
    }

    /** Pengajuan terakhir anggota untuk acara ini (apa pun statusnya), untuk panel status di halaman detail. */
    public function milik(member $anggota, ic_event $event): ?ic_pemeriksaan
    {
        return ic_pemeriksaan::query()
            ->where('member_id', $anggota->getKey())
            ->where('event_id', $event->getKey())
            ->first();
    }

    private function bersihkan(?string $teks, int $maks): ?string
    {
        $teks = $teks !== null ? trim($teks) : null;

        return $teks === null || $teks === '' ? null : mb_substr($teks, 0, $maks);
    }

    private function namaAdmin(?string $admin): ?string
    {
        return $admin !== null && $admin !== '' ? mb_substr($admin, 0, 60) : null;
    }
}