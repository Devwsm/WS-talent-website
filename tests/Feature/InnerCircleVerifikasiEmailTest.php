<?php

namespace Tests\Feature;

use App\Mail\InnerCircle\VerifikasiEmailMail;
use App\Models\ic_claim;
use App\Models\member;
use App\Services\InnerCircle\EmailService;
use App\Services\InnerCircle\StempelService;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * Verifikasi email akun sandi (hadiah baru bisa diajukan setelah terverifikasi) dan aturan kata sandi anggota
 * (huruf besar + kecil + angka, simbol boleh, emoji tidak) untuk daftar manual dan atur ulang.
 */
class InnerCircleVerifikasiEmailTest extends TestCase
{
    use RefreshDatabase;

    /** Broker kata sandi anggota, bertipe konkret supaya editor mengenali createToken(). */
    private function broker(): PasswordBroker
    {
        return Password::broker('members');
    }

    private function anggota(string $email = 'a@contoh.test', int $stempel = 0): member
    {
        $m = member::create(['nama' => 'Anggota Uji', 'email' => $email, 'password' => 'Rahasia123']);
        $m->kode_member = sprintf('IC-%05d', $m->id_member);
        $m->save();
        if ($stempel > 0) {
            app(StempelService::class)->tambahManual($m, $stempel, 'Uji', 'admin');
        }

        return $m->fresh();
    }

    private function daftar(array $o = [])
    {
        return $this->from(route('ic.daftar'))->post(route('ic.daftar.proses'), $o + [
            'nama' => 'Rani',
            'email' => 'rani@contoh.test',
            'sandi' => 'Rahasia123',
            'sandi_confirmation' => 'Rahasia123',
        ]);
    }

    // ---------- verifikasi email ----------

    public function test_daftar_dengan_sandi_mengirim_email_verifikasi_dan_akun_belum_terverifikasi(): void
    {
        Mail::fake();

        $this->daftar()->assertRedirect(route('ic.beranda'));

        $m = member::firstOrFail();
        $this->assertNull($m->email_verified_at);
        Mail::assertSent(VerifikasiEmailMail::class, fn($surat) => $surat->hasTo('rani@contoh.test'));
    }

    public function test_klaim_hadiah_ditolak_sebelum_verifikasi_dan_berhasil_sesudahnya(): void
    {
        $m = $this->anggota('a@contoh.test', 12);

        $this->actingAs($m, 'member')->post(route('ic.hadiah.klaim', 10))
            ->assertRedirect(route('ic.hadiah') . '#klaim')
            ->assertSessionHas('ic_toast_galat', fn($pesan) => str_contains($pesan, 'Verifikasi email dulu'));
        $this->assertSame(0, ic_claim::count());

        $m->forceFill(['email_verified_at' => now()])->save();

        $this->actingAs($m->fresh(), 'member')->post(route('ic.hadiah.klaim', 10))->assertSessionHas('ic_toast');
        $this->assertSame(1, ic_claim::count());
    }

    public function test_halaman_hadiah_menampilkan_ajakan_verifikasi_hanya_untuk_yang_belum(): void
    {
        $m = $this->anggota('a@contoh.test', 12);

        $this->actingAs($m, 'member')->get(route('ic.hadiah'))
            ->assertOk()->assertSee('Verifikasi emailmu dulu')->assertSee('Kirim ulang email verifikasi');

        $m->forceFill(['email_verified_at' => now()])->save();

        $this->actingAs($m->fresh(), 'member')->get(route('ic.hadiah'))
            ->assertOk()->assertDontSee('Verifikasi emailmu dulu');
    }

    public function test_tautan_verifikasi_mengesahkan_email_anggota_yang_sedang_masuk(): void
    {
        $m = $this->anggota();
        $url = EmailService::urlVerifikasi($m);

        $this->actingAs($m, 'member')->get($url)
            ->assertRedirect(route('ic.hadiah') . '#klaim')->assertSessionHas('ic_toast');

        $this->assertNotNull($m->fresh()->email_verified_at);
    }

    public function test_tamu_yang_membuka_tautan_diminta_masuk_dan_email_tidak_terverifikasi(): void
    {
        $m = $this->anggota();

        $this->get(EmailService::urlVerifikasi($m))->assertRedirect(route('ic.masuk'));

        $this->assertNull($m->fresh()->email_verified_at);
    }

    public function test_tautan_milik_akun_lain_ditolak(): void
    {
        // Skenario pengambilalihan: penyerang mendaftarkan email korban; korban tidak bisa masuk ke akun itu.
        $korban = $this->anggota('korban@contoh.test');
        $penyerang = $this->anggota('penyerang@contoh.test');

        $this->actingAs($penyerang, 'member')->get(EmailService::urlVerifikasi($korban))
            ->assertRedirect(route('ic.hadiah'))->assertSessionHas('ic_toast_galat');

        $this->assertNull($korban->fresh()->email_verified_at);
        $this->assertNull($penyerang->fresh()->email_verified_at);
    }

