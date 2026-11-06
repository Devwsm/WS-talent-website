<?php

namespace App\Services\InnerCircle;

/**
 * Daftar bagian tab Konten di CMS Inner Circle.
 *
 * Tab Konten dulu satu halaman panjang; sekarang tiap topik punya halamannya sendiri
 * (`/dashboard/inner-circle/konten/bagian/{slug}`); `/konten` langsung membuka bagian pertama. Pindah bagian lewat navbar atas. Kelas ini satu-satunya
 * tempat yang tahu daftar bagian, urutannya, dan alamatnya, supaya controller, form request, hub, dan tes memakai sumber yang sama.
 *
 * Ada dua jenis bagian: `grup` (teks halaman dari config/ic_konten.php, `kode` = kunci grupnya) dan `khusus`
 * (pilihan tipe/kategori, aturan check-in, tampilan, kebijakan privasi: tiap bagian punya komponen sendiri).
 */
class IcBagian
{
    /** Urutan bagian di navbar atas (per kelompok). Grup teks yang belum terdaftar di sini ditaruh di paling akhir. */
    private const KELOMPOK = [
        'publik' => ['label' => 'Halaman publik', 'isi' => ['tentang', 'teaser', 'masuk']],
        'anggota' => ['label' => 'Halaman anggota', 'isi' => ['beranda', 'checkin', 'hadiah', 'komunitas', 'profil']],
        'pilihan' => ['label' => 'Pilihan dan aturan', 'isi' => ['tipe-acara', 'kategori-kenangan', 'aturan-checkin']],
        'tampilan' => ['label' => 'Tampilan dan kebijakan', 'isi' => ['tampilan', 'privasi']],
    ];

    private const KHUSUS = [
        'tipe-acara' => ['label' => 'Tipe acara', 'ikon' => 'bi-calendar-event', 'petunjuk' => 'Nama, ikon, dan warna kartu tiap tipe acara.'],
        'kategori-kenangan' => ['label' => 'Kategori kenangan', 'ikon' => 'bi-images', 'petunjuk' => 'Kategori yang dipilih anggota saat mengunggah kenangan.'],
        'aturan-checkin' => ['label' => 'Aturan check-in', 'ikon' => 'bi-sliders', 'petunjuk' => 'Jam jendela, radius, dan akurasi GPS bawaan untuk acara baru.'],
        'tampilan' => ['label' => 'Tampilan dan merek', 'ikon' => 'bi-palette', 'petunjuk' => 'Nama program, tiga warna, dan logo area anggota.'],
        'privasi' => ['label' => 'Kebijakan privasi', 'ikon' => 'bi-shield-check', 'petunjuk' => 'Isi halaman /privasi yang juga dipakai layar izin Google.'],
    ];

    /**
     * Semua bagian menurut urutan tampil.
     *
     * @return array<string, array{slug: string, jenis: string, kode: string, label: string, ikon: string, petunjuk: string, kelompok: string}>
     */
    public static function semua(): array
    {
        $grup = (array) config('ic_konten.grup', []);
        $semua = [];

        foreach (self::KELOMPOK as $kelompok => $def) {
            foreach ($def['isi'] as $slug) {
                if (isset(self::KHUSUS[$slug])) {
                    $semua[$slug] = self::bagianKhusus($slug, $kelompok);
                } elseif (isset($grup[$slug])) {
                    $semua[$slug] = self::bagianGrup($slug, $grup[$slug], $kelompok);
                }
            }
        }

        foreach ($grup as $kode => $info) {
            $semua[$kode] ??= self::bagianGrup($kode, $info, 'lainnya');
        }

        return $semua;
    }

    /**
     * Bagian-bagian dikelompokkan untuk navbar atas: kelompok => label dan daftar bagiannya (menurut urutan tampil).
     * Grup teks yang belum terdaftar di KELOMPOK masuk kelompok "Lainnya".
     *
     * @return array<string, array{label: string, bagian: array<string, array<string, mixed>>}>
     */
    public static function berkelompok(): array
    {
        $hasil = [];
        foreach (self::KELOMPOK as $kode => $def) {
            $hasil[$kode] = ['label' => $def['label'], 'bagian' => []];
        }

        foreach (self::semua() as $slug => $b) {
            $kode = $b['kelompok'];
            $hasil[$kode] ??= ['label' => 'Lainnya', 'bagian' => []];
            $hasil[$kode]['bagian'][$slug] = $b;
        }

        return array_filter($hasil, fn($k) => $k['bagian'] !== []);
    }

