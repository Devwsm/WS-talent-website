<?php

namespace Tests\Feature\InnerCircle\Akun;

use App\Models\member;
use App\Services\InnerCircle\BatasEmailKembar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Pesan "Email ini sudah terdaftar" tetap ada (orang sering lupa sudah punya akun), tapi tidak boleh bisa dipakai
 * menebak email anggota secara massal: percobaan email kembar dibatasi per IP.
 */
class BatasEmailKembarTest extends TestCase
{
    use RefreshDatabase;

    private function anggota(string $email): member
    {
        return member::create(['nama' => 'Ada', 'email' => $email, 'password' => 'Rahasia123']);
    }

    private function daftar(string $email, string $ip = '127.0.0.1', string $sandi = 'Rahasia123')
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->from(route('ic.daftar'))
            ->post(route('ic.daftar.proses'), [
                'nama' => 'Penguji',
                'email' => $email,
                'sandi' => $sandi,
                'sandi_confirmation' => $sandi,
            ]);
    }

    public function test_email_kembar_tetap_memberi_pesan_biasa_di_bawah_batas(): void
    {
        $this->anggota('ada@contoh.test');

        $this->daftar('ada@contoh.test')
            ->assertSessionHasErrors(['email' => 'Email ini sudah terdaftar. Silakan masuk.']);
    }

    public function test_setelah_batas_ip_ditahan_dan_email_baru_pun_ditolak_tanpa_membuat_akun(): void
    {
        $this->anggota('ada@contoh.test');

        for ($i = 0; $i < BatasEmailKembar::MAKS; $i++) {
            $this->daftar('ada@contoh.test')->assertSessionHasErrors('email');
        }
        $this->assertSame(1, member::count());

        $this->daftar('baru@contoh.test')
            ->assertSessionHasErrors(['email' => BatasEmailKembar::PESAN_DITAHAN]);

        $this->assertSame(1, member::count(), 'Selama ditahan, tidak ada akun yang dibuat.');
        $this->assertGuest('member');
    }

    public function test_saat_ditahan_jawabannya_sama_untuk_email_terdaftar_dan_tidak(): void
    {
        $this->anggota('ada@contoh.test');
        for ($i = 0; $i < BatasEmailKembar::MAKS; $i++) {
            $this->daftar('ada@contoh.test');
        }

        $terdaftar = $this->daftar('ada@contoh.test');
        $tidak = $this->daftar('tidak-ada@contoh.test');

        $terdaftar->assertSessionHasErrors(['email' => BatasEmailKembar::PESAN_DITAHAN]);
        $tidak->assertSessionHasErrors(['email' => BatasEmailKembar::PESAN_DITAHAN]);
    }

    public function test_ip_lain_tidak_ikut_tertahan(): void
    {
        $this->anggota('ada@contoh.test');
        for ($i = 0; $i < BatasEmailKembar::MAKS; $i++) {
            $this->daftar('ada@contoh.test', '10.0.0.1');
        }

        $this->daftar('baru@contoh.test', '10.0.0.2')->assertSessionHasNoErrors();

        $this->assertDatabaseHas('members', ['email' => 'baru@contoh.test']);
    }

    public function test_pendaftaran_sah_dan_galat_lain_tidak_dihitung(): void
    {
        for ($i = 1; $i <= BatasEmailKembar::MAKS + 3; $i++) {
            $this->daftar("sah{$i}@contoh.test", '10.0.0.3')->assertSessionHasNoErrors();
            $this->post(route('ic.keluar'));
        }
        // Sandi lemah juga bukan percobaan email kembar.
        for ($i = 0; $i < BatasEmailKembar::MAKS + 3; $i++) {
            $this->daftar('lemah@contoh.test', '10.0.0.3', 'pendek')->assertSessionHasErrors('sandi');
        }

        $this->assertFalse(app(BatasEmailKembar::class)->ditahan(\Illuminate\Http\Request::create('/', 'POST', [], [], [], ['REMOTE_ADDR' => '10.0.0.3'])));
    }

    public function test_tahanan_berakhir_setelah_jendela_waktu(): void
    {
        $this->anggota('ada@contoh.test');
        for ($i = 0; $i < BatasEmailKembar::MAKS; $i++) {
            $this->daftar('ada@contoh.test');
        }
        $this->daftar('baru@contoh.test')->assertSessionHasErrors(['email' => BatasEmailKembar::PESAN_DITAHAN]);

        RateLimiter::clear('daftar-email-kembar:127.0.0.1'); // setara habisnya jendela 10 menit

        $this->daftar('baru@contoh.test')->assertSessionHasNoErrors();
        $this->assertDatabaseHas('members', ['email' => 'baru@contoh.test']);
    }
}