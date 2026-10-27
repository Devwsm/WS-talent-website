<?php

namespace Tests\Feature;

use App\Models\ic_claim;
use App\Models\ic_claim_log;
use App\Models\ic_memory;
use App\Models\ic_reward;
use App\Models\member;
use App\Services\InnerCircle\StempelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Fase 8b: halaman /privasi (syarat Google untuk status In production) dan hapus akun oleh anggota sendiri.
 */
class InnerCircleAkunPrivasiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['inner_circle.aktif' => true, 'site.kontak_privasi' => null]);
        Storage::fake('public');
    }

    /**
     * Teks halaman tanpa tag HTML dengan spasi dan baris baru dilipat jadi satu spasi. Pemformat Blade/Prettier
     * boleh memecah "Hapus akunku" jadi dua baris tanpa membuat tes gagal.
     */
    private function teks(TestResponse $respons): string
    {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($respons->getContent()), ENT_QUOTES | ENT_HTML5)));
    }

    private function adaBerkas(string $path): bool
    {
        return Storage::disk('public')->exists($path);
    }

    private function anggota(string $email = 'a@contoh.test', ?string $sandi = 'sandi-benar-123'): member
    {
        $m = member::create(['nama' => 'Anggota Uji', 'email' => $email, 'password' => $sandi]);
        $m->kode_member = sprintf('IC-%05d', $m->id_member);
        $m->save();

        return $m->fresh();
    }

    private function hapus(member $m, array $data = [])
    {
        return $this->actingAs($m, 'member')->delete(route('ic.akun.hapus'), $data);
    }

    private function klaim(member $m, string $status): ic_claim
    {
        $reward = ic_reward::orderBy('target_stempel')->firstOrFail();
        $k = ic_claim::create(['member_id' => $m->id_member, 'reward_id' => $reward->id_reward, 'status' => $status]);
        ic_claim_log::create(['claim_id' => $k->id_claim, 'status' => 'diajukan']);

        return $k;
    }

    private function kenangan(member $m, ?string $foto = null): ic_memory
    {
        if ($foto) {
            Storage::disk('public')->put($foto, 'gambar');
        }

        return ic_memory::create([
            'member_id' => $m->id_member,
            'caption' => 'Kenangan uji',
            'tempat' => 'Tempat',
            'kategori' => 'festival',
            'status' => 'tayang',
            'foto' => $foto,
        ]);
    }

    // ---------- /privasi ----------

    public function test_halaman_privasi_publik_dan_tidak_ikut_flag_inner_circle(): void
    {
        config(['inner_circle.aktif' => false]);

        $respons = $this->get('/privasi')
            ->assertOk()
            ->assertSee('<title>Kebijakan Privasi — Whisnu Santika</title>', false);

        foreach (['Kebijakan Privasi', 'Masuk dengan Google', 'Google API Services User Data Policy', 'Hapus akunku'] as $kalimat) {
            $this->assertStringContainsString($kalimat, $this->teks($respons));
        }
    }

    public function test_kontak_privasi_tampil_bila_diisi_dan_ada_alternatif_bila_kosong(): void
    {
        $this->get('/privasi')->assertOk()->assertDontSee('mailto:')->assertSee(route('profile'), false);

        config(['site.kontak_privasi' => 'privasi@contoh.test']);
        $this->get('/privasi')->assertOk()->assertSee('mailto:privasi@contoh.test', false);
    }

    public function test_privasi_ada_di_sitemap_footer_dan_halaman_masuk_daftar(): void
    {
        $this->get('/sitemap.xml')->assertOk()->assertSee(url('/privasi'), false);
        $beranda = $this->get('/')->assertOk()->assertSee(route('privasi'), false);
        $this->assertStringContainsString('Kebijakan Privasi', $this->teks($beranda));
        $this->get(route('ic.masuk'))->assertOk()->assertSee(route('privasi'), false);
        $this->get(route('ic.daftar'))->assertOk()->assertSee(route('privasi'), false);
    }

    // ---------- hapus akun: validasi ----------

    public function test_hapus_akun_butuh_login_anggota(): void
    {
        $this->delete(route('ic.akun.hapus'), ['konfirmasi' => 'HAPUS'])->assertRedirect(route('ic.masuk'));
    }

    public function test_konfirmasi_salah_atau_kosong_ditolak_dan_akun_aman(): void
    {
        $m = $this->anggota();

        $this->hapus($m, ['konfirmasi' => 'hapus aja', 'sandi' => 'sandi-benar-123'])->assertSessionHasErrors('konfirmasi');
        $this->hapus($m, ['sandi' => 'sandi-benar-123'])->assertSessionHasErrors('konfirmasi');

        $this->assertDatabaseHas('members', ['id_member' => $m->id_member]);
    }

    public function test_akun_bersandi_wajib_sandi_yang_benar(): void
    {
        $m = $this->anggota();

        $this->hapus($m, ['konfirmasi' => 'HAPUS'])->assertSessionHasErrors('sandi');
        $this->hapus($m, ['konfirmasi' => 'HAPUS', 'sandi' => 'sandi-salah-123'])->assertSessionHasErrors('sandi');

        $this->assertDatabaseHas('members', ['id_member' => $m->id_member]);
    }

    public function test_sandi_tidak_tersimpan_di_sesi_saat_validasi_gagal(): void
    {
        $this->post(route('ic.daftar.proses'), ['nama' => '', 'email' => 'bukan-email', 'sandi' => 'RAHASIA-DAFTAR-1', 'sandi_confirmation' => 'RAHASIA-DAFTAR-1']);
        $this->assertStringNotContainsString('RAHASIA-DAFTAR-1', json_encode(session()->get('_old_input')));

        $this->post(route('ic.masuk.proses'), ['email' => 'bukan-email', 'sandi' => 'RAHASIA-MASUK-1']);
        $this->assertStringNotContainsString('RAHASIA-MASUK-1', json_encode(session()->get('_old_input')));

        $m = $this->anggota();
        $this->hapus($m, ['konfirmasi' => 'salah', 'sandi' => 'RAHASIA-HAPUS-1']);
        $this->assertStringNotContainsString('RAHASIA-HAPUS-1', json_encode(session()->get('_old_input')));
    }

    // ---------- hapus akun: berhasil ----------

    public function test_hapus_akun_bersandi_berhasil_keluar_dan_sesi_admin_tetap(): void
    {
        $m = $this->anggota();

        $this->withSession(['login' => true, 'user' => 'admin'])
            ->actingAs($m, 'member')
            ->delete(route('ic.akun.hapus'), ['konfirmasi' => ' hapus ', 'sandi' => 'sandi-benar-123'])
            ->assertRedirect(route('ic.masuk'))
            ->assertSessionHas('ic_toast');

        $this->assertDatabaseMissing('members', ['id_member' => $m->id_member]);
        $this->assertGuest('member');
        $this->assertTrue(session('login'), 'Sesi admin CMS di browser yang sama tidak boleh ikut terhapus.');
        $this->assertNull(session('ic_sesi_versi'));
    }

    public function test_akun_hanya_google_cukup_mengetik_hapus(): void
    {
        $m = $this->anggota('g@contoh.test', null);
        $m->forceFill(['google_id' => '12345', 'email_verified_at' => now()])->save();

        $this->hapus($m, ['konfirmasi' => 'HAPUS'])->assertRedirect(route('ic.masuk'));

        $this->assertDatabaseMissing('members', ['id_member' => $m->id_member]);
    }

    public function test_semua_data_anggota_ikut_terhapus_termasuk_berkas_dan_data_orang_lain_aman(): void
    {
        $m = $this->anggota('hapus@contoh.test');
        $lain = $this->anggota('lain@contoh.test');

        app(StempelService::class)->tambahManual($m, 2, 'Uji', 'admin');
        app(StempelService::class)->tambahManual($lain, 3, 'Uji', 'admin');
        $this->klaim($m, 'selesai');
        $m->forceFill(['avatar' => 'inner-circle/avatar/saya.webp'])->save();
        Storage::disk('public')->put('inner-circle/avatar/saya.webp', 'avatar');

        $kenanganSaya = $this->kenangan($m, 'inner-circle/kenangan/saya.webp');
        $kenanganTanpaFoto = $this->kenangan($m);
        $kenanganLain = $this->kenangan($lain, 'inner-circle/kenangan/lain.webp');
        DB::table('ic_memory_likes')->insert([
            ['member_id' => $m->id_member, 'memory_id' => $kenanganLain->id_memory],
            ['member_id' => $lain->id_member, 'memory_id' => $kenanganSaya->id_memory],
        ]);

        $this->hapus($m, ['konfirmasi' => 'HAPUS', 'sandi' => 'sandi-benar-123'])->assertRedirect(route('ic.masuk'));

        // Milik anggota yang dihapus: hilang semua.
        $this->assertSame(0, DB::table('ic_stempel')->where('member_id', $m->id_member)->count());
        $this->assertSame(0, ic_claim::where('member_id', $m->id_member)->count());
        $this->assertSame(0, DB::table('ic_claim_logs')->count());
        $this->assertSame(0, ic_memory::where('member_id', $m->id_member)->count());
        $this->assertSame(0, DB::table('ic_memory_likes')->count(), 'Tanda suka dari dan untuk akun ini ikut terhapus.');
        $this->assertFalse($this->adaBerkas('inner-circle/avatar/saya.webp'), 'Avatar ikut terhapus dari disk.');
        $this->assertFalse($this->adaBerkas('inner-circle/kenangan/saya.webp'), 'Foto kenangan ikut terhapus dari disk.');

        // Milik orang lain: utuh.
        $this->assertDatabaseHas('members', ['id_member' => $lain->id_member, 'stempel_total' => 3]);
        $this->assertSame(1, DB::table('ic_stempel')->where('member_id', $lain->id_member)->count());
        $this->assertDatabaseHas('ic_memories', ['id_memory' => $kenanganLain->id_memory]);
        $this->assertTrue($this->adaBerkas('inner-circle/kenangan/lain.webp'), 'Foto milik anggota lain tetap ada.');
    }

    public function test_klaim_menunggu_menahan_penghapusan_dan_semua_data_aman(): void
    {
        foreach (ic_claim::statusMenunggu() as $status) {
            $m = $this->anggota($status . '@contoh.test');
            $this->klaim($m, $status);
            $this->kenangan($m, "inner-circle/kenangan/{$status}.webp");

            $this->hapus($m, ['konfirmasi' => 'HAPUS', 'sandi' => 'sandi-benar-123'])
                ->assertRedirect(route('ic.profil'))
                ->assertSessionHasErrors('akun');

            $this->assertDatabaseHas('members', ['id_member' => $m->id_member]);
            $this->assertSame(1, ic_claim::where('member_id', $m->id_member)->count());
            $this->assertTrue($this->adaBerkas("inner-circle/kenangan/{$status}.webp"));
        }
    }

    public function test_klaim_ditolak_tidak_menghalangi(): void
    {
        $m = $this->anggota();
        $this->klaim($m, 'ditolak');

        $this->hapus($m, ['konfirmasi' => 'HAPUS', 'sandi' => 'sandi-benar-123'])->assertRedirect(route('ic.masuk'));

        $this->assertDatabaseMissing('members', ['id_member' => $m->id_member]);
    }

    // ---------- tampilan profil ----------

    public function test_profil_menampilkan_bagian_akun_dan_privasi_dengan_form_hapus(): void
    {
        $m = $this->anggota();

        $respons = $this->actingAs($m, 'member')->get(route('ic.profil'))
            ->assertOk()
            ->assertSee(route('ic.akun.hapus'), false);

        foreach (['Akun dan privasi', 'Hapus akunku', 'Kata sandi saat ini'] as $kalimat) {
            $this->assertStringContainsString($kalimat, $this->teks($respons));
        }
    }

    public function test_form_hapus_untuk_akun_google_tanpa_kolom_sandi(): void
    {
        $m = $this->anggota('g@contoh.test', null);

        $respons = $this->actingAs($m, 'member')->get(route('ic.profil'))
            ->assertOk()
            ->assertSee('name="konfirmasi"', false);

        $this->assertStringNotContainsString('Kata sandi saat ini', $this->teks($respons));
    }

    public function test_profil_dengan_klaim_menunggu_menampilkan_penjelasan_bukan_form(): void
    {
        $m = $this->anggota();
        $this->klaim($m, 'diajukan');

        $respons = $this->actingAs($m, 'member')->get(route('ic.profil'))
            ->assertOk()
            ->assertDontSee('name="konfirmasi"', false);

        $this->assertStringContainsString('pengajuan hadiah yang belum selesai', $this->teks($respons));
    }

    public function test_hapus_akun_dibatasi_per_anggota(): void
    {
        $m = $this->anggota();

        for ($i = 0; $i < 5; $i++) {
            $this->hapus($m, ['konfirmasi' => 'salah'])->assertSessionHasErrors('konfirmasi');
        }

        $this->hapus($m, ['konfirmasi' => 'HAPUS', 'sandi' => 'sandi-benar-123'])->assertStatus(429);
        $this->assertDatabaseHas('members', ['id_member' => $m->id_member]);
    }
}