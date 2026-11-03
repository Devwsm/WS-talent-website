<?php

namespace App\Services\InnerCircle;

use App\Models\ic_memory;
use App\Models\ic_pilihan;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Tipe acara dan kategori kenangan yang bisa diubah dari CMS (tab Konten, bagian "Pilihan").
 *
 * - Tipe acara: kunci tetap (`festival`, `club`, `private`) karena dipakai logika (acara privat tidak bisa check-in).
 *   Admin hanya mengubah label, ikon, dan warna.
 * - Kategori kenangan: admin boleh menambah, mengubah, mengurutkan, menyembunyikan, dan menghapus. Kategori yang sudah
 *   dipakai kenangan tidak bisa dihapus (sembunyikan saja), dan minimal satu kategori harus tampil.
 *
 * Sumbernya tabel `ic_pilihan` (diisi awal dari config/inner_circle.php oleh migrasi). Bila tabel belum ada atau belum
 * terisi, yang dipakai config, jadi tampilan tidak pernah kosong. Hasil di-cache; cache dibuang tiap perubahan.
 *
 * Bentuk datanya sama dengan config lama: kunci => ['label', 'ikon', 'warna', 'sampul'?, 'aktif'].
 */
class IcPilihan
{
    private const CACHE = 'ic_pilihan.semua';

    /** Kunci yang tidak boleh dipakai kategori baru: "all" dipakai tautan filter "semua". */
    private const KUNCI_TERLARANG = ['all', 'semua'];

    private ?array $memo = null;

    /** @return array<string, array<string, mixed>> semua tipe acara (selalu tampil), urut */
    public function tipeEvent(): array
    {
        return $this->semua()[ic_pilihan::TIPE_EVENT];
    }

    /**
     * @param  bool  $semua  true = termasuk kategori yang disembunyikan (untuk menampilkan kenangan lama dan filter admin)
     * @return array<string, array<string, mixed>>
     */
    public function kategoriKenangan(bool $semua = false): array
    {
        $daftar = $this->semua()[ic_pilihan::KATEGORI_KENANGAN];

        return $semua ? $daftar : array_filter($daftar, fn($k) => $k['aktif']);
    }

    /**
     * Baris tabel untuk halaman CMS (termasuk id dan jumlah pemakaian).
     *
     * @return Collection<int, ic_pilihan>
     */
    public function baris(string $jenis): Collection
    {
        return ic_pilihan::query()->where('jenis', $jenis)->orderBy('urutan')->orderBy('id_pilihan')->get();
    }

    /** Jumlah kenangan per kategori, untuk menunjukkan mana yang tidak bisa dihapus. @return array<string, int> */
    public function pemakaianKategori(): array
    {
        return ic_memory::query()->selectRaw('kategori, count(*) as jumlah')->groupBy('kategori')->pluck('jumlah', 'kategori')
            ->map(fn($n) => (int) $n)->all();
    }

    /** @param  array{label: string, ikon: string, warna: string}  $data */
    public function ubahTipe(string $kunci, array $data): ic_pilihan
    {
        $baris = $this->cari(ic_pilihan::TIPE_EVENT, $kunci);
        $baris->update(['label' => $this->rapikan($data['label']), 'ikon' => $data['ikon'], 'warna' => $data['warna']]);
        $this->lupakan();

        return $baris;
    }

    /** @param  array{label: string, ikon: string, sampul: string, warna: string}  $data */
    public function tambahKategori(array $data): ic_pilihan
    {
        $label = $this->rapikan($data['label']);

        $baris = DB::transaction(function () use ($data, $label) {
            $semua = ic_pilihan::query()->where('jenis', ic_pilihan::KATEGORI_KENANGAN)->lockForUpdate()->get();
            $maks = (int) config('inner_circle.kategori_kenangan_maks');
            if ($semua->count() >= $maks) {
                throw new KontenGagal("Kategori kenangan sudah penuh (maksimal {$maks}). Hapus atau pakai ulang salah satunya.");
            }

            return ic_pilihan::create([
                'jenis' => ic_pilihan::KATEGORI_KENANGAN,
                'kunci' => $this->kunciBaru($label, $semua->pluck('kunci')->all()),
                'label' => $label,
                'ikon' => $data['ikon'],
                'sampul' => $data['sampul'],
                'warna' => $data['warna'],
                'urutan' => ((int) $semua->max('urutan')) + 1,
                'aktif' => true,
            ]);
        });

        $this->lupakan();

        return $baris;
    }

