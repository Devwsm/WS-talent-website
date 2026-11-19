<?php

namespace Tests\Feature\InnerCircle\Hadiah;

use App\Models\ic_claim;
use App\Models\ic_claim_log;
use App\Models\ic_reward;
use App\Models\member;
use App\Services\InnerCircle\ClaimService;
use App\Services\InnerCircle\PoinService;
use Database\Seeders\icRewardSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Hadiah dan klaim: aturan klaim, alur status admin, dan edit tahapan.
 */
class HadiahDanKlaimTest extends TestCase
{
    use RefreshDatabase;
    // ---------- hadiah ----------
    // ---------- klaim oleh anggota ----------
    // ---------- admin: antrean klaim ----------
    // ---------- admin: tahapan hadiah ----------

    private function anggota(int $poin = 0, string $email = 'a@contoh.test'): member
    {
        $m = member::create(['nama' => 'Anggota Uji', 'email' => $email, 'password' => 'rahasia123']);
        $m->kode_member = sprintf('IC-%05d', $m->id_member);
        $m->email_verified_at = now(); // klaim hadiah butuh email terverifikasi (lihat InnerCircleVerifikasiEmailTest)
        $m->save();
        if ($poin > 0) {
            app(PoinService::class)->tambahManual($m, $poin, 'Uji', 'admin');
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
        $reward = ic_reward::where('harga_poin', $target)->firstOrFail();
        $k = ic_claim::create(['member_id' => $m->id_member, 'reward_id' => $reward->id_reward, 'status' => $status]);
        ic_claim_log::create(['claim_id' => $k->id_claim, 'status' => 'diajukan']);

        return $k;
    }

    public function test_migration_mengisi_lima_tahapan_dan_seeder_tidak_menimpa_edit(): void
    {
        $this->assertSame([10000, 25000, 50000, 100000, 250000], ic_reward::orderBy('urutan')->pluck('harga_poin')->all());
        $this->assertSame(['Panggilan video', 'Ucapan video khusus'], ic_reward::where('harga_poin', 50000)->first()->opsi);

        ic_reward::where('harga_poin', 10000)->update(['judul' => 'Judul Baru']);
        (new icRewardSeeder())->run();

        $this->assertSame(5, ic_reward::count());
        $this->assertSame('Judul Baru', ic_reward::where('harga_poin', 10000)->value('judul'));
    }

    public function test_halaman_hadiah_menampilkan_status_sesuai_poin(): void
    {
        $m = $this->anggota(12000);
        $hadiah = app(ClaimService::class)->hadiahUntuk($m)->pluck('status_member', 'harga_poin')->all();
        $this->assertSame([10000 => 'terbuka', 25000 => 'terkunci', 50000 => 'terkunci', 100000 => 'terkunci', 250000 => 'terkunci'], $hadiah);

        $this->actingAs($m, 'member')->get(route('ic.hadiah'))->assertOk()->assertSee('Hadiah Pertama');

        $berikutnya = app(ClaimService::class)->berikutnya(12000);
        $this->assertSame(25000, $berikutnya->harga_poin);
        $this->assertSame(13000, $berikutnya->sisa);
        $this->assertSame(250000, app(ClaimService::class)->berikutnya(300000)->harga_poin);
    }

    public function test_klaim_berhasil_mencatat_pengajuan_dan_tidak_mengurangi_poin(): void
    {
        $m = $this->anggota(12000);

        $this->ajukan($m, 10000)->assertRedirect(route('ic.hadiah') . '#klaim')->assertSessionHas('ic_toast');

        $k = ic_claim::first();
        $this->assertSame('diajukan', $k->status);
        $this->assertSame($m->id_member, $k->member_id);
        $this->assertNull($k->opsi_dipilih);
        $this->assertSame(['diajukan'], $k->logs->pluck('status')->all());
        $this->assertSame(12000, $m->fresh()->poin_total);

        $hadiah = app(ClaimService::class)->hadiahUntuk($m->fresh())->pluck('status_member', 'harga_poin')->all();
        $this->assertSame('diajukan', $hadiah[10000]);
    }

    public function test_klaim_ditolak_bila_poin_belum_cukup(): void
    {
        $m = $this->anggota(9000);

        $this->ajukan($m, 10000)->assertSessionHas('ic_toast_galat');
        $this->assertSame(0, ic_claim::count());
    }

    public function test_klaim_dobel_ditolak_dan_hanya_satu_pengajuan_tersimpan(): void
    {
        $m = $this->anggota(30000);

        $this->ajukan($m, 10000)->assertSessionHas('ic_toast');
        $this->ajukan($m, 10000)->assertSessionHas('ic_toast_galat')->assertSessionMissing('ic_toast');

        $this->assertSame(1, ic_claim::count());
        $this->assertSame(1, ic_claim_log::count());

        // Tahap lain yang sudah terbuka tetap bisa diajukan.
        $this->ajukan($m, 25000)->assertSessionHas('ic_toast');
        $this->assertSame(2, ic_claim::count());
    }

    public function test_tahap_dengan_pilihan_wajib_memilih_opsi_yang_valid(): void
    {
        $m = $this->anggota(60000);

        $this->ajukan($m, 50000)->assertSessionHas('ic_toast_galat');
        $this->ajukan($m, 50000, ['opsi' => 'Hadiah ngawur'])->assertSessionHas('ic_toast_galat');
        $this->assertSame(0, ic_claim::count());

        $this->ajukan($m, 50000, ['opsi' => 'Ucapan video khusus'])->assertSessionHas('ic_toast');
        $this->assertSame('Ucapan video khusus', ic_claim::first()->opsi_dipilih);
    }

    public function test_tahap_tanpa_pilihan_mengabaikan_opsi_kiriman(): void
    {
        $m = $this->anggota(10000);

        $this->ajukan($m, 10000, ['opsi' => 'apa saja'])->assertSessionHas('ic_toast');
        $this->assertNull(ic_claim::first()->opsi_dipilih);
    }

    public function test_tahap_tidak_dikenal_dan_wajib_login(): void
    {
        $m = $this->anggota(500000);

        $this->ajukan($m, 999)->assertSessionHas('ic_toast_galat');
        $this->assertSame(0, ic_claim::count());

        // Keluar dari guard anggota dulu: instance tes ini masih login dari langkah di atas.
        $this->app['auth']->guard('member')->logout();
        $this->post(route('ic.hadiah.klaim', 10000))->assertRedirect(route('ic.masuk'));
    }

    public function test_limiter_klaim_per_anggota(): void
    {
        $m = $this->anggota(500000);
        config(['inner_circle.batas_klaim_per_menit' => 3]);

        foreach (range(1, 3) as $i) {
            $this->ajukan($m, 999);
        }
        $this->ajukan($m, 10000)->assertRedirect(route('ic.hadiah'))
            ->assertSessionHas('ic_toast_galat', 'Terlalu banyak percobaan. Tunggu sebentar lalu coba lagi.');
        $this->assertSame(0, ic_claim::count());
    }

    public function test_profil_menampilkan_pengajuan_catatan_dan_jumlahnya(): void
    {
        $m = $this->anggota(30000);
        $k = $this->klaim($m, 10000, 'selesai');
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
        $a = $this->anggota(30000, 'a@contoh.test');
        $b = $this->anggota(30000, 'b@contoh.test');
        $this->klaim($a, 10000)->update(['catatan_admin' => 'Rahasia anggota A']);

        $this->actingAs($b, 'member')->get(route('ic.profil'))->assertOk()->assertDontSee('Rahasia anggota A');
        $this->assertSame(0, app(ClaimService::class)->klaimAnggota($b)->count());
    }

    public function test_admin_tab_klaim_dan_ringkasan_memakai_data_asli(): void
    {
        $m = $this->anggota(30000);
        $this->klaim($m, 10000);
        $this->klaim($m, 25000, 'ditolak');
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
        $m = $this->anggota(30000);
        $k = $this->klaim($m, 10000);
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
        $this->assertSame(30000, $m->fresh()->poin_total); // poin tidak berkurang

        // Selesai adalah akhir alur.
        $admin->patch($url, ['aksi' => 'lanjut', 'dari' => 'selesai'])->assertSessionHas('error');
        $admin->patch($url, ['aksi' => 'tolak', 'dari' => 'selesai', 'catatan' => 'x'])->assertSessionHas('error');
        $this->assertSame('selesai', $k->fresh()->status);
        $this->assertCount(4, $k->fresh()->logs);
    }

    public function test_klik_ganda_atau_layar_basi_tidak_melompatkan_status(): void
    {
        $k = $this->klaim($this->anggota(30000), 10000);
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
        $m = $this->anggota(30000);
        $k = $this->klaim($m, 10000);
        $admin = $this->admin();
        $url = route('ic.admin.klaim.proses', $k->id_claim);

        $admin->patch($url, ['aksi' => 'tolak', 'dari' => 'diajukan', 'catatan' => '  '])->assertSessionHas('error');
        $this->assertSame('diajukan', $k->fresh()->status);

        $admin->patch($url, ['aksi' => 'tolak', 'dari' => 'diajukan', 'catatan' => 'Data belum cocok'])->assertSessionHas('success');
        $this->assertSame('ditolak', $k->fresh()->status);

        $this->actingAs($m, 'member')->get(route('ic.profil'))->assertSee('Data belum cocok')->assertSee('Ditolak');

        // Ditolak itu final: anggota tidak bisa mengajukan ulang tahap yang sama.
        $this->ajukan($m, 10000)->assertSessionHas('ic_toast_galat');
        $this->assertSame(1, ic_claim::count());
    }

    public function test_catatan_saja_mengubah_catatan_tanpa_mengubah_status(): void
    {
        $k = $this->klaim($this->anggota(30000), 10000, 'selesai');
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
        $k = $this->klaim($this->anggota(30000), 10000);
        $url = route('ic.admin.klaim.proses', $k->id_claim);

        $this->patch($url, ['aksi' => 'lanjut', 'dari' => 'diajukan'])->assertRedirect(route('login'));

        $admin = $this->admin();
        $admin->patch($url, ['aksi' => 'hapus', 'dari' => 'diajukan'])->assertSessionHasErrors('aksi');
        $admin->patch($url, ['aksi' => 'lanjut', 'dari' => 'sembarang'])->assertSessionHasErrors('dari');
        $admin->patch($url, ['aksi' => 'catatan', 'dari' => 'diajukan', 'catatan' => str_repeat('x', 161)])->assertSessionHasErrors('catatan');
        $admin->patch(route('ic.admin.klaim.proses', 99999), ['aksi' => 'lanjut', 'dari' => 'diajukan'])->assertNotFound();
        $this->assertSame('diajukan', $k->fresh()->status);
    }

    public function test_admin_tab_hadiah_tampil_dan_membuka_tahap_dari_url(): void
    {
        $admin = $this->admin();

        $admin->get(route('ic.admin.hadiah'))->assertOk()->assertSee('Hadiah Pertama');
        $admin->get(route('ic.admin.hadiah', ['tahap' => 50000]))->assertOk()
            ->assertSee(route('ic.admin.hadiah.ubah', 50000), false);
        $admin->get(route('ic.admin.hadiah', ['tahap' => 'ngawur']))->assertOk();
    }

    public function test_admin_mengubah_tahapan_dan_target_tetap(): void
    {
        $admin = $this->admin();

        $admin->put(route('ic.admin.hadiah.ubah', 10000), [
            'judul' => 'Hadiah Satu',
            'deskripsi' => 'Deskripsi baru',
            'ikon' => 'cd',
            'warna' => 'hijau',
            'opsi' => '',
            'harga_poin' => 999, // tidak boleh ikut berubah
        ])->assertRedirect(route('ic.admin.hadiah', ['tahap' => 10000]))->assertSessionHas('success');

        $r = ic_reward::where('harga_poin', 10000)->first();
        $this->assertSame(['Hadiah Satu', 'Deskripsi baru', 'cd', 'hijau', null], [$r->judul, $r->deskripsi, $r->ikon, $r->warna, $r->opsi]);
        $this->assertSame(0, ic_reward::where('harga_poin', 999)->count());

        // Dua pilihan, satu per baris (CRLF dari browser ikut dibersihkan).
        $admin->put(route('ic.admin.hadiah.ubah', 25000), [
            'judul' => 'Akses Eksklusif',
            'deskripsi' => 'X',
            'ikon' => 'cap',
            'warna' => 'kuning',
            'opsi' => "Pilihan A\r\n Pilihan B \r\n",
        ])->assertSessionHas('success');
        $this->assertSame(['Pilihan A', 'Pilihan B'], ic_reward::where('harga_poin', 25000)->first()->opsi);

        // Pilihan dikosongkan: kembali tanpa pilihan.
        $admin->put(route('ic.admin.hadiah.ubah', 25000), [
            'judul' => 'Akses Eksklusif',
            'deskripsi' => 'X',
            'ikon' => 'cap',
            'warna' => 'kuning',
            'opsi' => '',
        ])->assertSessionHas('success');
        $this->assertNull(ic_reward::where('harga_poin', 25000)->first()->opsi);
    }

    public function test_validasi_tahapan_hadiah_dan_redirect_ke_tahap_yang_diedit(): void
    {
        $admin = $this->admin();
        $dasar = ['judul' => 'Ok', 'deskripsi' => 'Ok', 'ikon' => 'gift', 'warna' => 'koral', 'opsi' => ''];

        $admin->put(route('ic.admin.hadiah.ubah', 50000), ['opsi' => 'Satu saja'] + $dasar)
            ->assertRedirect(route('ic.admin.hadiah', ['tahap' => 50000]))->assertSessionHasErrors('opsi');
        $admin->put(route('ic.admin.hadiah.ubah', 50000), ['opsi' => "Sama\nSama"] + $dasar)->assertSessionHasErrors('opsi.1');
        $admin->put(route('ic.admin.hadiah.ubah', 50000), ['opsi' => "A\nB\nC"] + $dasar)->assertSessionHasErrors('opsi');
        $admin->put(route('ic.admin.hadiah.ubah', 10000), ['judul' => ''] + $dasar)->assertSessionHasErrors('judul');
        $admin->put(route('ic.admin.hadiah.ubah', 10000), ['judul' => str_repeat('x', 41)] + $dasar)->assertSessionHasErrors('judul');
        $admin->put(route('ic.admin.hadiah.ubah', 10000), ['ikon' => 'lock'] + $dasar)->assertSessionHasErrors('ikon');
        $admin->put(route('ic.admin.hadiah.ubah', 10000), ['warna' => 'ungu'] + $dasar)->assertSessionHasErrors('warna');
        $admin->put(route('ic.admin.hadiah.ubah', 777), $dasar)->assertNotFound();

        $this->assertSame('Interaksi Personal', ic_reward::where('harga_poin', 50000)->value('judul'));
    }

    public function test_isian_yang_gagal_validasi_tampil_lagi_di_tahap_yang_sama(): void
    {
        $admin = $this->admin();

        $admin->from(route('ic.admin.hadiah', ['tahap' => 50000]))
            ->put(route('ic.admin.hadiah.ubah', 50000), [
                'judul' => 'Judul Yang Diketik',
                'deskripsi' => 'D',
                'ikon' => 'gift',
                'warna' => 'koral',
                'opsi' => 'Satu saja',
            ]);

        $admin->get(route('ic.admin.hadiah', ['tahap' => 50000]))->assertOk()->assertSee('Judul Yang Diketik');
    }

    public function test_perubahan_tahapan_tidak_mengubah_pengajuan_yang_sudah_masuk(): void
    {
        $m = $this->anggota(60000);
        $this->ajukan($m, 50000, ['opsi' => 'Panggilan video']);

        $this->admin()->put(route('ic.admin.hadiah.ubah', 50000), [
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