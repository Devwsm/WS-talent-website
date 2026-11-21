<?php

namespace App\Services\InnerCircle;

use App\Models\ic_claim;
use App\Models\ic_event;
use App\Models\ic_poin;
use App\Models\member;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Satu-satunya tempat poin ditambahkan: menulis baris buku besar `ic_poin` dan
 * memperbarui cache `members.poin_total` di transaksi yang sama. Buku besar hanya ditambah: koreksi
 * admin (kurangi) juga baris baru, bernilai negatif, bukan mengubah atau menghapus baris lama.
 */
class PoinService
{
    public function tambah(
        member $anggota,
        int $jumlah,
        string $sumber,
        string $judul,
        string $ikon,
        ?ic_event $event = null,
        ?string $catatan = null,
        ?string $dicatatOleh = null,
        ?int $jarakM = null,
        ?int $akurasiM = null,
        ?string $sidikPerangkat = null,
    ): ic_poin {
        return DB::transaction(function () use ($anggota, $jumlah, $sumber, $judul, $ikon, $event, $catatan, $dicatatOleh, $jarakM, $akurasiM, $sidikPerangkat) {
            $baris = ic_poin::create([
                'member_id' => $anggota->getKey(),
                'jumlah' => $jumlah,
                'sumber' => $sumber,
                'event_id' => $event?->getKey(),
                'judul' => $judul,
                'ikon' => $ikon,
                'catatan' => $catatan,
                'jarak_m' => $jarakM,
                'akurasi_m' => $akurasiM,
                'sidik_perangkat' => $sidikPerangkat,
                'dicatat_oleh' => $dicatatOleh,
            ]);

            $anggota->increment('poin_total', $jumlah);

            return $baris;
        });
    }

    /**
     * Kurangi poin (koreksi salah input) lewat baris negatif di buku besar. Check-in yang sudah tercatat tetap ada
     * (anggota tidak bisa check-in ulang di acara yang sama); yang berubah hanya totalnya.
     *
     * @throws ValidationException bila jumlahnya melebihi total poin anggota
     */
    public function kurangi(member $anggota, int $jumlah, string $catatan, ?string $admin): ic_poin
    {
        return DB::transaction(function () use ($anggota, $jumlah, $catatan, $admin) {
            // Total dibaca ulang dengan kunci baris: dua koreksi bersamaan tidak boleh membuat total di bawah nol.
            $terkini = member::query()->whereKey($anggota->getKey())->lockForUpdate()->firstOrFail();

            if ($jumlah > $terkini->poin_total) {
                throw ValidationException::withMessages([
                    'jumlah' => 'Anggota ini hanya punya ' . ic_angka($terkini->poin_total) . ' poin, tidak bisa dikurangi ' . ic_angka($jumlah) . '.',
                ]);
            }

            $baris = ic_poin::create([
                'member_id' => $terkini->getKey(),
                'jumlah' => -$jumlah,
                'sumber' => 'koreksi',
                'judul' => 'Koreksi poin oleh tim WSM',
                'ikon' => 'sparkles',
                'catatan' => $catatan,
                'dicatat_oleh' => $admin,
            ]);

            $terkini->decrement('poin_total', $jumlah);
            $anggota->setAttribute('poin_total', $terkini->poin_total)->syncOriginalAttribute('poin_total');

            return $baris;
        });
    }

    /**
     * Tahan poin untuk satu penukaran hadiah: baris NEGATIF `tukar` di buku besar dan saldo (`poin_total`) berkurang. Dipanggil
     * ClaimService di dalam transaksi yang sudah mengunci baris anggota, jadi tidak mengunci ulang. Kunci unik
     * (claim_id, sumber) menolak penahanan kedua untuk penukaran yang sama.
     */
    public function tahanUntukPenukaran(member $anggota, ic_claim $klaim, int $jumlah, string $judulHadiah, ?string $ikon = null): ic_poin
    {
        $baris = ic_poin::create([
            'member_id' => $anggota->getKey(),
            'jumlah' => -$jumlah,
            'sumber' => 'tukar',
            'claim_id' => $klaim->getKey(),
            'judul' => mb_substr('Tukar hadiah: ' . $judulHadiah, 0, 120),
            'ikon' => $ikon ?: 'gift',
            'catatan' => 'Poin ditahan sampai penukaran selesai.',
        ]);

        $anggota->decrement('poin_total', $jumlah);

        return $baris;
    }

    /**
     * Kembalikan poin yang ditahan (penukaran ditolak atau dibatalkan): baris positif `kembali`, saldo bertambah. Paling
     * banyak satu kali per penukaran (kunci unik claim_id + sumber); pemanggil memeriksa lebih dulu lewat sudahKembali().
     */
    public function kembalikanPenukaran(member $anggota, ic_claim $klaim, int $jumlah, string $judulHadiah, string $alasan, ?string $ikon = null): ic_poin
    {
        $baris = ic_poin::create([
            'member_id' => $anggota->getKey(),
            'jumlah' => $jumlah,
            'sumber' => 'kembali',
            'claim_id' => $klaim->getKey(),
            'judul' => mb_substr('Poin kembali: ' . $judulHadiah, 0, 120),
            'ikon' => $ikon ?: 'gift',
            'catatan' => mb_substr($alasan, 0, 160),
        ]);

        $anggota->increment('poin_total', $jumlah);

        return $baris;
    }

    /** Poin penukaran ini sudah pernah dikembalikan? */
    public function sudahKembali(ic_claim $klaim): bool
    {
        return ic_poin::query()->where('claim_id', $klaim->getKey())->where('sumber', 'kembali')->exists();
    }

    /** Poin manual dari admin (mis. kehadiran di acara privat yang diverifikasi tim WSM). */
    public function tambahManual(member $anggota, int $jumlah, string $catatan, ?string $admin): ic_poin
    {
        return $this->tambah(
            $anggota,
            $jumlah,
            'manual',
            'Ikut kegiatan resmi',
            'sparkles',
            catatan: $catatan,
            dicatatOleh: $admin,
        );
    }
}