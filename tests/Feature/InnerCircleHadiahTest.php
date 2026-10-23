<?php

namespace Tests\Feature;

use App\Models\ic_claim;
use App\Models\ic_claim_log;
use App\Models\ic_reward;
use App\Models\member;
use App\Services\InnerCircle\ClaimService;
use App\Services\InnerCircle\StempelService;
use Database\Seeders\icRewardSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** WS Inner Circle Fase 5: hadiah dan klaim (aturan klaim, alur status admin, edit tahapan). */
class InnerCircleHadiahTest extends TestCase
{
    use RefreshDatabase;

    private function anggota(int $stempel = 0, string $email = 'a@contoh.test'): member
    {
        $m = member::create(['nama' => 'Anggota Uji', 'email' => $email, 'password' => 'rahasia123']);
        $m->kode_member = sprintf('IC-%05d', $m->id_member);
        $m->save();
        if ($stempel > 0) {
            app(StempelService::class)->tambahManual($m, $stempel, 'Uji', 'admin');
        }

        return $m->fresh();
    }

    private function ajukan(member $m, int $target, array $data = [])
    {
        return $this->actingAs($m, 'member')->post(route('ic.hadiah.klaim', $target), $data);
    }

    private function admin()
    {
        return $this->withSession(['login' => true, 'user' => 'admin']);
    }

    private function klaim(member $m, int $target, string $status = 'diajukan'): ic_claim
    {
        $reward = ic_reward::where('target_stempel', $target)->firstOrFail();
        $k = ic_claim::create(['member_id' => $m->id_member, 'reward_id' => $reward->id_reward, 'status' => $status]);
        ic_claim_log::create(['claim_id' => $k->id_claim, 'status' => 'diajukan']);

        return $k;
    }

    // ---------- hadiah ----------

    public function test_migration_mengisi_lima_tahapan_dan_seeder_tidak_menimpa_edit(): void
    {
        $this->assertSame([10, 25, 50, 100, 250], ic_reward::orderBy('urutan')->pluck('target_stempel')->all());
        $this->assertSame(['Panggilan video', 'Ucapan video khusus'], ic_reward::where('target_stempel', 50)->first()->opsi);

        ic_reward::where('target_stempel', 10)->update(['judul' => 'Judul Baru']);
        (new icRewardSeeder())->run();

        $this->assertSame(5, ic_reward::count());
        $this->assertSame('Judul Baru', ic_reward::where('target_stempel', 10)->value('judul'));
    }

    public function test_halaman_hadiah_menampilkan_status_sesuai_stempel(): void
    {
        $m = $this->anggota(12);
        $hadiah = app(ClaimService::class)->hadiahUntuk($m)->pluck('status_member', 'target_stempel')->all();
        $this->assertSame([10 => 'terbuka', 25 => 'terkunci', 50 => 'terkunci', 100 => 'terkunci', 250 => 'terkunci'], $hadiah);

        $this->actingAs($m, 'member')->get(route('ic.hadiah'))->assertOk()->assertSee('Hadiah Pertama');

        $berikutnya = app(ClaimService::class)->berikutnya(12);
        $this->assertSame(25, $berikutnya->target_stempel);
        $this->assertSame(13, $berikutnya->sisa);
        $this->assertSame(250, app(ClaimService::class)->berikutnya(300)->target_stempel);
    }

    // ---------- klaim oleh anggota ----------

    public function test_klaim_berhasil_mencatat_pengajuan_dan_tidak_mengurangi_stempel(): void
    {
        $m = $this->anggota(12);

        $this->ajukan($m, 10)->assertRedirect(route('ic.hadiah') . '#klaim')->assertSessionHas('ic_toast');

        $k = ic_claim::first();
        $this->assertSame('diajukan', $k->status);
        $this->assertSame($m->id_member, $k->member_id);
        $this->assertNull($k->opsi_dipilih);
        $this->assertSame(['diajukan'], $k->logs->pluck('status')->all());
        $this->assertSame(12, $m->fresh()->stempel_total);

        $hadiah = app(ClaimService::class)->hadiahUntuk($m->fresh())->pluck('status_member', 'target_stempel')->all();
        $this->assertSame('diajukan', $hadiah[10]);
    }