    /** @param  array{label: string, ikon: string, sampul: string, warna: string}  $data */
    public function ubahKategori(string $kunci, array $data): ic_pilihan
    {
        $baris = $this->cari(ic_pilihan::KATEGORI_KENANGAN, $kunci);
        $baris->update([
            'label' => $this->rapikan($data['label']),
            'ikon' => $data['ikon'],
            'sampul' => $data['sampul'],
            'warna' => $data['warna'],
        ]);
        $this->lupakan();

        return $baris;
    }

    /** Tampilkan atau sembunyikan kategori. Minimal satu kategori harus tetap tampil. */
    public function aktifkanKategori(string $kunci, bool $aktif): ic_pilihan
    {
        $baris = $this->cari(ic_pilihan::KATEGORI_KENANGAN, $kunci);

        if (! $aktif && $baris->aktif) {
            $lain = ic_pilihan::query()->where('jenis', ic_pilihan::KATEGORI_KENANGAN)->where('aktif', true)->where('kunci', '!=', $kunci)->count();
            if ($lain === 0) {
                throw new KontenGagal('Minimal satu kategori kenangan harus tampil, supaya anggota masih bisa memilih kategori saat membagikan kenangan.');
            }
        }

        $baris->update(['aktif' => $aktif]);
        $this->lupakan();

        return $baris;
    }

    /** Geser satu langkah ("naik" atau "turun"). false bila sudah di ujung. */
    public function geserKategori(string $kunci, string $arah): bool
    {
        $bergeser = DB::transaction(function () use ($kunci, $arah) {
            $urut = ic_pilihan::query()->where('jenis', ic_pilihan::KATEGORI_KENANGAN)
                ->orderBy('urutan')->orderBy('id_pilihan')->lockForUpdate()->get()->values();
            $posisi = $urut->search(fn(ic_pilihan $b) => $b->kunci === $kunci);
            if ($posisi === false) {
                throw new KontenGagal('Kategori tidak ditemukan.');
            }

            $tujuan = $arah === 'naik' ? $posisi - 1 : $posisi + 1;
            if ($tujuan < 0 || $tujuan >= $urut->count()) {
                return false;
            }

            $tukar = $urut->all();
            [$tukar[$posisi], $tukar[$tujuan]] = [$tukar[$tujuan], $tukar[$posisi]];
            $this->rapatkan($tukar);

            return true;
        });

        $this->lupakan();

        return $bergeser;
    }

    /** Hapus kategori yang belum dipakai kenangan mana pun. */
    public function hapusKategori(string $kunci): void
    {
        DB::transaction(function () use ($kunci) {
            $baris = $this->cari(ic_pilihan::KATEGORI_KENANGAN, $kunci);

            $dipakai = ic_memory::query()->where('kategori', $kunci)->count();
            if ($dipakai > 0) {
                throw new KontenGagal("Kategori \"{$baris->label}\" dipakai {$dipakai} kenangan, jadi tidak bisa dihapus. Sembunyikan saja supaya tidak bisa dipilih lagi.");
            }

            $sisa = ic_pilihan::query()->where('jenis', ic_pilihan::KATEGORI_KENANGAN)->where('kunci', '!=', $kunci)->get();
            if ($sisa->where('aktif', true)->isEmpty()) {
                throw new KontenGagal('Minimal satu kategori kenangan harus tampil, jadi kategori terakhir tidak bisa dihapus.');
            }

            $baris->delete();
            $this->rapatkan($sisa->sortBy([['urutan', 'asc'], ['id_pilihan', 'asc']])->values()->all());
        });

        $this->lupakan();
    }

    /** Kembalikan label, ikon, sampul, dan warna satu pilihan bawaan ke nilai di config. false bila bukan pilihan bawaan. */
    public function kembalikanBawaan(string $jenis, string $kunci): bool
    {
        $bawaan = config("inner_circle.$jenis.$kunci");
        if (! is_array($bawaan)) {
            return false;
        }

        $this->cari($jenis, $kunci)->update([
            'label' => $bawaan['label'],
            'ikon' => $bawaan['ikon'],
            'sampul' => $bawaan['sampul'] ?? null,
            'warna' => $bawaan['warna'],
        ]);
        $this->lupakan();

        return true;
    }

