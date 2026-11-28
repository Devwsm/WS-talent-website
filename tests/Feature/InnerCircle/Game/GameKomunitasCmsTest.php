<?php

namespace Tests\Feature\InnerCircle\Game;

use App\Models\ic_game;
use App\Models\ic_pengaturan;
use App\Models\member;
use App\Services\InnerCircle\IcGame;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Game lewat Komunitas (landing cara main, peraturan, poin), ajakan di Beranda, dan aturan game yang diubah dari CMS.
 */
class GameKomunitasCmsTest extends TestCase
{
    use RefreshDatabase;

    private function anggota(): member
    {
        $m = member::create(['nama' => 'Pemain Uji', 'email' => 'k@contoh.test', 'password' => 'rahasia123']);
        $m->kode_member = sprintf('IC-%05d', $m->id_member);
        $m->email_verified_at = now();
        $m->save();

        return $m;
    }

    private function admin()
    {
        return $this->withSession(['login' => true, 'user' => 'admin']);
    }

    public function test_komunitas_punya_tab_game_dengan_cara_main_peraturan_dan_poin(): void
    {
        $html = $this->actingAs($this->anggota(), 'member')->get(route('ic.komunitas', ['tab' => 'game']))->assertOk()->getContent();

        foreach (['Pineapple Catcher', 'Cara main', 'Aturan main', 'Soal poin', 'Main sekarang', route('ic.game')] as $teks) {
            $this->assertStringContainsString($teks, $html);
        }
        // Bawaan: 5 ronde per hari, satu ronde paling lama 5 menit, 1 skor = 1 poin, maks 200 per ronde dan 1.000 per hari.
        $this->assertStringContainsString('Jatahmu 5 ronde per hari', $html);
        $this->assertStringContainsString('paling lama 5 menit', $html);
        $this->assertStringContainsString('1 skor = 1 poin', $html);
        $this->assertStringContainsString('maksimal 200 poin', $html);
        $this->assertStringContainsString('maksimal 1.000 poin', $html);
        // Banner bagikan kenangan tidak ikut tampil di tab Game.
        $this->assertStringNotContainsString('judul-banner', $html);
        $this->assertStringContainsString('komunitas?tab=game', $html);
    }

    public function test_tab_game_tidak_menandai_tab_lain_sebagai_dibaca(): void
    {
        $m = $this->anggota();
        $this->actingAs($m, 'member')->get(route('ic.komunitas', ['tab' => 'game']))->assertOk();

        $this->assertNull($m->fresh()->pengumuman_dibaca_pada);
        $this->assertNull($m->fresh()->galeri_dibaca_pada);
    }

    public function test_tab_game_punya_hero_bergambar_dan_navigasi_tab_selalu_di_atas_banner(): void
    {
        $m = $this->anggota();

        // Hero memakai aset game; teks aturan tetap lengkap (bisa dibaca penuh di kartu pendek).
        $html = $this->actingAs($m, 'member')->get(route('ic.komunitas', ['tab' => 'game']))->assertOk()->getContent();
        foreach (['ic-gk__hero', 'aset/game-mambo/cafe-beach.webp', 'aset/game-mambo/pineapple.webp', 'ic-gk__kartu--cara', 'ic-gk__kartu--aturan', 'ic-gk__kartu--poin'] as $teks) {
            $this->assertStringContainsString($teks, $html);
        }

        // Di tab Galeri, navigasi tab tampil SEBELUM banner (posisinya sama di semua tab, tidak melompat).
        $galeri = $this->actingAs($m, 'member')->get(route('ic.komunitas', ['tab' => 'galeri']))->assertOk()->getContent();
        $this->assertLessThan(strpos($galeri, 'judul-banner'), strpos($galeri, 'ic-tab-daftar'));
    }