    public function test_tautan_kedaluwarsa_atau_diubah_ditolak(): void
    {
        $m = $this->anggota();
        $url = EmailService::urlVerifikasi($m);

        $this->actingAs($m, 'member')->get($url . '0')
            ->assertRedirect(route('ic.hadiah'))->assertSessionHas('ic_toast_galat');
        $this->assertNull($m->fresh()->email_verified_at);

        $this->travel(EmailService::jamBerlakuVerifikasi() + 1)->hours();
        $this->actingAs($m, 'member')->get($url)
            ->assertRedirect(route('ic.hadiah'))->assertSessionHas('ic_toast_galat');
        $this->assertNull($m->fresh()->email_verified_at);
    }

    public function test_kirim_ulang_dibatasi_per_anggota(): void
    {
        Mail::fake();
        $m = $this->anggota();
        $kirim = fn() => $this->actingAs($m, 'member')->post(route('ic.verifikasi.kirim'));

        $kirim()->assertSessionHas('ic_toast');
        $kirim()->assertSessionHas('ic_toast');
        $kirim()->assertSessionHas('ic_toast_galat', fn($pesan) => str_contains($pesan, 'Terlalu banyak'));

        Mail::assertSent(VerifikasiEmailMail::class, 2);
    }

    public function test_kirim_ulang_tidak_mengirim_apa_apa_bila_sudah_terverifikasi(): void
    {
        Mail::fake();
        $m = $this->anggota();
        $m->forceFill(['email_verified_at' => now()])->save();

        $this->actingAs($m->fresh(), 'member')->post(route('ic.verifikasi.kirim'))->assertSessionHas('ic_toast');

        Mail::assertNothingSent();
    }

    public function test_batas_harian_seluruh_situs_menghentikan_pengiriman(): void
    {
        Mail::fake();
        config(['inner_circle.batas_verifikasi_global_per_hari' => 1]);
        $m = $this->anggota();

        $this->assertTrue(app(EmailService::class)->kirimVerifikasi($m));
        $this->assertFalse(app(EmailService::class)->kirimVerifikasi($m));

        Mail::assertSent(VerifikasiEmailMail::class, 1);

        $this->actingAs($m, 'member')->post(route('ic.verifikasi.kirim'))
            ->assertSessionHas('ic_toast_galat', fn($pesan) => str_contains($pesan, 'sedang padat'));
    }

    public function test_email_verifikasi_ada_di_pratinjau_admin(): void
    {
        $this->withSession(['login' => true, 'user' => 'admin'])
            ->get(route('ic.admin.email.pratinjau', 'verifikasi'))
            ->assertOk()->assertSee('Verifikasi email');
    }

    // ---------- aturan kata sandi anggota ----------

    public function test_daftar_menolak_sandi_yang_tidak_memenuhi_aturan(): void
    {
        $ditolak = [
            'rahasia123' => 'tanpa huruf besar',
            'RAHASIA123' => 'tanpa huruf kecil',
            'Rahasiaaaa' => 'tanpa angka',
            'Rahasia123😀' => 'emoji',
            'Rahasia123é' => 'karakter di luar keyboard biasa',
        ];

        foreach ($ditolak as $sandi => $alasan) {
            try {
                $this->daftar(['sandi' => $sandi, 'sandi_confirmation' => $sandi])->assertSessionHasErrors('sandi');
            } catch (\Throwable) {
                $this->fail("Sandi {$alasan} seharusnya ditolak.");
            }
        }

        $this->assertSame(0, member::count());
    }

    public function test_daftar_menerima_sandi_dengan_simbol(): void
    {
        $this->daftar(['sandi' => 'Rah@sia-123!', 'sandi_confirmation' => 'Rah@sia-123!'])
            ->assertSessionHasNoErrors()->assertRedirect(route('ic.beranda'));

        $this->assertSame(1, member::count());
    }

    public function test_atur_ulang_memakai_aturan_sandi_yang_sama(): void
    {
        $m = $this->anggota();
        $token = $this->broker()->createToken($m);
        $kirim = fn(string $sandi) => $this->post(route('ic.sandi.atur.proses'), [
            'token' => $token,
            'email' => $m->email,
            'sandi' => $sandi,
            'sandi_confirmation' => $sandi,
        ]);

        $kirim('lemahsekali1')->assertSessionHasErrors('sandi');
        $kirim('Rahasia123😀')->assertSessionHasErrors('sandi');
        $this->assertTrue(password_verify('Rahasia123', $m->fresh()->password));

        $kirim('Kuat-Baru-456')->assertRedirect(route('ic.masuk'));
        $this->assertTrue(password_verify('Kuat-Baru-456', $m->fresh()->password));
    }

    public function test_masuk_tidak_memakai_aturan_baru_agar_akun_lama_tetap_bisa_masuk(): void
    {
        member::create(['nama' => 'Lama', 'email' => 'lama@contoh.test', 'password' => 'sandilama123']);

        $this->post(route('ic.masuk.proses'), ['email' => 'lama@contoh.test', 'sandi' => 'sandilama123'])
            ->assertRedirect(route('ic.beranda'));
    }
}