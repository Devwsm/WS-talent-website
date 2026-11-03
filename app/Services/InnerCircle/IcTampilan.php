<?php

namespace App\Services\InnerCircle;

use App\Support\ImageProcessor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Tampilan dan merek Inner Circle yang bisa diubah dari CMS (tab Konten, bagian "Tampilan"): nama program, tiga warna
 * (aksen, latar, kartu), dan logo.
 *
 * Aturannya sama dengan teks lain: nilai disimpan di `ic_pengaturan` (kunci `tampilan.*`) HANYA bila berbeda dari bawaan,
 * jadi tabel kosong = tampilan persis seperti sebelum fitur ini ada, dan "kembalikan bawaan" cukup menghapus barisnya.
 * Nilai yang rusak (diubah manual di database) diabaikan dan bawaan yang dipakai.
 *
 * Keamanan: ke CSS hanya masuk warna #RRGGBB yang lolos regex (tidak pernah teks bebas), dan logo selalu diolah ulang
 * menjadi WebP oleh ImageProcessor (isi berkas dicek, metadata EXIF terbuang). Jalur logo yang tersimpan juga dicek
 * bentuknya sebelum dipakai atau dihapus, jadi baris yang diubah manual tidak bisa menunjuk berkas lain.
 */
class IcTampilan
{
    public const KUNCI_LOGO = 'tampilan.logo';
    public const KUNCI_PROGRAM = 'tampilan.program';

    public function __construct(
        private readonly IcKonten $konten,
        private readonly ImageProcessor $gambar,
    ) {}

    // ---------- Warna ----------

    /** Warna bawaan dari config. @return array{aksen: string, latar: string, kartu: string} */
    public function bawaan(): array
    {
        $hasil = [];
        foreach (config('ic_konten.tampilan.warna') as $nama => $def) {
            $hasil[$nama] = (string) $def['bawaan'];
        }

        return $hasil;
    }

    /** Warna yang berlaku sekarang: dari CMS bila valid, kalau tidak bawaan. @return array{aksen: string, latar: string, kartu: string} */
    public function warna(): array
    {
        $hasil = $this->bawaan();
        foreach (config('ic_konten.tampilan.warna') as $nama => $def) {
            $nilai = $this->konten->nilaiMentah($def['kunci']);
            if (self::hexValid($nilai)) {
                $hasil[$nama] = self::normalisasi((string) $nilai);
            }
        }

        return $hasil;
    }

    /** Nilai yang tersimpan per warna (null = memakai bawaan), untuk mengisi form. @return array<string, string|null> */
    public function warnaTersimpan(): array
    {
        $hasil = [];
        foreach (config('ic_konten.tampilan.warna') as $nama => $def) {
            $nilai = $this->konten->nilaiMentah($def['kunci']);
            $hasil[$nama] = self::hexValid($nilai) ? self::normalisasi((string) $nilai) : null;
        }

        return $hasil;
    }

    /** Warna kartu yang berlaku (untuk <meta name="theme-color">). */
    public function warnaKartu(): string
    {
        return $this->warna()['kartu'];
    }

    /** Ada warna yang diubah dari bawaan? */
    public function warnaKustom(): bool
    {
        return $this->warna() !== $this->bawaan();
    }

    /**
     * Aturan CSS yang menimpa token warna area anggota, atau '' bila semua warna masih bawaan (halaman tidak mendapat
     * <style> tambahan sama sekali). Aman ditulis mentah ke <style> karena isinya hanya nama variabel dari config dan
     * nilai #RRGGBB yang sudah divalidasi.
     */
    public function css(): string
    {
        $warna = $this->warna();
        $bawaan = $this->bawaan();
        $baris = [];

        foreach (config('ic_konten.tampilan.warna') as $nama => $def) {
            if ($warna[$nama] !== $bawaan[$nama]) {
                $baris[] = $def['css'] . ':' . $warna[$nama];
            }
        }

        if ($warna['aksen'] !== $bawaan['aksen']) {
            // Warna lembut turunan aksen (lencana, lingkaran bernomor). Teks di atasnya gelap dan selalu terbaca,
            // karena campuran 50% putih tidak pernah lebih gelap dari abu-abu tengah.
            $baris[] = '--ic-lilac:' . self::campur($warna['aksen'], '#ffffff', 0.5);
        }

        // body.ic-app (bukan .ic-app saja) supaya menang atas token bawaan di inner-circle.css apa pun urutan pemuatannya.
        return $baris === [] ? '' : 'body.ic-app{' . implode(';', $baris) . '}';
    }

