<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Olah foto unggahan anggota Inner Circle: terima JPG/PNG/WebP, perbaiki orientasi EXIF (foto HP yang
 * miring), kecilkan, lalu simpan ulang sebagai WebP. Karena gambarnya di-encode ulang, metadata EXIF
 * (termasuk lokasi GPS) ikut terbuang.
 *
 * Berbeda dari `convertToWebP()` lama di profileController (private, hanya JPEG/PNG), kelas ini menentukan
 * format dari ISI berkas (bukan nama atau tipe kiriman browser) dan menjaga memori: hosting bersama sering
 * memberi 128 MB, dan decode foto 48 MP butuh lebih dari itu.
 *
 * Tidak static supaya bisa diganti di tes (container: app(ImageProcessor::class)).
 */
class ImageProcessor
{
    /** Di atas ini ditolak sebelum didecode (pengaman bom dekompresi), apa pun sisa memorinya. */
    private const MAKS_PIKSEL = 40_000_000;

    /** Decode butuh 4 byte per piksel; ditambah ruang untuk salinan hasil kecil dan overhead. */
    private const FAKTOR_MEMORI = 1.4;
    private const CADANGAN_MEMORI = 8 * 1024 * 1024;

    /**
     * Baca gambar di $sumber, kembalikan isi WebP-nya (belum disimpan ke mana pun).
     *
     * @param  int  $sisiMaks  sisi terpanjang hasil dalam piksel; gambar yang lebih kecil tidak diperbesar
     *
     * @throws GambarGagal
     */
    public function keWebp(string $sumber, int $sisiMaks, int $kualitas = 82): string
    {
        if (! extension_loaded('gd') || ! function_exists('imagewebp')) {
            throw new GambarGagal('Server belum bisa memproses foto (ekstensi GD dengan dukungan WebP belum aktif). Hubungi tim WSM.');
        }

        $info = @getimagesize($sumber);
        if ($info === false) {
            throw new GambarGagal('Berkas ini bukan gambar yang valid. Pilih foto JPG, PNG, atau WebP.');
        }

        [$lebar, $tinggi, $tipe] = $info;
        if (! in_array($tipe, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            throw new GambarGagal('Format foto tidak didukung. Pilih foto JPG, PNG, atau WebP.');
        }
        if ($lebar < 1 || $tinggi < 1) {
            throw new GambarGagal('Berkas ini bukan gambar yang valid. Pilih foto JPG, PNG, atau WebP.');
        }
        if ($lebar * $tinggi > self::MAKS_PIKSEL || ! $this->memoriCukup($lebar, $tinggi)) {
            throw new GambarGagal('Resolusi foto terlalu besar untuk diproses server. Kecilkan foto lalu coba lagi.');
        }

        $gambar = match ($tipe) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($sumber),
            IMAGETYPE_PNG => @imagecreatefrompng($sumber),
            IMAGETYPE_WEBP => @imagecreatefromwebp($sumber),
        };
        if ($gambar === false) {
            throw new GambarGagal('Foto tidak bisa dibaca. Coba foto lain (WebP animasi belum didukung).');
        }

        // Kecilkan dulu, putar kemudian: puncak memori hanya satu salinan penuh. Kotak batasnya persegi,
        // jadi urutan ini tidak mengubah ukuran akhir.
        $gambar = $this->perkecil($gambar, $lebar, $tinggi, $sisiMaks);
        if ($tipe === IMAGETYPE_JPEG) {
            $gambar = $this->luruskan($gambar, $this->orientasiExif($sumber));
        }

        if (! imageistruecolor($gambar)) {
            imagepalettetotruecolor($gambar);
        }
        imagealphablending($gambar, false);
        imagesavealpha($gambar, true);

        ob_start();
        $berhasil = imagewebp($gambar, null, max(1, min(100, $kualitas)));
        $isi = (string) ob_get_clean();

        if (! $berhasil || $isi === '') {
            throw new GambarGagal('Foto gagal diolah. Coba foto lain.');
        }

        return $isi;
    }