    public function test_klaim_ditolak_bila_stempel_belum_cukup(): void
    {
        $m = $this->anggota(9);

        $this->ajukan($m, 10)->assertSessionHas('ic_toast_galat');
        $this->assertSame(0, ic_claim::count());
    }

    public function test_klaim_dobel_ditolak_dan_hanya_satu_pengajuan_tersimpan(): void
    {
        $m = $this->anggota(30);

        $this->ajukan($m, 10)->assertSessionHas('ic_toast');
        $this->ajukan($m, 10)->assertSessionHas('ic_toast_galat')->assertSessionMissing('ic_toast');

        $this->assertSame(1, ic_claim::count());
        $this->assertSame(1, ic_claim_log::count());

        // Tahap lain yang sudah terbuka tetap bisa diajukan.
        $this->ajukan($m, 25)->assertSessionHas('ic_toast');
        $this->assertSame(2, ic_claim::count());
    }

    public function test_tahap_dengan_pilihan_wajib_memilih_opsi_yang_valid(): void
    {
        $m = $this->anggota(60);

        $this->ajukan($m, 50)->assertSessionHas('ic_toast_galat');
        $this->ajukan($m, 50, ['opsi' => 'Hadiah ngawur'])->assertSessionHas('ic_toast_galat');
        $this->assertSame(0, ic_claim::count());

        $this->ajukan($m, 50, ['opsi' => 'Ucapan video khusus'])->assertSessionHas('ic_toast');
        $this->assertSame('Ucapan video khusus', ic_claim::first()->opsi_dipilih);
    }

    public function test_tahap_tanpa_pilihan_mengabaikan_opsi_kiriman(): void
    {
        $m = $this->anggota(10);

        $this->ajukan($m, 10, ['opsi' => 'apa saja'])->assertSessionHas('ic_toast');
        $this->assertNull(ic_claim::first()->opsi_dipilih);
    }

    public function test_tahap_tidak_dikenal_dan_wajib_login(): void
    {
        $m = $this->anggota(500);

        $this->ajukan($m, 999)->assertSessionHas('ic_toast_galat');
        $this->assertSame(0, ic_claim::count());

        // Keluar dari guard anggota dulu: instance tes ini masih login dari langkah di atas.
        $this->app['auth']->guard('member')->logout();
        $this->post(route('ic.hadiah.klaim', 10))->assertRedirect(route('ic.masuk'));
    }

    public function test_limiter_klaim_per_anggota(): void
    {
        $m = $this->anggota(500);
        config(['inner_circle.batas_klaim_per_menit' => 3]);

        foreach (range(1, 3) as $i) {
            $this->ajukan($m, 999);
        }
        $this->ajukan($m, 10)->assertRedirect(route('ic.hadiah'))
            ->assertSessionHas('ic_toast_galat', 'Terlalu banyak percobaan. Tunggu sebentar lalu coba lagi.');
        $this->assertSame(0, ic_claim::count());
    }

    public function test_profil_menampilkan_pengajuan_catatan_dan_jumlahnya(): void
    {
        $m = $this->anggota(30);
        $k = $this->klaim($m, 10, 'selesai');
        $k->update(['catatan_admin' => 'Kode voucher: WS-DISKON-10']);

        $this->actingAs($m, 'member')->get(route('ic.profil'))
            ->assertOk()
            ->assertSee('Hadiah Pertama')
            ->assertSee('Kode voucher: WS-DISKON-10')
            ->assertSee('Selesai');

        $this->actingAs($m, 'member')->get(route('ic.hadiah'))->assertOk()->assertSee('Kode voucher: WS-DISKON-10');
    }

    public function test_pengajuan_anggota_lain_tidak_bocor_ke_profil(): void
    {
        $a = $this->anggota(30, 'a@contoh.test');
        $b = $this->anggota(30, 'b@contoh.test');
        $this->klaim($a, 10)->update(['catatan_admin' => 'Rahasia anggota A']);

        $this->actingAs($b, 'member')->get(route('ic.profil'))->assertOk()->assertDontSee('Rahasia anggota A');
        $this->assertSame(0, app(ClaimService::class)->klaimAnggota($b)->count());
    }

    // ---------- admin: antrean klaim ----------