    /**
     * Periksa kontras warna yang akan dipakai. Kembalinya daftar pesan galat (kosong = lolos).
     *
     * @param  array{aksen: string, latar: string, kartu: string}  $warna
     * @return list<string>
     */
    public function periksaKontras(array $warna): array
    {
        $batas = config('ic_konten.tampilan.kontras');
        $galat = [];

        foreach (config('ic_konten.tampilan.teks') as $def) {
            $rasio = self::rasio($def['warna'], $warna['kartu']);
            if ($rasio < $batas['teks']) {
                $galat[] = 'Warna kartu kurang kontras dengan ' . $def['label'] . ' (' . self::tampilRasio($rasio)
                    . ', minimal ' . self::tampilRasio($batas['teks']) . '). Pilih warna kartu yang lebih terang.';
            }
        }

        $rasioLatar = self::rasio(config('ic_konten.tampilan.teks.tinta.warna'), $warna['latar']);
        if ($rasioLatar < $batas['teks']) {
            $galat[] = 'Warna latar terlalu gelap untuk teks utama (' . self::tampilRasio($rasioLatar)
                . ', minimal ' . self::tampilRasio($batas['teks']) . '). Pilih warna latar yang lebih terang.';
        }

        $rasioAksen = self::rasio($warna['aksen'], $warna['kartu']);
        if ($rasioAksen < $batas['aksen']) {
            $galat[] = 'Warna aksen kurang kontras dengan warna kartu (' . self::tampilRasio($rasioAksen)
                . ', minimal ' . self::tampilRasio($batas['aksen']) . '). Pilih aksen yang lebih gelap atau kartu yang lebih terang.';
        }

        return $galat;
    }

    // ---------- Nama program dan logo ----------

    public function program(): string
    {
        return $this->konten->namaProgram();
    }

    public function programBawaan(): string
    {
        return (string) config('ic_konten.program');
    }

    /** Nilai nama program yang tersimpan (null = bawaan), untuk mengisi form. */
    public function programTersimpan(): ?string
    {
        return $this->konten->nilaiMentah(self::KUNCI_PROGRAM);
    }

    /** Alamat logo kustom, atau null bila belum ada (atau berkasnya hilang): pemanggil memakai logo bawaan. */
    public function logoUrl(): ?string
    {
        $path = $this->konten->nilaiMentah(self::KUNCI_LOGO);
        if ($path === null || ! $this->pathLogoValid($path)) {
            return null;
        }

        return Storage::disk('public')->exists($path) ? Storage::url($path) : null;
    }

    public function punyaLogoKustom(): bool
    {
        return $this->logoUrl() !== null;
    }

    // ---------- Simpan ----------

