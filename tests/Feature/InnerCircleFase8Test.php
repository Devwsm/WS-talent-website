<?php

namespace Tests\Feature;

use App\Models\ic_pengaturan;
use App\Services\InnerCircle\EmailService;
use App\Services\InnerCircle\IcTampilan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Fase 8 (pisah CMS): satu tema untuk email, editor privasi yang terbaca di tema lavender, dan dock senada. */
class InnerCircleFase8Test extends TestCase
{
    use RefreshDatabase;

    private function admin()
    {
        return $this->withSession(['login' => true, 'user' => 'admin']);
    }

    public function test_email_tidak_lagi_memakai_merah_cms_artis(): void
    {
        foreach (['reset', 'google', 'selamat-datang', 'verifikasi', 'klaim'] as $jenis) {
            $html = EmailService::contoh($jenis)->render();
            $this->assertStringNotContainsStringIgnoringCase('#5e0006', $html, $jenis);
            $this->assertStringContainsString('#111322', $html, $jenis);
        }
    }

    public function test_palet_email_mengikuti_warna_tampilan_dari_cms(): void
    {
        $bawaan = app(IcTampilan::class)->warnaEmail();
        $this->assertSame('#d9cdf7', strtolower($bawaan['latar']));
        $this->assertSame('#f8f7fc', strtolower($bawaan['kartu']));

        $this->admin()->post(route('ic.admin.konten.tampilan.simpan'), ['k' => [
            'tampilan__program' => '',
            'tampilan__aksen' => '#1a5fb4',
            'tampilan__latar' => '',
            'tampilan__kartu' => '',
        ]])->assertSessionHasNoErrors();

        $html = EmailService::contoh('reset')->render();
        $this->assertStringContainsString('background-color:#d9cdf7', strtolower($html));
        $this->assertSame('#1a5fb4', strtolower(app(IcTampilan::class)->warnaEmail()['aksen']));
    }

    public function test_warna_rusak_di_database_tidak_masuk_email(): void
    {
        ic_pengaturan::create(['kunci' => 'tampilan.latar', 'nilai' => 'red;}</style><script>alert(1)</script>']);

        $html = EmailService::contoh('reset')->render();
        $this->assertStringNotContainsString('<script>alert(1)', $html);
        $this->assertStringContainsString('background-color:#d9cdf7', strtolower($html));
    }

    public function test_editor_privasi_tidak_memakai_warna_putih_tetap(): void
    {
        $html = $this->admin()->get(route('ic.admin.konten'))->assertOk()->getContent();

        $this->assertStringNotContainsString('color: white;"></div>', $html);
        $this->assertStringContainsString('var(--color-white', $html);
        $this->assertStringNotContainsString('#f87171', $html);
    }

    public function test_dock_cms_inner_circle_punya_tema_sendiri_dan_cms_asli_tetap_putih(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertMatchesRegularExpression('/body\.cms-ic \.cms-dock\s*\{[^}]*--color-neutral-900:\s*#2d2859/s', $css);
        $this->assertMatchesRegularExpression('/body\.cms-ic \.cms-asli\s*\{[^}]*--color-white:\s*#fff/s', $css);
    }
}