    public function test_admin_tab_klaim_dan_ringkasan_memakai_data_asli(): void
    {
        $m = $this->anggota(30);
        $this->klaim($m, 10);
        $this->klaim($m, 25, 'ditolak');
        $admin = $this->admin();

        $admin->get(route('ic.admin.klaim'))->assertOk()->assertSee('Anggota Uji')->assertSee('Hadiah Pertama')
            ->assertDontSee('Akses Eksklusif'); // 25 ditolak: tidak di antrean "menunggu"
        $admin->get(route('ic.admin.klaim', ['status' => 'ditolak']))->assertOk()->assertSee('Akses Eksklusif');
        $admin->get(route('ic.admin.klaim', ['status' => 'semua']))->assertOk()->assertSee('Akses Eksklusif')->assertSee('Hadiah Pertama');
        $admin->get(route('ic.admin.klaim', ['status' => ['x']]))->assertOk(); // parameter aneh jatuh ke bawaan

        $admin->get(route('ic.admin.ringkasan'))->assertOk()->assertSee('Anggota Uji');
    }

    public function test_alur_status_lengkap_dan_tercatat_di_log(): void
    {
        $m = $this->anggota(30);
        $k = $this->klaim($m, 10);
        $admin = $this->admin();
        $url = route('ic.admin.klaim.proses', $k->id_claim);

        $admin->patch($url, ['aksi' => 'lanjut', 'dari' => 'diajukan'])->assertSessionHas('success');
        $this->assertSame('ditinjau', $k->fresh()->status);

        $admin->patch($url, ['aksi' => 'lanjut', 'dari' => 'ditinjau', 'catatan' => 'Kode: WS-123'])->assertSessionHas('success');
        $this->assertSame('disetujui', $k->fresh()->status);
        $this->assertSame('Kode: WS-123', $k->fresh()->catatan_admin);

        // Lanjut tanpa catatan tidak menghapus catatan yang sudah ada.
        $admin->patch($url, ['aksi' => 'lanjut', 'dari' => 'disetujui'])->assertSessionHas('success');
        $akhir = $k->fresh();
        $this->assertSame('selesai', $akhir->status);
        $this->assertSame('Kode: WS-123', $akhir->catatan_admin);
        $this->assertSame('admin', $akhir->diproses_oleh);

        $this->assertSame(['diajukan', 'ditinjau', 'disetujui', 'selesai'], $akhir->logs->pluck('status')->all());
        $this->assertSame([null, 'admin', 'admin', 'admin'], $akhir->logs->pluck('oleh')->all());
        $this->assertSame(30, $m->fresh()->stempel_total); // stempel tidak berkurang

        // Selesai adalah akhir alur.
        $admin->patch($url, ['aksi' => 'lanjut', 'dari' => 'selesai'])->assertSessionHas('error');
        $admin->patch($url, ['aksi' => 'tolak', 'dari' => 'selesai', 'catatan' => 'x'])->assertSessionHas('error');
        $this->assertSame('selesai', $k->fresh()->status);
        $this->assertCount(4, $k->fresh()->logs);
    }

    public function test_klik_ganda_atau_layar_basi_tidak_melompatkan_status(): void
    {
        $k = $this->klaim($this->anggota(30), 10);
        $admin = $this->admin();
        $url = route('ic.admin.klaim.proses', $k->id_claim);

        $admin->patch($url, ['aksi' => 'lanjut', 'dari' => 'diajukan'])->assertSessionHas('success');
        // Permintaan kedua masih membawa status lama di layarnya.
        $admin->patch($url, ['aksi' => 'lanjut', 'dari' => 'diajukan'])->assertSessionHas('error');

        $this->assertSame('ditinjau', $k->fresh()->status);
        $this->assertCount(2, $k->fresh()->logs);
    }

