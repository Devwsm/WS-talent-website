<?php

namespace Tests\Feature\InnerCircle\Admin;

use App\Models\ic_claim;
use App\Models\ic_event;
use App\Models\ic_reward;
use App\Models\ic_poin;
use App\Models\log_aktivitas;
use App\Models\member;
use App\Services\InnerCircle\AcaraService;
use App\Services\InnerCircle\PoinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** CMS Inner Circle > Anggota: detail, kurangi poin (koreksi), nonaktifkan dan aktifkan akun. */
class KelolaAnggotaTest extends TestCase
{
    use RefreshDatabase;

    private function admin()
    {
        return $this->withSession(['login' => true, 'user' => 'admin-uji']);
    }

    private function anggota(string $email = 'a@contoh.test', string $nama = 'Anggota Uji'): member
    {
        $m = member::create(['nama' => $nama, 'email' => $email, 'password' => 'rahasia123']);
        $m->kode_member = sprintf('IC-%05d', $m->id_member);
        $m->email_verified_at = now();
        $m->save();

        return $m;
    }

    private function acara(): ic_event
    {
        return app(AcaraService::class)->buat(['tanggal' => now()->toDateString(), 'tipe' => 'club', 'venue' => 'Superhouse', 'kota' => 'Jakarta']);
    }

    private function beri(member $m, int $jumlah): void
    {
        app(PoinService::class)->tambahManual($m, $jumlah, 'Uji', 'admin-uji');
    }