    public function test_tombol_main_nonaktif_bila_jatah_habis(): void
    {
        config(['inner_circle.game.ronde_harian' => 1]);
        $m = $this->anggota();
        ic_game::create(['member_id' => $m->id_member, 'kode' => str_repeat('a', 32), 'seed' => 1, 'tanggal' => now()->toDateString(), 'status' => 'ditolak']);

        $this->actingAs($m, 'member')->get(route('ic.komunitas', ['tab' => 'game']))->assertOk()
            ->assertSee('Jatah ronde hari ini sudah habis')->assertDontSee(route('ic.game'));
    }

    public function test_game_nonaktif_menyembunyikan_tab_dan_ajakan_beranda(): void
    {
        $m = $this->anggota();
        $this->actingAs($m, 'member')->get(route('ic.beranda'))->assertOk()->assertSee('Main game, dapat poin!');

        $this->admin()->post(route('ic.admin.konten.game.simpan'), $this->isian(['aktif' => '0']))->assertSessionHasNoErrors();

        $this->actingAs($m, 'member')->get(route('ic.beranda'))->assertOk()->assertDontSee('Main game, dapat poin!');
        // Tab game diminta tapi game mati: jatuh ke Pengumuman.
        $this->actingAs($m, 'member')->get(route('ic.komunitas', ['tab' => 'game']))->assertOk()->assertDontSee('Cara main');
        $this->actingAs($m, 'member')->get(route('ic.game'))->assertNotFound();
    }

    public function test_beranda_punya_ajakan_main_ke_tab_game(): void
    {
        $this->actingAs($this->anggota(), 'member')->get(route('ic.beranda'))->assertOk()
            ->assertSee(route('ic.komunitas', ['tab' => 'game']))->assertSee('sampai 1.000 poin sehari');
    }

    // ---------- CMS ----------

    private function isian(array $ubah = []): array
    {
        return $ubah + ['aktif' => '1', 'ronde_harian' => 5, 'skor_per_poin' => 1, 'poin_maks_ronde' => 200, 'poin_maks_harian' => 1000];
    }

    public function test_halaman_aturan_game_menampilkan_bawaan_prototype(): void
    {
        $this->admin()->get(route('ic.admin.konten.bagian', 'game'))->assertOk()->assertSee('Aturan game')
            ->assertSee('name="ronde_harian"', false)->assertSee('value="200"', false);
    }

    public function test_perubahan_aturan_langsung_dipakai_tanpa_env(): void
    {
        $this->admin()->post(route('ic.admin.konten.game.simpan'), $this->isian(['ronde_harian' => 2, 'skor_per_poin' => 10, 'poin_maks_ronde' => 30, 'poin_maks_harian' => 60]))
            ->assertSessionHas('success', 'Aturan game disimpan. Berlaku untuk ronde berikutnya.');

        $e = app(IcGame::class)->efektif();
        $this->assertSame([true, 2, 10, 30, 60], [$e['aktif'], $e['ronde_harian'], $e['skor_per_poin'], $e['poin_maks_ronde'], $e['poin_maks_harian']]);

        $m = $this->anggota();
        $this->actingAs($m, 'member')->postJson(route('ic.game.mulai'))->assertOk();
        $this->actingAs($m, 'member')->postJson(route('ic.game.mulai'))->assertOk();
        $this->actingAs($m, 'member')->postJson(route('ic.game.mulai'))->assertStatus(429);

        // Teks halaman ikut angka baru.
        $this->actingAs($m, 'member')->get(route('ic.komunitas', ['tab' => 'game']))->assertSee('Jatahmu 2 ronde per hari')->assertSee('10 skor = 1 poin');
    }

    public function test_nilai_sama_dengan_bawaan_tidak_disimpan_dan_bisa_dikembalikan(): void
    {
        $this->admin()->post(route('ic.admin.konten.game.simpan'), $this->isian())->assertSessionHas('success', 'Tidak ada aturan game yang berubah.');
        $this->assertSame(0, ic_pengaturan::where('kunci', 'like', 'game.%')->count());

        $this->admin()->post(route('ic.admin.konten.game.simpan'), $this->isian(['ronde_harian' => 3]));
        $this->assertSame(1, ic_pengaturan::where('kunci', 'like', 'game.%')->count());

        $this->admin()->post(route('ic.admin.konten.game.bawaan'))->assertSessionHas('success');
        $this->assertSame(0, ic_pengaturan::where('kunci', 'like', 'game.%')->count());
        $this->assertSame(5, app(IcGame::class)->efektif()['ronde_harian']);
    }

