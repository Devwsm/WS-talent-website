<?php

namespace Tests\Feature\Keamanan;

use Tests\TestCase;

/**
 * Penjaga statis untuk pengamanan dependensi dan deploy: editor Quill dibundel (bukan CDN), tinker tidak ikut ke
 * produksi, dan deploy punya cadangan serta pemulihan. Tidak butuh database.
 */
class DependensiDanDeployTest extends TestCase
{
    private function baca(string $jalur): string
    {
        return (string) file_get_contents(base_path($jalur));
    }

    public function test_tidak_ada_view_yang_memuat_skrip_atau_css_dari_cdn_quill(): void
    {
        $ditemukan = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views')));
        foreach ($iterator as $berkas) {
            if ($berkas->isFile() && str_contains((string) file_get_contents($berkas->getPathname()), 'cdn.quilljs.com')) {
                $ditemukan[] = $berkas->getFilename();
            }
        }

        $this->assertSame([], $ditemukan, 'Quill harus dibundel lewat Vite, bukan dimuat dari CDN tanpa SRI.');
    }

    public function test_tiga_halaman_editor_memuat_quill_dari_bundel_vite(): void
    {
        foreach (
            [
                'resources/views/pages/dashboard-pages/news.blade.php',
                'resources/views/components/profile/dashboard/bio.blade.php',
                'resources/views/components/dashboard/inner-circle/privasi-editor.blade.php',
            ] as $view
        ) {
            $isi = $this->baca($view);
            $this->assertStringContainsString("@vite('resources/js/quill-editor.js')", $isi, $view);
            // Skrip inline menunggu modul Vite (berjalan sebelum DOMContentLoaded), bukan langsung memakai Quill.
            $this->assertStringContainsString("document.addEventListener('DOMContentLoaded'", $isi, $view);
        }

        $this->assertStringContainsString('resources/js/quill-editor.js', $this->baca('vite.config.js'));
        $this->assertStringContainsString('window.Quill = Quill', $this->baca('resources/js/quill-editor.js'));

        $paket = json_decode($this->baca('package.json'), true);
        $this->assertArrayHasKey('quill', $paket['dependencies']);
        $this->assertTrue(version_compare(ltrim($paket['dependencies']['quill'], '^~'), '1.3.7', '>='));
    }

    public function test_tinker_hanya_paket_pengembangan(): void
    {
        $composer = json_decode($this->baca('composer.json'), true);
        $this->assertArrayNotHasKey('laravel/tinker', $composer['require']);
        $this->assertArrayHasKey('laravel/tinker', $composer['require-dev']);

        $kunci = json_decode($this->baca('composer.lock'), true);
        $produksi = array_column($kunci['packages'], 'name');
        $pengembangan = array_column($kunci['packages-dev'], 'name');
        foreach (['laravel/tinker', 'psy/psysh'] as $paket) {
            $this->assertNotContains($paket, $produksi, "$paket tidak boleh terpasang di produksi.");
            $this->assertContains($paket, $pengembangan);
        }
    }

    public function test_runner_dan_aksi_github_memakai_versi_tetap_yang_tidak_usang(): void
    {
        $alur = $this->baca('.github/workflows/deploy.yml');

        $this->assertStringNotContainsString('ubuntu-latest', $alur, 'Runner dikunci agar deploy tidak berubah sendiri saat label latest pindah versi.');
        $this->assertStringContainsString('ubuntu-24.04', $alur);

        // Versi 4 berjalan di Node 20 yang sudah usang di GitHub Actions.
        $this->assertStringNotContainsString('actions/checkout@v4', $alur);
        $this->assertStringNotContainsString('actions/setup-node@v4', $alur);

        $this->assertStringContainsString('composer audit --locked --no-dev', $alur);
        $this->assertStringContainsString('npm audit --omit=dev', $alur);
    }

    public function test_deploy_punya_cadangan_pemulihan_dan_cek_situs(): void
    {
        $alur = $this->baca('.github/workflows/deploy.yml');

        $this->assertStringContainsString('.deploy_prev', $alur, 'Versi lama harus dicadangkan, bukan langsung dihapus.');
        $this->assertStringContainsString('deploy/pulihkan.sh', $alur);
        $this->assertStringContainsString('Cek situs setelah deploy', $alur);
        $this->assertStringNotContainsString('.old"', $alur, 'Tidak boleh lagi menghapus versi lama saat penukaran.');

        $pemulihan = $this->baca('deploy/pulihkan.sh');
        $this->assertStringContainsString('.deploy_id', $pemulihan, 'Pemulihan harus memastikan cadangan milik deploy yang sama.');
        $this->assertStringContainsString('bootstrap/cache', $pemulihan);
        $this->assertStringContainsString('storage/framework/down', $pemulihan);
    }
}