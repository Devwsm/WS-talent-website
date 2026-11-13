<?php

namespace Tests\Feature\Keamanan;

use App\Models\log_error;
use App\Support\KebijakanCsp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Content-Security-Policy: header menurut mode, penampung laporan pelanggaran, dan penjaga agar sumber luar yang
 * dipakai view selalu tercakup kebijakan.
 */
class CspTest extends TestCase
{
    use RefreshDatabase;

    private function laporanLama(array $ubah = []): array
    {
        return ['csp-report' => $ubah + [
            'document-uri' => 'https://contoh.test/profile?token=rahasia#bagian',
            'effective-directive' => 'script-src-elem',
            'violated-directive' => 'script-src-elem',
            'blocked-uri' => 'https://evil.example/skrip.js?x=1',
            'disposition' => 'report',
        ]];
    }

    public function test_bawaan_di_tes_mati_dan_tidak_ada_header_csp(): void
    {
        $respons = $this->get(route('home'))->assertOk();

        $this->assertFalse($respons->headers->has('Content-Security-Policy'));
        $this->assertFalse($respons->headers->has('Content-Security-Policy-Report-Only'));
    }

    public function test_mode_lapor_mengirim_header_report_only_yang_tidak_memblokir(): void
    {
        config(['keamanan.csp.mode' => 'report']);

        $respons = $this->get(route('home'))->assertOk();

        $this->assertFalse($respons->headers->has('Content-Security-Policy'), 'Mode lapor tidak boleh memblokir.');
        $nilai = $respons->headers->get('Content-Security-Policy-Report-Only');
        foreach (["object-src 'none'", "base-uri 'self'", "frame-ancestors 'self'", "form-action 'self'", 'report-uri /csp-report', 'script-src \'self\' \'unsafe-inline\' https://*.bandsintown.com'] as $harus) {
            $this->assertStringContainsString($harus, $nilai);
        }
        $this->assertStringNotContainsString('unsafe-eval', $nilai);
        $this->assertStringNotContainsString('googleapis', $nilai, 'Font sudah dimuat dari dalam project, Google Fonts tidak perlu diizinkan.');
        $this->assertStringNotContainsString('gstatic', $nilai);
    }

    public function test_mode_paksa_mengirim_header_yang_memblokir(): void
    {
        config(['keamanan.csp.mode' => 'enforce']);

        $respons = $this->get(route('home'))->assertOk();

        $this->assertTrue($respons->headers->has('Content-Security-Policy'));
        $this->assertFalse($respons->headers->has('Content-Security-Policy-Report-Only'));
    }

    public function test_mode_tidak_dikenal_dianggap_mati(): void
    {
        config(['keamanan.csp.mode' => 'enfroce']);

        $respons = $this->get(route('home'))->assertOk();

        $this->assertFalse($respons->headers->has('Content-Security-Policy'));
        $this->assertFalse($respons->headers->has('Content-Security-Policy-Report-Only'));
    }

    public function test_laporan_pelanggaran_disimpan_digabung_dan_tanpa_query_string(): void
    {
        config(['keamanan.csp.mode' => 'report']);

        $this->postJson(route('csp.laporan'), $this->laporanLama())->assertNoContent();
        $this->postJson(route('csp.laporan'), $this->laporanLama())->assertNoContent();

        $this->assertSame(1, log_error::query()->count());
        $baris = log_error::firstOrFail();
        $this->assertSame('CSP:script-src-elem', $baris->kelas);
        $this->assertSame('evil.example', explode(' ', $baris->lokasi)[0]);
        $this->assertSame(2, $baris->jumlah);
        $this->assertSame('/profile', $baris->url);
        $this->assertStringNotContainsString('rahasia', $baris->pesan . $baris->url . $baris->lokasi);
        $this->assertStringNotContainsString('skrip.js', $baris->pesan . $baris->lokasi);
        $this->assertNull($baris->jejak);
    }

    public function test_bentuk_reporting_api_juga_diterima(): void
    {
        config(['keamanan.csp.mode' => 'report']);

        $this->postJson(route('csp.laporan'), [[
            'type' => 'csp-violation',
            'body' => [
                'documentURL' => 'https://contoh.test/',
                'effectiveDirective' => 'img-src',
                'blockedURL' => 'http://gambar.example/a.png',
                'disposition' => 'enforce',
            ],
        ]])->assertNoContent();

        $baris = log_error::firstOrFail();
        $this->assertSame('CSP:img-src', $baris->kelas);
        $this->assertStringContainsString('diblokir', $baris->pesan);
    }

    public function test_mode_mati_tidak_menyimpan_apa_pun(): void
    {
        $this->postJson(route('csp.laporan'), $this->laporanLama())->assertNoContent();

        $this->assertSame(0, log_error::query()->count());
    }

