<?php

namespace Tests\Feature\InnerCircle\Pengaduan;

use App\Mail\InnerCircle\StatusPengaduanMail;
use App\Models\ic_event;
use App\Models\ic_pengaduan;
use App\Models\member;
use App\Services\InnerCircle\AcaraService;
use App\Services\InnerCircle\EmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Pengaduan anggota (Fase 9): kirim dari area anggota, batas harian, tindak lanjut admin di detail anggota,
 * pintasan di Ringkasan, email status, dan ikut terhapus bersama akun.
 */
class PengaduanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Pembatas laju per menit tidak ikut diuji di tes lain (satu tes banyak mengirim); tes khususnya menurunkannya lagi.
        config(['inner_circle.pengaduan.batas_per_menit' => 100]);
    }

    private function anggota(string $email = 'a@contoh.test', string $nama = 'Anggota Uji'): member
    {
        $m = member::create(['nama' => $nama, 'email' => $email, 'password' => 'rahasia123']);
        $m->kode_member = sprintf('IC-%05d', $m->id_member);
        $m->save();

        return $m->fresh();
    }

    private function admin()
    {
        return $this->withSession(['login' => true, 'user' => 'admin']);
    }

    private function acara(): ic_event
    {
        return app(AcaraService::class)->buat([
            'tanggal' => now()->addDays(3)->toDateString(),
            'tipe' => 'club',
            'venue' => 'Venue Pengaduan Uji',
            'kota' => 'Bandung',
        ]);
    }

    private function kirim(member $m, array $data = [])
    {
        return $this->actingAs($m, 'member')->post(route('ic.pengaduan.kirim'), $data + [
            'kategori' => 'checkin',
            'isi' => 'Poin check-in saya belum masuk padahal sudah scan QR.',
        ]);
    }

    public function test_halaman_pengaduan_butuh_login_dan_menampilkan_form(): void
    {
        $this->get(route('ic.pengaduan'))->assertRedirect(route('ic.masuk'));

        $m = $this->anggota();
        $this->acara();
        $this->actingAs($m, 'member')->get(route('ic.pengaduan'))->assertOk()
            ->assertSee('Kirim pengaduan')->assertSee('Check-in dan poin')->assertSee('Venue Pengaduan Uji')
            ->assertSee('Belum ada pengaduan.')->assertSee('Sisa pengaduan hari ini: 3');
    }

    public function test_profil_menaut_ke_halaman_pengaduan(): void
    {
        $this->actingAs($this->anggota(), 'member')->get(route('ic.profil'))->assertOk()
            ->assertSee(route('ic.pengaduan'), false)->assertSee('Ada kendala atau masukan?');
    }

    public function test_anggota_mengirim_pengaduan_dan_melihatnya_di_daftar(): void
    {
        $m = $this->anggota();
        $acara = $this->acara();

        $this->kirim($m, ['event_id' => $acara->id_event, 'kategori' => 'gl'])
            ->assertRedirect(route('ic.pengaduan'))->assertSessionHas('ic_toast');

        $p = ic_pengaduan::first();
        $this->assertSame([$m->id_member, 'gl', $acara->id_event, 'baru'], [$p->member_id, $p->kategori, $p->event_id, $p->status]);

        $this->actingAs($m, 'member')->get(route('ic.pengaduan'))->assertOk()
            ->assertSee('GL gratis dan undian')->assertSee('Poin check-in saya belum masuk')->assertSee('Baru')
            ->assertSee('Sisa pengaduan hari ini: 2');
    }

    public function test_acara_boleh_kosong_dan_isi_ditrim(): void
    {
        $m = $this->anggota();

        $this->kirim($m, ['event_id' => '', 'isi' => "   Tidak bisa masuk akun saya.  \n"])->assertSessionHas('ic_toast');

        $p = ic_pengaduan::first();
        $this->assertNull($p->event_id);
        $this->assertSame('Tidak bisa masuk akun saya.', $p->isi);
    }

    public function test_validasi_kategori_isi_dan_acara(): void
    {
        $m = $this->anggota();

        $this->kirim($m, ['kategori' => 'ngawur'])->assertSessionHasErrors('kategori');
        $this->kirim($m, ['kategori' => ''])->assertSessionHasErrors('kategori');
        $this->kirim($m, ['isi' => 'pendek'])->assertSessionHasErrors('isi');
        $this->kirim($m, ['isi' => str_repeat('x', 1001)])->assertSessionHasErrors('isi');
        $this->kirim($m, ['event_id' => 'abc'])->assertSessionHasErrors('event_id');
        // Acara yang tidak ada ditolak aturan bisnis (pesan lewat toast).
        $this->kirim($m, ['event_id' => 99999])->assertSessionHas('ic_toast_galat');

        $this->assertSame(0, ic_pengaduan::count());

        // Tepat di batas: 1.000 karakter lolos.
        $this->kirim($m, ['isi' => str_repeat('x', 1000)])->assertSessionHas('ic_toast');
        $this->assertSame(1, ic_pengaduan::count());
    }

    public function test_batas_tiga_pengaduan_per_hari_dan_pulih_keesokan_harinya(): void
    {
        $m = $this->anggota();

        foreach (range(1, 3) as $i) {
            $this->kirim($m)->assertSessionHas('ic_toast');
        }
        $this->kirim($m)->assertSessionHas('ic_toast_galat')->assertSessionMissing('ic_toast');
        $this->assertSame(3, ic_pengaduan::count());
        $this->actingAs($m, 'member')->get(route('ic.pengaduan'))->assertSee('Batas pengaduan hari ini sudah tercapai');

        // Satu hari kemudian jatahnya pulih.
        ic_pengaduan::query()->update(['created_at' => now()->subHours(25)]);
        $this->kirim($m)->assertSessionHas('ic_toast');
        $this->assertSame(4, ic_pengaduan::count());
    }

    public function test_pembatas_laju_per_menit(): void
    {
        $m = $this->anggota();
        config(['inner_circle.pengaduan.batas_per_menit' => 2, 'inner_circle.pengaduan.batas_per_hari' => 50]);

        $this->kirim($m);
        $this->kirim($m);
        $this->kirim($m)->assertRedirect(route('ic.pengaduan'))
            ->assertSessionHas('ic_toast_galat', 'Terlalu banyak percobaan. Tunggu sebentar lalu coba lagi.');
        $this->assertSame(2, ic_pengaduan::count());
    }

    public function test_pengaduan_anggota_lain_tidak_bocor(): void
    {
        $a = $this->anggota('a@contoh.test');
        $b = $this->anggota('b@contoh.test', 'Anggota Lain');
        $this->kirim($a, ['isi' => 'Rahasia pengaduan anggota A.']);

        $this->actingAs($b, 'member')->get(route('ic.pengaduan'))->assertOk()->assertDontSee('Rahasia pengaduan anggota A.');
    }

    public function test_isi_pengaduan_di_halaman_tidak_menjalankan_html(): void
    {
        $m = $this->anggota();
        $this->kirim($m, ['isi' => '<script>alert(1)</script> halo tim WSM']);

        $this->actingAs($m, 'member')->get(route('ic.pengaduan'))->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
    }

    // ---------- admin ----------

    public function test_admin_melihat_dan_menindaklanjuti_pengaduan_di_detail_anggota(): void
    {
        Mail::fake();
        $m = $this->anggota();
        $this->kirim($m);
        $p = ic_pengaduan::first();

        $this->admin()->get(route('ic.admin.anggota.detail', $m->id_member))->assertOk()
            ->assertSee('Pengaduan')->assertSee('Poin check-in saya belum masuk')
            ->assertSee(route('ic.admin.pengaduan.proses', $p->id_pengaduan), false);

        $url = route('ic.admin.pengaduan.proses', $p->id_pengaduan);

        $this->admin()->patch($url, ['status' => 'diproses', 'dari' => 'baru', 'catatan' => 'Sedang kami cek'])->assertSessionHas('success');
        $p->refresh();
        $this->assertSame(['diproses', 'Sedang kami cek', 'admin'], [$p->status, $p->catatan_admin, $p->diproses_oleh]);
        Mail::assertSent(StatusPengaduanMail::class, fn($mail) => $mail->hasTo('a@contoh.test'));

        // Catatan saja tidak mengirim email baru.
        $this->admin()->patch($url, ['status' => 'diproses', 'dari' => 'diproses', 'catatan' => 'Masih dicek, mohon sabar'])->assertSessionHas('success');
        Mail::assertSent(StatusPengaduanMail::class, 1);

        $this->admin()->patch($url, ['status' => 'selesai', 'dari' => 'diproses', 'catatan' => ''])->assertSessionHas('success');
        $this->assertSame('selesai', $p->fresh()->status);
        $this->assertNull($p->fresh()->catatan_admin);
        Mail::assertSent(StatusPengaduanMail::class, 2);

        // Anggota melihat statusnya.
        $this->actingAs($m, 'member')->get(route('ic.pengaduan'))->assertSee('Selesai');
    }

    public function test_catatan_admin_tampil_ke_anggota(): void
    {
        Mail::fake();
        $m = $this->anggota();
        $this->kirim($m);
        $p = ic_pengaduan::first();

        $this->admin()->patch(route('ic.admin.pengaduan.proses', $p->id_pengaduan), ['status' => 'diproses', 'dari' => 'baru', 'catatan' => 'Poinmu sudah kami tambahkan']);

        $this->actingAs($m, 'member')->get(route('ic.pengaduan'))->assertOk()
            ->assertSee('Catatan WSM:')->assertSee('Poinmu sudah kami tambahkan')->assertSee('Diproses');
    }

    public function test_klik_ganda_atau_layar_basi_ditolak(): void
    {
        Mail::fake();
        $this->kirim($this->anggota());
        $p = ic_pengaduan::first();
        $url = route('ic.admin.pengaduan.proses', $p->id_pengaduan);

        $this->admin()->patch($url, ['status' => 'diproses', 'dari' => 'baru'])->assertSessionHas('success');
        $this->admin()->patch($url, ['status' => 'diproses', 'dari' => 'baru'])->assertSessionHas('error');

        $this->assertSame('diproses', $p->fresh()->status);
        Mail::assertSent(StatusPengaduanMail::class, 1);
    }

    public function test_proses_pengaduan_menolak_masukan_ngawur_dan_butuh_admin(): void
    {
        $this->kirim($this->anggota());
        $p = ic_pengaduan::first();
        $url = route('ic.admin.pengaduan.proses', $p->id_pengaduan);

        $this->patch($url, ['status' => 'selesai', 'dari' => 'baru'])->assertRedirect(route('login'));

        $admin = $this->admin();
        $admin->patch($url, ['status' => 'hapus', 'dari' => 'baru'])->assertSessionHasErrors('status');
        $admin->patch($url, ['status' => 'selesai', 'dari' => 'sembarang'])->assertSessionHasErrors('dari');
        $admin->patch($url, ['status' => 'selesai', 'dari' => 'baru', 'catatan' => str_repeat('x', 301)])->assertSessionHasErrors('catatan');
        $admin->patch(route('ic.admin.pengaduan.proses', 99999), ['status' => 'selesai', 'dari' => 'baru'])->assertNotFound();

        $this->assertSame('baru', $p->fresh()->status);
    }

    public function test_ringkasan_menampilkan_angka_dan_pintasan_ke_anggota_dengan_pengaduan_terbuka(): void
    {
        Mail::fake();
        $a = $this->anggota('a@contoh.test', 'Anggota Mengadu');
        $b = $this->anggota('b@contoh.test', 'Anggota Tenang');
        $c = $this->anggota('c@contoh.test', 'Anggota Selesai');
        $this->kirim($a);
        $this->kirim($c);
        ic_pengaduan::where('member_id', $c->id_member)->update(['status' => 'selesai']);

        $this->admin()->get(route('ic.admin.ringkasan'))->assertOk()
            ->assertSee('Pengaduan terbuka')->assertSee(route('ic.admin.anggota', ['pengaduan' => 'terbuka']), false);

        $this->admin()->get(route('ic.admin.anggota', ['pengaduan' => 'terbuka']))->assertOk()
            ->assertSee('Anggota Mengadu')->assertDontSee('Anggota Tenang')->assertDontSee('Anggota Selesai')
            ->assertSee('Menampilkan anggota yang punya pengaduan belum selesai');
        $this->admin()->get(route('ic.admin.anggota'))->assertOk()
            ->assertSee('Anggota Mengadu')->assertSee('Anggota Tenang')->assertSee('Anggota Selesai');
    }

    public function test_pengaduan_ikut_terhapus_bersama_akun(): void
    {
        $m = $this->anggota();
        $this->kirim($m);
        $this->assertSame(1, ic_pengaduan::count());

        $m->delete();

        $this->assertSame(0, ic_pengaduan::count());
    }

    public function test_pengaduan_tetap_ada_bila_acara_terkaitnya_dihapus(): void
    {
        $m = $this->anggota();
        $acara = $this->acara();
        $this->kirim($m, ['event_id' => $acara->id_event]);

        $acara->delete();

        $p = ic_pengaduan::first();
        $this->assertNotNull($p);
        $this->assertNull($p->event_id);
    }

    public function test_email_contoh_pengaduan_tampil_di_tab_email_dan_memakai_teks_dari_cms(): void
    {
        $this->assertArrayHasKey('pengaduan', EmailService::JENIS);
        $this->assertSame('Pengaduanmu: Selesai', EmailService::contoh('pengaduan')->envelope()->subject);
        $this->assertStringContainsString('Sudah kami periksa', EmailService::contoh('pengaduan')->render());

        $this->admin()->get(route('ic.admin.email', ['jenis' => 'pengaduan']))->assertOk()->assertSee('Status pengaduan berubah');
        $this->admin()->get(route('ic.admin.email.pratinjau', 'pengaduan'))->assertOk()->assertSee('WS Inner Circle');
    }
}