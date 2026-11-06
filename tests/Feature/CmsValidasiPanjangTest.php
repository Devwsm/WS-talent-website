<?php

namespace Tests\Feature;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/** Isian CMS Artis dibatasi sesuai kolom database (255), jadi teks kepanjangan ditolak rapi, bukan error 500. */
class CmsValidasiPanjangTest extends TestCase
{
    use RefreshDatabase;

    private function admin()
    {
        return $this->withSession(['login' => true, 'user' => 'admin']);
    }

    public function test_banner_menolak_nama_dan_tautan_kepanjangan(): void
    {
        $this->admin()->post(route('banner.tambah'), [
            'banner_name' => str_repeat('a', 256),
            'link_banner' => 'https://contoh.id/' . str_repeat('x', 300),
        ])->assertSessionHasErrors(['banner_name', 'link_banner']);
    }

    public function test_statistik_menolak_isian_kepanjangan(): void
    {
        $this->admin()->post(route('statistik.tambah'), [
            'total' => str_repeat('1', 256),
            'platform' => str_repeat('p', 256),
        ])->assertSessionHasErrors(['total', 'platform']);
    }

    public function test_media_sosial_hanya_menerima_http_dan_https(): void
    {
        $this->admin()->post(route('media_sosial.tambah'), ['platform' => 'Situs', 'url' => 'ftp://contoh.id/berkas'])
            ->assertSessionHasErrors('url');

        $this->admin()->post(route('media_sosial.tambah'), ['platform' => 'Instagram', 'url' => 'https://instagram.com/whisnusantika'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('media_sosial', ['platform' => 'Instagram']);
    }

    public function test_batas_unggah_kenangan_punya_dua_kunci_berbeda(): void
    {
        $batas = RateLimiter::limiter('ic-kenangan')(Request::create('/', 'POST'));

        $this->assertCount(2, $batas);
        $kunci = array_map(fn(Limit $b) => $b->key, $batas);
        $this->assertCount(2, array_unique($kunci), 'Batas per menit dan per hari tidak boleh berbagi kunci.');
    }
}