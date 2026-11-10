<?php

namespace Tests\Feature\Cms;

use App\Models\bio;
use App\Models\banner;
use App\Models\color_pages;
use App\Models\news;
use App\Support\CachePublik;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Cache halaman publik (Home, Profile, footer, tema, sitemap) dan ringkasan dashboard.
 * Yang dijaga: halaman lebih ringan, tapi perubahan dari CMS tetap langsung tampil dan teks tetap disaring.
 */
class CachePublikTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Meniru cache production (database): nilai benar-benar diserialisasi dan objek PHP tidak boleh dipulihkan
        // (config/cache.php: serializable_classes = false). Cache `array` bawaan tes tidak melakukan ini, jadi
        // tanpa ini kesalahan semacam "model disimpan mentah di cache" tidak akan ketahuan.
        config(['cache.stores.array.serialize' => true]);
        Cache::forgetDriver('array');
    }

    private function buatBerita(string $judul = 'Berita Uji', string $isi = '<p>Isi berita</p>'): news
    {
        return news::create([
            'news_title' => $judul,
            'news_description' => $isi,
            'news_source' => 'Sumber',
            'news_date' => '1 Januari 2026',
            'news_cover' => 'x.webp',
            'news_link' => 'https://contoh.test/berita',
        ]);
    }

    private function jumlahQuery(callable $aksi): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $aksi();
        $jumlah = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $jumlah;
    }

    public function test_kunjungan_kedua_ke_home_memakai_lebih_sedikit_query(): void
    {
        $this->buatBerita();

        $pertama = $this->jumlahQuery(fn() => $this->get(route('home'))->assertOk());
        $kedua = $this->jumlahQuery(fn() => $this->get(route('home'))->assertOk());

        $this->assertLessThan($pertama, $kedua);
        $this->assertLessThanOrEqual(3, $kedua, 'Home yang sudah di-cache cukup membaca cache, bukan tabel data.');
    }

    public function test_berita_baru_dan_berita_dihapus_langsung_tampil_di_home(): void
    {
        $this->get(route('home'))->assertOk()->assertDontSee('Judul Segar');

        $berita = $this->buatBerita('Judul Segar');
        $this->get(route('home'))->assertOk()->assertSee('Judul Segar');

        $berita->delete();
        $this->get(route('home'))->assertOk()->assertDontSee('Judul Segar');
    }

    public function test_warna_tema_langsung_berubah_setelah_disimpan(): void
    {
        $warna = color_pages::create(['color' => '#112233']);
        $this->get(route('home'))->assertOk()->assertSee('--theme-color: #112233', false);

        $warna->update(['color' => '#445566']);
        $this->get(route('home'))->assertOk()
            ->assertSee('--theme-color: #445566', false)
            ->assertDontSee('--theme-color: #112233', false);
    }

    public function test_teks_berita_disaring_walau_view_tidak_menyaring_ulang(): void
    {
        $this->buatBerita('Berita Berbahaya', '<p>Halo semua</p><script>alert(1)</script><img src="x" onerror="alert(2)">');

        $html = $this->get(route('home'))->assertOk()->getContent();
        $html2 = $this->get(route('home'))->assertOk()->getContent(); // dari cache

        foreach ([$html, $html2] as $isi) {
            $this->assertStringContainsString('Halo semua', $isi);
            $this->assertStringNotContainsString('alert(1)', $isi);
            $this->assertStringNotContainsString('onerror', $isi);
        }
    }

    public function test_bio_disaring_di_halaman_profile_dan_perubahan_langsung_tampil(): void
    {
        $bio = bio::create(['konten' => '<p>Bio awal</p><script>alert(7)</script>']);

        foreach ([1, 2] as $_) {
            $html = $this->get(route('profile'))->assertOk()->getContent();
            $this->assertStringContainsString('Bio awal', $html);
            $this->assertStringNotContainsString('alert(7)', $html);
        }

        $bio->update(['konten' => '<p>Bio sudah diganti</p>']);
        $this->get(route('profile'))->assertOk()->assertSee('Bio sudah diganti')->assertDontSee('Bio awal');
    }

    public function test_semua_data_seeder_tampil_sama_dari_cache_dan_dari_database(): void
    {
        // Data lengkap (header, album, banner, merchandise, profil, genre, booking, media sosial, dll.) lewat seeder asli.
        $this->seed([
            \Database\Seeders\bannerSeeder::class,
            \Database\Seeders\headerSeeder::class,
            \Database\Seeders\albumsSeeder::class,
            \Database\Seeders\merchandiseSeeder::class,
            \Database\Seeders\colorPagesSeeder::class,
            \Database\Seeders\profileSeeder::class,
        ]);

        foreach (['home', 'profile'] as $rute) {
            $dariDatabase = $this->get(route($rute))->assertOk()->getContent();   // kunjungan pertama: isi cache
            $dariCache = $this->get(route($rute))->assertOk()->getContent();      // kunjungan kedua: dibaca dari cache

            // Token CSRF/nonce bisa beda antar kunjungan, jadi dibuang sebelum dibandingkan.
            $samarkan = fn(string $html) => preg_replace('/name="csrf-token" content="[^"]*"|_token" value="[^"]*"/', '', $html);
            $this->assertSame($samarkan($dariDatabase), $samarkan($dariCache), "Halaman {$rute} dari cache harus identik dengan dari database.");
        }
    }

    public function test_sitemap_di_cache_tapi_alamatnya_tetap_mengikuti_domain(): void
    {
        $pertama = $this->jumlahQuery(fn() => $this->get('http://contoh.test/sitemap.xml')->assertOk()->assertSee('http://contoh.test/', false));
        $kedua = $this->jumlahQuery(fn() => $this->get('http://lain.test/sitemap.xml')->assertOk()->assertSee('http://lain.test/', false)->assertDontSee('contoh.test', false));

        $this->assertLessThan($pertama, $kedua);
        $this->assertLessThanOrEqual(1, $kedua);
    }

    public function test_cache_yang_rusak_tidak_mematikan_halaman(): void
    {
        Cache::shouldReceive('get')->once()->andThrow(new \RuntimeException('cache mati'));
        $this->assertSame(['ok'], CachePublik::ingat('uji', fn() => ['ok']));

        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')->once()->andThrow(new \RuntimeException('tulis gagal'));
        $this->assertSame(['ok'], CachePublik::ingat('uji', fn() => ['ok']));
    }

    public function test_dashboard_menampilkan_jumlah_tanpa_memuat_semua_baris(): void
    {
        foreach (range(1, 5) as $i) {
            $this->buatBerita('Berita ' . $i);
        }
        banner::create(['banner_name' => 'B1', 'link_banner' => 'https://contoh.test', 'banner_cover' => 'b1.webp']);
        banner::create(['banner_name' => 'B2', 'link_banner' => 'https://contoh.test', 'banner_cover' => 'b2.webp']);

        $this->withSession(['login' => true, 'user' => 'admin'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('newsTotal', 5)
            ->assertViewHas('bannerTotal', 2)
            ->assertViewMissing('news')
            ->assertSee('Berita Terbaru (5)')
            ->assertSee('+2 berita lainnya')
            ->assertSee('+1 lainnya')
            ->assertSee('B1');
    }
}