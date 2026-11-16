<?php

namespace Tests\Feature\Cms;

use App\Models\albums;
use App\Models\merchandise;
use App\Models\news;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Pencarian di daftar CMS Artis (?q=): Album, Berita, dan Merchandise. */
class PencarianCmsTest extends TestCase
{
    use RefreshDatabase;

    private function admin()
    {
        return $this->withSession(['login' => true, 'user' => 'admin-uji']);
    }

    private function album(string $nama, string $link = 'https://open.spotify.com/album/x'): albums
    {
        return albums::create(['albums_name' => $nama, 'link_spotify' => $link, 'albums_cover' => md5($nama) . '.webp', 'urutan' => albums::urutanBerikut()]);
    }

    public function test_album_dicari_dari_nama_dan_tombol_urutan_disembunyikan(): void
    {
        $a = $this->album('Bounce Hits');
        $this->album('Sunset Mix');

        $this->admin()->get(route('albums', ['q' => 'bounce']))
            ->assertOk()
            ->assertSee(md5('Bounce Hits') . '.webp', false)
            ->assertDontSee(md5('Sunset Mix') . '.webp', false)
            ->assertDontSee(route('albums.urutan', $a->id_albums), false)
            ->assertSee('Urutan tidak bisa diubah saat mencari');
    }

    public function test_tanpa_hasil_menampilkan_pesan_yang_menyebut_kata_cari(): void
    {
        $this->album('Bounce Hits');

        $this->admin()->get(route('albums', ['q' => 'zzz']))->assertOk()->assertSee('Tidak ada album yang cocok dengan “zzz”.');
    }

    public function test_persen_dan_garis_bawah_dibaca_apa_adanya(): void
    {
        $this->album('Diskon 50% Mix');
        $this->album('Diskon 500 Mix');
        $this->album('Satu_Dua');
        $this->album('SatuXDua');

        $html = $this->admin()->get(route('albums', ['q' => '50%']))->getContent();
        $this->assertStringContainsString(md5('Diskon 50% Mix') . '.webp', $html);
        $this->assertStringNotContainsString(md5('Diskon 500 Mix') . '.webp', $html);

        $html = $this->admin()->get(route('albums', ['q' => 'Satu_Dua']))->getContent();
        $this->assertStringContainsString(md5('Satu_Dua') . '.webp', $html);
        $this->assertStringNotContainsString(md5('SatuXDua') . '.webp', $html);
    }

    public function test_berita_dicari_dari_judul_atau_sumber(): void
    {
        foreach ([['Konser Jakarta', 'Kompas'], ['Album Baru', 'Detik']] as [$judul, $sumber]) {
            news::create(['news_title' => $judul, 'news_description' => '<p>x</p>', 'news_source' => $sumber, 'news_date' => '2026-11-01', 'news_cover' => md5($judul) . '.webp', 'news_link' => 'https://a.test']);
        }

        $this->admin()->get(route('news', ['q' => 'konser']))->assertSee(md5('Konser Jakarta') . '.webp', false)->assertDontSee(md5('Album Baru') . '.webp', false);
        $this->admin()->get(route('news', ['q' => 'detik']))->assertSee(md5('Album Baru') . '.webp', false)->assertDontSee(md5('Konser Jakarta') . '.webp', false);
    }

    public function test_merchandise_dicari_dan_kata_cari_ikut_di_halaman_berikutnya(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            merchandise::create(['merchandise_name' => "Kaos $i", 'link_merchandise' => 'https://a.test', 'merchandise_cover' => "kaos$i.webp", 'urutan' => $i]);
        }
        merchandise::create(['merchandise_name' => 'Topi', 'link_merchandise' => 'https://a.test', 'merchandise_cover' => 'topi.webp', 'urutan' => 13]);

        $this->admin()->get(route('merchandise', ['q' => 'kaos']))
            ->assertOk()
            ->assertDontSee('topi.webp', false)
            ->assertSee('q=kaos', false); // tautan halaman 2 membawa kata cari
    }
}