    public function test_isian_di_luar_rentang_ditolak(): void
    {
        foreach ([['ronde_harian', 0], ['ronde_harian', 51], ['skor_per_poin', 0], ['poin_maks_ronde', 100001], ['poin_maks_harian', 'abc'], ['aktif', '2']] as [$kolom, $nilai]) {
            $this->admin()->post(route('ic.admin.konten.game.simpan'), $this->isian([$kolom => $nilai]))->assertSessionHasErrorsIn('game', $kolom);
        }
    }

    public function test_nilai_rusak_di_database_diabaikan(): void
    {
        ic_pengaturan::create(['kunci' => 'game.ronde_harian', 'nilai' => '999999']);
        ic_pengaturan::create(['kunci' => 'game.skor_per_poin', 'nilai' => 'abc']);

        $e = app(IcGame::class)->efektif();
        $this->assertSame(5, $e['ronde_harian']);
        $this->assertSame(1, $e['skor_per_poin']);
    }

    public function test_bawaan_prototype_5_ronde_5_menit_200_per_ronde_1000_per_hari(): void
    {
        $e = app(IcGame::class)->efektif();

        $this->assertSame([5, 1, 200, 1000], [$e['ronde_harian'], $e['skor_per_poin'], $e['poin_maks_ronde'], $e['poin_maks_harian']]);
        $this->assertSame(5, app(IcGame::class)->menitRonde());
        $this->assertSame(18000, (int) config('inner_circle.game.langkah_maks'));
        // 5 ronde x 200 poin = 1.000: batas harian sama dengan jatah penuh.
        $this->assertSame($e['ronde_harian'] * $e['poin_maks_ronde'], $e['poin_maks_harian']);
    }

    public function test_cms_butuh_login(): void
    {
        $this->post(route('ic.admin.konten.game.simpan'), $this->isian())->assertRedirect(route('login'));
    }

    public function test_halaman_cms_game_memuat_aturan_dan_semua_teks_tab_game_dengan_navbar_sendiri(): void
    {
        $html = $this->admin()->get(route('ic.admin.game'))->assertOk()->assertSee('Aturan game')
            ->assertSee('name="ronde_harian"', false)->assertSee('Bagian halaman Game')->getContent();

        foreach (['pengantar', 'cara', 'aturan', 'poin', 'papan', 'papan-kosong'] as $modul) {
            $this->assertStringContainsString('id="modul-game-' . $modul . '"', $html, $modul);
            $this->assertStringContainsString('href="#modul-game-' . $modul . '"', $html, $modul);
        }

        // Teks Game tidak lagi di tab Konten Halaman Komunitas.
        $this->admin()->get(route('ic.admin.konten.bagian', 'komunitas'))->assertOk()->assertDontSee('id="modul-komunitas-game"', false);
        // Menu Game ada di navbar CMS Inner Circle.
        $this->assertStringContainsString('href="' . route('ic.admin.game') . '"', $html);
        $this->flushSession();
        $this->get(route('ic.admin.game'))->assertRedirect(route('login'));
    }

    public function test_simpan_teks_game_kembali_ke_halaman_game_dan_tampil_di_tab_game(): void
    {
        $this->admin()->post(route('ic.admin.konten.simpan', 'game'), ['modul' => 'papan-kosong', 'k' => ['komunitas__game__papan__kosong__judul' => 'Sepi banget']])
            ->assertRedirect(route('ic.admin.game') . '#modul-game-papan-kosong');

        $this->actingAs($this->anggota(), 'member')->get(route('ic.komunitas', ['tab' => 'game']))->assertOk()->assertSee('Sepi banget');
        // Simpan aturan angka juga kembali ke halaman Game.
        $this->admin()->post(route('ic.admin.konten.game.simpan'), $this->isian(['ronde_harian' => 3]))->assertRedirect(route('ic.admin.game'));
    }

