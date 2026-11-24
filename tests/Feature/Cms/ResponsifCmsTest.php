<?php

namespace Tests\Feature\Cms;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * Penjaga tampilan mobile CMS: dialog tidak memakai tinggi layar statis (tombol Simpan terpotong di bawah bilah alamat
 * HP) dan pop-up penjelasan izin lokasi/kamera tetap terpasang di semua tempat yang memintanya.
 */
class ResponsifCmsTest extends TestCase
{
    /** @return list<string> */
    private function viewCms(): array
    {
        $hasil = [];

        foreach (['views/pages/dashboard-pages', 'views/components/dashboard'] as $dir) {
            $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path($dir), RecursiveDirectoryIterator::SKIP_DOTS));
            foreach ($iter as $berkas) {
                if (str_ends_with($berkas->getFilename(), '.blade.php')) {
                    $hasil[] = $berkas->getPathname();
                }
            }
        }

        return $hasil;
    }

    public function test_view_cms_tidak_memakai_tinggi_layar_statis(): void
    {
        $melanggar = [];

        foreach ($this->viewCms() as $path) {
            if (preg_match('/(?<![\w-])(min-h-screen|h-screen)(?![\w-])|100vh/', (string) file_get_contents($path))) {
                $melanggar[] = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $path);
            }
        }

        $this->assertSame([], $melanggar, 'Pakai h-dvh / min-h-dvh / 100dvh: tinggi layar statis memotong dialog di HP.');
    }

    public function test_popup_izin_terpasang_di_semua_tempat_yang_meminta_lokasi_dan_kamera(): void
    {
        $js = fn(string $f) => (string) file_get_contents(resource_path("js/{$f}"));

        $this->assertStringContainsString('pastikanIzin("lokasi"', $js('inner-circle.js'));
        $this->assertStringContainsString('pastikanIzin("kamera"', $js('inner-circle.js'));
        $this->assertStringContainsString('pastikanIzin(', $js('peta-acara.js'));
        $this->assertStringContainsString("window.pastikanIzin('lokasi'", (string) file_get_contents(resource_path('views/pages/dashboard-pages/inner-circle/acara/hari-h.blade.php')));
        $this->assertStringContainsString('resources/js/izin-perangkat.js', (string) file_get_contents(base_path('vite.config.js')));
    }

    public function test_tidak_ada_pemanggilan_gps_atau_kamera_tanpa_penjelasan(): void
    {
        // Setiap berkas yang memanggil geolocation/QrScanner harus memuat pastikanIzin (modul izin-perangkat sendiri dikecualikan).
        foreach (['inner-circle.js', 'peta-acara.js'] as $f) {
            $isi = (string) file_get_contents(resource_path("js/{$f}"));
            if (str_contains($isi, 'navigator.geolocation') || str_contains($isi, 'QrScanner')) {
                $this->assertStringContainsString('pastikanIzin', $isi, "{$f} memanggil GPS/kamera tanpa penjelasan izin.");
            }
        }
    }
}