<?php

namespace Tests\Feature;

use App\Models\account;
use App\Models\member;
use Database\Seeders\accountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Hardening hasil audit: header keamanan, robots.txt tidak tertimpa berkas statis, login admin, pembatas laju
 * login anggota, dan seeder akun admin yang tidak lagi membawa sandi di dalam kode.
 */
class InnerCircleKeamananTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Seeder dijalankan langsung, bukan lewat `$this->seed()` (perintah `db:seed`): di env production perintah itu
     * meminta konfirmasi interaktif, sehingga hasil tes bergantung pada terminal yang dipakai (lolos di Linux,
     * gagal di PowerShell). Yang diuji di sini adalah logika seedernya, bukan perintah artisan-nya.
     */
    private function jalankanSeeder(): void
    {
        $this->app->make(accountSeeder::class)->run();
    }

    public function test_header_keamanan_terpasang_di_halaman_publik(): void
    {
        $respons = $this->get('/')->assertOk();

        $respons->assertHeader('X-Content-Type-Options', 'nosniff');
        $respons->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $respons->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertStringContainsString('camera=(self)', $respons->headers->get('Permissions-Policy'));
        $this->assertStringContainsString('geolocation=()', $respons->headers->get('Permissions-Policy'));
    }

    public function test_hsts_hanya_di_produksi_lewat_https(): void
    {
        $this->get('/')->assertHeaderMissing('Strict-Transport-Security');

        $this->app['env'] = 'production';
        $this->get('https://localhost/')->assertHeader('Strict-Transport-Security');
    }

    public function test_robots_tidak_tertimpa_berkas_statis(): void
    {
        // Apache melayani public/robots.txt lebih dulu daripada route Laravel, jadi berkas ini tidak boleh ada.
        $this->assertFileDoesNotExist(public_path('robots.txt'));
    }

    public function test_login_admin_username_tidak_dikenal_ditolak_dengan_pesan_generik(): void
    {
        $this->post('/login', ['username' => 'tidak-ada', 'password' => 'sandi-apa-saja'])
            ->assertSessionHasErrors('login');

        account::create(['username' => 'admin-uji', 'password' => Hash::make('sandi-benar-123')]);

        $this->post('/login', ['username' => 'admin-uji', 'password' => 'sandi-salah-123'])
            ->assertSessionHasErrors('login');

        $this->post('/login', ['username' => 'admin-uji', 'password' => 'sandi-benar-123'])
            ->assertRedirect(route('dashboard'));
    }

    public function test_masuk_anggota_dibatasi_per_email_bukan_hanya_per_ip(): void
    {
        config(['inner_circle.aktif' => true]);
        RateLimiter::clear('masuk:' . sha1('korban@contoh.test|127.0.0.1'));

        member::create(['nama' => 'Korban', 'email' => 'korban@contoh.test', 'password' => 'sandi-benar-123']);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('ic.masuk.proses'), ['email' => 'korban@contoh.test', 'sandi' => 'salah-' . $i])
                ->assertSessionHasErrors('email');
        }

        // Percobaan ke-6 untuk email yang sama ditahan, walau sandinya benar.
        $this->post(route('ic.masuk.proses'), ['email' => 'korban@contoh.test', 'sandi' => 'sandi-benar-123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest('member');

        // Email lain dari IP yang sama tetap bisa masuk (penonton satu venue tidak saling mengunci).
        member::create(['nama' => 'Lain', 'email' => 'lain@contoh.test', 'password' => 'sandi-benar-123']);
        $this->post(route('ic.masuk.proses'), ['email' => 'lain@contoh.test', 'sandi' => 'sandi-benar-123'])
            ->assertRedirect();
        $this->assertAuthenticated('member');
    }

    public function test_seeder_admin_berhenti_di_produksi_tanpa_kredensial_env(): void
    {
        $this->app['env'] = 'production';
        config(['site.admin_username' => null, 'site.admin_password' => null]);

        $this->jalankanSeeder();

        $this->assertSame(0, account::count());
    }

    public function test_seeder_admin_memakai_kredensial_dari_env_dan_tidak_menimpa(): void
    {
        config(['site.admin_username' => 'admin-env', 'site.admin_password' => 'sandi-panjang-yang-kuat-1']);

        $this->jalankanSeeder();

        $akun = account::where('username', 'admin-env')->firstOrFail();
        $this->assertTrue(Hash::check('sandi-panjang-yang-kuat-1', $akun->password));

        config(['site.admin_password' => 'sandi-baru-yang-lain-123']);
        $this->jalankanSeeder();

        $this->assertSame(1, account::count());
        $this->assertTrue(Hash::check('sandi-panjang-yang-kuat-1', $akun->fresh()->password));
    }

    public function test_seeder_admin_menolak_sandi_pendek(): void
    {
        config(['site.admin_username' => 'admin-env', 'site.admin_password' => 'pendek']);

        $this->jalankanSeeder();

        $this->assertSame(0, account::count());
    }

    public function test_kredensial_lama_tidak_ada_lagi_di_kode_seeder(): void
    {
        $isi = file_get_contents(database_path('seeders/accountSeeder.php'));

        $this->assertStringNotContainsString('manage@ws2026', $isi);
        $this->assertStringNotContainsString('staff@ws', $isi);
    }
}