<?php

namespace Tests\Feature\Cms;

use App\Services\InnerCircle\AcaraService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CMS dibagi dua mode (Artis dan Inner Circle) dengan navbar yang sama; yang beda hanya menu dan tema.
 */
class ModeDanNavigasiTest extends TestCase
{
    use RefreshDatabase;

    private function admin()
    {
        return $this->withSession(['login' => true, 'user' => 'admin']);
    }

    private function halamanIc(): array
    {
        return [
            'ic.admin.ringkasan',
            'ic.admin.anggota',
            'ic.admin.acara',
            'ic.admin.klaim',
            'ic.admin.hadiah',
            'ic.admin.pengumuman',
            'ic.admin.kenangan',
            'ic.admin.konten',
            'ic.admin.email',
        ];
    }

    public function test_cms_artis_punya_saklar_ke_inner_circle_dan_tanpa_tema_ic(): void
    {
        $html = $this->admin()->get(route('banner'))->assertOk()->getContent();

        $this->assertStringContainsString('Pindah ke CMS Inner Circle', $html);
        $this->assertStringContainsString('href="' . route('ic.admin.ringkasan') . '"', $html);
        $this->assertStringNotContainsString('cms-ic', $html);
        // Inner Circle tidak lagi jadi satu item di menu artis: hanya lewat saklar.
        $this->assertStringNotContainsString('Menu CMS Inner Circle', $html);
        $this->assertStringContainsString('Menu Dashboard', $html);
    }

    public function test_semua_halaman_cms_inner_circle_memakai_tema_dan_saklar_ke_artis(): void
    {
        foreach ($this->halamanIc() as $nama) {
            $html = $this->admin()->get(route($nama))->assertOk()->getContent();

            $this->assertStringContainsString('class="cms-ic', $html, $nama);
            $this->assertStringContainsString('Pindah ke CMS Artis', $html, $nama);
            $this->assertStringContainsString('href="' . route('dashboard') . '"', $html, $nama);
            $this->assertStringContainsString('Menu CMS Inner Circle', $html, $nama);
        }
    }

    public function test_menu_inner_circle_memuat_semua_halaman_tanpa_bar_tab_ganda(): void
    {
        $html = $this->admin()->get(route('ic.admin.klaim'))->assertOk()->getContent();

        foreach (['ic.admin.anggota', 'ic.admin.acara', 'ic.admin.klaim', 'ic.admin.hadiah', 'ic.admin.pengumuman', 'ic.admin.kenangan', 'ic.admin.konten', 'ic.admin.email'] as $nama) {
            $this->assertStringContainsString('href="' . route($nama) . '"', $html, $nama);
        }
        // Bar tab lama dibuang: navigasi hanya satu, yaitu navbar bawah.
        $this->assertStringNotContainsString('aria-label="Bagian Inner Circle"', $html);
    }

    public function test_saklar_hilang_bila_inner_circle_dimatikan(): void
    {
        config(['inner_circle.aktif' => false]);

        $this->admin()->get(route('banner'))
            ->assertOk()
            ->assertDontSee('Pindah ke CMS Inner Circle');
    }

    public function test_halaman_cetak_qr_tetap_berdiri_sendiri(): void
    {
        $e = app(AcaraService::class)->buat(['tanggal' => now()->toDateString(), 'tipe' => 'club', 'venue' => 'Klub Uji', 'kota' => 'Jakarta']);

        $this->admin()->get(route('ic.admin.acara.cetak', $e->kode))
            ->assertOk()
            ->assertDontSee('cms-ic');
    }

    public function test_halaman_cms_tetap_butuh_login(): void
    {
        foreach ($this->halamanIc() as $nama) {
            $this->get(route($nama))->assertRedirect(route('login'));
        }
    }
}