    /**
     * Olah unggahan lalu simpan di disk `public` dengan nama acak; kembalikan path relatifnya.
     *
     * @throws GambarGagal
     */
    public function simpanWebp(UploadedFile $berkas, string $folder, int $sisiMaks, int $kualitas = 82): string
    {
        $isi = $this->keWebp((string) $berkas->getRealPath(), $sisiMaks, $kualitas);
        $path = trim($folder, '/') . '/' . Str::uuid()->toString() . '.webp';

        Storage::disk('public')->put($path, $isi);

        return $path;
    }

    private function perkecil(\GdImage $gambar, int $lebar, int $tinggi, int $sisiMaks): \GdImage
    {
        $terpanjang = max($lebar, $tinggi);
        if ($sisiMaks < 1 || $terpanjang <= $sisiMaks) {
            return $gambar;
        }

        $skala = $sisiMaks / $terpanjang;
        $lebarBaru = max(1, (int) round($lebar * $skala));
        $tinggiBaru = max(1, (int) round($tinggi * $skala));

        $baru = imagecreatetruecolor($lebarBaru, $tinggiBaru);
        imagealphablending($baru, false);
        imagesavealpha($baru, true);
        imagefill($baru, 0, 0, imagecolorallocatealpha($baru, 0, 0, 0, 127));
        imagecopyresampled($baru, $gambar, 0, 0, 0, 0, $lebarBaru, $tinggiBaru, $lebar, $tinggi);

        return $baru;
    }

    private function orientasiExif(string $sumber): int
    {
        if (! function_exists('exif_read_data')) {
            return 1;
        }

        $exif = @exif_read_data($sumber);

        return is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;
    }

    /**
     * Terapkan orientasi EXIF 1 sampai 8. imagerotate() memutar BERLAWANAN arah jarum jam untuk sudut positif,
     * jadi "putar 90 searah jarum jam" (EXIF 6) adalah -90.
     */
    private function luruskan(\GdImage $gambar, int $orientasi): \GdImage
    {
        $putar = static function (\GdImage $g, int $sudut): \GdImage {
            $hasil = imagerotate($g, $sudut, imagecolorallocatealpha($g, 0, 0, 0, 127));

            return $hasil === false ? $g : $hasil;
        };

        switch ($orientasi) {
            case 2: // cermin horizontal
                imageflip($gambar, IMG_FLIP_HORIZONTAL);

                return $gambar;
            case 3: // putar 180
                return $putar($gambar, 180);
            case 4: // cermin vertikal
                imageflip($gambar, IMG_FLIP_VERTICAL);

                return $gambar;
            case 5: // transpos: cermin horizontal, lalu 90 berlawanan jarum jam
                imageflip($gambar, IMG_FLIP_HORIZONTAL);

                return $putar($gambar, 90);
            case 6: // 90 searah jarum jam (foto HP potret yang paling umum)
                return $putar($gambar, -90);
            case 7: // cermin horizontal, lalu 90 searah jarum jam
                imageflip($gambar, IMG_FLIP_HORIZONTAL);

                return $putar($gambar, -90);
            case 8: // 90 berlawanan jarum jam
                return $putar($gambar, 90);
            default:
                return $gambar;
        }
    }

    private function memoriCukup(int $lebar, int $tinggi): bool
    {
        $batas = $this->batasMemori();
        if ($batas <= 0) {
            return true; // memory_limit = -1 (tanpa batas)
        }

        $butuh = (int) ($lebar * $tinggi * 4 * self::FAKTOR_MEMORI) + self::CADANGAN_MEMORI;

        return $butuh <= $batas - memory_get_usage(true);
    }

    /** memory_limit dalam byte; 0 atau kurang berarti tanpa batas. */
    private function batasMemori(): int
    {
        $nilai = trim((string) ini_get('memory_limit'));
        if ($nilai === '' || $nilai === '-1') {
            return 0;
        }

        $angka = (int) $nilai;

        return match (strtolower(substr($nilai, -1))) {
            'g' => $angka * 1024 ** 3,
            'm' => $angka * 1024 ** 2,
            'k' => $angka * 1024,
            default => $angka,
        };
    }
}