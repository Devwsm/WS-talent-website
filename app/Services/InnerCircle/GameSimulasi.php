<?php

namespace App\Services\InnerCircle;

/**
 * Mambo Jambo: Pineapple Catcher, versi server. Menjalankan ulang satu ronde dari seed + catatan input dan menghitung
 * skornya SENDIRI, jadi skor kiriman browser tidak pernah dipercaya.
 *
 * Kembar dari `resources/js/game-mambo-sim.js`: aturan, angka, dan urutan hitungannya harus sama persis. Semua hitungan
 * memakai bilangan bulat (posisi dalam 1/60 piksel, 60 langkah per detik, acak xorshift32) supaya JS dan PHP identik.
 * Mengubah salah satu berkas berarti mengubah yang lain dan membuat ulang angka emas di GameSimulasiTest.
 *
 * Catatan input: daftar [selisihLangkah, kode, selisihLangkah, kode, ...]. Selisih dihitung dari kejadian sebelumnya
 * (yang pertama dari langkah 0). Kode 0..320 = keranjang diarahkan ke kolom itu; 1000/1001/1002 = panah kiri/diam/kanan.
 * Kejadian pada langkah t diterapkan SEBELUM langkah ke-t dijalankan.
 */
final class GameSimulasi
{
    public const LEBAR = 320;
    public const TINGGI = 280;
    public const SKALA = 60;
    public const LANGKAH_PER_DETIK = 60;
    public const NYAWA = 5;
    public const POIN_NANAS = 10;
    public const POIN_PER_LEVEL = 250;
    public const KECEPATAN_AWAL = 104;
    public const KECEPATAN_PER_LEVEL = 30;
    public const KECEPATAN_KERANJANG = 340;
    public const BATAS_TEPI = 38;
    public const JARAK_JATUH = 26;
    public const MULUT_Y = 211;
    public const MULUT_SETENGAH = 19;
    public const PELUANG_BIR = 24;
    public const TINGGI_NANAS = 26;
    public const TINGGI_BIR = 29;
    public const KODE_PANAH = 1001;

    private int $s;
    private int $bx;
    private int $arah = 0;
    private int $skor = 0;
    private int $level = 1;
    private int $nyawa = self::NYAWA;
    private int $langkah = 0;
    private int $nanas = 0;
    private int $bir = 0;
    private int $lewat = 0;
    private bool $selesai = false;

    /** @var array{jenis:int,x:int,y:int}|null */
    private ?array $drop = null;

    private function __construct(int $seed)
    {
        $this->s = ($seed & 0xFFFFFFFF) ?: 1;
        $this->bx = intdiv(self::LEBAR, 2) * self::SKALA;
        $this->drop = $this->jatuhBaru(true);
    }

    /**
     * @param  list<int>  $log  [selisihLangkah, kode, ...] (sudah divalidasi pemanggil, lihat GameService::uraikanLog)
     * @return array{skor:int,level:int,nyawa:int,langkah:int,nanas:int,bir:int,lewat:int,selesai:bool}
     */
    public static function ulang(int $seed, array $log, int $langkahTotal): array
    {
        $sim = new self($seed);
        $i = 0;
        $jumlah = count($log);
        $waktuKejadian = $jumlah > 0 ? $log[0] : PHP_INT_MAX;

        for ($t = 0; $t < $langkahTotal; $t++) {
            while ($i < $jumlah && $waktuKejadian === $t) {
                $sim->masuk($log[$i + 1]);
                $i += 2;
                $waktuKejadian = $i < $jumlah ? $t + $log[$i] : PHP_INT_MAX;
            }
            $sim->langkah();
            if ($sim->selesai) {
                break;
            }
        }

        return [
            'skor' => $sim->skor,
            'level' => $sim->level,
            'nyawa' => $sim->nyawa,
            'langkah' => $sim->langkah,
            'nanas' => $sim->nanas,
            'bir' => $sim->bir,
            'lewat' => $sim->lewat,
            'selesai' => $sim->selesai,
        ];
    }

    private function acak(int $n): int
    {
        $s = $this->s;
        $s ^= ($s << 13) & 0xFFFFFFFF;
        $s ^= $s >> 17;
        $s ^= ($s << 5) & 0xFFFFFFFF;
        $this->s = $s;

        return $s % $n;
    }

    /** @return array{jenis:int,x:int,y:int} */
    private function jatuhBaru(bool $paksaNanas): array
    {
        $jenis = $paksaNanas ? 0 : ($this->acak(100) < self::PELUANG_BIR ? 1 : 0);
        $x = self::JARAK_JATUH + $this->acak(self::LEBAR - 2 * self::JARAK_JATUH + 1);
        $tinggi = $jenis === 1 ? self::TINGGI_BIR : self::TINGGI_NANAS;

        return ['jenis' => $jenis, 'x' => $x, 'y' => - ($tinggi + 1) * self::SKALA];
    }

    private function geser(int $x): int
    {
        return min((self::LEBAR - self::BATAS_TEPI) * self::SKALA, max(self::BATAS_TEPI * self::SKALA, $x));
    }

    private function masuk(int $kode): void
    {
        if ($kode <= self::LEBAR) {
            $this->bx = $this->geser($kode * self::SKALA);
        } else {
            $this->arah = $kode - self::KODE_PANAH;
        }
    }

    private function langkah(): void
    {
        if ($this->arah !== 0) {
            $this->bx = $this->geser($this->bx + $this->arah * self::KECEPATAN_KERANJANG);
        }

        $d = $this->drop;
        $tinggi = $d['jenis'] === 1 ? self::TINGGI_BIR : self::TINGGI_NANAS;
        $selisih = ($tinggi - 1) * self::SKALA;
        $bawahLama = $d['y'] + $selisih;
        $d['y'] += self::KECEPATAN_AWAL + ($this->level - 1) * self::KECEPATAN_PER_LEVEL;
        $bawahBaru = $d['y'] + $selisih;
        $kolomKeranjang = intdiv($this->bx + intdiv(self::SKALA, 2), self::SKALA);
        $melewatiMulut = $bawahLama < self::MULUT_Y * self::SKALA && $bawahBaru >= self::MULUT_Y * self::SKALA;
        $masukMulut = abs($d['x'] - $kolomKeranjang) + 4 <= self::MULUT_SETENGAH;
        $this->langkah++;
        $this->drop = $d;

        if ($melewatiMulut && $masukMulut) {
            $nanas = $d['jenis'] === 0;
            $this->skor += $nanas ? self::POIN_NANAS : -self::POIN_NANAS;
            $this->level = max($this->level, 1 + (int) floor($this->skor / self::POIN_PER_LEVEL));
            $nanas ? $this->nanas++ : $this->bir++;
            $this->drop = $this->jatuhBaru(false);
        } elseif ($bawahBaru >= self::TINGGI * self::SKALA) {
            if ($d['jenis'] === 0) {
                $this->nyawa--;
                $this->lewat++;
                if ($this->nyawa === 0) {
                    $this->selesai = true;
                    $this->drop = null;

                    return;
                }
            }
            $this->drop = $this->jatuhBaru(false);
        }
    }
}