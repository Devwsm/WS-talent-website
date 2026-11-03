<?php

namespace Tests\Feature;

use App\Mail\InnerCircle\StatusKlaimMail;
use App\Models\ic_pengaturan;
use App\Services\InnerCircle\EmailService;
use App\Services\InnerCircle\IcKonten;
use App\Services\InnerCircle\IcPrivasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Fase 7 (pisah CMS): teks email (subjek, pembuka) dan isi /privasi yang bisa diubah dari CMS. */
class InnerCircleFase7Test extends TestCase
{
    use RefreshDatabase;

    private function admin()
    {
        return $this->withSession(['login' => true, 'user' => 'admin']);
    }

    private function simpanEmail(string $jenis, array $isi)
    {
        return $this->admin()->post(route('ic.admin.email.teks', $jenis), $isi + ['subjek' => '', 'pembuka' => '']);
    }

    private function simpanPrivasi(string $html)
    {
        return $this->admin()->post(route('ic.admin.konten.privasi.simpan'), ['isi' => $html]);
    }

    private function subjek(string $jenis): string
    {
        return EmailService::contoh($jenis)->envelope()->subject;
    }

    private function htmlEmail(string $jenis): string
    {
        return EmailService::contoh($jenis)->render();
    }

    // ---------- Email: bawaan identik ----------

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

    // ---------- Email: simpan ----------

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

    // ---------- Email: keamanan ----------

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

    // ---------- Privasi ----------

    public function test_privasi_bawaan_tidak_berubah(): void
    {
        $this->get(route('privasi'))->assertOk()
            ->assertSee('Saat kamu hanya melihat situs')
            ->assertSee('Masuk dengan Google')
            ->assertSee('Diperbarui 31 Oktober 2026');
        $this->assertFalse(app(IcPrivasi::class)->sudahKustom());
    }

    public function test_isi_privasi_tersimpan_tampil_dan_tanggal_terisi_otomatis(): void
    {
        $this->travelTo('2027-02-03 10:00:00');

        $this->simpanPrivasi('<h2>Data kami</h2><p>Kami <strong>tidak</strong> menjual datamu.</p><ul><li>Satu</li></ul>')
            ->assertSessionHasNoErrors()->assertRedirect(route('ic.admin.konten') . '#privasi');

        $this->get(route('privasi'))->assertOk()
            ->assertSee('<h2>Data kami</h2>', false)
            ->assertSee('Kami <strong>tidak</strong> menjual datamu.', false)
            ->assertSee('Diperbarui 3 Februari 2027')
            ->assertDontSee('Saat kamu hanya melihat situs');
    }

    public function test_html_berbahaya_di_privasi_dibersihkan(): void
    {
        $this->simpanPrivasi('<p onclick="x()">Halo</p><script>alert(1)</script><iframe src="https://jahat.test"></iframe><a href="javascript:alert(1)">klik</a><img src=x onerror=alert(1)>')
            ->assertSessionHasNoErrors();

        $html = $this->get(route('privasi'))->assertOk()->getContent();
        $this->assertStringContainsString('Halo', $html);
        $this->assertStringNotContainsString('alert(1)', $html);
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringNotContainsString('jahat.test', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
    }

    public function test_isi_rusak_di_database_dibersihkan_saat_dibaca(): void
    {
        ic_pengaturan::create(['kunci' => 'privasi.isi', 'nilai' => '<p>Aman</p><script>alert("db")</script>']);
        app(IcKonten::class)->lupakan();

        $html = $this->get(route('privasi'))->assertOk()->getContent();
        $this->assertStringContainsString('Aman', $html);
        $this->assertStringNotContainsString('alert("db")', $html);
    }

    public function test_isi_kosong_atau_kepanjangan_ditolak(): void
    {
        foreach (['', '   ', '<p><br></p>', '<p>&nbsp;</p>', '<script>x</script>'] as $kosong) {
            $this->simpanPrivasi($kosong)->assertSessionHasErrorsIn('privasi', 'isi');
        }
        $this->simpanPrivasi('<p>' . str_repeat('a', 30001) . '</p>')->assertSessionHasErrorsIn('privasi', 'isi');

        $this->assertDatabaseMissing('ic_pengaturan', ['kunci' => 'privasi.isi']);
    }

    public function test_tanggal_tidak_berubah_bila_isi_sama(): void
    {
        $this->simpanPrivasi('<p>Isi tetap</p>');
        ic_pengaturan::query()->where('kunci', 'privasi.diperbarui')->update(['nilai' => '2026-01-02']);
        app(IcKonten::class)->lupakan();

        $this->simpanPrivasi('<p>Isi tetap</p>')->assertSessionHasNoErrors();
        $this->assertSame('2026-01-02', app(IcPrivasi::class)->diperbarui());

        $this->simpanPrivasi('<p>Isi berubah</p>');
        $this->assertSame(now()->toDateString(), app(IcPrivasi::class)->diperbarui());
    }

    public function test_kembalikan_teks_bawaan_privasi(): void
    {
        $this->simpanPrivasi('<p>Isi sendiri</p>');

        $this->admin()->post(route('ic.admin.konten.privasi.bawaan'))->assertRedirect(route('ic.admin.konten') . '#privasi')->assertSessionHas('success');

        $this->assertDatabaseMissing('ic_pengaturan', ['kunci' => 'privasi.isi']);
        $this->assertDatabaseMissing('ic_pengaturan', ['kunci' => 'privasi.diperbarui']);
        $this->get(route('privasi'))->assertSee('Saat kamu hanya melihat situs')->assertSee('Diperbarui 31 Oktober 2026');
    }

    public function test_tanggal_rusak_memakai_tanggal_config(): void
    {
        ic_pengaturan::create(['kunci' => 'privasi.diperbarui', 'nilai' => 'kemarin']);
        app(IcKonten::class)->lupakan();

        $this->assertSame(config('site.privasi_diperbarui'), app(IcPrivasi::class)->diperbarui());
    }

    public function test_cms_menampilkan_editor_dengan_teks_bawaan_lalu_isi_sendiri(): void
    {
        $this->admin()->get(route('ic.admin.konten'))->assertOk()
            ->assertSee('id="privasi"', false)->assertSee('Kebijakan privasi')
            ->assertSee('Saat kamu hanya melihat situs')->assertSee('teks bawaan');

        $this->simpanPrivasi('<p>Isi sendiri yang unik</p>');

        $this->admin()->get(route('ic.admin.konten'))->assertOk()
            ->assertSee('Isi sendiri yang unik')->assertDontSee('Saat kamu hanya melihat situs')->assertSee('Kembalikan teks');
    }

    public function test_hanya_admin_yang_boleh_mengubah_privasi(): void
    {
        $this->post(route('ic.admin.konten.privasi.simpan'), ['isi' => '<p>x</p>'])->assertRedirect(route('login'));
        $this->post(route('ic.admin.konten.privasi.bawaan'))->assertRedirect(route('login'));
        $this->assertSame(0, ic_pengaturan::query()->count());
    }

    public function test_deteksi_kosong(): void
    {
        $this->assertTrue(IcPrivasi::kosong(null));
        $this->assertTrue(IcPrivasi::kosong('<p><br></p>'));
        $this->assertTrue(IcPrivasi::kosong("<p>&nbsp; \u{00A0}</p>"));
        $this->assertFalse(IcPrivasi::kosong('<p>Isi</p>'));
    }
}