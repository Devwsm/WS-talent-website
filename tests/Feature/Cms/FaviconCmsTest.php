<?php

namespace Tests\Feature\Cms;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Halaman CMS punya favicon sesuai mereknya: CMS Artis memakai ws-artis, CMS Inner Circle memakai ws-inner-circle. */
class FaviconCmsTest extends TestCase
{
    use RefreshDatabase;

    private function admin()
    {
        return $this->withSession(['login' => true, 'user' => 'admin']);
    }

    public function test_cms_artis_dan_halaman_login_memakai_favicon_artis(): void
    {
        foreach ([$this->get(route('login')), $this->admin()->get(route('dashboard')), $this->admin()->get(route('log.index'))] as $respons) {
            $respons->assertOk()
                ->assertSee('aset/favicon/ws-artis/favicon-32x32.png', false)
                ->assertSee('aset/favicon/ws-artis/favicon.ico', false)
                ->assertSee('aset/favicon/ws-artis/apple-touch-icon.png', false)
                ->assertDontSee('ws-inner-circle', false);
        }
    }

    public function test_cms_inner_circle_memakai_favicon_inner_circle(): void
    {
        foreach ([route('ic.admin.ringkasan'), route('ic.admin.log.index'), route('ic.admin.anggota')] as $alamat) {
            $this->admin()->get($alamat)->assertOk()
                ->assertSee('aset/favicon/ws-inner-circle/favicon-32x32.png', false)
                ->assertSee('aset/favicon/ws-inner-circle/favicon.ico', false)
                ->assertDontSee('favicon/ws-artis', false);
        }
    }

    public function test_cms_tidak_memasang_manifest_tapi_situs_publik_tetap(): void
    {
        $this->admin()->get(route('dashboard'))->assertOk()->assertDontSee('site.webmanifest', false);
        $this->admin()->get(route('ic.admin.ringkasan'))->assertOk()->assertDontSee('site.webmanifest', false);
        $this->get('/')->assertOk()->assertSee('aset/favicon/ws-artis/site.webmanifest', false);
    }

    public function test_berkas_favicon_yang_dipakai_cms_ada(): void
    {
        foreach (['ws-artis', 'ws-inner-circle'] as $merek) {
            foreach (['favicon.ico', 'favicon-16x16.png', 'favicon-32x32.png', 'apple-touch-icon.png'] as $berkas) {
                $this->assertFileExists(public_path("aset/favicon/$merek/$berkas"));
            }
        }
    }
}