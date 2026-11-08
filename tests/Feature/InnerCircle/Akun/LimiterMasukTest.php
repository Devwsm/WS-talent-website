<?php

namespace Tests\Feature\InnerCircle\Akun;

use App\Models\member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Pembatas laju masuk anggota per email+IP (bukan hanya per IP).
 */
class LimiterMasukTest extends TestCase
{
    use RefreshDatabase;

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
}
