<?php

namespace Tests\Feature\InnerCircle\KontenCms;

use App\Models\ic_pengaturan;
use App\Services\InnerCircle\IcKonten;
use App\Services\InnerCircle\IcPrivasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Isi /privasi yang bisa diubah dari CMS: penyaringan HTML dan tanggal otomatis.
 */
class KebijakanPrivasiTest extends TestCase
{
    use RefreshDatabase;
    // ---------- Email: bawaan identik ----------
    // ---------- Email: simpan ----------
    // ---------- Email: keamanan ----------
    // ---------- Privasi ----------

    private function admin()
    {
        return $this->withSession(['login' => true, 'user' => 'admin']);
    }

    private function simpanPrivasi(string $html)
    {
        return $this->admin()->post(route('ic.admin.konten.privasi.simpan'), ['isi' => $html]);
    }

    public function test_privasi_bawaan_tidak_berubah(): void
    {
        $this->get(route('privasi'))->assertOk()
            ->assertSee('Saat kamu hanya melihat situs')
            ->assertSee('Masuk dengan Google')
            ->assertSee('Diperbarui 31 Oktober 2026');
        $this->assertFalse(app(IcPrivasi::class)->sudahKustom());
    }

    public function test_isi_privasi_tersimpan_tampil_dan_tanggal_terisi_otomatis(): void
    {
        $this->travelTo('2027-02-03 10:00:00');

        $this->simpanPrivasi('<h2>Data kami</h2><p>Kami <strong>tidak</strong> menjual datamu.</p><ul><li>Satu</li></ul>')
            ->assertSessionHasNoErrors()->assertRedirect(route('ic.admin.konten.bagian', 'privasi'));

        $this->get(route('privasi'))->assertOk()
            ->assertSee('<h2>Data kami</h2>', false)
            ->assertSee('Kami <strong>tidak</strong> menjual datamu.', false)
            ->assertSee('Diperbarui 3 Februari 2027')
            ->assertDontSee('Saat kamu hanya melihat situs');
    }

    public function test_html_berbahaya_di_privasi_dibersihkan(): void
    {
        $this->simpanPrivasi('<p onclick="x()">Halo</p><script>alert(1)</script><iframe src="https://jahat.test"></iframe><a href="javascript:alert(1)">klik</a><img src=x onerror=alert(1)>')
            ->assertSessionHasNoErrors();

        $html = $this->get(route('privasi'))->assertOk()->getContent();
        $this->assertStringContainsString('Halo', $html);
        $this->assertStringNotContainsString('alert(1)', $html);
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringNotContainsString('jahat.test', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
    }

    public function test_isi_rusak_di_database_dibersihkan_saat_dibaca(): void
    {
        ic_pengaturan::create(['kunci' => 'privasi.isi', 'nilai' => '<p>Aman</p><script>alert("db")</script>']);
        app(IcKonten::class)->lupakan();

        $html = $this->get(route('privasi'))->assertOk()->getContent();
        $this->assertStringContainsString('Aman', $html);
        $this->assertStringNotContainsString('alert("db")', $html);
    }

    public function test_isi_kosong_atau_kepanjangan_ditolak(): void
    {
        foreach (['', '   ', '<p><br></p>', '<p>&nbsp;</p>', '<script>x</script>'] as $kosong) {
            $this->simpanPrivasi($kosong)->assertSessionHasErrorsIn('privasi', 'isi');
        }
        $this->simpanPrivasi('<p>' . str_repeat('a', 30001) . '</p>')->assertSessionHasErrorsIn('privasi', 'isi');

        $this->assertDatabaseMissing('ic_pengaturan', ['kunci' => 'privasi.isi']);
    }

    public function test_tanggal_tidak_berubah_bila_isi_sama(): void
    {
        $this->simpanPrivasi('<p>Isi tetap</p>');
        ic_pengaturan::query()->where('kunci', 'privasi.diperbarui')->update(['nilai' => '2026-01-02']);
        app(IcKonten::class)->lupakan();

        $this->simpanPrivasi('<p>Isi tetap</p>')->assertSessionHasNoErrors();
        $this->assertSame('2026-01-02', app(IcPrivasi::class)->diperbarui());

        $this->simpanPrivasi('<p>Isi berubah</p>');
        $this->assertSame(now()->toDateString(), app(IcPrivasi::class)->diperbarui());
    }

    public function test_kembalikan_teks_bawaan_privasi(): void
    {
        $this->simpanPrivasi('<p>Isi sendiri</p>');

        $this->admin()->post(route('ic.admin.konten.privasi.bawaan'))->assertRedirect(route('ic.admin.konten.bagian', 'privasi'))->assertSessionHas('success');

        $this->assertDatabaseMissing('ic_pengaturan', ['kunci' => 'privasi.isi']);
        $this->assertDatabaseMissing('ic_pengaturan', ['kunci' => 'privasi.diperbarui']);
        $this->get(route('privasi'))->assertSee('Saat kamu hanya melihat situs')->assertSee('Diperbarui 31 Oktober 2026');
    }

    public function test_tanggal_rusak_memakai_tanggal_config(): void
    {
        ic_pengaturan::create(['kunci' => 'privasi.diperbarui', 'nilai' => 'kemarin']);
        app(IcKonten::class)->lupakan();

        $this->assertSame(config('site.privasi_diperbarui'), app(IcPrivasi::class)->diperbarui());
    }

    public function test_cms_menampilkan_editor_dengan_teks_bawaan_lalu_isi_sendiri(): void
    {
        $this->admin()->get(route('ic.admin.konten.bagian', 'privasi'))->assertOk()
            ->assertSee('id="privasi"', false)->assertSee('Kebijakan privasi')
            ->assertSee('Saat kamu hanya melihat situs')->assertSee('teks bawaan');

        $this->simpanPrivasi('<p>Isi sendiri yang unik</p>');

        $this->admin()->get(route('ic.admin.konten.bagian', 'privasi'))->assertOk()
            ->assertSee('Isi sendiri yang unik')->assertDontSee('Saat kamu hanya melihat situs')->assertSee('Kembalikan teks');
    }

    public function test_hanya_admin_yang_boleh_mengubah_privasi(): void
    {
        $this->post(route('ic.admin.konten.privasi.simpan'), ['isi' => '<p>x</p>'])->assertRedirect(route('login'));
        $this->post(route('ic.admin.konten.privasi.bawaan'))->assertRedirect(route('login'));
        $this->assertSame(0, ic_pengaturan::query()->count());
    }

    public function test_deteksi_kosong(): void
    {
        $this->assertTrue(IcPrivasi::kosong(null));
        $this->assertTrue(IcPrivasi::kosong('<p><br></p>'));
        $this->assertTrue(IcPrivasi::kosong("<p>&nbsp; \u{00A0}</p>"));
        $this->assertFalse(IcPrivasi::kosong('<p>Isi</p>'));
    }
}
