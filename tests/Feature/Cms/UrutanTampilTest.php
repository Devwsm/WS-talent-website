<?php

namespace Tests\Feature\Cms;

use App\Models\albums;
use App\Models\banner;
use App\Models\merchandise;
use App\Models\log_aktivitas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Urutan tampil album, banner, header, dan merchandise: satu urutan untuk halaman publik dan daftar CMS. */
class UrutanTampilTest extends TestCase
{
    use RefreshDatabase;

    private function admin()
    {
        return $this->withSession(['login' => true, 'user' => 'admin-uji']);
    }

    private function album(string $nama, ?int $urutan = null): albums
    {
        return albums::create([
            'albums_name' => $nama,
            'link_spotify' => 'https://open.spotify.com/album/' . $nama,
            'albums_cover' => $nama . '.webp',
            'urutan' => $urutan ?? albums::urutanBerikut(),
        ]);
    }

    /** @return string[] nama album sesuai urutan di database */
    private function urutan(): array
    {
        return albums::query()->urut()->pluck('albums_name')->all();
    }

    public function test_data_baru_masuk_paling_belakang(): void
    {
        $this->album('a');
        $this->album('b');
        $this->album('c');

        $this->assertSame(['a', 'b', 'c'], $this->urutan());
        $this->assertSame(4, albums::urutanBerikut());
    }

    public function test_geser_naik_dan_turun_satu_langkah(): void
    {
        $a = $this->album('a');
        $b = $this->album('b');
        $c = $this->album('c');

        $this->admin()->patch(route('albums.urutan', $c->id_albums), ['arah' => 'naik'])->assertRedirect();
        $this->assertSame(['a', 'c', 'b'], $this->urutan());

        $this->admin()->patch(route('albums.urutan', $a->id_albums), ['arah' => 'turun'])->assertRedirect();
        $this->assertSame(['c', 'a', 'b'], $this->urutan());
    }

    public function test_jadikan_pertama_menyematkan_di_depan(): void
    {
        $this->album('a');
        $this->album('b');
        $c = $this->album('c');

        $this->admin()->patch(route('albums.urutan', $c->id_albums), ['arah' => 'awal']);

        $this->assertSame(['c', 'a', 'b'], $this->urutan());
    }

    public function test_di_ujung_tidak_ada_yang_berubah_dan_pesannya_jelas(): void
    {
        $a = $this->album('a');
        $this->album('b');

        $this->admin()->patch(route('albums.urutan', $a->id_albums), ['arah' => 'naik'])
            ->assertSessionHas('success', 'Album ini sudah paling depan.');
        $this->assertSame(['a', 'b'], $this->urutan());
    }

    public function test_arah_tidak_dikenal_ditolak_dan_data_tidak_ada_tidak_error(): void
    {
        $a = $this->album('a');

        $this->admin()->patch(route('albums.urutan', $a->id_albums), ['arah' => 'miring'])->assertSessionHasErrors('arah');
        $this->admin()->patch(route('albums.urutan', 9999), ['arah' => 'naik'])->assertRedirect(route('albums'));
    }

    public function test_tanpa_login_tidak_bisa_menggeser(): void
    {
        $a = $this->album('a');
        $this->album('b');

        $this->patch(route('albums.urutan', $a->id_albums), ['arah' => 'turun'])->assertRedirect();
        $this->assertSame(['a', 'b'], $this->urutan());
    }

    public function test_urutan_yang_dengan_nomor_ganda_tetap_pasti_dan_dirapatkan(): void
    {
        // Data lama / hapus-tengah: nomor boleh bolong atau sama, urutannya tetap pasti (kunci utama pemutus).
        $this->album('a', 5);
        $b = $this->album('b', 5);
        $this->album('c', 9);
        $this->assertSame(['a', 'b', 'c'], $this->urutan());

        $this->admin()->patch(route('albums.urutan', $b->id_albums), ['arah' => 'naik']);

        $this->assertSame(['b', 'a', 'c'], $this->urutan());
        $this->assertSame([1, 2, 3], albums::query()->urut()->pluck('urutan')->all());
    }

