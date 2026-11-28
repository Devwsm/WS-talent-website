<?php

namespace Tests\Feature\InnerCircle\Game;

use App\Models\ic_game;
use App\Models\ic_poin;
use App\Models\member;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Mini game Mambo Jambo: jatah harian, skor dihitung server, batas poin, dan penolakan ronde yang tidak sah.
 * Seed 777 tanpa input = ronde 938 langkah, skor 10 (lihat GameSimulasiTest).
 */
class GameTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(now()->setTime(10, 0));
        // Angka tes tetap (bawaan produksi mengikuti prototype: 1 skor = 1 poin).
        config(['inner_circle.game.skor_per_poin' => 10, 'inner_circle.game.poin_maks_ronde' => 30, 'inner_circle.game.poin_maks_harian' => 100]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function anggota(string $email = 'g@contoh.test', bool $verif = true): member
    {
        $m = member::create(['nama' => 'Pemain Uji', 'email' => $email, 'password' => 'rahasia123']);
        $m->kode_member = sprintf('IC-%05d', $m->id_member);
        $m->email_verified_at = $verif ? now() : null;
        $m->save();

        return $m;
    }

    /** Mulai ronde lalu paksa seed 777 supaya hasilnya bisa diprediksi. */
    private function mulaiRonde(member $m, int $seed = 777): string
    {
        $kode = $this->actingAs($m, 'member')->postJson(route('ic.game.mulai'))->assertOk()->json('kode');
        ic_game::where('kode', $kode)->update(['seed' => $seed]);

        return $kode;
    }

    private function selesai(member $m, string $kode, array $o = [])
    {
        return $this->actingAs($m, 'member')->postJson(route('ic.game.selesai'), $o + ['kode' => $kode, 'langkah' => 938, 'log' => '', 'skor' => 10]);
    }

    public function test_halaman_game_tampil_untuk_anggota(): void
    {
        $this->actingAs($this->anggota(), 'member')->get(route('ic.game'))->assertOk()->assertSee('Pineapple Catcher')->assertSee('data-ronde-sisa="5"', false);
    }

    public function test_tamu_tidak_bisa_membuka_atau_memulai(): void
    {
        $this->get(route('ic.game'))->assertRedirect();
        $this->postJson(route('ic.game.mulai'))->assertUnauthorized();
    }

    public function test_game_mati_lewat_config_mengembalikan_404(): void
    {
        config(['inner_circle.game.aktif' => false]);
        $m = $this->anggota();
        $this->actingAs($m, 'member')->get(route('ic.game'))->assertNotFound();
        $this->actingAs($m, 'member')->postJson(route('ic.game.mulai'))->assertStatus(404);
    }

    public function test_email_belum_terverifikasi_tidak_bisa_main(): void
    {
        $this->actingAs($this->anggota('v@contoh.test', false), 'member')->postJson(route('ic.game.mulai'))->assertForbidden();
        $this->assertSame(0, ic_game::count());
    }

    public function test_ronde_sah_memberi_poin_dari_hitungan_server(): void
    {
        $m = $this->anggota();
        $kode = $this->mulaiRonde($m);
        Carbon::setTestNow(now()->addSeconds(20));

        // Browser mengaku skor 99999: tidak dipercaya, server menghitung 10 -> 1 poin.
        $this->selesai($m, $kode, ['skor' => 99999])->assertOk()->assertJson(['skor' => 10, 'poin' => 1, 'ronde_sisa' => 4]);

        $this->assertSame(1, $m->fresh()->poin_total);
        $ronde = ic_game::where('kode', $kode)->first();
        $this->assertSame('selesai', $ronde->status);
        $this->assertSame(99999, $ronde->skor_klaim);
        $this->assertSame('selisih-skor', $ronde->alasan);
        $baris = ic_poin::where('member_id', $m->id_member)->first();
        $this->assertSame('game', $baris->sumber);
        $this->assertSame(1, $baris->jumlah);
    }

    public function test_ronde_hanya_bisa_dibayar_sekali(): void
    {
        $m = $this->anggota();
        $kode = $this->mulaiRonde($m);
        Carbon::setTestNow(now()->addSeconds(20));

        $this->selesai($m, $kode)->assertOk();
        $this->selesai($m, $kode)->assertStatus(409);
        $this->assertSame(1, $m->fresh()->poin_total);
    }

    public function test_jatah_ronde_harian_dibatasi(): void
    {
        config(['inner_circle.game.ronde_harian' => 2]);
        $m = $this->anggota();

        $this->actingAs($m, 'member')->postJson(route('ic.game.mulai'))->assertOk();
        $this->actingAs($m, 'member')->postJson(route('ic.game.mulai'))->assertOk();
        $this->actingAs($m, 'member')->postJson(route('ic.game.mulai'))->assertStatus(429);

        $this->assertSame(2, ic_game::count());
    }

    public function test_ronde_baru_menggugurkan_ronde_berjalan_tapi_tetap_terhitung(): void
    {
        $m = $this->anggota();
        $lama = $this->mulaiRonde($m);
        $this->actingAs($m, 'member')->postJson(route('ic.game.mulai'))->assertOk();
        Carbon::setTestNow(now()->addSeconds(20));

        $this->assertSame('ditinggalkan', ic_game::where('kode', $lama)->value('status'));
        $this->selesai($m, $lama)->assertStatus(409);
    }

    public function test_jatah_kembali_di_hari_berikutnya(): void
    {
        config(['inner_circle.game.ronde_harian' => 1]);
        $m = $this->anggota();
        $this->actingAs($m, 'member')->postJson(route('ic.game.mulai'))->assertOk();
        $this->actingAs($m, 'member')->postJson(route('ic.game.mulai'))->assertStatus(429);

        Carbon::setTestNow(now()->addDay());
        $this->actingAs($m, 'member')->postJson(route('ic.game.mulai'))->assertOk();
    }

    public function test_poin_dibatasi_per_ronde_dan_per_hari(): void
    {
        config(['inner_circle.game.skor_per_poin' => 1, 'inner_circle.game.poin_maks_ronde' => 6, 'inner_circle.game.poin_maks_harian' => 8]);
        $m = $this->anggota();

        $a = $this->mulaiRonde($m);
        Carbon::setTestNow(now()->addSeconds(20));
        // Skor 10 -> 10 poin mentah, dipotong batas ronde 6.
        $this->selesai($m, $a)->assertOk()->assertJson(['poin' => 6, 'poin_mentah' => 10, 'alasan_batas' => 'ronde']);

        $b = $this->mulaiRonde($m);
        Carbon::setTestNow(now()->addSeconds(20));
        // Sisa jatah poin hari ini tinggal 2.
        $this->selesai($m, $b)->assertOk()->assertJson(['poin' => 2, 'alasan_batas' => 'harian', 'poin_hari_ini' => 8]);

        $this->assertSame(8, $m->fresh()->poin_total);
    }

    public function test_ronde_yang_dikirim_terlalu_cepat_ditolak_tanpa_poin(): void
    {
        $m = $this->anggota();
        $kode = $this->mulaiRonde($m);
        // 938 langkah butuh 15,6 detik asli; baru lewat 2 detik.
        Carbon::setTestNow(now()->addSeconds(2));

        $this->selesai($m, $kode)->assertStatus(422);
        $this->assertSame(0, $m->fresh()->poin_total);
        $this->assertSame('terlalu-cepat', ic_game::where('kode', $kode)->value('alasan'));
        $this->assertSame('ditolak', ic_game::where('kode', $kode)->value('status'));
    }

    public function test_ronde_kedaluwarsa_ditolak(): void
    {
        $m = $this->anggota();
        $kode = $this->mulaiRonde($m);
        Carbon::setTestNow(now()->addMinutes(25));

        $this->selesai($m, $kode)->assertStatus(422);
        $this->assertSame('kedaluwarsa', ic_game::where('kode', $kode)->value('alasan'));
    }

    public function test_catatan_yang_tidak_sesuai_seed_ditolak(): void
    {
        $m = $this->anggota();
        $kode = $this->mulaiRonde($m);
        Carbon::setTestNow(now()->addSeconds(20));

        // Mengaku selesai di langkah 500 padahal ronde baru berakhir di langkah 938.
        $this->selesai($m, $kode, ['langkah' => 500])->assertStatus(422);
        $this->assertSame('catatan-tidak-sesuai', ic_game::where('kode', $kode)->value('alasan'));
        $this->assertSame(0, $m->fresh()->poin_total);
    }

    public function test_catatan_berbentuk_salah_ditolak(): void
    {
        foreach (['abc', '1,2,3', '5,999', '5,-3', '9999,100', '1,2,3,4,5,6,x,1'] as $log) {
            $m = $this->anggota($log . '@contoh.test');
            $kode = $this->mulaiRonde($m);
            Carbon::setTestNow(now()->addSeconds(20));

            $this->selesai($m, $kode, ['log' => $log])->assertStatus(422);
            $this->assertSame('catatan-rusak', ic_game::where('kode', $kode)->value('alasan'), $log);
            Carbon::setTestNow(now()->setTime(10, 0));
        }
    }

    public function test_ronde_milik_anggota_lain_tidak_bisa_diselesaikan(): void
    {
        $a = $this->anggota('a@contoh.test');
        $b = $this->anggota('b@contoh.test');
        $kode = $this->mulaiRonde($a);
        Carbon::setTestNow(now()->addSeconds(20));

        $this->selesai($b, $kode)->assertNotFound();
        $this->assertSame('berjalan', ic_game::where('kode', $kode)->value('status'));
    }

    public function test_kode_ronde_sembarangan_ditolak_validasi(): void
    {
        $m = $this->anggota();
        $this->selesai($m, 'bukan-kode')->assertStatus(422);
    }

    public function test_rekor_dihitung_dari_ronde_sah_saja(): void
    {
        $m = $this->anggota();
        $kode = $this->mulaiRonde($m);
        Carbon::setTestNow(now()->addSeconds(20));
        $this->selesai($m, $kode)->assertOk()->assertJson(['rekor' => 10, 'rekor_baru' => true]);

        $this->actingAs($m, 'member')->get(route('ic.game'))->assertOk()->assertSee('data-rekor="10"', false);
    }

    public function test_akun_diblokir_tidak_bisa_memulai(): void
    {
        $m = $this->anggota();
        $m->forceFill(['diblokir_pada' => now()])->save();

        // Sesi akun yang dinonaktifkan sudah diputus lebih dulu oleh middleware icSesi.
        $this->actingAs($m, 'member')->postJson(route('ic.game.mulai'))->assertRedirect();
        $this->assertSame(0, ic_game::count());
    }
}