    // ---------- Papan skor ----------

    private function ronde(member $m, int $skor, string $status = 'selesai'): void
    {
        ic_game::create(['member_id' => $m->id_member, 'kode' => bin2hex(random_bytes(16)), 'seed' => 1, 'tanggal' => now()->toDateString(), 'status' => $status, 'skor' => $skor]);
    }

    public function test_papan_skor_kosong_mengajak_jadi_yang_pertama(): void
    {
        $this->actingAs($this->anggota(), 'member')->get(route('ic.komunitas', ['tab' => 'game']))->assertOk()
            ->assertSee('Papan skor')->assertSee('Ayo jadi yang pertama!')->assertDontSee('ic-gk__papan-daftar', false);
    }

    public function test_papan_skor_mengurutkan_rekor_dan_menandai_posisi_sendiri(): void
    {
        $saya = $this->anggota();
        $juara = member::create(['nama' => 'Si Juara', 'email' => 'j@contoh.test', 'password' => 'rahasia123']);
        $diblokir = member::create(['nama' => 'Akun Mati', 'email' => 'm@contoh.test', 'password' => 'rahasia123']);
        $diblokir->forceFill(['diblokir_pada' => now()])->save();

        $this->ronde($saya, 300);
        $this->ronde($saya, 120);
        $this->ronde($juara, 900);
        $this->ronde($diblokir, 5000);
        $this->ronde($juara, 9999, 'ditolak');

        $html = $this->actingAs($saya, 'member')->get(route('ic.komunitas', ['tab' => 'game']))->assertOk()->getContent();

        $this->assertStringNotContainsString('Akun Mati', $html);
        $this->assertStringNotContainsString('9.999', $html);
        $this->assertLessThan(strpos($html, 'Pemain Uji'), strpos($html, 'Si Juara'));
        $this->assertStringContainsString('is-saya', $html);

        $papan = app(\App\Services\InnerCircle\GameService::class)->papan($saya);
        $this->assertSame([900, 300], array_column($papan['daftar'], 'skor'));
        $this->assertSame([1, 2], array_column($papan['daftar'], 'peringkat'));
        $this->assertSame(['peringkat' => 2, 'skor' => 300], $papan['saya']);
    }

    public function test_papan_skor_hanya_sepuluh_teratas_dan_posisi_di_luar_daftar_tetap_ditampilkan(): void
    {
        $saya = $this->anggota();
        $this->ronde($saya, 10);
        for ($i = 1; $i <= 11; $i++) {
            $lain = member::create(['nama' => 'Pemain ' . $i, 'email' => "p$i@contoh.test", 'password' => 'rahasia123']);
            $this->ronde($lain, 100 + $i * 10);
        }

        $papan = app(\App\Services\InnerCircle\GameService::class)->papan($saya);
        $this->assertCount(10, $papan['daftar']);
        $this->assertSame(12, $papan['saya']['peringkat']);

        $this->actingAs($saya, 'member')->get(route('ic.komunitas', ['tab' => 'game']))->assertOk()
            ->assertSee('Kamu di peringkat 12 dengan rekor 10. Gas terus!');
    }

    public function test_tab_game_menumpuk_keranjang_di_atas_tokoh_dan_pengumuman_punya_banner_sendiri(): void
    {
        $m = $this->anggota();
        $html = $this->actingAs($m, 'member')->get(route('ic.komunitas', ['tab' => 'game']))->assertOk()->getContent();
        $this->assertStringContainsString('aset/game-mambo/basket.webp', $html);
        $this->assertStringContainsString('aset/game-mambo/dj-left.webp', $html);

        $this->actingAs($m, 'member')->get(route('ic.komunitas'))->assertOk()
            ->assertSee('ic-banner--kabar', false)->assertDontSee('Bagikan momen kamu');
    }
}