    public function test_derau_dan_masukan_ngawur_diabaikan_dan_tetap_204(): void
    {
        config(['keamanan.csp.mode' => 'report']);

        // Ekstensi browser, JSON rusak, bukan array, kosong, dan terlalu besar.
        $this->postJson(route('csp.laporan'), $this->laporanLama(['blocked-uri' => 'chrome-extension://abc/inject.js']))->assertNoContent();
        $this->call('POST', route('csp.laporan'), [], [], [], ['CONTENT_TYPE' => 'application/csp-report'], '{rusak')->assertNoContent();
        $this->call('POST', route('csp.laporan'), [], [], [], ['CONTENT_TYPE' => 'application/csp-report'], '"teks"')->assertNoContent();
        $this->call('POST', route('csp.laporan'), [], [], [], ['CONTENT_TYPE' => 'application/csp-report'], '')->assertNoContent();
        $this->call('POST', route('csp.laporan'), [], [], [], ['CONTENT_TYPE' => 'application/csp-report'], json_encode($this->laporanLama(['blocked-uri' => str_repeat('a', 20000)])))->assertNoContent();

        $this->assertSame(0, log_error::query()->count());
    }

    public function test_bidang_laporan_dipotong_dan_nama_aturan_disaring(): void
    {
        config(['keamanan.csp.mode' => 'report']);

        $this->postJson(route('csp.laporan'), $this->laporanLama([
            'effective-directive' => '<script>alert(1)</script>',
            'blocked-uri' => 'https://' . str_repeat('a', 400) . '.example/x',
            'document-uri' => 'https://contoh.test/' . str_repeat('b', 900),
        ]))->assertNoContent();

        $baris = log_error::firstOrFail();
        $this->assertSame('CSP:tidak-diketahui', $baris->kelas);
        $this->assertLessThanOrEqual(255, strlen($baris->lokasi));
        $this->assertLessThanOrEqual(500, strlen($baris->url));
    }

    public function test_jumlah_baris_laporan_dibatasi(): void
    {
        config(['keamanan.csp.mode' => 'report', 'keamanan.csp.laporan_maks' => 2]);

        foreach (['a.example', 'b.example', 'c.example'] as $host) {
            $this->postJson(route('csp.laporan'), $this->laporanLama(['blocked-uri' => "https://$host/x.js"]))->assertNoContent();
        }

        $this->assertSame(2, log_error::query()->count());
    }

    public function test_penampung_tanpa_sesi_cookie_dan_csrf_serta_dibatasi_per_ip(): void
    {
        config(['keamanan.csp.mode' => 'report']);

        $respons = $this->postJson(route('csp.laporan'), $this->laporanLama())->assertNoContent();
        $this->assertSame([], $respons->headers->getCookies(), 'Laporan tidak boleh membuat sesi atau cookie.');
        $this->assertSame(0, DB::table('sessions')->count());

        foreach (range(1, 19) as $_) {
            $this->postJson(route('csp.laporan'), $this->laporanLama())->assertNoContent();
        }
        $this->postJson(route('csp.laporan'), $this->laporanLama())->assertStatus(429);
    }

    public function test_laporan_muncul_di_halaman_log_dashboard(): void
    {
        config(['keamanan.csp.mode' => 'report']);
        $this->postJson(route('csp.laporan'), $this->laporanLama())->assertNoContent();

        $this->withSession(['login' => true, 'user' => 'admin'])
            ->get(route('log.index'))
            ->assertOk()
            ->assertSee('CSP:script-src-elem')
            ->assertSee('Laporan dari browser pengunjung')
            ->assertDontSee('Pesan lengkap dan jejak penuh ada di storage/logs');
    }

    public function test_sumber_luar_yang_dipakai_view_tercakup_kebijakan(): void
    {
        $skrip = [];
        $gaya = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views'), \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $berkas) {
            if (! $berkas->isFile()) {
                continue;
            }
            $isi = (string) file_get_contents($berkas->getPathname());
            // <script src="https://host/..."> dan skrip.src = "https://host/..."
            preg_match_all('#<script[^>]+src=["\']https://([^/"\']+)#i', $isi, $a);
            preg_match_all('#\.src\s*=\s*["\']https://([^/"\']+)#i', $isi, $b);
            // <link rel="stylesheet" href="https://host/...">
            preg_match_all('#<link[^>]+rel=["\']stylesheet["\'][^>]+href=["\']https://([^/"\']+)#i', $isi, $c);
            $skrip = array_merge($skrip, $a[1], $b[1]);
            $gaya = array_merge($gaya, $c[1]);
        }

        foreach (array_unique($skrip) as $host) {
            $this->assertTrue(KebijakanCsp::hostDiizinkan($host, ['script-src']), "Skrip dari $host belum ada di script-src (config/keamanan.php).");
        }
        foreach (array_unique($gaya) as $host) {
            $this->assertTrue(KebijakanCsp::hostDiizinkan($host, ['style-src']), "Stylesheet dari $host belum ada di style-src (config/keamanan.php).");
        }

        $this->assertContains('widgetv3.bandsintown.com', $skrip, 'Penjaga ini harus benar-benar menemukan widget Bandsintown.');
    }

    public function test_wildcard_host_dan_penolakan_host_asing(): void
    {
        $this->assertTrue(KebijakanCsp::hostDiizinkan('rest.bandsintown.com', ['connect-src']));
        // Pelanggaran asli dari production: widget memuat skrip data dari rest.bandsintown.com.
        $this->assertTrue(KebijakanCsp::hostDiizinkan('rest.bandsintown.com', ['script-src']));
        $this->assertTrue(KebijakanCsp::hostDiizinkan('widgetv3.bandsintown.com', ['script-src']));
        $this->assertFalse(KebijakanCsp::hostDiizinkan('evilbandsintown.com', ['connect-src']));
        $this->assertFalse(KebijakanCsp::hostDiizinkan('evil.example', ['script-src']));
    }
}