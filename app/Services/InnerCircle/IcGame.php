<?php

namespace App\Services\InnerCircle;

use Illuminate\Support\Facades\DB;

/**
 * Pengaturan mini game yang bisa diubah dari CMS (tab Konten, bagian "Aturan game"): game aktif atau tidak, jatah ronde
 * per hari, skor per poin, dan batas poin per ronde dan per hari.
 *
 * Nilai disimpan di `ic_pengaturan` dengan kunci `game.*` dan HANYA bila berbeda dari bawaan di config/inner_circle.php
 * (bawaan mengikuti prototype game), jadi tabel kosong = perilaku bawaan. Nilai yang rusak atau di luar rentang
 * (diubah manual di database) diabaikan dan bawaan yang dipakai. Perubahan berlaku langsung untuk ronde berikutnya;
 * poin yang sudah diberikan tidak berubah.
 */
class IcGame
{
    public const KUNCI = [
        'aktif' => 'game.aktif',
        'ronde_harian' => 'game.ronde_harian',
        'skor_per_poin' => 'game.skor_per_poin',
        'poin_maks_ronde' => 'game.poin_maks_ronde',
        'poin_maks_harian' => 'game.poin_maks_harian',
    ];

    public function __construct(private readonly IcKonten $konten) {}

    /** Nilai bawaan dari config (bukan dari CMS). @return array{aktif: bool, ronde_harian: int, skor_per_poin: int, poin_maks_ronde: int, poin_maks_harian: int} */
    public function bawaan(): array
    {
        $c = (array) config('inner_circle.game');

        return [
            'aktif' => (bool) ($c['aktif'] ?? true),
            'ronde_harian' => (int) $c['ronde_harian'],
            'skor_per_poin' => (int) $c['skor_per_poin'],
            'poin_maks_ronde' => (int) $c['poin_maks_ronde'],
            'poin_maks_harian' => (int) $c['poin_maks_harian'],
        ];
    }

    /** Rentang isian [min, maks] tiap angka, untuk form CMS dan validasi. @return array<string, array{int, int}> */
    public function batas(): array
    {
        return (array) config('inner_circle.game.batas');
    }

    /** Nilai yang berlaku sekarang: dari CMS bila ada dan masuk akal, kalau tidak bawaan. @return array{aktif: bool, ronde_harian: int, skor_per_poin: int, poin_maks_ronde: int, poin_maks_harian: int} */
    public function efektif(): array
    {
        $a = $this->bawaan();
        $aktif = $this->konten->nilaiMentah(self::KUNCI['aktif']);

        $hasil = ['aktif' => in_array($aktif, ['0', '1'], true) ? $aktif === '1' : $a['aktif']];
        foreach ($this->batas() as $nama => [$min, $maks]) {
            $hasil[$nama] = $this->dalamBatas($this->konten->nilaiMentah(self::KUNCI[$nama]), (int) $min, (int) $maks) ?? $a[$nama];
        }

        return $hasil;
    }

    public function aktif(): bool
    {
        return $this->efektif()['aktif'];
    }

    /**
     * Isi placeholder teks game ({ronde}, {skor_per_poin}, {poin_ronde}, {poin_hari}, {menit}) dengan nilai yang berlaku.
     *
     * @return array<string, string>
     */
    public function untukTeks(): array
    {
        $e = $this->efektif();

        return [
            'ronde' => ic_angka($e['ronde_harian']),
            'skor_per_poin' => ic_angka($e['skor_per_poin']),
            'poin_ronde' => ic_angka($e['poin_maks_ronde']),
            'poin_hari' => ic_angka($e['poin_maks_harian']),
            'menit' => ic_angka($this->menitRonde()),
        ];
    }

    /** Lama maksimum satu ronde dalam menit (dari config langkah_maks: 60 langkah = 1 detik). */
    public function menitRonde(): int
    {
        return max(1, (int) round(((int) config('inner_circle.game.langkah_maks')) / 3600));
    }

    /**
     * Simpan pengaturan baru (sudah divalidasi). Nilai yang sama dengan bawaan tidak disimpan (barisnya dihapus).
     *
     * @param  array<string, string|int|bool>  $baru  kunci pendek (aktif, ronde_harian, ...) => nilai
     * @return int jumlah pengaturan yang benar-benar berubah
     */
    public function simpan(array $baru, ?string $oleh = null): int
    {
        $bawaan = $this->bawaan();

        $tulis = [];
        foreach (self::KUNCI as $pendek => $kunci) {
            if (! array_key_exists($pendek, $baru)) {
                continue;
            }
            $nilai = $pendek === 'aktif' ? ((bool) $baru[$pendek] ? '1' : '0') : (string) (int) $baru[$pendek];
            $standar = $pendek === 'aktif' ? ($bawaan['aktif'] ? '1' : '0') : (string) $bawaan[$pendek];
            $tulis[$kunci] = $nilai === $standar ? null : $nilai;
        }

        return DB::transaction(fn() => $this->konten->simpanMentah($tulis, $oleh));
    }

    /** Kembalikan semuanya ke bawaan. */
    public function kembalikanBawaan(?string $oleh = null): int
    {
        return $this->simpan($this->bawaan(), $oleh);
    }

    private function dalamBatas(?string $nilai, int $min, int $maks): ?int
    {
        if ($nilai === null || ! ctype_digit($nilai)) {
            return null;
        }
        $angka = (int) $nilai;

        return ($angka >= $min && $angka <= $maks) ? $angka : null;
    }
}