    /**
     * Simpan tampilan baru (sudah divalidasi). Nilai kosong atau sama dengan bawaan menghapus barisnya.
     *
     * @param  array{program?: string, aksen?: string, latar?: string, kartu?: string}  $nilai
     * @return int jumlah pengaturan yang benar-benar berubah
     *
     * @throws \App\Support\GambarGagal bila logo tidak bisa diolah (tidak ada yang disimpan)
     */
    public function simpan(array $nilai, ?UploadedFile $logo = null, bool $hapusLogo = false, ?string $oleh = null): int
    {
        $tulis = [];

        if (array_key_exists('program', $nilai)) {
            $program = $this->konten->rapikan((string) $nilai['program']);
            $tulis[self::KUNCI_PROGRAM] = ($program === '' || $program === $this->programBawaan()) ? null : $program;
        }

        $bawaan = $this->bawaan();
        foreach (config('ic_konten.tampilan.warna') as $nama => $def) {
            if (! array_key_exists($nama, $nilai)) {
                continue;
            }
            $hex = self::normalisasi(trim((string) $nilai[$nama]));
            $tulis[$def['kunci']] = ($hex === '' || $hex === $bawaan[$nama]) ? null : $hex;
        }

        $logoLama = $this->konten->nilaiMentah(self::KUNCI_LOGO);
        $logoBaru = null;

        if ($logo !== null) {
            // Diolah lebih dulu: bila gagal, GambarGagal keluar sebelum ada yang berubah.
            $logoBaru = $this->gambar->simpanWebp(
                $logo,
                (string) config('ic_konten.tampilan.logo_folder'),
                (int) config('ic_konten.tampilan.logo_sisi_maks'),
                90,
            );
            $tulis[self::KUNCI_LOGO] = $logoBaru;
        } elseif ($hapusLogo) {
            $tulis[self::KUNCI_LOGO] = null;
        }

        try {
            $berubah = DB::transaction(fn() => $this->konten->simpanMentah($tulis, $oleh));
        } catch (\Throwable $e) {
            if ($logoBaru !== null) {
                Storage::disk('public')->delete($logoBaru);
            }
            throw $e;
        }

        // Logo lama baru dihapus setelah semuanya tersimpan.
        if (($logoBaru !== null || $hapusLogo) && $logoLama !== null && $logoLama !== $logoBaru) {
            $this->hapusBerkasLogo($logoLama);
        }

        return $berubah;
    }

    /** Kembalikan nama program, warna, dan logo ke bawaan. @return int jumlah pengaturan yang berubah */
    public function kembalikanBawaan(?string $oleh = null): int
    {
        $kunci = [self::KUNCI_PROGRAM, self::KUNCI_LOGO];
        foreach (config('ic_konten.tampilan.warna') as $def) {
            $kunci[] = $def['kunci'];
        }

        $logoLama = $this->konten->nilaiMentah(self::KUNCI_LOGO);
        $berubah = DB::transaction(fn() => $this->konten->simpanMentah(array_fill_keys($kunci, null), $oleh));

        if ($logoLama !== null) {
            $this->hapusBerkasLogo($logoLama);
        }

        return $berubah;
    }

    // ---------- Warna: hitungan ----------

    public static function hexValid(?string $nilai): bool
    {
        return $nilai !== null && preg_match('/^#[0-9a-fA-F]{6}$/', $nilai) === 1;
    }

    public static function normalisasi(string $hex): string
    {
        return strtolower($hex);
    }

    /** Rasio kontras WCAG antara dua warna #RRGGBB (1 sampai 21). */
    public static function rasio(string $a, string $b): float
    {
        $la = self::luminans($a);
        $lb = self::luminans($b);

        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    /** "4,5:1" (dibulatkan ke bawah satu desimal, supaya 4,46 tidak tampil seolah lolos 4,5). */
    public static function tampilRasio(float $rasio): string
    {
        return number_format(floor($rasio * 10) / 10, 1, ',', '') . ':1';
    }

    /** Campur $hex dengan $dengan; $bagian = porsi $dengan (0 sampai 1). */
    public static function campur(string $hex, string $dengan, float $bagian): string
    {
        $a = self::saluran($hex);
        $b = self::saluran($dengan);
        $hasil = '#';
        foreach ([0, 1, 2] as $i) {
            $hasil .= sprintf('%02x', (int) round($a[$i] + ($b[$i] - $a[$i]) * $bagian));
        }

        return $hasil;
    }

    /** @return array{0: int, 1: int, 2: int} */
    private static function saluran(string $hex): array
    {
        return [hexdec(substr($hex, 1, 2)), hexdec(substr($hex, 3, 2)), hexdec(substr($hex, 5, 2))];
    }

    private static function luminans(string $hex): float
    {
        [$r, $g, $b] = array_map(function (int $v): float {
            $c = $v / 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, self::saluran($hex));

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    // ---------- Berkas logo ----------

    /** Hanya jalur buatan sistem sendiri (folder logo + UUID + .webp) yang dianggap logo. */
    private function pathLogoValid(string $path): bool
    {
        $folder = preg_quote(trim((string) config('ic_konten.tampilan.logo_folder'), '/'), '#');

        return preg_match('#^' . $folder . '/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\.webp$#', $path) === 1;
    }

    private function hapusBerkasLogo(string $path): void
    {
        if ($this->pathLogoValid($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}