    public function test_tolak_wajib_alasan_dan_tampil_di_profil_anggota(): void
    {
        $m = $this->anggota(30);
        $k = $this->klaim($m, 10);
        $admin = $this->admin();
        $url = route('ic.admin.klaim.proses', $k->id_claim);

        $admin->patch($url, ['aksi' => 'tolak', 'dari' => 'diajukan', 'catatan' => '  '])->assertSessionHas('error');
        $this->assertSame('diajukan', $k->fresh()->status);

        $admin->patch($url, ['aksi' => 'tolak', 'dari' => 'diajukan', 'catatan' => 'Data belum cocok'])->assertSessionHas('success');
        $this->assertSame('ditolak', $k->fresh()->status);

        $this->actingAs($m, 'member')->get(route('ic.profil'))->assertSee('Data belum cocok')->assertSee('Ditolak');

        // Ditolak itu final: anggota tidak bisa mengajukan ulang tahap yang sama.
        $this->ajukan($m, 10)->assertSessionHas('ic_toast_galat');
        $this->assertSame(1, ic_claim::count());
    }

    public function test_catatan_saja_mengubah_catatan_tanpa_mengubah_status(): void
    {
        $k = $this->klaim($this->anggota(30), 10, 'selesai');
        $admin = $this->admin();
        $url = route('ic.admin.klaim.proses', $k->id_claim);

        $admin->patch($url, ['aksi' => 'catatan', 'dari' => 'selesai', 'catatan' => 'Voucher dikirim'])->assertSessionHas('success');
        $this->assertSame('selesai', $k->fresh()->status);
        $this->assertSame('Voucher dikirim', $k->fresh()->catatan_admin);

        $admin->patch($url, ['aksi' => 'catatan', 'dari' => 'selesai', 'catatan' => ''])->assertSessionHas('success');
        $this->assertNull($k->fresh()->catatan_admin);

        // Tidak ada perubahan: tidak menambah baris log.
        $jumlahLog = $k->fresh()->logs->count();
        $admin->patch($url, ['aksi' => 'catatan', 'dari' => 'selesai', 'catatan' => ''])->assertSessionHas('success');
        $this->assertCount($jumlahLog, $k->fresh()->logs);
    }

    public function test_proses_klaim_menolak_masukan_ngawur_dan_butuh_login_admin(): void
    {
        $k = $this->klaim($this->anggota(30), 10);
        $url = route('ic.admin.klaim.proses', $k->id_claim);

        $this->patch($url, ['aksi' => 'lanjut', 'dari' => 'diajukan'])->assertRedirect(route('login'));

        $admin = $this->admin();
        $admin->patch($url, ['aksi' => 'hapus', 'dari' => 'diajukan'])->assertSessionHasErrors('aksi');
        $admin->patch($url, ['aksi' => 'lanjut', 'dari' => 'sembarang'])->assertSessionHasErrors('dari');
        $admin->patch($url, ['aksi' => 'catatan', 'dari' => 'diajukan', 'catatan' => str_repeat('x', 161)])->assertSessionHasErrors('catatan');
        $admin->patch(route('ic.admin.klaim.proses', 99999), ['aksi' => 'lanjut', 'dari' => 'diajukan'])->assertNotFound();
        $this->assertSame('diajukan', $k->fresh()->status);
    }

    // ---------- admin: tahapan hadiah ----------

    public function test_admin_tab_hadiah_tampil_dan_membuka_tahap_dari_url(): void
    {
        $admin = $this->admin();

        $admin->get(route('ic.admin.hadiah'))->assertOk()->assertSee('Hadiah Pertama');
        $admin->get(route('ic.admin.hadiah', ['tahap' => 50]))->assertOk()
            ->assertSee(route('ic.admin.hadiah.ubah', 50), false);
        $admin->get(route('ic.admin.hadiah', ['tahap' => 'ngawur']))->assertOk();
    }

