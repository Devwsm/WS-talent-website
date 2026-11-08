<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Batas laju aksi tulis dashboard. `throttle:N,M` bawaan Laravel berbagi SATU penghitung per IP untuk semua rute,
 * jadi tim admin di satu jaringan saling menghabiskan jatah. Sekarang semuanya memakai pembatas bernama per admin.
 */
class BatasLajuTulisTest extends TestCase
{
    use RefreshDatabase;

    private const RUTE = '/dashboard/profile/tambahGenre';

    private function sebagai(string $admin)
    {
        return $this->withSession(['login' => true, 'user' => $admin]);
    }

    public function test_tidak_ada_rute_yang_memakai_throttle_bawaan_berhitung_bersama(): void
    {
        foreach (Route::getRoutes() as $rute) {
            foreach ($rute->gatherMiddleware() as $mw) {
                $this->assertDoesNotMatchRegularExpression(
                    '/^throttle:\d+(,\d+)?$/',
                    (string) $mw,
                    "Rute {$rute->uri()} memakai {$mw}: pakai pembatas bernama (RateLimiter::for) supaya penghitungnya tidak dibagi dengan rute lain.",
                );
            }
        }
    }

    public function test_jatah_tulis_dihitung_per_admin_bukan_per_ip(): void
    {
        for ($i = 1; $i <= 30; $i++) {
            $this->sebagai('admin-a')->post(self::RUTE, [])->assertStatus(302);
        }

        $this->sebagai('admin-a')->post(self::RUTE, [])->assertStatus(429);

        // Admin lain dari IP yang sama tidak ikut terkunci.
        $this->sebagai('admin-b')->post(self::RUTE, [])->assertStatus(302);
    }

    public function test_rute_lain_tidak_menghabiskan_jatah_rute_berbeda(): void
    {
        // Dulu `throttle:3,1` dan `throttle:10,1` berbagi penghitung: tiga aksi apa pun menutup rute yang lain.
        for ($i = 0; $i < 5; $i++) {
            $this->sebagai('admin-c')->post('/dashboard/inner-circle/email/tes', [])->assertStatus(302);
        }

        $this->sebagai('admin-c')->post(self::RUTE, [])->assertStatus(302);
    }
}
