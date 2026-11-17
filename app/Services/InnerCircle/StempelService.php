<?php

namespace App\Services\InnerCircle;

use App\Models\ic_event;
use App\Models\ic_stempel;
use App\Models\member;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Satu-satunya tempat stempel ditambahkan: menulis baris buku besar `ic_stempel` dan
 * memperbarui cache `members.stempel_total` di transaksi yang sama. Buku besar hanya ditambah: koreksi
 * admin (kurangi) juga baris baru, bernilai negatif, bukan mengubah atau menghapus baris lama.
 */
class StempelService
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
    ): ic_stempel {
        return DB::transaction(function () use ($anggota, $jumlah, $sumber, $judul, $ikon, $event, $catatan, $dicatatOleh, $jarakM, $akurasiM) {
            $baris = ic_stempel::create([
                'member_id' => $anggota->getKey(),
                'jumlah' => $jumlah,
                'sumber' => $sumber,
                'event_id' => $event?->getKey(),
                'judul' => $judul,
                'ikon' => $ikon,
                'catatan' => $catatan,
                'jarak_m' => $jarakM,
                'akurasi_m' => $akurasiM,
                'dicatat_oleh' => $dicatatOleh,
            ]);

            $anggota->increment('stempel_total', $jumlah);

            return $baris;
        });
    }

    /**
     * Kurangi stempel (koreksi salah input) lewat baris negatif di buku besar. Check-in yang sudah tercatat tetap ada
     * (anggota tidak bisa check-in ulang di acara yang sama); yang berubah hanya totalnya.
     *
     * @throws ValidationException bila jumlahnya melebihi total stempel anggota
     */
    public function kurangi(member $anggota, int $jumlah, string $catatan, ?string $admin): ic_stempel
    {
        return DB::transaction(function () use ($anggota, $jumlah, $catatan, $admin) {
            // Total dibaca ulang dengan kunci baris: dua koreksi bersamaan tidak boleh membuat total di bawah nol.
            $terkini = member::query()->whereKey($anggota->getKey())->lockForUpdate()->firstOrFail();

            if ($jumlah > $terkini->stempel_total) {
                throw ValidationException::withMessages([
                    'jumlah' => "Anggota ini hanya punya {$terkini->stempel_total} stempel, tidak bisa dikurangi {$jumlah}.",
                ]);
            }

            $baris = ic_stempel::create([
                'member_id' => $terkini->getKey(),
                'jumlah' => -$jumlah,
                'sumber' => 'koreksi',
                'judul' => 'Koreksi stempel oleh tim WSM',
                'ikon' => 'sparkles',
                'catatan' => $catatan,
                'dicatat_oleh' => $admin,
            ]);

            $terkini->decrement('stempel_total', $jumlah);
            $anggota->setAttribute('stempel_total', $terkini->stempel_total)->syncOriginalAttribute('stempel_total');

            return $baris;
        });
    }

    /** Stempel manual dari admin (mis. kehadiran di acara privat yang diverifikasi tim WSM). */
    public function tambahManual(member $anggota, int $jumlah, string $catatan, ?string $admin): ic_stempel
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