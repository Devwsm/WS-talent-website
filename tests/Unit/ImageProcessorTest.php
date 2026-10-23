<?php

namespace Tests\Unit;

use App\Support\GambarGagal;
use App\Support\ImageProcessor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * ImageProcessor murni GD + EXIF (tanpa Laravel), jadi dites sebagai tes unit biasa.
 * Dilewati otomatis bila server tes belum punya GD dengan WebP.
 */
class ImageProcessorTest extends TestCase
{
    private const MERAH = [255, 0, 0];
    private const HIJAU = [0, 200, 0];
    private const BIRU = [0, 0, 255];
    private const KUNING = [255, 255, 0];

    /** @var array<int, string> */
    private array $berkasSementara = [];

    protected function setUp(): void
    {
        if (! extension_loaded('gd') || ! function_exists('imagewebp')) {
            $this->markTestSkipped('Ekstensi GD dengan dukungan WebP tidak tersedia.');
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->berkasSementara as $berkas) {
            @unlink($berkas);
        }
    }

    private function tulis(string $isi, string $akhiran = '.bin'): string
    {
        $path = tempnam(sys_get_temp_dir(), 'ic_img_') . $akhiran;
        file_put_contents($path, $isi);
        $this->berkasSementara[] = $path;

        return $path;
    }

    /** Gambar empat kuadran: kiri-atas merah, kanan-atas hijau, kiri-bawah biru, kanan-bawah kuning. */
    private function kuadran(int $lebar = 80, int $tinggi = 40): \GdImage
    {
        $g = imagecreatetruecolor($lebar, $tinggi);
        $warna = fn(array $c) => imagecolorallocate($g, ...$c);
        $tx = intdiv($lebar, 2);
        $ty = intdiv($tinggi, 2);
        imagefilledrectangle($g, 0, 0, $tx - 1, $ty - 1, $warna(self::MERAH));
        imagefilledrectangle($g, $tx, 0, $lebar - 1, $ty - 1, $warna(self::HIJAU));
        imagefilledrectangle($g, 0, $ty, $tx - 1, $tinggi - 1, $warna(self::BIRU));
        imagefilledrectangle($g, $tx, $ty, $lebar - 1, $tinggi - 1, $warna(self::KUNING));

        return $g;
    }

    private function jpegBiner(\GdImage $g): string
    {
        ob_start();
        imagejpeg($g, null, 95);

        return (string) ob_get_clean();
    }

    /** Sisipkan segmen EXIF APP1 (hanya tag Orientation) tepat setelah penanda SOI. */
    private function denganOrientasi(string $jpeg, int $orientasi): string
    {
        $tiff = 'II' . pack('v', 42) . pack('V', 8)         // header TIFF little-endian, IFD0 di offset 8
            . pack('v', 1)                                   // satu entri
            . pack('v', 0x0112) . pack('v', 3) . pack('V', 1) . pack('v', $orientasi) . "\x00\x00"
            . pack('V', 0);                                  // tidak ada IFD berikutnya
        $data = "Exif\x00\x00" . $tiff;

        return substr($jpeg, 0, 2) . "\xFF\xE1" . pack('n', strlen($data) + 2) . $data . substr($jpeg, 2);
    }

