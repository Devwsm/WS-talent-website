<?php

namespace App\Services\InnerCircle;

use App\Models\ic_event;
use App\Models\ic_penanda;
use App\Models\ic_poin;
use App\Models\member;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

/**
 * Penanda mencurigakan pada check-in (Fase 5B).
 *
 * Dipanggil CheckinService SESUDAH poin tercatat. Check-in tidak pernah ditahan atau dibatalkan oleh penanda: HP yang
 * dipakai bergantian satu keluarga atau GPS yang kebetulan bagus tidak boleh merugikan anggota yang jujur. Penanda
 * hanya masuk antrean tinjauan di CMS (Acara > Pemeriksaan); admin yang memutuskan (aman, atau tindak lanjut lewat
 * koreksi poin / nonaktifkan akun di detail anggota).
 *
 * Empat isyarat: perangkat sama dipakai beberapa akun di satu acara, lokasi terlalu sempurna, perjalanan antar acara
 * yang tidak masuk akal, dan banyak percobaan lokasi gagal sebelum berhasil. Angkanya di config inner_circle.penanda.
 * Koordinat anggota tidak pernah disimpan; yang dipakai hanya jarak dan akurasi hasil ukur server.
 */
class PenandaService
{
    /**
     * Periksa satu check-in yang baru tercatat. Galat apa pun hanya dilaporkan, tidak pernah dilempar:
     * penanda tidak boleh membuat check-in yang sah gagal.
     *
     * @return int jumlah penanda yang dibuat
     */
    public function periksa(member $anggota, ic_event $event, ic_poin $poin): int
    {
        try {
            $dibuat = 0;
            $dibuat += $this->perangkatSama($anggota, $event, $poin);
            $dibuat += $this->lokasiJanggal($anggota, $event, $poin);
            $dibuat += $this->perjalananMustahil($anggota, $event, $poin);
            $dibuat += $this->banyakGagal($anggota, $event, $poin);

            return $dibuat;
        } catch (Throwable $e) {
            report($e);

            return 0;
        }
    }

