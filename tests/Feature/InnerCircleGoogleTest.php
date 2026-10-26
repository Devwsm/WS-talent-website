<?php

namespace Tests\Feature;

use App\Models\member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Fase 8: masuk/daftar dengan Google. Google dipalsukan dengan Http::fake (tidak ada panggilan jaringan sungguhan).
 */
class InnerCircleGoogleTest extends TestCase
{
    use RefreshDatabase;

    private const CLIENT_ID = 'uji-123.apps.googleusercontent.com';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'inner_circle.aktif' => true,
            'inner_circle.google.aktif' => true,
            'services.google.client_id' => self::CLIENT_ID,
            'services.google.client_secret' => 'rahasia-uji',
            'services.google.redirect' => null,
        ]);
    }

    /** Meniru pindah ke browser lain: sesi kosong dan guard tidak lagi mengingat pengguna dari permintaan sebelumnya. */
    private function browserBaru(): void
    {
        $this->flushSession();
        $this->app['auth']->forgetGuards();
    }

    private function sesiGoogle(array $timpa = []): array
    {
        return ['ic_google' => array_merge(['state' => 'STATE1', 'nonce' => 'NONCE1', 'verifier' => 'VERIF1', 'dibuat' => time()], $timpa)];
    }

    private function idToken(array $timpa = []): string
    {
        $b64 = fn(array $a) => rtrim(strtr(base64_encode(json_encode($a)), '+/', '-_'), '=');
        $klaim = array_merge([
            'iss' => 'https://accounts.google.com',
            'aud' => self::CLIENT_ID,
            'exp' => time() + 3600,
            'nonce' => 'NONCE1',
            'sub' => '1000000001',
            'email' => 'fans@contoh.test',
            'email_verified' => true,
            'name' => 'Penggemar Uji',
        ], $timpa);

        return $b64(['alg' => 'RS256', 'typ' => 'JWT']) . '.' . $b64($klaim) . '.tanda-tangan';
    }

    /** Http::fake menumpuk stub (yang pertama cocok menang), jadi tiap pemalsuan dimulai dari klien HTTP yang baru. */
    private function fakeGoogle($stub): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(['oauth2.googleapis.com/*' => $stub]);
    }

    private function googleMenjawab(array $klaim = []): void
    {
        $this->fakeGoogle(Http::response(['id_token' => $this->idToken($klaim), 'access_token' => 'tidak-dipakai']));
    }

    private function kembaliDariGoogle(array $query = [], array $sesi = [])
    {
        return $this->withSession($this->sesiGoogle($sesi))
            ->get(route('ic.google.callback', array_merge(['state' => 'STATE1', 'code' => 'kode-abc'], $query)));
    }

    // ---------- ketersediaan ----------

    public function test_flag_mati_atau_kredensial_kosong_membuat_route_404_dan_tombol_hilang(): void
    {
        config(['inner_circle.google.aktif' => false]);
        $this->get(route('ic.google.masuk'))->assertNotFound();
        $this->get(route('ic.google.callback'))->assertNotFound();
        $this->get(route('ic.masuk'))->assertOk()->assertDontSee('Lanjutkan dengan Google');

        config(['inner_circle.google.aktif' => true, 'services.google.client_secret' => '']);
        $this->get(route('ic.google.masuk'))->assertNotFound();
    }

    public function test_tombol_google_tampil_di_masuk_dan_daftar_saat_aktif(): void
    {
        $this->get(route('ic.masuk'))->assertOk()->assertSee('Lanjutkan dengan Google')->assertSee(route('ic.google.masuk'), false);
        $this->get(route('ic.daftar'))->assertOk()->assertSee('Daftar dengan Google');
    }

    // ---------- mulai ----------

    public function test_mulai_mengalihkan_ke_google_dengan_state_nonce_dan_pkce(): void
    {
        $respons = $this->get(route('ic.google.masuk'));

        $respons->assertRedirectContains('https://accounts.google.com/o/oauth2/v2/auth?');
        parse_str(parse_url($respons->headers->get('Location'), PHP_URL_QUERY), $q);
        $sesi = session('ic_google');

        $this->assertSame(self::CLIENT_ID, $q['client_id']);
        $this->assertSame(route('ic.google.callback'), $q['redirect_uri']);
        $this->assertSame('code', $q['response_type']);
        $this->assertSame('openid email profile', $q['scope']);
        $this->assertSame($sesi['state'], $q['state']);
        $this->assertSame($sesi['nonce'], $q['nonce']);
        $this->assertSame('S256', $q['code_challenge_method']);
        $this->assertSame(rtrim(strtr(base64_encode(hash('sha256', $sesi['verifier'], true)), '+/', '-_'), '='), $q['code_challenge']);
    }

    public function test_redirect_uri_bisa_ditimpa_lewat_config(): void
    {
        config(['services.google.redirect' => 'https://whisnusantika.com/inner-circle/google/callback']);

        $respons = $this->get(route('ic.google.masuk'));
        parse_str(parse_url($respons->headers->get('Location'), PHP_URL_QUERY), $q);

        $this->assertSame('https://whisnusantika.com/inner-circle/google/callback', $q['redirect_uri']);
    }

    public function test_anggota_yang_sudah_masuk_tidak_diteruskan_ke_google(): void
    {
        $anggota = member::create(['nama' => 'A', 'email' => 'a@contoh.test', 'password' => 'sandi-12345678']);

        $this->actingAs($anggota, 'member')->get(route('ic.google.masuk'))->assertRedirect(route('ic.beranda'));
    }

    // ---------- callback: anggota baru ----------

    public function test_callback_membuat_anggota_baru_dan_langsung_masuk(): void
    {
        $this->googleMenjawab();

        $this->kembaliDariGoogle()->assertRedirect(route('ic.beranda'))->assertSessionHas('ic_toast', 'Selamat datang di WS Inner Circle!');

        $anggota = member::where('email', 'fans@contoh.test')->firstOrFail();
        $this->assertSame('1000000001', $anggota->google_id);
        $this->assertNotNull($anggota->email_verified_at);
        $this->assertNull($anggota->password);
        $this->assertSame('Penggemar Uji', $anggota->nama);
        $this->assertSame(sprintf('IC-%05d', $anggota->id_member), $anggota->kode_member);
        $this->assertSame(0, $anggota->stempel_total);
        $this->assertAuthenticatedAs($anggota, 'member');

        // Kode, rahasia klien, dan verifier PKCE dikirim ke Google; state/nonce sekali pakai sudah dibuang.
        Http::assertSent(fn(HttpRequest $r) => $r->url() === 'https://oauth2.googleapis.com/token'
            && $r['code'] === 'kode-abc'
            && $r['grant_type'] === 'authorization_code'
            && $r['code_verifier'] === 'VERIF1'
            && $r['client_secret'] === 'rahasia-uji');
        $this->assertNull(session('ic_google'));
    }

    public function test_nama_kosong_memakai_bagian_depan_email_dan_nama_panjang_dipotong(): void
    {
        $this->googleMenjawab(['name' => '', 'sub' => '2', 'email' => 'tanpa.nama@contoh.test']);
        $this->kembaliDariGoogle();
        $this->assertSame('tanpa.nama', member::where('email', 'tanpa.nama@contoh.test')->value('nama'));

        $this->browserBaru();
        $this->googleMenjawab(['name' => str_repeat('x', 300), 'sub' => '3', 'email' => 'panjang@contoh.test']);
        $this->kembaliDariGoogle();
        $this->assertSame(100, mb_strlen(member::where('email', 'panjang@contoh.test')->value('nama')));
    }

    public function test_email_dari_google_dinormalkan_huruf_kecil(): void
    {
        $this->googleMenjawab(['email' => 'CampurAN@Contoh.TEST', 'sub' => '5']);
        $this->kembaliDariGoogle();

        $this->assertDatabaseHas('members', ['email' => 'campuran@contoh.test', 'google_id' => '5']);
    }

    // ---------- callback: anggota yang sudah ada ----------

    public function test_google_id_yang_sama_langsung_masuk_tanpa_membuat_akun_kedua(): void
    {
        $anggota = member::create(['nama' => 'Lama', 'email' => 'fans@contoh.test', 'password' => null]);
        $anggota->forceFill(['google_id' => '1000000001', 'email_verified_at' => now()])->save();
        $this->googleMenjawab(['email' => 'email-baru-di-google@contoh.test']);

        $this->kembaliDariGoogle()->assertRedirect(route('ic.beranda'))->assertSessionHas('ic_toast', 'Selamat datang kembali!');

        $this->assertSame(1, member::count());
        $this->assertAuthenticatedAs($anggota, 'member');
    }

    public function test_akun_sandi_yang_belum_terverifikasi_dikaitkan_dan_sandi_serta_sesi_lama_dicabut(): void
    {
        $akun = member::create(['nama' => 'Didaftarkan Orang Lain', 'email' => 'fans@contoh.test', 'password' => 'sandi-penyerang-1']);
        $this->googleMenjawab();

        $this->kembaliDariGoogle()->assertRedirect(route('ic.beranda'))
            ->assertSessionHas('ic_toast', 'Akunmu sudah terhubung ke Google. Mulai sekarang masuk lewat Google ya.');

        $akun->refresh();
        $this->assertSame('1000000001', $akun->google_id);
        $this->assertNotNull($akun->email_verified_at);
        $this->assertNull($akun->password, 'Sandi lama harus dihapus supaya pendaftar awal tidak bisa masuk.');
        $this->assertSame(1, $akun->sesi_versi);
        $this->assertSame(1, member::count());
        $this->assertAuthenticatedAs($akun, 'member');
    }

    public function test_sesi_lama_penyerang_otomatis_keluar_setelah_akun_dikaitkan(): void
    {
        $akun = member::create(['nama' => 'Korban', 'email' => 'fans@contoh.test', 'password' => 'sandi-penyerang-1']);

        // Penyerang sudah masuk dengan sandi (sesi_versi 0 tercatat di sesinya) dan bisa membuka beranda.
        $this->post(route('ic.masuk.proses'), ['email' => 'fans@contoh.test', 'sandi' => 'sandi-penyerang-1']);
        $this->get(route('ic.beranda'))->assertOk();
        $sesiPenyerang = session()->all();

        // Korban masuk lewat Google dari browser lain: akun dikaitkan, sesi_versi naik.
        $this->browserBaru();
        $this->googleMenjawab();
        $this->kembaliDariGoogle();
        $this->assertSame(1, $akun->fresh()->sesi_versi);

        // Sesi penyerang yang lama kini ditolak dan diarahkan ke halaman masuk.
        $this->browserBaru();
        $this->withSession($sesiPenyerang)->get(route('ic.beranda'))->assertRedirect(route('ic.masuk'));
    }

    public function test_akun_yang_sudah_terverifikasi_dikaitkan_tanpa_menghapus_sandi(): void
    {
        $akun = member::create(['nama' => 'Terverifikasi', 'email' => 'fans@contoh.test', 'password' => 'sandi-sendiri-123']);
        $akun->forceFill(['email_verified_at' => now()])->save();
        $this->googleMenjawab();

        $this->kembaliDariGoogle()->assertSessionHas('ic_toast', 'Selamat datang kembali!');

        $akun->refresh();
        $this->assertSame('1000000001', $akun->google_id);
        $this->assertTrue(Hash::check('sandi-sendiri-123', $akun->password));
        $this->assertSame(0, $akun->sesi_versi);
    }

    public function test_email_yang_sudah_terhubung_ke_google_lain_ditolak(): void
    {
        $akun = member::create(['nama' => 'Punya Google', 'email' => 'fans@contoh.test', 'password' => null]);
        $akun->forceFill(['google_id' => 'sub-lain', 'email_verified_at' => now()])->save();
        $this->googleMenjawab(['sub' => '1000000001']);

        $this->kembaliDariGoogle()->assertRedirect(route('ic.masuk'))->assertSessionHasErrors('google');

        $this->assertGuest('member');
        $this->assertSame('sub-lain', $akun->fresh()->google_id);
    }

    public function test_akun_hanya_google_tidak_bisa_masuk_dengan_sandi(): void
    {
        $this->googleMenjawab();
        $this->kembaliDariGoogle();
        $this->browserBaru();

        $this->post(route('ic.masuk.proses'), ['email' => 'fans@contoh.test', 'sandi' => 'sandi-apa-saja-12'])
            ->assertSessionHasErrors(['email' => 'Email atau kata sandi salah.']);
        $this->assertGuest('member');
    }

    // ---------- callback: penolakan ----------

    public static function kasusDitolak(): array
    {
        return [
            'email belum terverifikasi' => [['email_verified' => false], 'belum terverifikasi'],
            'email_verified berupa string false' => [['email_verified' => 'false'], 'belum terverifikasi'],
            'tanpa email' => [['email' => ''], 'belum terverifikasi'],
            'aud milik aplikasi lain' => [['aud' => 'orang-lain.apps.googleusercontent.com'], 'tidak valid'],
            'penerbit bukan google' => [['iss' => 'https://penipu.test'], 'tidak valid'],
            'token kedaluwarsa' => [['exp' => time() - 10], 'tidak valid'],
            'nonce tidak cocok' => [['nonce' => 'NONCE-LAIN'], 'tidak valid'],
            'tanpa sub' => [['sub' => ''], 'tidak valid'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('kasusDitolak')]
    public function test_id_token_yang_tidak_layak_ditolak_dan_tidak_membuat_akun(array $klaim, string $potongan): void
    {
        $this->googleMenjawab($klaim);

        $this->kembaliDariGoogle()->assertRedirect(route('ic.masuk'))->assertSessionHasErrors('google');

        $this->assertStringContainsString($potongan, session('errors')->first('google'));
        $this->assertSame(0, member::count());
        $this->assertGuest('member');
    }

    public function test_pengguna_membatalkan_di_halaman_google(): void
    {
        Http::fake();

        $this->withSession($this->sesiGoogle())
            ->get(route('ic.google.callback', ['error' => 'access_denied', 'state' => 'STATE1']))
            ->assertRedirect(route('ic.masuk'))
            ->assertSessionHasErrors(['google' => 'Masuk dengan Google dibatalkan.']);

        Http::assertNothingSent();
    }

    public function test_state_tidak_cocok_atau_sesi_hilang_atau_kedaluwarsa_ditolak_tanpa_menghubungi_google(): void
    {
        Http::fake();

        $this->kembaliDariGoogle(['state' => 'STATE-PALSU'])->assertSessionHasErrors('google');
        $this->get(route('ic.google.callback', ['state' => 'STATE1', 'code' => 'x']))->assertSessionHasErrors('google'); // tanpa sesi
        $this->kembaliDariGoogle([], ['dibuat' => time() - 3600])->assertSessionHasErrors('google'); // kedaluwarsa
        $this->kembaliDariGoogle(['code' => ''])->assertSessionHasErrors('google'); // tanpa kode

        Http::assertNothingSent();
        $this->assertSame(0, member::count());
    }

    public function test_google_menolak_kode_atau_tidak_terjangkau_tidak_membuat_error_500(): void
    {
        $this->fakeGoogle(Http::response(['error' => 'invalid_grant'], 400));
        $this->kembaliDariGoogle()->assertRedirect(route('ic.masuk'))->assertSessionHasErrors('google');

        $this->fakeGoogle(fn() => throw new \Illuminate\Http\Client\ConnectionException('timeout'));
        $this->kembaliDariGoogle()->assertRedirect(route('ic.masuk'))->assertSessionHasErrors('google');

        $this->assertSame(0, member::count());
    }

    public function test_halaman_masuk_menampilkan_pesan_galat_google(): void
    {
        Http::fake();

        $this->withSession($this->sesiGoogle())
            ->followingRedirects()
            ->get(route('ic.google.callback', ['error' => 'access_denied', 'state' => 'STATE1']))
            ->assertOk()
            ->assertSee('Masuk dengan Google dibatalkan.');
    }

    // ---------- alur QR check-in ----------

    public function test_setelah_masuk_google_anggota_kembali_ke_tujuan_semula(): void
    {
        $this->googleMenjawab();

        $this->withSession($this->sesiGoogle() + ['url.intended' => route('ic.checkin', ['t' => 'token-qr'])])
            ->get(route('ic.google.callback', ['state' => 'STATE1', 'code' => 'kode-abc']))
            ->assertRedirect(route('ic.checkin', ['t' => 'token-qr']));
    }

    // ---------- sesi_versi pada login biasa ----------

    public function test_login_dan_daftar_biasa_mencatat_sesi_versi_dan_tetap_bisa_dipakai(): void
    {
        $this->post(route('ic.daftar.proses'), ['nama' => 'Biasa', 'email' => 'biasa@contoh.test', 'sandi' => 'sandi-12345678', 'sandi_confirmation' => 'sandi-12345678']);
        $this->assertSame(0, session('ic_sesi_versi'));
        $this->get(route('ic.beranda'))->assertOk();

        $this->post(route('ic.keluar'));
        $this->post(route('ic.masuk.proses'), ['email' => 'biasa@contoh.test', 'sandi' => 'sandi-12345678']);
        $this->get(route('ic.beranda'))->assertOk();
    }
}