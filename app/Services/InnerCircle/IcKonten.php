<?php

namespace App\Services\InnerCircle;

use App\Models\ic_item;
use App\Models\ic_pengaturan;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Konten Inner Circle yang bisa diubah dari CMS.
 *
 * Aturan dasarnya: teks yang tampil = isi tabel `ic_pengaturan` bila ada, kalau tidak teks bawaan dari
 * config/ic_konten.php. Jadi tabel kosong = tampilan persis seperti sebelum fitur ini ada, dan menghapus baris
 * ("kembalikan bawaan") selalu aman. Hasil di-cache dan cache dibuang tiap kali konten disimpan.
 *
 * Isi yang dikembalikan adalah TEKS BIASA: view wajib menampilkannya dengan {{ }} (di-escape), tidak pernah {!! !!}.
 */
class IcKonten
{
    private const CACHE_PENGATURAN = 'ic_konten.pengaturan';
    private const CACHE_ITEM = 'ic_konten.item';

    /** Salinan per permintaan, supaya satu halaman dengan puluhan teks tidak membaca cache berulang kali. */
    private ?array $memoPengaturan = null;
    private ?array $memoItem = null;

    /** @return array<string, array<string, mixed>> semua kunci yang terdaftar di config */
    public function definisi(): array
    {
        return config('ic_konten.kunci', []);
    }

    /** @return array<string, mixed>|null */
    public function definisiKunci(string $kunci): ?array
    {
        return $this->definisi()[$kunci] ?? null;
    }

    /** Teks untuk satu kunci. $isi mengisi placeholder yang diizinkan kunci itu, mis. ['nama' => 'Ancha']. */
    public function teks(string $kunci, array $isi = []): string
    {
        $def = $this->definisiKunci($kunci);
        if ($def === null) {
            // Salah ketik kunci harus ketahuan saat pengembangan dan tes, tapi tidak boleh menjatuhkan halaman di produksi.
            if (app()->environment('local', 'testing')) {
                throw new \InvalidArgumentException("Kunci konten tidak terdaftar di config/ic_konten.php: {$kunci}");
            }

            return '';
        }

        $nilai = $this->pengaturan()[$kunci] ?? null;
        $teks = ($nilai !== null && trim($nilai) !== '') ? $nilai : (string) $def['bawaan'];

        return $this->isiPlaceholder($teks, $def['placeholder'] ?? [], $isi);
    }

    /** Teks bawaan (tanpa placeholder diisi), untuk petunjuk di form CMS. */
    public function bawaan(string $kunci): string
    {
        return (string) ($this->definisiKunci($kunci)['bawaan'] ?? '');
    }

    /** Nilai yang tersimpan (diubah admin) per kunci; kunci yang memakai teks bawaan tidak ada di sini. */
    public function tersimpan(): array
    {
        return array_filter($this->pengaturan(), fn($v) => $v !== null && trim((string) $v) !== '');
    }

    /**
     * Simpan isian satu grup. Isian kosong atau sama dengan teks bawaan berarti "pakai bawaan": barisnya dihapus,
     * jadi perubahan teks bawaan di kode nanti tetap sampai ke halaman.
     *
     * @param  array<string, string>  $nilai  kunci => teks (hanya kunci milik grup yang diproses)
     * @return int jumlah isian yang benar-benar berubah
     */
    public function simpan(string $grup, array $nilai, ?string $oleh = null): int
    {
        $berubah = 0;
        $tersimpan = $this->tersimpan();

        foreach ($this->definisi() as $kunci => $def) {
            if ($def['grup'] !== $grup || ! array_key_exists($kunci, $nilai)) {
                continue;
            }

            $baru = $this->rapikan((string) $nilai[$kunci], ! empty($def['baris']));
            $lama = $tersimpan[$kunci] ?? null;

            if ($baru === '' || $baru === (string) $def['bawaan']) {
                if ($lama !== null) {
                    ic_pengaturan::query()->where('kunci', $kunci)->delete();
                    $berubah++;
                }
                continue;
            }

            if ($baru !== $lama) {
                ic_pengaturan::query()->updateOrCreate(['kunci' => $kunci], ['nilai' => $baru, 'diubah_oleh' => $oleh]);
                $berubah++;
            }
        }

        $this->lupakan();

        return $berubah;
    }

    /** Kembalikan satu kunci ke teks bawaan. */
    public function reset(string $kunci): void
    {
        ic_pengaturan::query()->where('kunci', $kunci)->delete();
        $this->lupakan();
    }

    /**
     * Daftar item aktif satu grup, urut menurut kolom urutan. Dikembalikan sebagai objek biasa.
     *
     * @return Collection<int, object>
     */
    public function daftar(string $grup): Collection
    {
        return collect($this->semuaItem()[$grup] ?? [])->map(fn($baris) => (object) $baris);
    }

    /** Buang cache; dipanggil setiap konten disimpan. */
    public function lupakan(): void
    {
        $this->memoPengaturan = null;
        $this->memoItem = null;
        Cache::forget(self::CACHE_PENGATURAN);
        Cache::forget(self::CACHE_ITEM);
    }

    /** Teks biasa: tanpa spasi berlebih di tepi, baris baru disamakan; isian satu baris tidak boleh berisi baris baru. */
    public function rapikan(string $teks, bool $banyakBaris = false): string
    {
        $teks = str_replace(["\r\n", "\r"], "\n", $teks);
        $teks = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $teks) ?? '';

        if (! $banyakBaris) {
            $teks = preg_replace('/\s+/u', ' ', $teks) ?? '';
        }

        return trim($teks);
    }

    private function isiPlaceholder(string $teks, array $diizinkan, array $isi): string
    {
        $ganti = ['{program}' => (string) config('ic_konten.program')];
        foreach ($diizinkan as $nama) {
            if (array_key_exists($nama, $isi)) {
                $ganti['{' . $nama . '}'] = (string) $isi[$nama];
            }
        }

        return strtr($teks, $ganti);
    }

    /** @return array<string, string|null> */
    private function pengaturan(): array
    {
        if ($this->memoPengaturan !== null) {
            return $this->memoPengaturan;
        }

        try {
            return $this->memoPengaturan = Cache::rememberForever(
                self::CACHE_PENGATURAN,
                fn() => ic_pengaturan::query()->pluck('nilai', 'kunci')->all(),
            );
        } catch (QueryException) {
            // Tabel belum ada (jeda singkat antara kode terpasang dan migrasi selesai saat deploy): pakai teks bawaan.
            return [];
        }
    }

    /** @return array<string, list<array<string, mixed>>> */
    private function semuaItem(): array
    {
        if ($this->memoItem !== null) {
            return $this->memoItem;
        }

        try {
            return $this->memoItem = Cache::rememberForever(
                self::CACHE_ITEM,
                fn() => ic_item::query()->where('aktif', true)->orderBy('grup')->orderBy('urutan')->orderBy('id_item')->get()
                    ->groupBy('grup')->map(fn($g) => $g->map->only(['id_item', 'urutan', 'judul', 'isi', 'ikon'])->values()->all())->all(),
            );
        } catch (QueryException) {
            return [];
        }
    }
}