    /** Hitung satu percobaan lokasi yang gagal (di cache, bukan database). */
    public function catatGagal(member $anggota, ic_event $event): void
    {
        try {
            $kunci = $this->kunciGagal($anggota, $event);
            Cache::add($kunci, 0, now()->addHours((int) config('inner_circle.penanda.gagal_simpan_jam', 12)));
            Cache::increment($kunci);
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function perangkatSama(member $anggota, ic_event $event, ic_poin $poin): int
    {
        if (! filled($poin->sidik_perangkat)) {
            return 0;
        }

        $lain = ic_poin::query()
            ->with('member')
            ->where('event_id', $event->getKey())
            ->where('sidik_perangkat', $poin->sidik_perangkat)
            ->where('member_id', '!=', $anggota->getKey())
            ->orderBy('id_poin')
            ->limit(10)
            ->get();

        if ($lain->isEmpty()) {
            return 0;
        }

        $nama = $lain->map(fn(ic_poin $p) => ($p->member?->nama ?? 'Anggota') . ' (' . ($p->member?->kode_member ?? '?') . ')')
            ->prepend($anggota->nama . ' (' . $anggota->kode_member . ')');
        $detail = 'Perangkat yang sama dipakai ' . $nama->count() . ' akun di acara ini: ' . $nama->implode(', ') . '.';

        // Semua akun yang berbagi perangkat itu ditandai, supaya admin melihat kelompoknya dari sisi mana pun.
        $dibuat = $this->tandai($anggota, $event, $poin, ic_penanda::PERANGKAT_SAMA, $detail) ? 1 : 0;
        foreach ($lain as $p) {
            $dibuat += $this->tandai($p->member, $event, $p, ic_penanda::PERANGKAT_SAMA, $detail) ? 1 : 0;
        }

        return $dibuat;
    }

    private function lokasiJanggal(member $anggota, ic_event $event, ic_poin $poin): int
    {
        if ($poin->akurasi_m === null) {
            return 0;
        }

        $cfg = config('inner_circle.penanda');
        $akurasi = (int) $poin->akurasi_m;
        $jarak = $poin->jarak_m;

        $terlaluBagus = $akurasi < (int) $cfg['akurasi_janggal_m'];
        $tepatDiTitik = $jarak !== null && $jarak <= (int) $cfg['titik_pas_jarak_m'] && $akurasi < (int) $cfg['titik_pas_akurasi_m'];

        if (! $terlaluBagus && ! $tepatDiTitik) {
            return 0;
        }

        return $this->tandai(
            $anggota,
            $event,
            $poin,
            ic_penanda::LOKASI_JANGGAL,
            'Akurasi GPS ' . $akurasi . ' m' . ($jarak !== null ? ' dan jarak ' . $jarak . ' m dari titik acara' : '') .
                '. Nilai sesempurna ini jarang dihasilkan GPS HP biasa; bisa pertanda aplikasi lokasi palsu.'
        ) ? 1 : 0;
    }

    private function perjalananMustahil(member $anggota, ic_event $event, ic_poin $poin): int
    {
        if (! $event->lokasi_aktif) {
            return 0;
        }

        $cfg = config('inner_circle.penanda');

        $sebelumnya = ic_poin::query()
            ->with('event')
            ->where('member_id', $anggota->getKey())
            ->whereNotNull('event_id')
            ->where('event_id', '!=', $event->getKey())
            ->where('created_at', '>=', $poin->created_at->copy()->subHours((int) $cfg['perjalanan_jam']))
            ->orderByDesc('created_at')
            ->get();

        foreach ($sebelumnya as $lama) {
            $acaraLama = $lama->event;
            if (! $acaraLama || ! $acaraLama->lokasi_aktif) {
                continue;
            }

            $km = LokasiService::jarakMeter(
                (float) $acaraLama->lokasi_lat,
                (float) $acaraLama->lokasi_lng,
                (float) $event->lokasi_lat,
                (float) $event->lokasi_lng,
            ) / 1000;
            if ($km < (float) $cfg['perjalanan_km_min']) {
                continue;
            }

            // Selisih waktu dibatasi minimal semenit supaya dua check-in beruntun tidak menghasilkan pembagian nol.
            $jam = max(abs($poin->created_at->diffInSeconds($lama->created_at)), 60) / 3600;
            $kecepatan = $km / $jam;
            if ($kecepatan <= (float) $cfg['perjalanan_kecepatan_kmj']) {
                continue;
            }

            return $this->tandai(
                $anggota,
                $event,
                $poin,
                ic_penanda::PERJALANAN_MUSTAHIL,
                'Check-in di ' . ($acaraLama->venue ?? $acaraLama->kode) . ' lalu di ' . ($event->venue ?? $event->kode) .
                    ' berjarak sekitar ' . number_format($km, 0, ',', '.') . ' km dalam ' . $this->durasi($jam) .
                    ', mustahil ditempuh biasa.'
            ) ? 1 : 0;
        }

        return 0;
    }

    private function banyakGagal(member $anggota, ic_event $event, ic_poin $poin): int
    {
        $kunci = $this->kunciGagal($anggota, $event);
        $jumlah = (int) Cache::get($kunci, 0);
        Cache::forget($kunci);

        if ($jumlah < (int) config('inner_circle.penanda.gagal_banyak', 6)) {
            return 0;
        }

        return $this->tandai(
            $anggota,
            $event,
            $poin,
            ic_penanda::BANYAK_GAGAL,
            $jumlah . ' percobaan lokasi gagal di acara ini sebelum akhirnya berhasil. Bisa GPS yang sulit, bisa juga mencoba-coba posisi.'
        ) ? 1 : 0;
    }

    /** Buat penanda bila belum ada untuk (anggota, acara, jenis). True bila baru dibuat. */
    private function tandai(?member $anggota, ic_event $event, ic_poin $poin, string $jenis, string $detail): bool
    {
        if (! $anggota) {
            return false;
        }

        $penanda = ic_penanda::query()->firstOrCreate(
            ['member_id' => $anggota->getKey(), 'event_id' => $event->getKey(), 'jenis' => $jenis],
            ['poin_id' => $poin->getKey(), 'detail' => Str::limit($detail, 250, '...'), 'status' => ic_penanda::BARU],
        );

        return $penanda->wasRecentlyCreated;
    }

    private function kunciGagal(member $anggota, ic_event $event): string
    {
        return 'ic:gagal-lokasi:' . $anggota->getKey() . ':' . $event->getKey();
    }

    private function durasi(float $jam): string
    {
        $menit = (int) round($jam * 60);

        return $menit < 60 ? $menit . ' menit' : number_format($jam, 1, ',', '.') . ' jam';
    }
}