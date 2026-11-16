<?php

namespace Tests\Feature\Keamanan;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** CMS dan login admin tidak boleh diindeks mesin pencari; halaman publik tetap boleh. */
class NoindexCmsTest extends TestCase
{
    use RefreshDatabase;

    private function admin()
    {
        return $this->withSession(['login' => true, 'user' => 'admin']);
    }

    public function test_cms_dan_login_admin_mengirim_header_noindex(): void
    {
        $semua = [
            'login' => $this->get(route('login')),
            'login otp (belum ada sesi)' => $this->get(route('login.otp')),
            'dashboard tamu (dialihkan ke login)' => $this->get(route('dashboard')),
            'dashboard' => $this->admin()->get(route('dashboard')),
            'profile cms' => $this->admin()->get(route('dashboard.profile')),
            'log cms artis' => $this->admin()->get(route('log.index')),
            'cms inner circle' => $this->admin()->get(route('ic.admin.ringkasan')),
            'log cms inner circle' => $this->admin()->get(route('ic.admin.log.index')),
        ];

        foreach ($semua as $nama => $respons) {
            $this->assertSame('noindex, nofollow', $respons->headers->get('X-Robots-Tag'), $nama);
        }
    }

    public function test_halaman_publik_tidak_diberi_noindex(): void
    {
        foreach (['/', '/profile', '/privasi', route('ic.tentang'), '/sitemap.xml', '/robots.txt'] as $alamat) {
            $this->get($alamat)->assertOk();
            $this->assertNull($this->get($alamat)->headers->get('X-Robots-Tag'), $alamat);
        }
    }

    public function test_kerangka_cms_juga_memuat_meta_robots_noindex(): void
    {
        $this->admin()->get(route('dashboard'))->assertOk()->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        $this->admin()->get(route('ic.admin.ringkasan'))->assertOk()->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        $this->get(route('login'))->assertOk()->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_alamat_yang_mirip_tetapi_bukan_cms_tidak_ikut_noindex(): void
    {
        // "/dashboardku" dan "/loginz" bukan bagian CMS; pencocokan memakai batas jalur, bukan awalan teks.
        $this->assertNull($this->get('/dashboardku')->headers->get('X-Robots-Tag'));
        $this->assertNull($this->get('/loginz')->headers->get('X-Robots-Tag'));
    }
}