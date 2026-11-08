<?php

namespace Tests\Feature\Admin;

use App\Mail\Admin\KodeLoginAdminMail;
use App\Models\account;
use App\Models\log_aktivitas;
use App\Models\log_error;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use ReflectionProperty;
use RuntimeException;
use Tests\TestCase;

/**
 * Login admin dengan kode OTP email (2FA): kirim, verifikasi, kedaluwarsa, batas percobaan, dan kegagalan email.
 */
class LoginDuaFaktorTest extends TestCase
{
    use RefreshDatabase;
    // ---------- log error ----------
    // ---------- email peringatan error ----------
    // ---------- halaman Log ----------
    // ---------- log aktivitas ----------
    // ---------- 2FA: kode OTP lewat email ----------

    private const SANDI = 'SandiPanjangUji123';

    private function akun(?string $email = 'admin@contoh.test'): account
    {
        return account::create(['username' => 'admin', 'email' => $email, 'password' => Hash::make(self::SANDI)]);
    }

    private function login(array $o = [])
    {
        return $this->post(route('login.proses'), $o + ['username' => 'admin', 'password' => self::SANDI]);
    }

    /** Kode OTP dari email terakhir yang terkirim (properti privat dibaca lewat refleksi). */
    private function kodeTerkirim(): string
    {
        $surat = Mail::sent(KodeLoginAdminMail::class)->last();
        $p = new ReflectionProperty(KodeLoginAdminMail::class, 'kode');

        return $p->getValue($surat);
    }

    public function test_tanpa_2fa_login_langsung_masuk_seperti_biasa(): void
    {
        Mail::fake();
        $this->akun();

        $this->login()->assertRedirect(route('dashboard'))->assertSessionHas('login', true);
        Mail::assertNothingSent();
    }

