<?php

namespace Tests\Feature\Cms;

use App\Models\albums;
use App\Models\news;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** Jadwal tayang berita dan album: belum tampil sebelum waktunya, tampil tepat waktu walau halaman sudah di-cache. */
class JadwalTayangTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seperti production: nilai cache benar-benar diserialisasi (lihat CachePublikTest).
        config(['cache.stores.array.serialize' => true]);
        Cache::forgetDriver('array');
        Carbon::setTestNow(Carbon::parse('2026-11-16 10:00:00', 'Asia/Jakarta'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function admin()
    {
        return $this->withSession(['login' => true, 'user' => 'admin-uji']);
    }

    private function berita(string $judul, ?string $terbit = null): news
    {
        return news::create([
            'news_title' => $judul,
            'news_description' => '<p>Isi</p>',
            'news_source' => 'Media',
            'news_date' => '2026-11-01',
            'news_cover' => $judul . '.webp',
            'news_link' => 'https://contoh.test/a',
            'terbit_pada' => $terbit,
        ]);
    }

    private function album(string $nama, ?string $terbit = null): albums
    {
        return albums::create([
            'albums_name' => $nama,
            'link_spotify' => 'https://open.spotify.com/album/x',
            'albums_cover' => $nama . '.webp',
            'urutan' => albums::urutanBerikut(),
            'terbit_pada' => $terbit,
        ]);
    }

    public function test_berita_dan_album_terjadwal_belum_tampil_tapi_yang_biasa_tetap_tampil(): void
    {
        $this->berita('LangsungTayang');
        $this->berita('MasihTunggu', '2026-11-20 19:00:00');
        $this->album('albumlama');
        $this->album('albumbaru', '2026-11-20 00:00:00');

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('LangsungTayang', $html);
        $this->assertStringNotContainsString('MasihTunggu', $html);
        $this->assertStringContainsString('albumlama.webp', $html);
        $this->assertStringNotContainsString('albumbaru.webp', $html);
    }

    public function test_tampil_tepat_waktu_walau_halaman_sudah_di_cache(): void
    {
        $this->berita('LangsungTayang');
        $this->berita('MasihTunggu', '2026-11-20 19:00:00');
        $this->album('albumbaru', '2026-11-20 00:00:00');

        $this->get('/')->assertDontSee('MasihTunggu')->assertDontSee('albumbaru.webp', false); // isi cache

        Carbon::setTestNow(Carbon::parse('2026-11-20 19:00:01', 'Asia/Jakarta'));

        $html = $this->get('/')->getContent();
        $this->assertStringContainsString('MasihTunggu', $html);
        $this->assertStringContainsString('albumbaru.webp', $html);
    }

    public function test_berita_terjadwal_diurutkan_menurut_saat_tayangnya(): void
    {
        $this->berita('Lama', '2026-11-01 08:00:00');
        $this->berita('Dijadwalkan', '2026-11-18 08:00:00'); // dibuat belakangan tapi tayang paling akhir
        $this->berita('Tengah', '2026-11-10 08:00:00');

        $this->get('/')->getContent(); // cache terisi sebelum jadwalnya tiba
        Carbon::setTestNow(Carbon::parse('2026-11-19 00:00:00', 'Asia/Jakarta'));

        $this->get('/')->assertSeeInOrder(['Dijadwalkan', 'Tengah', 'Lama']);
    }

    public function test_link_news_di_navbar_baru_muncul_saat_berita_pertama_tayang(): void
    {
        $this->berita('MasihTunggu', '2026-11-20 19:00:00');

        $this->get('/profile')->assertOk()->assertDontSee('#news', false);

        Carbon::setTestNow(Carbon::parse('2026-11-21 00:00:00', 'Asia/Jakarta'));
        $this->get('/profile')->assertOk()->assertSee('#news', false);
    }

    public function test_admin_menambah_berita_terjadwal_dan_pesannya_menyebut_waktunya(): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('Ekstensi GD dibutuhkan untuk membuat gambar uji.');
        }

        $this->admin()->post(route('news.tambah'), [
            'news_title' => 'Rilis Jumat',
            'news_description' => '<p>Isi</p>',
            'news_source' => 'Media',
            'news_date' => '2026-11-20',
            'news_link' => 'https://contoh.test/a',
            'news_cover' => \Illuminate\Http\UploadedFile::fake()->image('c.jpg', 100, 100),
            'terbit_pada' => '2026-11-20T19:00',
        ])->assertSessionHas('success', fn($p) => str_contains($p, 'dijadwalkan') && str_contains($p, '20 November 2026, 19.00'));

        $item = news::where('news_title', 'Rilis Jumat')->firstOrFail();
        $this->assertTrue($item->terjadwal);
        $this->assertSame('2026-11-20 19:00:00', $item->terbit_pada->format('Y-m-d H:i:s'));
    }

    public function test_format_jadwal_salah_ditolak_dan_kosong_berarti_langsung_tampil(): void
    {
        $item = $this->berita('Judul', '2026-11-20 19:00:00');
        $isi = [
            'news_title' => 'Judul',
            'news_description' => '<p>Isi</p>',
            'news_source' => 'Media',
            'news_date' => '2026-11-01',
            'news_link' => 'https://contoh.test/a',
        ];

        $this->admin()->put(route('news.update', $item->id_news), $isi + ['terbit_pada' => 'besok sore'])
            ->assertSessionHasErrors('terbit_pada');
        $this->assertTrue($item->fresh()->terjadwal);

        $this->admin()->put(route('news.update', $item->id_news), $isi + ['terbit_pada' => ''])->assertSessionHasNoErrors();
        $this->assertNull($item->fresh()->terbit_pada);
        $this->assertFalse($item->fresh()->terjadwal);
    }

    public function test_daftar_cms_menandai_yang_terjadwal(): void
    {
        $this->berita('MasihTunggu', '2026-11-20 19:00:00');
        $this->album('albumbaru', '2026-11-20 00:00:00');

        $this->admin()->get(route('news'))->assertOk()->assertSee('Terjadwal · 20 Nov 2026, 19.00');
        $this->admin()->get(route('albums'))->assertOk()->assertSee('Terjadwal · 20 Nov 2026, 00.00');
    }
}