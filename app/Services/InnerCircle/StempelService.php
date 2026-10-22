<?php

namespace App\Services\InnerCircle;

use App\Models\ic_event;
use App\Models\ic_stempel;
use App\Models\member;
use Illuminate\Support\Facades\DB;

/**
 * Satu-satunya tempat stempel ditambahkan: menulis baris buku besar `ic_stempel` dan
 * memperbarui cache `members.stempel_total` di transaksi yang sama. Stempel bersifat kumulatif
 * dan tidak pernah dikurangi.
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
    ): ic_stempel {
        return DB::transaction(function () use ($anggota, $jumlah, $sumber, $judul, $ikon, $event, $catatan, $dicatatOleh) {
            $baris = ic_stempel::create([
                'member_id' => $anggota->getKey(),
                'jumlah' => $jumlah,
                'sumber' => $sumber,
                'event_id' => $event?->getKey(),
                'judul' => $judul,
                'ikon' => $ikon,
                'catatan' => $catatan,
                'dicatat_oleh' => $dicatatOleh,
            ]);

            $anggota->increment('stempel_total', $jumlah);

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
