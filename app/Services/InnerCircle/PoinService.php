<?php

namespace App\Services\InnerCircle;

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