    public function test_halaman_publik_mengikuti_urutan_dan_cache_langsung_diperbarui(): void
    {
        $this->album('pertama');
        $kedua = $this->album('kedua');

        $urutanDiHtml = fn() => array_keys(array_filter([
            'pertama' => strpos($this->get('/')->getContent(), 'pertama.webp'),
            'kedua' => strpos($this->get('/')->getContent(), 'kedua.webp'),
        ], fn($p) => $p !== false)) === ['pertama', 'kedua'];

        $this->assertTrue($urutanDiHtml());

        $this->admin()->patch(route('albums.urutan', $kedua->id_albums), ['arah' => 'awal']);

        $html = $this->get('/')->getContent();
        $this->assertLessThan(strpos($html, 'pertama.webp'), strpos($html, 'kedua.webp'));
    }

    public function test_daftar_cms_memakai_urutan_yang_sama_dan_memuat_kontrol(): void
    {
        $this->album('a');
        $b = $this->album('b');

        $this->admin()->patch(route('albums.urutan', $b->id_albums), ['arah' => 'awal']);

        $this->admin()->get(route('albums'))
            ->assertOk()
            ->assertSeeInOrder(['b.webp', 'a.webp'], false)
            ->assertSee(route('albums.urutan', $b->id_albums), false)
            ->assertSee('Jadikan pertama');
    }

    public function test_menggeser_banner_dan_merchandise_juga_jalan(): void
    {
        $x = banner::create(['banner_name' => 'x', 'link_banner' => 'https://a.test', 'banner_cover' => 'x.webp', 'urutan' => 1]);
        $y = banner::create(['banner_name' => 'y', 'link_banner' => 'https://a.test', 'banner_cover' => 'y.webp', 'urutan' => 2]);
        $this->admin()->patch(route('banner.urutan', $y->id_banner), ['arah' => 'naik']);
        $this->assertSame(['y', 'x'], banner::query()->urut()->pluck('banner_name')->all());

        $m1 = merchandise::create(['merchandise_name' => 'm1', 'link_merchandise' => 'https://a.test', 'merchandise_cover' => 'm1.webp', 'urutan' => 1]);
        merchandise::create(['merchandise_name' => 'm2', 'link_merchandise' => 'https://a.test', 'merchandise_cover' => 'm2.webp', 'urutan' => 2]);
        $this->admin()->patch(route('merchandise.urutan', $m1->id_merchandise), ['arah' => 'turun']);
        $this->assertSame(['m2', 'm1'], merchandise::query()->urut()->pluck('merchandise_name')->all());
    }

    public function test_halaman_banner_header_merchandise_dan_dashboard_tampil_dengan_kontrol_urutan(): void
    {
        $b = banner::create(['banner_name' => 'bn', 'link_banner' => 'https://a.test', 'banner_cover' => 'bn.webp', 'urutan' => 1]);
        $h = \App\Models\header::create([
            'header_color' => '#ff0000',
            'header_title' => 'ht',
            'header_img' => 'h.webp',
            'header_name' => 'hn',
            'header_description' => 'd',
            'link_header' => 'https://a.test',
            'header_background' => 'hb.webp',
            'urutan' => 1
        ]);
        $m = merchandise::create(['merchandise_name' => 'mm', 'link_merchandise' => 'https://a.test', 'merchandise_cover' => 'mm.webp', 'urutan' => 1]);

        $this->admin()->get(route('banner'))->assertOk()->assertSee(route('banner.urutan', $b->id_banner), false)->assertSee('new MediaPreview', false);
        $this->admin()->get(route('headers'))->assertOk()->assertSee(route('headers.urutan', $h->id_header), false);
        $this->admin()->get(route('merchandise'))->assertOk()->assertSee(route('merchandise.urutan', $m->id_merchandise), false);
        $this->admin()->get(route('dashboard'))->assertOk();
    }

    public function test_menggeser_tercatat_di_log_aktivitas_dengan_bahasa_sehari_hari(): void
    {
        $this->album('a');
        $b = $this->album('b');

        $this->admin()->patch(route('albums.urutan', $b->id_albums), ['arah' => 'naik']);

        $log = log_aktivitas::query()->where('rute', 'albums.urutan')->first();
        $this->assertNotNull($log);
        $this->assertSame('Urutan Album diubah', $log->aksi);
        $this->assertStringContainsString('b', (string) $log->keterangan);
    }
}