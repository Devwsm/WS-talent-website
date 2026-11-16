<?php

namespace App\Services\Cms;

use App\Models\albums;
use App\Models\banner;
use App\Models\header;
use App\Models\merchandise;
use App\Support\CachePublik;
use Illuminate\Support\Facades\DB;

/**
 * Mengatur urutan tampil album, banner, header, dan merchandise di halaman publik (kolom `urutan`, dari 1).
 *
 * Satu urutan dipakai di dua tempat: halaman publik dan daftar di CMS, jadi yang admin lihat di CMS selalu sama
 * dengan yang tampil di situs. Data baru masuk di paling belakang; urutan lama (kunci utama naik) sudah diisi oleh
 * migrasinya, jadi tampilan situs tidak berubah sampai admin menggeser sesuatu.
 */
class UrutanTampil
{
    /** Jumlah data per halaman pada daftar CMS yang dipaginasi (album dan merchandise). */
    public const PER_HALAMAN = 9;

    /** Jenis data yang bisa diurutkan: kunci = awalan nama route dan alamat. */
    public const MODUL = [
        'albums' => albums::class,
        'banner' => banner::class,
        'headers' => header::class,
        'merchandise' => merchandise::class,
    ];

    public const ARAH = ['naik', 'turun', 'awal'];

    /**
     * Geser satu data: "naik"/"turun" satu langkah, atau "awal" untuk menaruhnya paling depan (sematkan).
     *
     * @return array{bergeser: bool, posisi: int} posisi = nomor urut (dari 1) setelah digeser
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException bila datanya tidak ada
     */
    public function geser(string $modul, int $id, string $arah): array
    {
        $model = self::MODUL[$modul] ?? throw new \InvalidArgumentException("Jenis data tidak dikenal: $modul");
        $kunci = (new $model)->getKeyName();

        $hasil = DB::transaction(function () use ($model, $kunci, $id, $arah) {
            // Seluruh baris dikunci dan dibaca dalam urutan yang sama dengan halaman publik.
            $urut = $model::query()->urut()->lockForUpdate()->get([$kunci, 'urutan'])->values();

            $posisi = $urut->search(fn($b) => (int) $b->getKey() === $id);
            if ($posisi === false) {
                throw (new \Illuminate\Database\Eloquent\ModelNotFoundException)->setModel($model, [$id]);
            }

            $tujuan = match ($arah) {
                'naik' => $posisi - 1,
                'turun' => $posisi + 1,
                'awal' => 0,
            };

            if ($tujuan < 0 || $tujuan >= $urut->count() || $tujuan === $posisi) {
                return ['bergeser' => false, 'posisi' => $posisi + 1];
            }

            $susunan = $urut->all();
            [$satu] = array_splice($susunan, $posisi, 1);
            array_splice($susunan, $tujuan, 0, [$satu]);

            // Nomor ditulis ulang rapat (1..n); hanya baris yang nomornya berubah yang disentuh.
            foreach ($susunan as $i => $baris) {
                if ((int) $baris->urutan !== $i + 1) {
                    $model::query()->whereKey($baris->getKey())->update(['urutan' => $i + 1, 'updated_at' => now()]);
                }
            }

            return ['bergeser' => true, 'posisi' => $tujuan + 1];
        });

        // Update lewat query tidak memicu event model, jadi cache halaman publik dibuang di sini.
        if ($hasil['bergeser']) {
            CachePublik::lupakan();
        }

        return $hasil;
    }

    /** Nomor halaman (dari 1) tempat sebuah posisi berada pada daftar yang dipaginasi. */
    public static function halamanUntuk(int $posisi): int
    {
        return max(1, (int) ceil($posisi / self::PER_HALAMAN));
    }
}