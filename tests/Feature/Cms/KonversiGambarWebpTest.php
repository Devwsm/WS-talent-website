<?php

namespace Tests\Feature\Cms;

use App\Http\Controllers\Concerns\MengonversiGambarWebp;
use App\Http\Controllers\dashboardController;
use App\Http\Controllers\profileController;
use App\Support\GambarGagal;
use App\Support\ImageProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Konversi unggahan gambar CMS ke WebP dipusatkan di satu trait (dulu dua salinan persis di dashboardController
 * dan profileController) dan memakai ImageProcessor.
 */
class KonversiGambarWebpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('gd') || ! function_exists('imagewebp')) {
            $this->markTestSkipped('Ekstensi GD dengan dukungan WebP tidak tersedia di mesin ini.');
        }
    }

    /** Objek kecil yang memakai trait, supaya method privatnya bisa dites langsung. */
    private function pengonversi(): object
    {
        return new class {
            use MengonversiGambarWebp;

            public function panggil(string $path, int $kualitas = 82): string
            {
                return $this->convertToWebP($path, $kualitas);
            }
        };
    }

    private function png(int $lebar, int $tinggi): string
    {
        $path = tempnam(sys_get_temp_dir(), 'png');
        $gambar = imagecreatetruecolor($lebar, $tinggi);
        imagepng($gambar, $path);

        return $path;
    }

    /** PNG sah secara kepala (IHDR) dengan dimensi besar tapi tanpa data piksel: murah dibuat, mahal bila didecode. */
    private function pngBomDekompresi(int $lebar, int $tinggi): string
    {
        $potong = fn(string $jenis, string $isi) => pack('N', strlen($isi)) . $jenis . $isi . pack('N', crc32($jenis . $isi));
        $isi = "\x89PNG\r\n\x1a\n"
            . $potong('IHDR', pack('NNCCCCC', $lebar, $tinggi, 8, 2, 0, 0, 0))
            . $potong('IEND', '');
        $path = tempnam(sys_get_temp_dir(), 'bom');
        file_put_contents($path, $isi);

        return $path;
    }

    public function test_tidak_ada_salinan_converttowebp_di_controller(): void
    {
        foreach ([dashboardController::class, profileController::class] as $kelas) {
            $berkas = (new \ReflectionClass($kelas))->getFileName();

            $this->assertStringNotContainsString('function convertToWebP', (string) file_get_contents($berkas), "{$kelas} masih punya salinan sendiri.");
            $this->assertContains(MengonversiGambarWebp::class, class_uses($kelas), $kelas);
        }
    }

    public function test_png_diubah_menjadi_webp_dengan_ukuran_asli(): void
    {
        $path = $this->png(120, 80);

        $isi = $this->pengonversi()->panggil($path);

        $this->assertSame('RIFF', substr($isi, 0, 4));
        $this->assertSame('WEBP', substr($isi, 8, 4));
        $info = getimagesizefromstring($isi);
        $this->assertSame([120, 80], [$info[0], $info[1]], 'CMS tidak boleh memperkecil gambar.');
    }

    public function test_berkas_bukan_gambar_jadi_pesan_validasi_bukan_error(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'txt');
        file_put_contents($path, '<?php echo 1;');

        try {
            $this->pengonversi()->panggil($path);
            $this->fail('Seharusnya melempar ValidationException.');
        } catch (ValidationException $e) {
            $this->assertSame('Berkas ini bukan gambar yang valid. Pilih gambar JPG atau PNG.', $e->errors()['gambar'][0]);
        }
    }

    public function test_di_atas_16_megapiksel_ditolak_sebelum_didecode(): void
    {
        $path = $this->pngBomDekompresi(5000, 5000); // 25 MP

        try {
            $this->pengonversi()->panggil($path);
            $this->fail('Seharusnya melempar ValidationException.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('maksimal 16 megapiksel', $e->errors()['gambar'][0]);
        }
    }

    public function test_tepat_di_batas_16_megapiksel_tidak_ditolak_oleh_pemeriksa_cms(): void
    {
        // 4000 x 4000 = 16.000.000 (tidak lebih): lolos pemeriksa CMS; kegagalan, bila ada, hanya dari ImageProcessor.
        $this->app->instance(ImageProcessor::class, new class extends ImageProcessor {
            public function keWebp(string $sumber, int $sisiMaks, int $kualitas = 82): string
            {
                return 'WEBP-PALSU';
            }
        });

        $this->assertSame('WEBP-PALSU', $this->pengonversi()->panggil($this->pngBomDekompresi(4000, 4000)));
    }

    public function test_kegagalan_pengolah_menjadi_pesan_validasi_pada_kunci_gambar(): void
    {
        $this->app->instance(ImageProcessor::class, new class extends ImageProcessor {
            public function keWebp(string $sumber, int $sisiMaks, int $kualitas = 82): string
            {
                throw new GambarGagal('Server belum bisa memproses foto.');
            }
        });

        try {
            $this->pengonversi()->panggil($this->png(10, 10));
            $this->fail('Seharusnya melempar ValidationException.');
        } catch (ValidationException $e) {
            $this->assertSame(['gambar' => ['Server belum bisa memproses foto.']], $e->errors());
        }
    }

    public function test_unggah_banner_lewat_cms_tersimpan_sebagai_webp(): void
    {
        Storage::fake('public');

        $this->withSession(['login' => true, 'user' => 'admin'])
            ->post(route('banner.tambah'), [
                'banner_name' => 'Tur Baru',
                'link_banner' => 'https://contoh.test/tur',
                'banner_cover' => UploadedFile::fake()->image('cover.png', 300, 150),
            ])
            ->assertSessionHasNoErrors();

        $berkas = Storage::disk('public')->files('banner');
        $this->assertCount(1, $berkas);
        $this->assertStringEndsWith('.webp', $berkas[0]);
        $this->assertSame('WEBP', substr(Storage::disk('public')->get($berkas[0]), 8, 4));
    }

    public function test_foto_hero_profil_lewat_cms_tersimpan_sebagai_webp(): void
    {
        Storage::fake('public');

        $this->withSession(['login' => true, 'user' => 'admin'])
            ->post(route('hero.simpan'), [
                'judul_singkat' => 'DJ & Producer',
                'nama' => 'Whisnu Santika',
                'tagline' => 'Tagline uji',
                'foto' => UploadedFile::fake()->image('hero.jpg', 400, 300),
            ])
            ->assertSessionHasNoErrors();

        $berkas = Storage::disk('public')->allFiles();
        $this->assertNotEmpty($berkas);
        $this->assertStringEndsWith('.webp', $berkas[0]);
    }
}