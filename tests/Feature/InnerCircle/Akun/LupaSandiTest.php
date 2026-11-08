<?php

namespace Tests\Feature\InnerCircle\Akun;

use App\Mail\InnerCircle\AkunGoogleMail;
use App\Mail\InnerCircle\ResetSandiMail;
use App\Models\member;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Lupa dan atur ulang kata sandi anggota.
 */
class LupaSandiTest extends TestCase
{
    use RefreshDatabase;
    // ---------- Token rusak ("The MAC is invalid") ----------
    // ---------- Bandsintown ----------
    // ---------- Lupa kata sandi ----------
    // ---------- Tab Email ----------
    // ---------- Validasi link CMS ----------

    /** Broker kata sandi anggota, bertipe konkret supaya editor mengenali createToken(). */
    private function broker(): PasswordBroker
    {
        return Password::broker('members');
    }

    private function anggota(array $o = []): member
    {
        $m = new member();
        $m->nama = 'Budi';
        $m->email = $o['email'] ?? 'budi@contoh.com';
        $m->password = array_key_exists('password', $o) ? $o['password'] : 'sandi-lama-123';
        $m->kode_member = 'IC-00001';
        $m->save();
        if (isset($o['google'])) {
            $m->forceFill(['google_id' => 'g-1'])->save();
        }

        return $m;
    }

    public function test_lupa_sandi_mengirim_tautan_dan_jawabannya_sama_untuk_email_tak_dikenal(): void
    {
        Mail::fake();
        $this->anggota();

        $a = $this->post(route('ic.sandi.kirim'), ['email' => 'budi@contoh.com']);
        $b = $this->post(route('ic.sandi.kirim'), ['email' => 'tidak@ada.com']);

        $a->assertRedirect(route('ic.sandi.lupa'));
        $b->assertRedirect(route('ic.sandi.lupa'));
        Mail::assertSent(ResetSandiMail::class, 1);
        Mail::assertSent(ResetSandiMail::class, fn($m) => $m->hasTo('budi@contoh.com'));
    }

    public function test_akun_google_menerima_email_masuk_dengan_google_bukan_tautan(): void
    {
        Mail::fake();
        $this->anggota(['password' => null, 'google' => true]);

        $this->post(route('ic.sandi.kirim'), ['email' => 'budi@contoh.com'])->assertRedirect();

        Mail::assertSent(AkunGoogleMail::class, 1);
        Mail::assertNotSent(ResetSandiMail::class);
    }

    public function test_atur_ulang_mengganti_sandi_memverifikasi_email_dan_mengeluarkan_sesi_lama(): void
    {
        $m = $this->anggota();
        $this->assertNull($m->email_verified_at);
        $token = $this->broker()->createToken($m);

        $this->get(route('ic.sandi.atur', ['token' => $token, 'email' => $m->email]))->assertOk()->assertSee('Buat kata sandi baru');

        $this->post(route('ic.sandi.atur.proses'), [
            'token' => $token,
            'email' => $m->email,
            'sandi' => 'Sandi-Baru-456',
            'sandi_confirmation' => 'Sandi-Baru-456',
        ])->assertRedirect(route('ic.masuk'));

        $m = $m->fresh();
        $this->assertTrue(password_verify('Sandi-Baru-456', $m->password));
        $this->assertNotNull($m->email_verified_at);
        $this->assertSame(1, $m->sesi_versi);

        // Token sekali pakai.
        $this->post(route('ic.sandi.atur.proses'), [
            'token' => $token,
            'email' => $m->email,
            'sandi' => 'Sandi-Lain-789',
            'sandi_confirmation' => 'Sandi-Lain-789',
        ])->assertSessionHasErrors('email');
    }

    public function test_atur_ulang_token_salah_ditolak(): void
    {
        $m = $this->anggota();

        $this->post(route('ic.sandi.atur.proses'), [
            'token' => 'salah',
            'email' => $m->email,
            'sandi' => 'Sandi-Baru-456',
            'sandi_confirmation' => 'Sandi-Baru-456',
        ])->assertSessionHasErrors('email');
        $this->assertTrue(password_verify('sandi-lama-123', $m->fresh()->password));
    }

    public function test_lupa_sandi_dibatasi_per_email(): void
    {
        Mail::fake();
        RateLimiter::clear('x');
        for ($i = 0; $i < 3; $i++) {
            $this->post(route('ic.sandi.kirim'), ['email' => 'a@b.com'])->assertSessionDoesntHaveErrors();
        }
        $this->post(route('ic.sandi.kirim'), ['email' => 'a@b.com'])->assertSessionHasErrors('email');
    }
}
