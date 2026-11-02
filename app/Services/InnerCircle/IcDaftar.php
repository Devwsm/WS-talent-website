<?php

namespace App\Services\InnerCircle;

use App\Models\ic_item;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Pengelolaan daftar berurutan di tab Konten (langkah "Cara kerjanya", FAQ, poin teaser): tambah, ubah, urutkan,
 * tampil/sembunyikan, hapus, dan kembalikan ke daftar bawaan. Definisi tiap daftar ada di config/ic_konten.php (`daftar`).
 *
 * Nomor `urutan` selalu dirapatkan 1..n setelah tiap perubahan, jadi tidak ada lubang atau nomor kembar. Setiap
 * perubahan membuang cache IcKonten supaya halaman anggota dan publik langsung memakai daftar terbaru.
 */
class IcDaftar
{
    public function __construct(private readonly IcKonten $konten) {}

    /** @return array<string, array<string, mixed>> */
    public function definisi(): array
    {
        return config('ic_konten.daftar', []);
    }

    /** @return array<string, mixed>|null */
    public function def(string $grup): ?array
    {
        return $this->definisi()[$grup] ?? null;
    }

    /**
     * Semua butir satu daftar (termasuk yang disembunyikan), urut.
     *
     * @return Collection<int, ic_item>
     */
    public function semua(string $grup): Collection
    {
        return ic_item::query()->where('grup', $grup)->orderBy('urutan')->orderBy('id_item')->get();
    }

    /** @param  array{judul: string, isi?: ?string, ikon?: ?string}  $data */
    public function tambah(string $grup, array $data): ic_item
    {
        $def = $this->wajibDef($grup);

        $butir = DB::transaction(function () use ($grup, $def, $data) {
            $jumlah = ic_item::query()->where('grup', $grup)->lockForUpdate()->count();
            if ($jumlah >= (int) $def['maks_item']) {
                throw new KontenGagal("Daftar \"{$def['label']}\" sudah penuh (maksimal {$def['maks_item']} {$def['satuan']}). Hapus satu dulu.");
            }

            return ic_item::create($this->kolom($def, $data) + ['grup' => $grup, 'urutan' => $jumlah + 1, 'aktif' => true]);
        });

        $this->konten->lupakan();

        return $butir;
    }

    /** @param  array{judul: string, isi?: ?string, ikon?: ?string}  $data */
    public function ubah(string $grup, int $id, array $data): ic_item
    {
        $def = $this->wajibDef($grup);
        $butir = $this->cari($grup, $id);

        $butir->update($this->kolom($def, $data));
        $this->konten->lupakan();

        return $butir;
    }

    /** Geser satu langkah ke atas ("naik") atau ke bawah ("turun"). Mengembalikan false bila sudah di ujung. */
    public function geser(string $grup, int $id, string $arah): bool
    {
        $this->wajibDef($grup);

        $bergeser = DB::transaction(function () use ($grup, $id, $arah) {
            $urut = ic_item::query()->where('grup', $grup)->orderBy('urutan')->orderBy('id_item')->lockForUpdate()->get()->values();
            $posisi = $urut->search(fn(ic_item $b) => (int) $b->id_item === $id);
            if ($posisi === false) {
                throw new KontenGagal('Butir tidak ditemukan.');
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

        $this->konten->lupakan();

        return $bergeser;
    }

    public function aktifkan(string $grup, int $id, bool $aktif): ic_item
    {
        $this->wajibDef($grup);
        $butir = $this->cari($grup, $id);

        $butir->update(['aktif' => $aktif]);
        $this->konten->lupakan();

        return $butir;
    }

    public function hapus(string $grup, int $id): void
    {
        $this->wajibDef($grup);

        DB::transaction(function () use ($grup, $id) {
            $this->cari($grup, $id)->delete();
            $this->rapatkan(ic_item::query()->where('grup', $grup)->orderBy('urutan')->orderBy('id_item')->lockForUpdate()->get()->all());
        });

        $this->konten->lupakan();
    }

    /** Ganti seluruh isi daftar dengan isi awal dari config. Mengembalikan jumlah butir hasilnya. */
    public function kembalikanBawaan(string $grup): int
    {
        $def = $this->wajibDef($grup);

        DB::transaction(function () use ($grup, $def) {
            ic_item::query()->where('grup', $grup)->delete();
            $this->tanam($grup, $def);
        });

        $this->konten->lupakan();

        return count($def['bawaan']);
    }

    /**
     * Tanam isi awal untuk setiap daftar yang belum punya butir sama sekali (dipakai migrasi). Daftar yang sudah punya
     * isi tidak disentuh. Mengembalikan jumlah butir yang ditanam. Tidak menyentuh cache: migrasi yang memanggilnya
     * membuang cache sendiri, karena tabel cache bisa belum ada pada pemasangan pertama.
     */
    public function tanamBawaanBilaKosong(): int
    {
        $jumlah = 0;
        foreach ($this->definisi() as $grup => $def) {
            if (! ic_item::query()->where('grup', $grup)->exists()) {
                $jumlah += $this->tanam($grup, $def);
            }
        }

        return $jumlah;
    }

    /** @param  array<string, mixed>  $def */
    private function tanam(string $grup, array $def): int
    {
        foreach (array_values($def['bawaan']) as $i => $butir) {
            ic_item::create([
                'grup' => $grup,
                'urutan' => $i + 1,
                'judul' => $butir['judul'],
                'isi' => $butir['isi'] ?? null,
                'ikon' => $butir['ikon'] ?? null,
                'aktif' => true,
            ]);
        }

        return count($def['bawaan']);
    }

    /** Beri nomor 1..n sesuai susunan; hanya yang berubah yang ditulis. @param  list<ic_item>  $urut */
    private function rapatkan(array $urut): void
    {
        foreach (array_values($urut) as $i => $butir) {
            if ($butir->urutan !== $i + 1) {
                $butir->update(['urutan' => $i + 1]);
            }
        }
    }

    /** Hanya kolom yang dipakai daftar ini yang disimpan (daftar tanpa isi/ikon tidak menyimpan keduanya). */
    private function kolom(array $def, array $data): array
    {
        return [
            'judul' => $this->konten->rapikan((string) $data['judul']),
            'isi' => ($def['isi'] ?? null) ? $this->konten->rapikan((string) ($data['isi'] ?? ''), ! empty($def['isi']['baris'])) : null,
            'ikon' => ! empty($def['ikon']) ? (string) ($data['ikon'] ?? '') : null,
        ];
    }

    private function cari(string $grup, int $id): ic_item
    {
        return ic_item::query()->where('grup', $grup)->where('id_item', $id)->firstOr(fn() => throw new KontenGagal('Butir tidak ditemukan.'));
    }

    /** @return array<string, mixed> */
    private function wajibDef(string $grup): array
    {
        return $this->def($grup) ?? throw new KontenGagal('Daftar tidak dikenal.');
    }
}