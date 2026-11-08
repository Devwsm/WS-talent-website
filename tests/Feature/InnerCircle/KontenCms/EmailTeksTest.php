<?php

namespace Tests\Feature\InnerCircle\KontenCms;

use App\Mail\InnerCircle\StatusKlaimMail;
use App\Models\ic_pengaturan;
use App\Services\InnerCircle\EmailService;
use App\Services\InnerCircle\IcKonten;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Teks email (subjek dan pembuka) yang bisa diubah dari CMS.
 */
class EmailTeksTest extends TestCase
{
    use RefreshDatabase;
    // ---------- Email: bawaan identik ----------
    // ---------- Email: simpan ----------
    // ---------- Email: keamanan ----------
    // ---------- Privasi ----------

    private function admin()
    {
        return $this->withSession(['login' => true, 'user' => 'admin']);
    }

    private function simpanEmail(string $jenis, array $isi)
    {
        return $this->admin()->post(route('ic.admin.email.teks', $jenis), $isi + ['subjek' => '', 'pembuka' => '']);
    }

    private function subjek(string $jenis): string
    {
        return EmailService::contoh($jenis)->envelope()->subject;
    }

    private function htmlEmail(string $jenis): string
    {
        return EmailService::contoh($jenis)->render();
    }

    public function test_subjek_bawaan_sama_persis_seperti_sebelumnya(): void
    {
        $this->assertSame('Atur ulang kata sandi WS Inner Circle', $this->subjek('reset'));
        $this->assertSame('Akun WS Inner Circle kamu masuk lewat Google', $this->subjek('google'));
        $this->assertSame('Selamat datang di WS Inner Circle', $this->subjek('selamat-datang'));
        $this->assertSame('Verifikasi email WS Inner Circle', $this->subjek('verifikasi'));
        $this->assertSame('Hadiah "Merch Eksklusif": Ditolak', $this->subjek('klaim'));
    }

    public function test_pembuka_bawaan_sama_persis_dan_klaim_memakai_kalimat_menurut_status(): void
    {
        $this->assertStringContainsString('Kami menerima permintaan untuk mengatur ulang kata sandi akun WS Inner Circle kamu.', $this->htmlEmail('reset'));
        $this->assertStringContainsString('Akun WS Inner Circle kamu sudah aktif. Kode anggotamu: IC-00001.', $this->htmlEmail('selamat-datang'));
        $this->assertStringContainsString('belum bisa kami proses', $this->htmlEmail('klaim'));
        $this->assertStringContainsString('Buka WS Inner Circle', $this->htmlEmail('selamat-datang'));
    }

    public function test_teks_email_tersimpan_dipakai_dan_placeholder_diganti(): void
    {
        $this->simpanEmail('selamat-datang', ['subjek' => 'Halo {nama}, selamat datang di {program}', 'pembuka' => 'Kode kamu {kode}. Sampai jumpa di show!'])
            ->assertSessionHasNoErrors()->assertRedirect(route('ic.admin.email', ['jenis' => 'selamat-datang']) . '#teks-email');

        $this->assertSame('Halo Contoh Anggota, selamat datang di WS Inner Circle', $this->subjek('selamat-datang'));
        $this->assertStringContainsString('Kode kamu IC-00001. Sampai jumpa di show!', $this->htmlEmail('selamat-datang'));
        $this->assertStringNotContainsString('sudah aktif. Kode anggotamu', $this->htmlEmail('selamat-datang'));
    }

    public function test_email_lain_tidak_ikut_berubah(): void
    {
        $this->simpanEmail('reset', ['subjek' => 'Reset sandi']);

        $this->assertSame('Reset sandi', $this->subjek('reset'));
        $this->assertSame('Verifikasi email WS Inner Circle', $this->subjek('verifikasi'));
    }

    public function test_teks_sama_dengan_bawaan_atau_kosong_tidak_membuat_baris(): void
    {
        $this->simpanEmail('reset', ['subjek' => 'Reset sandi']);
        $this->assertDatabaseHas('ic_pengaturan', ['kunci' => 'email.reset.subjek']);

        $this->simpanEmail('reset', ['subjek' => 'Atur ulang kata sandi {program}'])->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('ic_pengaturan', ['kunci' => 'email.reset.subjek']);

        $this->simpanEmail('reset', ['subjek' => 'Reset sandi']);
        $this->simpanEmail('reset', ['subjek' => ''])->assertSessionHasNoErrors();
        $this->assertSame('Atur ulang kata sandi WS Inner Circle', $this->subjek('reset'));
    }

    public function test_pembuka_klaim_menggantikan_kalimat_semua_status_dan_kosong_kembali_ke_bawaan(): void
    {
        $this->simpanEmail('klaim', ['pembuka' => 'Soal {hadiah}: statusnya {status}.', 'subjek' => '{hadiah} - {status}'])->assertSessionHasNoErrors();

        $html = $this->htmlEmail('klaim');
        $this->assertStringContainsString('Soal Merch Eksklusif: statusnya Ditolak.', $html);
        $this->assertStringNotContainsString('belum bisa kami proses', $html);
        $this->assertSame('Merch Eksklusif - Ditolak', $this->subjek('klaim'));

        $this->simpanEmail('klaim', ['pembuka' => '']);
        $this->assertStringContainsString('belum bisa kami proses', $this->htmlEmail('klaim'));
    }