    /**
     * Isi awal dari config untuk setiap jenis yang belum punya baris sama sekali (dipakai migrasi). Jenis yang sudah
     * punya isi tidak disentuh. Tidak menyentuh cache: tabel cache bisa belum ada pada pemasangan pertama.
     */
    public function tanamBawaanBilaKosong(): int
    {
        $jumlah = 0;

        foreach ([ic_pilihan::TIPE_EVENT, ic_pilihan::KATEGORI_KENANGAN] as $jenis) {
            if (ic_pilihan::query()->where('jenis', $jenis)->exists()) {
                continue;
            }

            $urutan = 0;
            foreach (config("inner_circle.$jenis") as $kunci => $b) {
                ic_pilihan::create([
                    'jenis' => $jenis,
                    'kunci' => $kunci,
                    'label' => $b['label'],
                    'ikon' => $b['ikon'],
                    'sampul' => $b['sampul'] ?? null,
                    'warna' => $b['warna'],
                    'urutan' => ++$urutan,
                    'aktif' => true,
                ]);
                $jumlah++;
            }
        }

        return $jumlah;
    }

    /** Buang cache; dipanggil setiap pilihan diubah. */
    public function lupakan(): void
    {
        $this->memo = null;
        Cache::forget(self::CACHE);
    }

    /** @return array{tipe_event: array<string, array<string, mixed>>, kategori_kenangan: array<string, array<string, mixed>>} */
    private function semua(): array
    {
        if ($this->memo !== null) {
            return $this->memo;
        }

        try {
            $dariDb = Cache::rememberForever(self::CACHE, fn() => ic_pilihan::query()
                ->orderBy('urutan')->orderBy('id_pilihan')->get()
                ->groupBy('jenis')
                ->map(fn($g) => $g->mapWithKeys(fn(ic_pilihan $b) => [$b->kunci => [
                    'label' => $b->label,
                    'ikon' => $b->ikon,
                    'sampul' => $b->sampul,
                    'warna' => $b->warna,
                    'aktif' => $b->aktif,
                ]])->all())
                ->all());
        } catch (QueryException) {
            // Tabel belum ada (jeda singkat antara kode terpasang dan migrasi selesai saat deploy): pakai config.
            $dariDb = [];
        }

        $hasil = [];
        foreach ([ic_pilihan::TIPE_EVENT, ic_pilihan::KATEGORI_KENANGAN] as $jenis) {
            $hasil[$jenis] = ! empty($dariDb[$jenis]) ? $dariDb[$jenis] : $this->dariConfig($jenis);
        }

        return $this->memo = $hasil;
    }

    /** @return array<string, array<string, mixed>> */
    private function dariConfig(string $jenis): array
    {
        return array_map(fn($b) => $b + ['sampul' => null, 'aktif' => true], config("inner_circle.$jenis", []));
    }

    private function cari(string $jenis, string $kunci): ic_pilihan
    {
        return ic_pilihan::query()->where('jenis', $jenis)->where('kunci', $kunci)
            ->firstOr(fn() => throw new KontenGagal('Pilihan tidak ditemukan.'));
    }

    /** Kunci dari label: huruf kecil, angka, garis bawah (maks 30). Tidak boleh kembar atau memakai kata terlarang. */
    private function kunciBaru(string $label, array $dipakai): string
    {
        $dasar = Str::limit(Str::slug($label, '_'), 24, '');
        if ($dasar === '' || in_array($dasar, self::KUNCI_TERLARANG, true)) {
            $dasar = 'kategori';
        }

        $kunci = $dasar;
        for ($i = 2; in_array($kunci, $dipakai, true) || in_array($kunci, self::KUNCI_TERLARANG, true); $i++) {
            $kunci = $dasar . '_' . $i;
        }

        return $kunci;
    }

    private function rapikan(string $teks): string
    {
        return trim(preg_replace('/\s+/u', ' ', $teks) ?? '');
    }

    /** Beri nomor 1..n sesuai susunan; hanya yang berubah yang ditulis. @param  list<ic_pilihan>  $urut */
    private function rapatkan(array $urut): void
    {
        foreach (array_values($urut) as $i => $baris) {
            if ($baris->urutan !== $i + 1) {
                $baris->update(['urutan' => $i + 1]);
            }
        }
    }
}