    public function test_admin_mengubah_tahapan_dan_target_tetap(): void
    {
        $admin = $this->admin();

        $admin->put(route('ic.admin.hadiah.ubah', 10), [
            'judul' => 'Hadiah Satu',
            'deskripsi' => 'Deskripsi baru',
            'ikon' => 'cd',
            'warna' => 'hijau',
            'opsi' => '',
            'target_stempel' => 999, // tidak boleh ikut berubah
        ])->assertRedirect(route('ic.admin.hadiah', ['tahap' => 10]))->assertSessionHas('success');

        $r = ic_reward::where('target_stempel', 10)->first();
        $this->assertSame(['Hadiah Satu', 'Deskripsi baru', 'cd', 'hijau', null], [$r->judul, $r->deskripsi, $r->ikon, $r->warna, $r->opsi]);
        $this->assertSame(0, ic_reward::where('target_stempel', 999)->count());

        // Dua pilihan, satu per baris (CRLF dari browser ikut dibersihkan).
        $admin->put(route('ic.admin.hadiah.ubah', 25), [
            'judul' => 'Akses Eksklusif',
            'deskripsi' => 'X',
            'ikon' => 'cap',
            'warna' => 'kuning',
            'opsi' => "Pilihan A\r\n Pilihan B \r\n",
        ])->assertSessionHas('success');
        $this->assertSame(['Pilihan A', 'Pilihan B'], ic_reward::where('target_stempel', 25)->first()->opsi);

        // Pilihan dikosongkan: kembali tanpa pilihan.
        $admin->put(route('ic.admin.hadiah.ubah', 25), [
            'judul' => 'Akses Eksklusif',
            'deskripsi' => 'X',
            'ikon' => 'cap',
            'warna' => 'kuning',
            'opsi' => '',
        ])->assertSessionHas('success');
        $this->assertNull(ic_reward::where('target_stempel', 25)->first()->opsi);
    }

    public function test_validasi_tahapan_hadiah_dan_redirect_ke_tahap_yang_diedit(): void
    {
        $admin = $this->admin();
        $dasar = ['judul' => 'Ok', 'deskripsi' => 'Ok', 'ikon' => 'gift', 'warna' => 'koral', 'opsi' => ''];

        $admin->put(route('ic.admin.hadiah.ubah', 50), ['opsi' => 'Satu saja'] + $dasar)
            ->assertRedirect(route('ic.admin.hadiah', ['tahap' => 50]))->assertSessionHasErrors('opsi');
        $admin->put(route('ic.admin.hadiah.ubah', 50), ['opsi' => "Sama\nSama"] + $dasar)->assertSessionHasErrors('opsi.1');
        $admin->put(route('ic.admin.hadiah.ubah', 50), ['opsi' => "A\nB\nC"] + $dasar)->assertSessionHasErrors('opsi');
        $admin->put(route('ic.admin.hadiah.ubah', 10), ['judul' => ''] + $dasar)->assertSessionHasErrors('judul');
        $admin->put(route('ic.admin.hadiah.ubah', 10), ['judul' => str_repeat('x', 41)] + $dasar)->assertSessionHasErrors('judul');
        $admin->put(route('ic.admin.hadiah.ubah', 10), ['ikon' => 'lock'] + $dasar)->assertSessionHasErrors('ikon');
        $admin->put(route('ic.admin.hadiah.ubah', 10), ['warna' => 'ungu'] + $dasar)->assertSessionHasErrors('warna');
        $admin->put(route('ic.admin.hadiah.ubah', 777), $dasar)->assertNotFound();

        $this->assertSame('Interaksi Personal', ic_reward::where('target_stempel', 50)->value('judul'));
    }

    public function test_isian_yang_gagal_validasi_tampil_lagi_di_tahap_yang_sama(): void
    {
        $admin = $this->admin();

        $admin->from(route('ic.admin.hadiah', ['tahap' => 50]))
            ->put(route('ic.admin.hadiah.ubah', 50), [
                'judul' => 'Judul Yang Diketik',
                'deskripsi' => 'D',
                'ikon' => 'gift',
                'warna' => 'koral',
                'opsi' => 'Satu saja',
            ]);

        $admin->get(route('ic.admin.hadiah', ['tahap' => 50]))->assertOk()->assertSee('Judul Yang Diketik');
    }

    public function test_perubahan_tahapan_tidak_mengubah_pengajuan_yang_sudah_masuk(): void
    {
        $m = $this->anggota(60);
        $this->ajukan($m, 50, ['opsi' => 'Panggilan video']);

        $this->admin()->put(route('ic.admin.hadiah.ubah', 50), [
            'judul' => 'Interaksi Baru',
            'deskripsi' => 'D',
            'ikon' => 'love-letter',
            'warna' => 'biru',
            'opsi' => "Pilihan Baru 1\nPilihan Baru 2",
        ])->assertSessionHas('success');

        $this->assertSame('Panggilan video', ic_claim::first()->opsi_dipilih);
        $this->actingAs($m, 'member')->get(route('ic.profil'))->assertSee('Interaksi Baru')->assertSee('Panggilan video');
    }
}