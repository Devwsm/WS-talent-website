<?php

namespace App\Services\InnerCircle;

use App\Models\ic_item;
use App\Models\ic_pengaturan;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;

/**
 * Konten Inner Circle yang bisa diubah dari CMS.
 *
 * Aturan dasarnya: teks yang tampil = isi tabel `ic_pengaturan` bila ada, kalau tidak teks bawaan dari
 * config/ic_konten.php. Jadi tabel kosong = tampilan persis seperti sebelum fitur ini ada, dan menghapus baris
 * ("kembalikan bawaan") selalu aman. Hasil di-cache dan cache dibuang tiap kali konten disimpan.
 *
 * Isi yang dikembalikan adalah TEKS BIASA: view wajib menampilkannya dengan {{ }} (di-escape), tidak pernah {!! !!}.
 * Teks yang perlu baris baru atau tautan resmi memakai html()/htmlDaftar(): hasilnya HtmlString yang SUDAH di-escape,
 * jadi tetap ditulis {{ ic_html('kunci') }} di view.
 */
class IcKonten
{
    private const CACHE_PENGATURAN = 'ic_konten.pengaturan';
    private const CACHE_ITEM = 'ic_konten.item';

    /** Salinan per permintaan, supaya satu halaman dengan puluhan teks tidak membaca cache berulang kali. */
    private ?array $memoPengaturan = null;
    private ?array $memoItem = null;

    /** Nilai draf pratinjau CMS (belum disimpan). Bila terisi, menggantikan isi tabel untuk permintaan ini saja. */
    private ?array $draf = null;

    /** Pratinjau potongan: bila true, tiap teks diberi penanda tak terlihat berisi kuncinya (lihat PratinjauPotongan). */
    private bool $penanda = false;

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

        $hasil = $this->isiPlaceholder($teks, $def['placeholder'] ?? [], $isi);

