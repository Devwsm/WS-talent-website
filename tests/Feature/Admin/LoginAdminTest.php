<?php

namespace Tests\Feature\Admin;

use App\Models\account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Login admin CMS: pesan generik dan pembatas laju per username+IP serta per IP.
 */
class LoginAdminTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_login_admin_dibatasi_per_username_dan_ip_tanpa_mengunci_username_lain(): void
    {
        account::create(['username' => 'admin', 'password' => Hash::make('SandiPanjangUji123')]);
        $coba = fn(string $username, string $sandi) => $this->post(route('login.proses'), ['username' => $username, 'password' => $sandi]);

        for ($i = 0; $i < 5; $i++) {
            $coba('admin', 'salah-' . $i)->assertSessionHasErrors('login');
        }

        // Percobaan ke-6 diblokir, bahkan dengan sandi yang benar.
        $coba('admin', 'SandiPanjangUji123')->assertSessionHasErrors('login');
        $this->assertStringContainsString('Terlalu banyak', session('errors')->first('login'));
        $this->assertNull(session('login'));

        // Username lain dari IP yang sama tidak ikut terkunci (hanya salah sandi biasa).
        $coba('lain', 'salah-sekali')->assertSessionHasErrors('login');
        $this->assertStringNotContainsString('Terlalu banyak', session('errors')->first('login'));
    }

    public function test_pembatas_login_admin_per_ip_menghentikan_tebakan_banyak_username(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post(route('login.proses'), ['username' => 'tebakan-' . $i, 'password' => 'salah-sekali'])->assertSessionHasErrors('login');
            $this->assertStringNotContainsString('Terlalu banyak', session('errors')->first('login'));
        }

        $this->post(route('login.proses'), ['username' => 'tebakan-baru', 'password' => 'salah-sekali'])->assertSessionHasErrors('login');
        $this->assertStringContainsString('Terlalu banyak', session('errors')->first('login'));
    }
}