    public function test_dengan_2fa_sandi_benar_belum_cukup_dan_kode_dikirim(): void
    {
        Mail::fake();
        config(['site.admin_2fa_aktif' => true]);
        $this->akun();

        $this->login()->assertRedirect(route('login.otp'))->assertSessionMissing('login')->assertSessionHas('otp_akun');
        Mail::assertSent(KodeLoginAdminMail::class, fn($m) => $m->hasTo('admin@contoh.test'));
        $this->assertMatchesRegularExpression('/^\d{6}$/', $this->kodeTerkirim());

        // Dashboard tetap tertutup selama kode belum benar.
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('login.otp'))->assertOk()->assertSee('ad***@contoh.test');
    }

    public function test_sandi_salah_dengan_2fa_tidak_mengirim_kode(): void
    {
        Mail::fake();
        config(['site.admin_2fa_aktif' => true]);
        $this->akun();

        $this->login(['password' => 'sandi-salah-sekali'])->assertSessionHasErrors('login');
        Mail::assertNothingSent();
    }

    public function test_kode_benar_masuk_dan_hanya_berlaku_sekali(): void
    {
        Mail::fake();
        config(['site.admin_2fa_aktif' => true]);
        $this->akun();
        $this->login();
        $kode = $this->kodeTerkirim();

        $this->post(route('login.otp.proses'), ['kode' => substr($kode, 0, 3) . ' ' . substr($kode, 3)])
            ->assertRedirect(route('dashboard'))->assertSessionHas('login', true)->assertSessionHas('user', 'admin');
        $this->assertTrue(log_aktivitas::where('aksi', 'Login')->where('keterangan', 'kode OTP email')->exists());

        // Kode yang sama tidak bisa dipakai di sesi lain.
        $this->flushSession();
        $this->withSession(['otp_akun' => account::first()->getKey()])
            ->post(route('login.otp.proses'), ['kode' => $kode])->assertSessionHasErrors('kode')->assertSessionMissing('login');
    }

    public function test_kode_salah_ditolak_dan_lima_kali_salah_menghanguskan_kode(): void
    {
        Mail::fake();
        config(['site.admin_2fa_aktif' => true]);
        $this->akun();
        $this->login();
        $benar = $this->kodeTerkirim();
        $salah = $benar === '000000' ? '111111' : '000000';

        for ($i = 0; $i < 4; $i++) {
            $this->post(route('login.otp.proses'), ['kode' => $salah])->assertSessionHasErrors('kode')->assertSessionMissing('login');
        }

        // Kali kelima: kode hangus dan proses OTP berakhir, harus masuk ulang dengan sandi.
        $this->post(route('login.otp.proses'), ['kode' => $salah])->assertRedirect(route('login'))->assertSessionHasErrors('login');
        $this->assertNull(session('otp_akun'));

        // Kode yang benar pun tidak lagi berlaku.
        $this->withSession(['otp_akun' => account::first()->getKey()])
            ->post(route('login.otp.proses'), ['kode' => $benar])->assertSessionMissing('login');
    }

    public function test_kode_kedaluwarsa_setelah_sepuluh_menit(): void
    {
        Mail::fake();
        config(['site.admin_2fa_aktif' => true]);
        $this->akun();
        $this->login();
        $kode = $this->kodeTerkirim();

        $this->travel(11)->minutes();

        $this->post(route('login.otp.proses'), ['kode' => $kode])->assertSessionHasErrors('kode')->assertSessionMissing('login');
    }

    public function test_format_kode_dicek_dan_halaman_otp_butuh_sandi_benar_dulu(): void
    {
        $this->get(route('login.otp'))->assertRedirect(route('login'));
        $this->post(route('login.otp.proses'), ['kode' => '123456'])->assertRedirect(route('login'));

        Mail::fake();
        config(['site.admin_2fa_aktif' => true]);
        $this->akun();
        $this->login();

        $this->post(route('login.otp.proses'), ['kode' => '12ab'])->assertSessionHasErrors('kode');
        $this->post(route('login.otp.proses'), ['kode' => ''])->assertSessionHasErrors('kode');
    }

    public function test_kirim_ulang_kode_dibatasi_tiga_kali_per_sepuluh_menit(): void
    {
        Mail::fake();
        config(['site.admin_2fa_aktif' => true]);
        $this->akun();
        $this->login(); // pengiriman ke-1

        $this->post(route('login.otp.ulang'))->assertRedirect(route('login.otp'))->assertSessionHas('success'); // ke-2
        $this->post(route('login.otp.ulang'))->assertRedirect(route('login.otp'))->assertSessionHas('success'); // ke-3
        Mail::assertSent(KodeLoginAdminMail::class, 3);

        $this->post(route('login.otp.ulang'))->assertSessionHasErrors('kode'); // ke-4 ditolak
        Mail::assertSent(KodeLoginAdminMail::class, 3);
    }

    public function test_email_admin_kosong_tidak_mengunci_pemilik(): void
    {
        Mail::fake();
        config(['site.admin_2fa_aktif' => true]);
        $this->akun(null);

        $this->login()->assertRedirect(route('dashboard'))->assertSessionHas('login', true);
        Mail::assertNothingSent();
        $this->assertTrue(log_aktivitas::where('aksi', 'Login tanpa 2FA')->exists());
    }

    public function test_email_gagal_terkirim_memberi_pesan_jelas_dan_tidak_membuka_dashboard(): void
    {
        config(['site.admin_2fa_aktif' => true]);
        $this->akun();
        Mail::shouldReceive('to')->andThrow(new RuntimeException('smtp mati'));

        $this->login()->assertRedirect(route('login'))->assertSessionHasErrors('login')
            ->assertSessionMissing('login')->assertSessionMissing('otp_akun');

        $this->assertTrue(log_aktivitas::where('aksi', 'Kode OTP gagal dikirim')->exists());
        $this->assertTrue(log_error::where('pesan', 'smtp mati')->exists()); // tampil di halaman Log
    }
}
