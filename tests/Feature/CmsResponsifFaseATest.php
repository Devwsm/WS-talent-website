<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Fase A (UI/UX CMS): baris tab/filter memakai grid (tanpa geser samping) dan kartu acara tidak terpotong di layar kecil. */
class CmsResponsifFaseATest extends TestCase
{
    use RefreshDatabase;

    private function admin()
    {
        return $this->withSession(['login' => true, 'user' => 'admin']);
    }

    private function viewCms(): array
    {
        return glob(resource_path('views/pages/dashboard-pages/inner-circle/*.blade.php'));
    }

    public function test_tab_klaim_memakai_chip_mengalir_bukan_baris_yang_digeser(): void
    {
        $html = $this->admin()->get(route('ic.admin.klaim'))->assertOk()->getContent();

        $this->assertStringContainsString('cms-chips', $html);
        $this->assertStringContainsString('cms-chip', $html);
        $this->assertStringNotContainsString('overflow-x-auto', $html);
        $this->assertStringNotContainsString('w-max', $html);
    }

    public function test_filter_kenangan_memakai_chip_mengalir(): void
    {
        $html = $this->admin()->get(route('ic.admin.kenangan'))->assertOk()->getContent();

        $this->assertSame(2, substr_count($html, 'class="cms-chips"'));
    }

    public function test_tidak_ada_halaman_cms_inner_circle_yang_menggeser_baris_tab_ke_samping(): void
    {
        foreach ($this->viewCms() as $berkas) {
            $isi = file_get_contents($berkas);
            $this->assertStringNotContainsString('overflow-x-auto md:overflow-visible', $isi, basename($berkas));
            $this->assertStringNotContainsString('w-max md:w-auto', $isi, basename($berkas));
        }
    }

    public function test_filter_acara_menyisakan_dua_kolom_di_hp(): void
    {
        $html = $this->admin()->get(route('ic.admin.acara'))->assertOk()->getContent();

        $this->assertStringContainsString('grid grid-cols-2 lg:grid-cols-[1fr_1fr_1.6fr_auto]', $html);
        $this->assertStringNotContainsString('grid-cols-1 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1.6fr_auto]', $html);
    }

    public function test_css_grid_filter_dan_kartu_acara_kecil_terdaftar(): void
    {
        $app = file_get_contents(resource_path('css/app.css'));
        $ic = file_get_contents(resource_path('css/inner-circle.css'));

        $this->assertMatchesRegularExpression('/\\.cms-chips\\s*\\{[^}]*flex-wrap:\\s*wrap/s', $app);
        $this->assertMatchesRegularExpression('/\\.cms-chip\\s*\\{[^}]*min-height:\\s*2\\.75rem/s', $app);
        $this->assertStringNotContainsString('auto-fill', $app);
        $this->assertMatchesRegularExpression('/@media \(max-width: 479px\)\s*\{\s*body\.cms-ic \.ic-event\s*\{[^}]*flex-wrap:\s*wrap/s', $ic);
        $this->assertMatchesRegularExpression('/body\.cms-ic \.ic-event__isi\s*\{[^}]*flex:\s*1 1 100%/s', $ic);
    }
}