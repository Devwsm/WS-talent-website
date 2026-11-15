<?php

namespace Tests\Feature\InnerCircle\KontenCms;

use App\Models\ic_pengaturan;
use App\Services\InnerCircle\EmailService;
use App\Services\InnerCircle\IcTampilan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Satu tema untuk email, editor privasi yang terbaca di tema terang, dan dock senada.
 */
class TemaCmsTest extends TestCase
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
        $this->assertSame('#d6f4ff', strtolower($bawaan['latar']));
        $this->assertSame('#ffffff', strtolower($bawaan['kartu']));

        $this->admin()->post(route('ic.admin.konten.tampilan.simpan'), ['k' => [
            'tampilan__program' => '',
            'tampilan__aksen' => '#1a5fb4',
            'tampilan__latar' => '',
            'tampilan__kartu' => '',
        ]])->assertSessionHasNoErrors();

        $html = EmailService::contoh('reset')->render();
        $this->assertStringContainsString('background-color:#d6f4ff', strtolower($html));
        $this->assertSame('#1a5fb4', strtolower(app(IcTampilan::class)->warnaEmail()['aksen']));
    }

    public function test_warna_rusak_di_database_tidak_masuk_email(): void
    {
        ic_pengaturan::create(['kunci' => 'tampilan.latar', 'nilai' => 'red;}</style><script>alert(1)</script>']);

        $html = EmailService::contoh('reset')->render();
        $this->assertStringNotContainsString('<script>alert(1)', $html);
        $this->assertStringContainsString('background-color:#d6f4ff', strtolower($html));
    }

    public function test_editor_privasi_tidak_memakai_warna_putih_tetap(): void
    {
        $html = $this->admin()->get(route('ic.admin.konten.bagian', 'privasi'))->assertOk()->getContent();

        $this->assertStringNotContainsString('color: white;"></div>', $html);
        $this->assertStringContainsString('var(--color-white', $html);
        $this->assertStringNotContainsString('#f87171', $html);
    }

    public function test_dock_cms_inner_circle_ikut_tema_dan_cms_asli_tetap_putih(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        // Permukaan dock/dropdown putih, hover biru sekunder, dan kartu QR/pratinjau email tetap putih asli.
        $this->assertMatchesRegularExpression('/body\.cms-ic\s*\{[^}]*--color-neutral-900:\s*#ffffff/s', $css);
        $this->assertMatchesRegularExpression('/body\.cms-ic\s*\{[^}]*--color-neutral-800:\s*#e1f2fc/s', $css);
        $this->assertMatchesRegularExpression('/body\.cms-ic \.cms-asli\s*\{[^}]*--color-white:\s*#fff/s', $css);
    }

    public function test_tema_cms_inner_circle_memakai_palet_biru_tanpa_sisa_warna_lama(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));
        $js = file_get_contents(resource_path('js/sweetalert.js'));

        // Latar #F3F8FC, tinta Dark Blue #0B4778, aksen Primary #1769A6.
        $this->assertMatchesRegularExpression('/body\.cms-ic\s*\{[^}]*--color-black:\s*#f3f8fc/s', $css);
        $this->assertMatchesRegularExpression('/body\.cms-ic\s*\{[^}]*--color-white:\s*#0b4778/s', $css);
        $this->assertMatchesRegularExpression('/body\.cms-ic\s*\{[^}]*--color-red-950:\s*#1769a6/s', $css);

        // Lavender (tema pertama) dan krem (tema kedua) sudah tidak ada.
        foreach (['#ebe7f7', '#27233f', '#c8b2ff', '#8d73ff', '#a58bff', '#f6f3fd', '#f5efe0', '#3b3126', '#e6d3a8', '#b98b3e', '#fbf7ec', '#fbf6e9', '#efe5cf', '#e0b873'] as $lama) {
            $this->assertStringNotContainsStringIgnoringCase($lama, $css, $lama);
            $this->assertStringNotContainsStringIgnoringCase($lama, $js, $lama);
        }
    }

    public function test_tema_cms_artis_memakai_palet_dark_editorial_emas(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        // Background #090909, surface #151515, secondary #242424, aksen emas #D6A84B, teks #F5F5F5.
        $this->assertMatchesRegularExpression('/body\.dash-theme\s*\{[^}]*--color-black:\s*#090909/s', $css);
        $this->assertMatchesRegularExpression('/body\.dash-theme\s*\{[^}]*--color-neutral-900:\s*#151515/s', $css);
        $this->assertMatchesRegularExpression('/body\.dash-theme\s*\{[^}]*--color-neutral-800:\s*#242424/s', $css);
        $this->assertMatchesRegularExpression('/body\.dash-theme\s*\{[^}]*--color-red-950:\s*#d6a84b/s', $css);
        $this->assertMatchesRegularExpression('/body\.dash-theme\s*\{[^}]*--color-white:\s*#f5f5f5/s', $css);
        $this->assertStringNotContainsStringIgnoringCase('#252525', $css);
    }

    public function test_dashboard_artis_dan_inner_circle_memakai_kelas_tema_masing_masing(): void
    {
        $this->admin()->get(route('dashboard'))->assertOk()->assertSee('dash-theme', false)->assertDontSee('cms-ic', false);
        $this->admin()->get(route('ic.admin.ringkasan'))->assertOk()->assertSee('cms-ic', false);
    }
}