    public function test_nama_program_ikut_ke_email(): void
    {
        ic_pengaturan::create(['kunci' => 'tampilan.program', 'nilai' => 'Whisnu Crew']);
        app(IcKonten::class)->lupakan();

        $this->assertSame('Selamat datang di Whisnu Crew', $this->subjek('selamat-datang'));
        $this->assertStringContainsString('Buka Whisnu Crew', $this->htmlEmail('selamat-datang'));
        $this->assertStringContainsString('Whisnu Crew', $this->htmlEmail('verifikasi'));
    }

    public function test_placeholder_asing_dan_tanda_berbahaya_ditolak(): void
    {
        $this->simpanEmail('reset', ['subjek' => 'Halo {kode}'])->assertSessionHasErrorsIn('emailteks', 'subjek');
        $this->simpanEmail('reset', ['pembuka' => 'Klik <a href="x">sini</a>'])->assertSessionHasErrorsIn('emailteks', 'pembuka');
        $this->simpanEmail('reset', ['subjek' => str_repeat('x', 91)])->assertSessionHasErrorsIn('emailteks', 'subjek');
        $this->simpanEmail('reset', ['pembuka' => str_repeat('x', 401)])->assertSessionHasErrorsIn('emailteks', 'pembuka');

        $this->assertSame(0, ic_pengaturan::query()->where('kunci', 'like', 'email.%')->count());
    }

    public function test_teks_email_di_escape_di_badan_email(): void
    {
        $this->simpanEmail('reset', ['pembuka' => 'Tom & "Jerry" dan {nama}'])->assertSessionHasNoErrors();

        $html = $this->htmlEmail('reset');
        $this->assertStringContainsString('Tom &amp; &quot;Jerry&quot; dan Contoh Anggota', $html);
        $this->assertStringNotContainsString('Tom & "Jerry"', $html);
    }

    public function test_data_anggota_tidak_bisa_menyisipkan_baris_baru_ke_subjek(): void
    {
        $surat = new StatusKlaimMail('Budi', "Merch\r\nBcc: korban@contoh.test", 'ditolak', 'Ditolak', null, 'https://contoh.test');

        $this->assertStringNotContainsString("\n", $surat->envelope()->subject);
        $this->assertStringNotContainsString("\r", $surat->envelope()->subject);
    }

    public function test_nilai_placeholder_tidak_diproses_ulang(): void
    {
        $this->simpanEmail('selamat-datang', ['pembuka' => 'Halo {nama}, kodemu {kode}.']);

        $surat = new \App\Mail\InnerCircle\SelamatDatangMail('{kode}', 'IC-00007', 'https://contoh.test');
        $this->assertStringContainsString('Halo {kode}, kodemu IC-00007.', $surat->render());
    }

    public function test_kembalikan_bawaan_dan_akses_email(): void
    {
        $this->simpanEmail('reset', ['subjek' => 'Reset sandi', 'pembuka' => 'Pembuka baru']);
        $this->simpanEmail('verifikasi', ['subjek' => 'Verifikasi lain']);

        $this->admin()->post(route('ic.admin.email.teks.bawaan', 'reset'))->assertRedirect()->assertSessionHas('success');

        $this->assertSame('Atur ulang kata sandi WS Inner Circle', $this->subjek('reset'));
        $this->assertSame('Verifikasi lain', $this->subjek('verifikasi'), 'jenis lain tidak ikut dikembalikan');

        $this->admin()->post(route('ic.admin.email.teks', 'tidak-ada'), ['subjek' => 'x'])->assertNotFound();
        $this->admin()->post(route('ic.admin.email.teks.bawaan', 'tidak-ada'))->assertNotFound();
    }

    public function test_hanya_admin_yang_boleh_mengubah_teks_email(): void
    {
        $this->post(route('ic.admin.email.teks', 'reset'), ['subjek' => 'x'])->assertRedirect(route('login'));
        $this->post(route('ic.admin.email.teks.bawaan', 'reset'))->assertRedirect(route('login'));
        $this->assertSame(0, ic_pengaturan::query()->count());
    }

    public function test_tab_email_menampilkan_form_teks_dan_nilai_tersimpan(): void
    {
        $this->admin()->get(route('ic.admin.email', ['jenis' => 'reset']))
            ->assertOk()->assertSee('id="teks-email"', false)->assertSee('Paragraf pembuka')->assertSee('Atur ulang kata sandi {program}');

        $this->simpanEmail('reset', ['subjek' => 'Reset sandi']);

        $this->admin()->get(route('ic.admin.email', ['jenis' => 'reset']))->assertOk()->assertSee('value="Reset sandi"', false);
    }
}