    /** Teks halaman tanpa tag, spasi dan baris baru dirapatkan: aman dari auto-formatter yang membungkus baris HTML. */
    private function teks($respons): string
    {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($respons->getContent()), ENT_QUOTES | ENT_HTML5)));
    }

    // ---------- kurangi poin ----------

    public function test_kurangi_menulis_baris_negatif_dan_menurunkan_total_tanpa_menyentuh_baris_lama(): void
    {
        $m = $this->anggota();
        $this->beri($m, 5);
        $lama = ic_poin::query()->first();

        $this->admin()->post(route('ic.admin.anggota.kurangi', $m->id_member), ['jumlah' => 2, 'catatan' => 'Salah input'])
            ->assertRedirect()->assertSessionHas('success');

        $this->assertSame(3, $m->fresh()->poin_total);
        $koreksi = ic_poin::query()->where('sumber', 'koreksi')->firstOrFail();
        $this->assertSame(-2, $koreksi->jumlah);
        $this->assertSame('Salah input', $koreksi->catatan);
        $this->assertSame('admin-uji', $koreksi->dicatat_oleh);
        $this->assertSame(5, $lama->fresh()->jumlah, 'Baris lama tidak boleh berubah.');
        // Total = jumlah seluruh buku besar.
        $this->assertSame(3, (int) ic_poin::query()->where('member_id', $m->id_member)->sum('jumlah'));
    }

    public function test_kurangi_tidak_boleh_melebihi_total_atau_tanpa_alasan(): void
    {
        $m = $this->anggota();
        $this->beri($m, 2);

        $this->admin()->post(route('ic.admin.anggota.kurangi', $m->id_member), ['jumlah' => 3, 'catatan' => 'x'])->assertSessionHasErrors('jumlah');
        $this->admin()->post(route('ic.admin.anggota.kurangi', $m->id_member), ['jumlah' => 0, 'catatan' => 'x'])->assertSessionHasErrors('jumlah');
        $this->admin()->post(route('ic.admin.anggota.kurangi', $m->id_member), ['jumlah' => 1, 'catatan' => ''])->assertSessionHasErrors('catatan');

        $this->assertSame(2, $m->fresh()->poin_total);
        $this->assertSame(0, ic_poin::query()->where('sumber', 'koreksi')->count());
    }

    public function test_kurangi_mengingatkan_bila_ada_klaim_yang_butuh_poin_lebih_banyak(): void
    {
        $m = $this->anggota();
        $this->beri($m, 5);
        $hadiah = ic_reward::create(['harga_poin' => 5, 'judul' => 'Kaos', 'deskripsi' => 'x', 'ikon' => 'gift', 'warna' => 'merah', 'opsi' => null, 'urutan' => 1, 'aktif' => true]);
        ic_claim::create(['member_id' => $m->id_member, 'reward_id' => $hadiah->id_reward, 'status' => 'diajukan']);

        $this->admin()->post(route('ic.admin.anggota.kurangi', $m->id_member), ['jumlah' => 1, 'catatan' => 'Koreksi'])
            ->assertSessionHas('success', fn($p) => str_contains($p, '1 klaim hadiah'));
    }

    public function test_koreksi_tampil_negatif_di_riwayat_anggota_dan_di_detail_admin(): void
    {
        $m = $this->anggota();
        $this->beri($m, 3);
        app(PoinService::class)->kurangi($m, 2, 'Salah input', 'admin-uji');

        $detail = $this->admin()->get(route('ic.admin.anggota.detail', $m->id_member))->assertOk();
        $this->assertStringContainsString('Koreksi poin oleh tim WSM', $this->teks($detail));
        $this->assertStringContainsString('Catatan: Salah input', $this->teks($detail));
        $this->assertStringContainsString('−2', $this->teks($detail));

        $profil = $this->actingAs($m->fresh(), 'member')->get(route('ic.profil'))->assertOk()->assertSee('Koreksi poin oleh tim WSM');
        // Spasi dan baris baru di HTML bisa berubah bila Blade diformat ulang editor, jadi dicocokkan longgar.
        $this->assertMatchesRegularExpression('/−2\s+✦/u', $profil->getContent());
    }

    // ---------- nonaktifkan / aktifkan ----------

    public function test_nonaktifkan_menyimpan_alasan_menaikkan_versi_sesi_dan_tercatat(): void
    {
        $m = $this->anggota();
        $versi = $m->sesi_versi;

        $this->admin()->patch(route('ic.admin.anggota.nonaktifkan', $m->id_member), ['alasan' => 'Akun ganda'])->assertRedirect()->assertSessionHas('success');

        $m->refresh();
        $this->assertTrue($m->diblokir());
        $this->assertSame('Akun ganda', $m->alasan_blokir);
        $this->assertSame('admin-uji', $m->diblokir_oleh);
        $this->assertSame($versi + 1, $m->sesi_versi);

        $log = log_aktivitas::query()->where('rute', 'ic.admin.anggota.nonaktifkan')->first();
        $this->assertNotNull($log);
        $this->assertSame('Akun anggota dinonaktifkan', $log->aksi);
        $this->assertStringContainsString('Anggota Uji', (string) $log->keterangan);
    }

    public function test_nonaktifkan_wajib_beralasan_dan_hanya_untuk_admin(): void
    {
        $m = $this->anggota();

        $this->admin()->patch(route('ic.admin.anggota.nonaktifkan', $m->id_member), ['alasan' => ''])->assertSessionHasErrors('alasan');
        $this->assertFalse($m->fresh()->diblokir());
    }

    public function test_tindakan_kelola_anggota_butuh_login_admin(): void
    {
        $m = $this->anggota();

        $this->patch(route('ic.admin.anggota.nonaktifkan', $m->id_member), ['alasan' => 'x'])->assertRedirect(route('login'));
        $this->post(route('ic.admin.anggota.kurangi', $m->id_member), ['jumlah' => 1, 'catatan' => 'x'])->assertRedirect(route('login'));
        $this->get(route('ic.admin.anggota.detail', $m->id_member))->assertRedirect(route('login'));
        $this->assertFalse($m->fresh()->diblokir());
    }

    public function test_akun_nonaktif_tidak_bisa_masuk_tapi_pesan_salah_sandi_tetap_umum(): void
    {
        $m = $this->anggota();
        $this->admin()->patch(route('ic.admin.anggota.nonaktifkan', $m->id_member), ['alasan' => 'Akun ganda']);

        // Sandi benar: ditolak dengan pesan khusus.
        $this->post(route('ic.masuk.proses'), ['email' => 'a@contoh.test', 'sandi' => 'rahasia123'])
            ->assertSessionHasErrors(['email' => 'Akun ini dinonaktifkan. Hubungi tim WSM bila ini keliru.']);
        $this->assertGuest('member');

        // Sandi salah: pesan umum yang sama seperti biasa (tidak membocorkan status akun).
        $this->post(route('ic.masuk.proses'), ['email' => 'a@contoh.test', 'sandi' => 'salah-total'])
            ->assertSessionHasErrors(['email' => 'Email atau kata sandi salah.']);
    }

    public function test_sesi_yang_sedang_berjalan_berakhir_pada_permintaan_berikutnya(): void
    {
        $m = $this->anggota();
        $this->post(route('ic.masuk.proses'), ['email' => 'a@contoh.test', 'sandi' => 'rahasia123'])->assertRedirect();
        $this->get(route('ic.profil'))->assertOk();

        $this->admin()->patch(route('ic.admin.anggota.nonaktifkan', $m->id_member), ['alasan' => 'Penyalahgunaan']);

        // Tiap permintaan di production memuat anggota dari database lagi; dalam tes, guard menyimpan model lama.
        $this->app['auth']->forgetGuards();

        $this->get(route('ic.profil'))->assertRedirect(route('ic.masuk'))->assertSessionHasErrors('email');
        $this->assertGuest('member');
    }

    public function test_aktifkan_kembali_mengembalikan_akses_dan_data_tidak_hilang(): void
    {
        $m = $this->anggota();
        $this->beri($m, 4);
        $this->admin()->patch(route('ic.admin.anggota.nonaktifkan', $m->id_member), ['alasan' => 'Uji']);

        $this->admin()->patch(route('ic.admin.anggota.aktifkan', $m->id_member))->assertRedirect()->assertSessionHas('success');

        $m->refresh();
        $this->assertFalse($m->diblokir());
        $this->assertNull($m->alasan_blokir);
        $this->assertSame(4, $m->poin_total);
        $this->post(route('ic.masuk.proses'), ['email' => 'a@contoh.test', 'sandi' => 'rahasia123'])->assertRedirect();
        $this->assertAuthenticatedAs($m, 'member');
    }

    public function test_daftar_anggota_menandai_dan_menyaring_akun_nonaktif(): void
    {
        $aktif = $this->anggota('aktif@contoh.test', 'Si Aktif');
        $mati = $this->anggota('mati@contoh.test', 'Si Mati');
        $this->admin()->patch(route('ic.admin.anggota.nonaktifkan', $mati->id_member), ['alasan' => 'Uji']);

        $this->admin()->get(route('ic.admin.anggota'))->assertOk()->assertSee('Dinonaktifkan')->assertSee(route('ic.admin.anggota.detail', $mati->id_member), false);
        $this->admin()->get(route('ic.admin.anggota', ['akun' => 'nonaktif']))->assertSee('Si Mati')->assertDontSee('Si Aktif');
        $this->admin()->get(route('ic.admin.anggota', ['akun' => 'aktif']))->assertSee('Si Aktif')->assertDontSee('Si Mati');
        $this->admin()->get(route('ic.admin.anggota', ['akun' => 'ngawur']))->assertOk()->assertSee('Si Aktif')->assertSee('Si Mati');
    }

    public function test_detail_anggota_menampilkan_riwayat_klaim_dan_404_bila_tidak_ada(): void
    {
        $m = $this->anggota();
        $e = $this->acara();
        app(PoinService::class)->tambah($m, 1, 'checkin', 'Check-in Superhouse', 'pin', $e, jarakM: 20);
        $hadiah = ic_reward::create(['harga_poin' => 1, 'judul' => 'Stiker Eksklusif', 'deskripsi' => 'x', 'ikon' => 'gift', 'warna' => 'merah', 'opsi' => null, 'urutan' => 1, 'aktif' => true]);
        ic_claim::create(['member_id' => $m->id_member, 'reward_id' => $hadiah->id_reward, 'status' => 'diajukan']);

        $this->admin()->get(route('ic.admin.anggota.detail', $m->id_member))
            ->assertOk()->assertSee('Check-in Superhouse')->assertSee('Superhouse, Jakarta')->assertSee('Stiker Eksklusif')->assertSee('Nonaktifkan akun');

        $this->admin()->get(route('ic.admin.anggota.detail', 99999))->assertNotFound();
    }
}