        return $this->penanda ? PratinjauPotongan::AWAL . $kunci . PratinjauPotongan::AKHIR . $hasil : $hasil;
    }

    /**
     * Teks satu kunci sebagai HTML aman: seluruh isi di-escape, baris baru menjadi <br>, dan {nama_tautan} diganti tautan
     * resmi (label tetap, alamat dari route; lihat `tautan` di config/ic_konten.php). Admin tidak pernah bisa menyisipkan HTML.
     */
    public function html(string $kunci, array $isi = []): HtmlString
    {
        $def = $this->definisiKunci($kunci);

        return $this->susunHtml($this->teks($kunci, $isi), $def['tautan'] ?? [], ! empty($def['baris']));
    }

    /** Sama seperti html(), untuk isi butir daftar (mis. jawaban FAQ) menurut aturan grup daftarnya. */
    public function htmlDaftar(string $grup, ?string $teks): HtmlString
    {
        $def = config("ic_konten.daftar.$grup") ?? [];

        return $this->susunHtml((string) $teks, $def['tautan'] ?? [], ! empty($def['isi']['baris']));
    }

    /** @param  list<string>  $tautan nama tautan yang boleh muncul di teks ini */
    private function susunHtml(string $teks, array $tautan, bool $banyakBaris): HtmlString
    {
        $html = e($teks);
        if ($banyakBaris) {
            $html = preg_replace('/\r?\n/', '<br>', $html) ?? $html;
        }

        $ganti = [];
        foreach ($tautan as $nama) {
            $def = config("ic_konten.tautan.$nama");
            if (! $def) {
                continue;
            }
            $ganti['{' . $nama . '}'] = '<a class="ic-tautan" href="' . e(route($def['route'])) . '"'
                . (! empty($def['baru']) ? ' target="_blank" rel="noopener"' : '')
                . '>' . e($def['label']) . '</a>';
        }

        return new HtmlString($ganti ? strtr($html, $ganti) : $html);
    }

    /**
     * Pakai isian form yang BELUM disimpan sebagai pengganti sementara, hanya selama permintaan ini (pratinjau CMS).
     * Tidak menulis ke database maupun cache. Isian kosong berarti teks bawaan, sama seperti saat disimpan.
     *
     * @param  array<string, mixed>  $nilai  nama kolom form (kunci dengan "." diganti "__") => teks
     */
    public function pakaiDraf(array $nilai): void
    {
        $dasar = $this->tersimpan();

        foreach ($this->definisi() as $kunci => $def) {
            $kolom = str_replace('.', '__', $kunci);
            if (! array_key_exists($kolom, $nilai)) {
                continue;
            }

            $teks = mb_substr($this->rapikan(is_string($nilai[$kolom]) ? $nilai[$kolom] : '', ! empty($def['baris'])), 0, (int) $def['maks']);

            if ($teks === '') {
                unset($dasar[$kunci]);
            } else {
                $dasar[$kunci] = $teks;
            }
        }

        // Pengaturan tampilan (warna, nama program) ikut dipratinjau; nilai yang tidak valid dibuang oleh IcTampilan saat dibaca.
        foreach ((array) config('ic_konten.tampilan.kunci', []) as $kunci => $def) {
            $kolom = str_replace('.', '__', $kunci);
            if (! array_key_exists($kolom, $nilai)) {
                continue;
            }

            $teks = mb_substr($this->rapikan(is_string($nilai[$kolom]) ? $nilai[$kolom] : ''), 0, 40);
            if ($teks === '') {
                unset($dasar[$kunci]);
            } else {
                $dasar[$kunci] = $teks;
            }
        }

        $this->draf = $dasar;
    }

    /** Hentikan pemakaian draf (kembali ke isi database). */
    public function lupakanDraf(): void
    {
        $this->draf = null;
        $this->penanda = false;
    }

    /** Nyalakan penanda kunci di depan tiap teks, hanya untuk permintaan pratinjau potongan (dimatikan oleh lupakanDraf). */
    public function pakaiPenanda(bool $aktif = true): void
    {
        $this->penanda = $aktif;
    }

    /**
     * Nilai mentah satu pengaturan di luar daftar teks (mis. `aturan.radius_bawaan_m`, `tampilan.aksen`), atau null bila
     * tidak ada. Dipakai IcAturan dan IcTampilan; isinya bukan teks tampil, jadi pemanggil wajib memeriksanya sendiri.
     */
    public function nilaiMentah(string $kunci): ?string
    {
        $nilai = $this->pengaturan()[$kunci] ?? null;

        return ($nilai !== null && trim((string) $nilai) !== '') ? (string) $nilai : null;
    }

    /**
     * Simpan atau hapus beberapa nilai mentah sekaligus: null berarti hapus (kembali ke bawaan).
     *
     * @param  array<string, string|null>  $nilai
     * @return int jumlah nilai yang benar-benar berubah
     */
    public function simpanMentah(array $nilai, ?string $oleh = null): int
    {
        $berubah = 0;

        foreach ($nilai as $kunci => $baru) {
            $lama = $this->nilaiMentah($kunci);

            if ($baru === null) {
                if ($lama !== null) {
                    ic_pengaturan::query()->where('kunci', $kunci)->delete();
                    $berubah++;
                }
            } elseif ($baru !== $lama) {
                ic_pengaturan::query()->updateOrCreate(['kunci' => $kunci], ['nilai' => $baru, 'diubah_oleh' => $oleh]);
                $berubah++;
            }
        }

        $this->lupakan();

        return $berubah;
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
        $ganti = ['{program}' => $this->namaProgram()];
        foreach ($diizinkan as $nama) {
            if (array_key_exists($nama, $isi)) {
                $ganti['{' . $nama . '}'] = (string) $isi[$nama];
            }
        }

        return strtr($teks, $ganti);
    }

    /** Nama program yang dipakai placeholder {program}: dari CMS (Tampilan) bila diisi, kalau tidak dari config. */
    public function namaProgram(): string
    {
        return $this->nilaiMentah('tampilan.program') ?? (string) config('ic_konten.program');
    }

    /** @return array<string, string|null> */
    private function pengaturan(): array
    {
        if ($this->draf !== null) {
            return $this->draf;
        }

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