    /** @return array<string, mixed>|null */
    public static function cari(string $slug): ?array
    {
        return self::semua()[$slug] ?? null;
    }

    /**
     * Modul sebuah grup teks (lihat config/ic_konten_modul.php): formulir kecil + pratinjau replika masing-masing.
     * Tiap modul dilengkapi `slug` dan `fokus` (kunci teks yang ditampilkan di pratinjau bila modul tidak punya form; bawaannya kunci pertama).
     *
     * @return array<string, array{slug: string, label: string, ikon: string, kunci: list<string>, daftar: ?string, fokus: ?string}>
     */
    public static function modul(string $grup): array
    {
        $hasil = [];
        foreach ((array) config("ic_konten_modul.$grup", []) as $slug => $def) {
            $hasil[$slug] = [
                'slug' => $slug,
                'label' => (string) $def['label'],
                'ikon' => (string) ($def['ikon'] ?? 'bi-pencil'),
                'kunci' => array_values($def['kunci'] ?? []),
                'daftar' => $def['daftar'] ?? null,
                'fokus' => $def['fokus'] ?? ($def['kunci'][0] ?? null),
            ];
        }

        return $hasil;
    }

    /** Alamat satu bagian, dengan jangkar (#...) bila perlu. */
    public static function url(string $slug, ?string $jangkar = null): string
    {
        return route('ic.admin.konten.bagian', $slug) . ($jangkar !== null && $jangkar !== '' ? '#' . $jangkar : '');
    }

    /** Bagian tempat sebuah daftar berurutan (langkah, FAQ, poin teaser) berada. */
    public static function untukDaftar(string $daftar): string
    {
        foreach ((array) config('ic_konten.grup', []) as $kode => $info) {
            if (in_array($daftar, $info['daftar'] ?? [], true)) {
                return $kode;
            }
        }

        return array_key_first(self::semua()) ?? 'tentang';
    }

    /** Bagian untuk jenis pilihan (`tipe` atau `kategori`). */
    public static function untukPilihan(string $jenis): string
    {
        return $jenis === 'kategori' ? 'kategori-kenangan' : 'tipe-acara';
    }

    /**
     * Peta jangkar lama (#grup-x, #daftar-x, #pilihan-tipe, ...) ke alamat bagian baru. Dipakai halaman pilihan
     * untuk mengalihkan tautan lama yang masih tersimpan di bookmark atau catatan.
     *
     * @return array<string, string>
     */
    public static function petaJangkarLama(): array
    {
        $peta = [
            'pilihan-tipe' => self::url('tipe-acara'),
            'pilihan-kategori' => self::url('kategori-kenangan'),
            'aturan-checkin' => self::url('aturan-checkin'),
            'tampilan' => self::url('tampilan'),
            'privasi' => self::url('privasi'),
        ];

        foreach ((array) config('ic_konten.grup', []) as $kode => $info) {
            $peta['grup-' . $kode] = self::url($kode);
            foreach ($info['daftar'] ?? [] as $daftar) {
                $peta['daftar-' . $daftar] = self::url($kode, 'daftar-' . $daftar);
            }
        }

        return $peta;
    }

    /** @return array{slug: string, jenis: string, kode: string, label: string, ikon: string, petunjuk: string, kelompok: string} */
    private static function bagianKhusus(string $slug, string $kelompok): array
    {
        return ['slug' => $slug, 'jenis' => 'khusus', 'kode' => $slug, 'kelompok' => $kelompok] + self::KHUSUS[$slug];
    }

    /** @return array{slug: string, jenis: string, kode: string, label: string, ikon: string, petunjuk: string, kelompok: string} */
    private static function bagianGrup(string $kode, array $info, string $kelompok): array
    {
        return [
            'slug' => $kode,
            'jenis' => 'grup',
            'kode' => $kode,
            'label' => (string) ($info['label'] ?? $kode),
            'ikon' => (string) ($info['ikon'] ?? 'bi-file-text'),
            'petunjuk' => (string) ($info['petunjuk'] ?? ''),
            'kelompok' => $kelompok,
        ];
    }
}