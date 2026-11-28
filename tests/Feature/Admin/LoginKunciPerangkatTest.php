<?php

namespace Tests\Feature\Admin;

use App\Models\account;
use App\Models\log_aktivitas;
use App\Support\SidikPerangkat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Kunci sementara bertingkat untuk login admin (LoginPembatas): menghentikan penebak yang pelan, yang lolos dari
 * pembatas per menit. Username sengaja dibuat beda-beda di tiap percobaan supaya yang teruji hanya kunci perangkat,
 * bukan pembatas per username+IP.
 */
class LoginKunciPerangkatTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function gagal(string $perangkat, int $ke): \Illuminate\Testing\TestResponse
    {
        return $this->withCookie(SidikPerangkat::COOKIE, $perangkat)
            ->post(route('login.proses'), ['username' => 'tebakan-' . $ke, 'password' => 'sandi-salah-' . $ke]);
    }

    public function test_perangkat_dikunci_setelah_lima_gagal_dan_menolak_bahkan_sandi_benar(): void
    {
        account::create(['username' => 'admin', 'password' => Hash::make('SandiPanjangUji123')]);
        $perangkat = Str::random(40);

        for ($i = 1; $i <= 4; $i++) {
            $this->gagal($perangkat, $i)->assertSessionHasErrors('login');
            $this->assertStringNotContainsString('Terlalu banyak', session('errors')->first('login'));
        }

        // Gagal ke-5 melewati ambang: kunci 15 menit dan pesan menyebut lamanya.
        $this->gagal($perangkat, 5)->assertSessionHasErrors('login');
        $this->assertStringContainsString('15 menit', session('errors')->first('login'));

        // Sandi yang benar pun ditolak selama terkunci.
        $this->withCookie(SidikPerangkat::COOKIE, $perangkat)
            ->post(route('login.proses'), ['username' => 'admin', 'password' => 'SandiPanjangUji123'])
            ->assertSessionHasErrors('login');
        $this->assertStringContainsString('Terlalu banyak', session('errors')->first('login'));
        $this->assertNull(session('login'));

        // Kunci hanya dicatat sekali di Log (bukan tiap penolakan).
        $this->assertSame(1, log_aktivitas::where('aksi', 'Perangkat dikunci sementara')->count());
    }

    public function test_perangkat_lain_tidak_ikut_terkunci(): void
    {
        $perangkat = Str::random(40);
        for ($i = 1; $i <= 5; $i++) {
            $this->gagal($perangkat, $i);
        }

        $this->gagal(Str::random(40), 6)->assertSessionHasErrors('login');
        $this->assertStringNotContainsString('Terlalu banyak', session('errors')->first('login'));
    }

    public function test_kunci_berakhir_setelah_waktunya(): void
    {
        account::create(['username' => 'admin', 'password' => Hash::make('SandiPanjangUji123')]);
        $perangkat = Str::random(40);
        for ($i = 1; $i <= 5; $i++) {
            $this->gagal($perangkat, $i);
        }

        Carbon::setTestNow(now()->addMinutes(16));

        $this->withCookie(SidikPerangkat::COOKIE, $perangkat)
            ->post(route('login.proses'), ['username' => 'admin', 'password' => 'SandiPanjangUji123'])
            ->assertRedirect(route('dashboard'));
    }

    public function test_login_berhasil_membersihkan_hitungan_gagal_perangkat(): void
    {
        account::create(['username' => 'admin', 'password' => Hash::make('SandiPanjangUji123')]);
        $perangkat = Str::random(40);

        for ($i = 1; $i <= 4; $i++) {
            $this->gagal($perangkat, $i);
        }
        $this->withCookie(SidikPerangkat::COOKIE, $perangkat)
            ->post(route('login.proses'), ['username' => 'admin', 'password' => 'SandiPanjangUji123'])
            ->assertRedirect(route('dashboard'));
        $this->flushSession();

        // Hitungan mulai dari nol lagi: empat gagal berikutnya belum mengunci.
        for ($i = 5; $i <= 8; $i++) {
            $this->gagal($perangkat, $i)->assertSessionHasErrors('login');
            $this->assertStringNotContainsString('Terlalu banyak', session('errors')->first('login'));
        }
    }

    public function test_bot_tanpa_cookie_tetap_terkunci_lewat_ip_dan_user_agent(): void
    {
        config(['keamanan.login_admin.ip_ua' => [3 => 15]]);
        account::create(['username' => 'admin', 'password' => Hash::make('SandiPanjangUji123')]);

        // Tanpa cookie, tiap permintaan dianggap perangkat baru; yang menahan adalah kunci IP + User-Agent.
        for ($i = 1; $i <= 3; $i++) {
            $this->post(route('login.proses'), ['username' => 'bot-' . $i, 'password' => 'salah-sekali'])->assertSessionHasErrors('login');
        }

        $this->post(route('login.proses'), ['username' => 'admin', 'password' => 'SandiPanjangUji123'])->assertSessionHasErrors('login');
        $this->assertStringContainsString('Terlalu banyak', session('errors')->first('login'));
        $this->assertNull(session('login'));
    }
}