<?php

namespace Tests\Unit;

use App\Support\AreaLog;
use App\Support\PenjelasLog;
use App\Support\PerangkatPengguna;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PenjelasLogTest extends TestCase
{
    #[DataProvider('userAgent')]
    public function test_perangkat_diringkas_dari_user_agent(string $ua, string $harapan): void
    {
        $this->assertSame($harapan, PerangkatPengguna::ringkas($ua));
    }

    public static function userAgent(): array
    {
        return [
            'edge windows' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36 Edg/126.0.0.0', 'Komputer Windows · Edge'],
            'chrome android hp' => ['Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Mobile Safari/537.36', 'HP Android · Chrome'],
            'tablet android' => ['Mozilla/5.0 (Linux; Android 13; SM-X700) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36', 'Tablet Android · Chrome'],
            'safari iphone' => ['Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1', 'iPhone · Safari'],
            'firefox mac' => ['Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:127.0) Gecko/20100101 Firefox/127.0', 'Komputer Mac · Firefox'],
            'kosong' => ['', 'Perangkat tidak dikenal'],
            'aneh' => ['curl/8.0', 'Perangkat tidak dikenal'],
        ];
    }

    public function test_area_dari_alamat(): void
    {
        $this->assertSame('inner-circle', AreaLog::dariUrl('/innercircle/daftar'));
        $this->assertSame('inner-circle', AreaLog::dariUrl('https://whisnusantika.com/dashboard/inner-circle/acara?x=1'));
        $this->assertSame('artis', AreaLog::dariUrl('/dashboard/albums'));
        $this->assertSame('artis', AreaLog::dariUrl('/innercircles-bukan'));
        $this->assertSame('inner-circle', AreaLog::dariUrl('artisan', 'app/Services/InnerCircle/BandsintownService.php:10'));
        $this->assertSame('artis', AreaLog::dariUrl('artisan', 'app/Console/Commands/OptimalkanGambar.php:5'));
        $this->assertNotSame(AreaLog::sidikJari('x', 'artis'), AreaLog::sidikJari('x', 'inner-circle'));
        $this->assertSame(sha1('x'), AreaLog::sidikJari('x', 'artis')); // rumus lama tidak berubah untuk CMS Artis
    }

    public function test_csp_hanya_dilaporkan_dijelaskan_tanpa_bahasa_teknis(): void
    {
        $t = PenjelasLog::untuk('CSP:script-src-elem', 'CSP script-src-elem: connect.facebook.net (hanya dilaporkan) di halaman /innercircle/daftar', 'connect.facebook.net (dari whisnusantika.com/:1)', '/innercircle/daftar', 'CSP');

        $this->assertSame('Skrip dari connect.facebook.net terdeteksi', $t['judul']);
        $this->assertStringContainsString('Daftar Inner Circle', $t['deskripsi']);
        $this->assertStringContainsString('tidak diblokir', $t['deskripsi']);
        $this->assertStringContainsString('Instagram atau Facebook', $t['deskripsi']);
    }

    public function test_csp_diblokir_berbeda_dari_hanya_dilaporkan(): void
    {
        $t = PenjelasLog::untuk('CSP:img-src', 'CSP img-src: cdn.contoh.com (diblokir) di halaman /', 'cdn.contoh.com', '/', 'CSP');

        $this->assertSame('Gambar dari cdn.contoh.com diblokir', $t['judul']);
        $this->assertStringContainsString('mungkin tidak tampil', $t['deskripsi']);
    }

    public function test_error_tak_dikenal_tetap_punya_teks_umum(): void
    {
        $t = PenjelasLog::untuk('RuntimeException', 'boom', 'app/X.php:1', 'https://x.test/tidak-ada-halaman-ini', 'GET');

        $this->assertSame('Terjadi kesalahan di sistem', $t['judul']);
        $this->assertNotSame('', $t['deskripsi']);
    }

    public function test_nama_halaman_dari_alamat(): void
    {
        $this->assertSame('Beranda website', PenjelasLog::halaman('/', 'GET'));
        $this->assertSame('CMS Artis › Album', PenjelasLog::halaman('https://x.test/dashboard/albums/tambahAlbums', 'POST'));
        $this->assertSame('CMS Inner Circle › Acara & QR', PenjelasLog::halaman('/dashboard/inner-circle/acara', 'GET'));
        $this->assertNull(PenjelasLog::halaman('artisan'));
        $this->assertNull(PenjelasLog::halaman('/halaman-ngawur'));
    }
}