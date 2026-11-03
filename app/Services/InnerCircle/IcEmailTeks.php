<?php

namespace App\Services\InnerCircle;

/**
 * Subjek dan paragraf pembuka email Inner Circle yang bisa diubah dari CMS (tab Email).
 *
 * Sama seperti teks lain: nilai ada di `ic_pengaturan` (kunci `email.<jenis>.<bagian>`) hanya bila beda dari bawaan di
 * config/ic_konten.php, jadi tabel kosong = email persis seperti sebelumnya. Placeholder diganti dalam satu putaran
 * (strtr), jadi isi nama anggota yang kebetulan berbentuk `{kode}` tidak diproses lagi. Hasilnya TEKS BIASA: template
 * email menampilkannya dengan {{ }} (di-escape). Tautan, token, dan tombol tidak lewat sini.
 */
class IcEmailTeks
{
    public function __construct(private readonly IcKonten $konten) {}

    /** @return array<string, mixed>|null definisi satu jenis (placeholder, subjek, pembuka) */
    public function definisi(string $jenis): ?array
    {
        return config("ic_konten.email.$jenis");
    }

    public static function kunci(string $jenis, string $bagian): string
    {
        return "email.$jenis.$bagian";
    }

    /** Teks yang berlaku untuk satu bagian. $isi mengisi placeholder yang diizinkan jenis itu. */
    public function teks(string $jenis, string $bagian, array $isi = []): string
    {
        $def = $this->definisi($jenis)[$bagian] ?? null;
        if ($def === null) {
            if (app()->environment('local', 'testing')) {
                throw new \InvalidArgumentException("Teks email tidak terdaftar: $jenis.$bagian");
            }

            return '';
        }

        $nilai = $this->konten->nilaiMentah(self::kunci($jenis, $bagian));
        $teks = $nilai ?? (string) $def['bawaan'];

        $ganti = ['{program}' => $this->konten->namaProgram()];
        foreach ($this->definisi($jenis)['placeholder'] ?? [] as $nama) {
            if (array_key_exists($nama, $isi)) {
                // Satu baris saja: nilai dari data anggota tidak boleh menyisipkan baris baru (terutama ke subjek).
                $ganti['{' . $nama . '}'] = trim((string) preg_replace('/\s+/u', ' ', (string) $isi[$nama]));
            }
        }

        return strtr($teks, $ganti);
    }

    /** Nilai tersimpan per bagian (null = bawaan), untuk mengisi form. @return array<string, string|null> */
    public function tersimpan(string $jenis): array
    {
        $hasil = [];
        foreach (['subjek', 'pembuka'] as $bagian) {
            $hasil[$bagian] = $this->konten->nilaiMentah(self::kunci($jenis, $bagian));
        }

        return $hasil;
    }

    /**
     * @param  array{subjek?: ?string, pembuka?: ?string}  $nilai
     * @return int jumlah bagian yang benar-benar berubah
     */
    public function simpan(string $jenis, array $nilai, ?string $oleh = null): int
    {
        $tulis = [];
        foreach (['subjek', 'pembuka'] as $bagian) {
            if (! array_key_exists($bagian, $nilai)) {
                continue;
            }
            $baru = $this->konten->rapikan((string) $nilai[$bagian]);
            $tulis[self::kunci($jenis, $bagian)] = ($baru === '' || $baru === (string) $this->definisi($jenis)[$bagian]['bawaan']) ? null : $baru;
        }

        return $this->konten->simpanMentah($tulis, $oleh);
    }

    public function kembalikan(string $jenis, ?string $oleh = null): int
    {
        return $this->konten->simpanMentah([
            self::kunci($jenis, 'subjek') => null,
            self::kunci($jenis, 'pembuka') => null,
        ], $oleh);
    }
}