    /** @return array{0: int, 1: int, 2: array<string, string>} lebar, tinggi, warna terdekat di tiap kuadran */
    private function bacaKuadran(string $webp): array
    {
        $g = imagecreatefromstring($webp);
        $this->assertInstanceOf(\GdImage::class, $g);
        $w = imagesx($g);
        $h = imagesy($g);

        $titik = [
            'kiri-atas' => [intdiv($w, 4), intdiv($h, 4)],
            'kanan-atas' => [intdiv($w * 3, 4), intdiv($h, 4)],
            'kiri-bawah' => [intdiv($w, 4), intdiv($h * 3, 4)],
            'kanan-bawah' => [intdiv($w * 3, 4), intdiv($h * 3, 4)],
        ];
        $kandidat = ['merah' => self::MERAH, 'hijau' => self::HIJAU, 'biru' => self::BIRU, 'kuning' => self::KUNING];

        $hasil = [];
        foreach ($titik as $nama => [$x, $y]) {
            $rgb = imagecolorat($g, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $gr = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            $terdekat = null;
            $jarak = PHP_INT_MAX;
            foreach ($kandidat as $label => [$kr, $kg, $kb]) {
                $d = ($r - $kr) ** 2 + ($gr - $kg) ** 2 + ($b - $kb) ** 2;
                if ($d < $jarak) {
                    $jarak = $d;
                    $terdekat = $label;
                }
            }
            $hasil[$nama] = $terdekat;
        }

        return [$w, $h, $hasil];
    }

    /** @return array<string, array{0: int, 1: int, 2: array<string, string>}> */
    public static function orientasiExif(): array
    {
        // Hasil yang harus dilihat anggota setelah foto diluruskan. Sumber: 80x40, TL merah, TR hijau, BL biru, BR kuning.
        return [
            '1 normal' => [1, 80, ['kiri-atas' => 'merah', 'kanan-atas' => 'hijau', 'kiri-bawah' => 'biru', 'kanan-bawah' => 'kuning']],
            '2 cermin horizontal' => [2, 80, ['kiri-atas' => 'hijau', 'kanan-atas' => 'merah', 'kiri-bawah' => 'kuning', 'kanan-bawah' => 'biru']],
            '3 putar 180' => [3, 80, ['kiri-atas' => 'kuning', 'kanan-atas' => 'biru', 'kiri-bawah' => 'hijau', 'kanan-bawah' => 'merah']],
            '4 cermin vertikal' => [4, 80, ['kiri-atas' => 'biru', 'kanan-atas' => 'kuning', 'kiri-bawah' => 'merah', 'kanan-bawah' => 'hijau']],
            '5 transpos' => [5, 40, ['kiri-atas' => 'merah', 'kanan-atas' => 'biru', 'kiri-bawah' => 'hijau', 'kanan-bawah' => 'kuning']],
            '6 putar 90 searah jarum jam' => [6, 40, ['kiri-atas' => 'biru', 'kanan-atas' => 'merah', 'kiri-bawah' => 'kuning', 'kanan-bawah' => 'hijau']],
            '7 transversal' => [7, 40, ['kiri-atas' => 'kuning', 'kanan-atas' => 'hijau', 'kiri-bawah' => 'biru', 'kanan-bawah' => 'merah']],
            '8 putar 90 berlawanan jarum jam' => [8, 40, ['kiri-atas' => 'hijau', 'kanan-atas' => 'kuning', 'kiri-bawah' => 'merah', 'kanan-bawah' => 'biru']],
        ];
    }

    #[DataProvider('orientasiExif')]
    public function test_orientasi_exif_diluruskan(int $orientasi, int $lebarHasil, array $diharapkan): void
    {
        if (! function_exists('exif_read_data')) {
            $this->markTestSkipped('Ekstensi exif tidak tersedia.');
        }

        $sumber = $this->tulis($this->denganOrientasi($this->jpegBiner($this->kuadran()), $orientasi), '.jpg');

        [$w, $h, $warna] = $this->bacaKuadran((new ImageProcessor())->keWebp($sumber, 1600, 95));

        $this->assertSame($lebarHasil, $w, 'lebar hasil');
        $this->assertSame($lebarHasil === 80 ? 40 : 80, $h, 'tinggi hasil');
        $this->assertSame($diharapkan, $warna);
    }

    public function test_hasil_adalah_webp_dan_exif_terbuang(): void
    {
        $sumber = $this->tulis($this->denganOrientasi($this->jpegBiner($this->kuadran()), 6), '.jpg');
        $isi = (new ImageProcessor())->keWebp($sumber, 1600);

        $this->assertSame('RIFF', substr($isi, 0, 4));
        $this->assertSame('WEBP', substr($isi, 8, 4));

        // Berkas hasil tidak lagi membawa tanda orientasi: dibaca ulang, ukurannya sudah final.
        $hasil = $this->tulis($isi, '.webp');
        $info = getimagesize($hasil);
        $this->assertSame([40, 80], [$info[0], $info[1]]);
    }

    public function test_foto_besar_diperkecil_dan_yang_kecil_tidak_diperbesar(): void
    {
        $besar = $this->tulis($this->jpegBiner($this->kuadran(3000, 1000)), '.jpg');
        $info = getimagesizefromstring((new ImageProcessor())->keWebp($besar, 1600));
        $this->assertSame([1600, 533], [$info[0], $info[1]]);

        $kecil = $this->tulis($this->jpegBiner($this->kuadran(80, 40)), '.jpg');
        $info = getimagesizefromstring((new ImageProcessor())->keWebp($kecil, 512));
        $this->assertSame([80, 40], [$info[0], $info[1]]);

        // Potret: sisi terpanjang (tinggi) yang dibatasi.
        $potret = $this->tulis($this->jpegBiner($this->kuadran(1000, 3000)), '.jpg');
        $info = getimagesizefromstring((new ImageProcessor())->keWebp($potret, 512));
        $this->assertSame([171, 512], [$info[0], $info[1]]);
    }

    public function test_png_transparan_dan_png_palet_diterima(): void
    {
        $g = imagecreatetruecolor(60, 60);
        imagealphablending($g, false);
        imagesavealpha($g, true);
        imagefill($g, 0, 0, imagecolorallocatealpha($g, 0, 0, 0, 127));
        ob_start();
        imagepng($g);
        $png = $this->tulis((string) ob_get_clean(), '.png');

        $hasil = imagecreatefromstring((new ImageProcessor())->keWebp($png, 512));
        $this->assertSame(127, (imagecolorat($hasil, 30, 30) >> 24) & 0x7F, 'piksel transparan tetap transparan');

        $palet = imagecreate(30, 30);
        imagecolorallocate($palet, 10, 20, 30);
        ob_start();
        imagepng($palet);
        $berkasPalet = $this->tulis((string) ob_get_clean(), '.png');

        $this->assertSame('WEBP', substr((new ImageProcessor())->keWebp($berkasPalet, 512), 8, 4));
    }

    public function test_webp_masuk_webp_keluar(): void
    {
        ob_start();
        imagewebp($this->kuadran(), null, 90);
        $webp = $this->tulis((string) ob_get_clean(), '.webp');

        [$w, $h, $warna] = $this->bacaKuadran((new ImageProcessor())->keWebp($webp, 1600, 95));
        $this->assertSame([80, 40], [$w, $h]);
        $this->assertSame('merah', $warna['kiri-atas']);
    }

    public function test_berkas_bukan_gambar_ditolak_walau_namanya_jpg(): void
    {
        $palsu = $this->tulis('<?php echo "bukan gambar"; ?>', '.jpg');

$this->expectException(GambarGagal::class);
$this->expectExceptionMessage('bukan gambar yang valid');
(new ImageProcessor())->keWebp($palsu, 1600);
}

public function test_format_gif_ditolak(): void
{
$g = imagecreatetruecolor(10, 10);
ob_start();
imagegif($g);
$gif = $this->tulis((string) ob_get_clean(), '.jpg'); // sengaja berekstensi jpg

$this->expectException(GambarGagal::class);
$this->expectExceptionMessage('tidak didukung');
(new ImageProcessor())->keWebp($gif, 1600);
}

public function test_resolusi_raksasa_ditolak_sebelum_didecode(): void
{
// Header PNG 10000x10000 (100 MP) tanpa data piksel: cukup untuk getimagesize(), tanpa memakan memori.
$ihdr = pack('N', 10000) . pack('N', 10000) . "\x08\x02\x00\x00\x00";
$png = "\x89PNG\r\n\x1a\n" . pack('N', 13) . 'IHDR' . $ihdr . pack('N', crc32('IHDR' . $ihdr));
$berkas = $this->tulis($png, '.png');

$this->expectException(GambarGagal::class);
$this->expectExceptionMessage('Resolusi foto terlalu besar');
(new ImageProcessor())->keWebp($berkas, 1600);
}
}