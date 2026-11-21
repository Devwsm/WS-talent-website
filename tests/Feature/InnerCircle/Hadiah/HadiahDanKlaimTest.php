<?php

namespace Tests\Feature\InnerCircle\Hadiah;

use App\Models\ic_claim;
use App\Models\ic_claim_log;
use App\Models\ic_poin;
use App\Models\ic_reward;
use App\Models\ic_reward_tunggu;
use App\Models\member;
use App\Services\InnerCircle\ClaimService;
use App\Services\InnerCircle\PoinService;
use Database\Seeders\icRewardSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Hadiah dan penukaran poin: katalog, poin ditahan dan dikembalikan, stok, daftar tunggu, alur status admin, dan CMS katalog.
 * Katalog baru dari migrasi: Meet & Greet 150.000 poin dan Merchandise 250.000 poin. Lima tahap lama (10.000 sampai 250.000)
 * tidak aktif dan dipakai di sini sebagai "klaim lama" yang tidak pernah memotong poin.
 */
class HadiahDanKlaimTest extends TestCase
{
    use RefreshDatabase;

    private function anggota(int $poin = 0, string $email = 'a@contoh.test'): member
    {
        $m = member::create(['nama' => 'Anggota Uji', 'email' => $email, 'password' => 'rahasia123']);
        $m->kode_member = sprintf('IC-%05d', $m->id_member);
        $m->email_verified_at = now(); // penukaran hadiah butuh email terverifikasi (lihat VerifikasiEmailTest)
        $m->save();
        if ($poin > 0) {
            app(PoinService::class)->tambahManual($m, $poin, 'Uji', 'admin');
        }

        return $m->fresh();
    }

    private function idHadiah(string $judul): int
    {
        return (int) ic_reward::where('judul', $judul)->value('id_reward');
    }

    private function tukar(member $m, int $idHadiah, array $data = [])
    {
        return $this->actingAs($m, 'member')->post(route('ic.hadiah.tukar', $idHadiah), $data);
    }

    private function admin()
    {
        return $this->withSession(['login' => true, 'user' => 'admin']);
    }

    /** Klaim lama (tahap 10.000 dst): dibuat langsung, poin_ditahan 0. */
    private function klaimLama(member $m, int $harga, string $status = 'diajukan'): ic_claim
    {
        $reward = ic_reward::where('harga_poin', $harga)->where('urutan', '>', 10)->firstOrFail();
        $k = ic_claim::create(['member_id' => $m->id_member, 'reward_id' => $reward->id_reward, 'status' => $status]);
        ic_claim_log::create(['claim_id' => $k->id_claim, 'status' => 'diajukan']);

        return $k;
    }

    // ---------- katalog ----------

    public function test_migrasi_mengisi_katalog_baru_dan_menonaktifkan_tahap_lama(): void
    {
        $aktif = ic_reward::where('aktif', true)->orderBy('urutan')->get();
        $this->assertSame(['Meet & Greet', 'Merchandise'], $aktif->pluck('judul')->all());
        $this->assertSame([150000, 250000], $aktif->pluck('harga_poin')->all());
        $this->assertSame([null, null], $aktif->pluck('stok')->all());

        $lama = ic_reward::where('aktif', false)->orderBy('harga_poin')->pluck('harga_poin')->all();
        $this->assertSame([10000, 25000, 50000, 100000, 250000], $lama);
        $this->assertSame(['Panggilan video', 'Ucapan video khusus'], ic_reward::where('harga_poin', 50000)->first()->opsi);

        // Seeder aman dijalankan ulang: tidak menimpa edit dan tidak menggandakan.
        ic_reward::where('harga_poin', 10000)->update(['judul' => 'Judul Baru']);
        (new icRewardSeeder())->run();
        $this->assertSame(7, ic_reward::count());
        $this->assertSame('Judul Baru', ic_reward::where('harga_poin', 10000)->value('judul'));
    }

    public function test_halaman_hadiah_menampilkan_status_sesuai_saldo(): void
    {
        $m = $this->anggota(160000);
        $hadiah = app(ClaimService::class)->hadiahUntuk($m)->pluck('status_member', 'harga_poin')->all();
        $this->assertSame([150000 => 'terbuka', 250000 => 'terkunci'], $hadiah);

        $this->actingAs($m, 'member')->get(route('ic.hadiah'))->assertOk()
            ->assertSee('Meet & Greet')->assertSee('Merchandise')->assertSee('Saldo poin')
            ->assertSee('kurang')->assertDontSee('Hadiah Pertama');

        $berikutnya = app(ClaimService::class)->berikutnya(160000);
        $this->assertSame(250000, $berikutnya->harga_poin);
        $this->assertSame(90000, $berikutnya->sisa);
        $akhir = app(ClaimService::class)->berikutnya(300000);
        $this->assertSame([250000, 0], [$akhir->harga_poin, $akhir->sisa]);
    }

    // ---------- tukar oleh anggota ----------

    public function test_tukar_menahan_poin_dan_mencatat_buku_besar(): void
    {
        $m = $this->anggota(160000);
        $id = $this->idHadiah('Meet & Greet');

        $this->tukar($m, $id)->assertRedirect(route('ic.hadiah') . '#klaim')->assertSessionHas('ic_toast');

        $k = ic_claim::first();
        $this->assertSame(['diajukan', 150000, $id], [$k->status, $k->poin_ditahan, $k->reward_id]);
        $this->assertNull($k->opsi_dipilih);
        $this->assertSame(['diajukan'], $k->logs->pluck('status')->all());
        $this->assertSame(10000, $m->fresh()->poin_total);

        $baris = ic_poin::where('claim_id', $k->id_claim)->get();
        $this->assertSame(['tukar'], $baris->pluck('sumber')->all());
        $this->assertSame([-150000], $baris->pluck('jumlah')->all());
        // Saldo cache sama dengan jumlah buku besar.
        $this->assertSame(10000, (int) ic_poin::where('member_id', $m->id_member)->sum('jumlah'));

        $dompet = app(ClaimService::class)->dompet($m->fresh());
        $this->assertSame([10000, 150000, 0], [$dompet->saldo, $dompet->tertahan, $dompet->dipakai]);

        $this->actingAs($m->fresh(), 'member')->get(route('ic.hadiah'))->assertOk()->assertSee('Penukaran saya')
            ->assertSee('150.000 poin ditahan')->assertSee('Batalkan');
    }

    public function test_tukar_ditolak_bila_poin_belum_cukup(): void
    {
        $m = $this->anggota(100000);

        $this->tukar($m, $this->idHadiah('Meet & Greet'))->assertSessionHas('ic_toast_galat');
        $this->assertSame(0, ic_claim::count());
        $this->assertSame(100000, $m->fresh()->poin_total);
    }

    public function test_satu_penukaran_berjalan_per_hadiah_tapi_hadiah_lain_tetap_bisa(): void
    {
        $m = $this->anggota(500000);
        $mg = $this->idHadiah('Meet & Greet');

        $this->tukar($m, $mg)->assertSessionHas('ic_toast');
        $this->tukar($m, $mg)->assertSessionHas('ic_toast_galat')->assertSessionMissing('ic_toast');
        $this->assertSame(1, ic_claim::count());
        $this->assertSame(1, ic_claim_log::count());

        $this->tukar($m, $this->idHadiah('Merchandise'))->assertSessionHas('ic_toast');
        $this->assertSame(2, ic_claim::count());
        $this->assertSame(100000, $m->fresh()->poin_total);
    }

    public function test_hadiah_boleh_ditukar_berulang_setelah_penukaran_sebelumnya_selesai(): void
    {
        $m = $this->anggota(400000);
        $mg = $this->idHadiah('Meet & Greet');

        $this->tukar($m, $mg)->assertSessionHas('ic_toast');
        ic_claim::query()->update(['status' => 'selesai']);

        $this->tukar($m, $mg)->assertSessionHas('ic_toast');
        $this->assertSame(2, ic_claim::where('reward_id', $mg)->count());
        $this->assertSame(100000, $m->fresh()->poin_total);

        $dompet = app(ClaimService::class)->dompet($m->fresh());
        $this->assertSame([150000, 150000], [$dompet->tertahan, $dompet->dipakai]);
    }

    public function test_hadiah_dengan_pilihan_wajib_memilih_opsi_yang_valid(): void
    {
        $m = $this->anggota(300000);
        $id = $this->idHadiah('Merchandise');
        ic_reward::whereKey($id)->update(['opsi' => json_encode(['Kaos', 'Topi'])]);

        $this->tukar($m, $id)->assertSessionHas('ic_toast_galat');
        $this->tukar($m, $id, ['opsi' => 'Barang ngawur'])->assertSessionHas('ic_toast_galat');
        $this->assertSame(0, ic_claim::count());
        $this->assertSame(300000, $m->fresh()->poin_total);

        $this->tukar($m, $id, ['opsi' => 'Topi'])->assertSessionHas('ic_toast');
        $this->assertSame('Topi', ic_claim::first()->opsi_dipilih);
    }

    public function test_hadiah_tanpa_pilihan_mengabaikan_opsi_kiriman(): void
    {
        $m = $this->anggota(150000);

        $this->tukar($m, $this->idHadiah('Meet & Greet'), ['opsi' => 'apa saja'])->assertSessionHas('ic_toast');
        $this->assertNull(ic_claim::first()->opsi_dipilih);
    }

    public function test_hadiah_tidak_dikenal_dan_wajib_login(): void
    {
        $m = $this->anggota(500000);

        $this->tukar($m, 9999)->assertSessionHas('ic_toast_galat');
        $this->assertSame(0, ic_claim::count());

        // Keluar dari guard anggota dulu: instance tes ini masih login dari langkah di atas.
        $this->app['auth']->guard('member')->logout();
        $this->post(route('ic.hadiah.tukar', $this->idHadiah('Meet & Greet')))->assertRedirect(route('ic.masuk'));
    }

    public function test_limiter_penukaran_per_anggota(): void
    {
        $m = $this->anggota(500000);
        config(['inner_circle.batas_klaim_per_menit' => 3]);

        foreach (range(1, 3) as $i) {
            $this->tukar($m, 9999);
        }
        $this->tukar($m, $this->idHadiah('Meet & Greet'))->assertRedirect(route('ic.hadiah'))
            ->assertSessionHas('ic_toast_galat', 'Terlalu banyak percobaan. Tunggu sebentar lalu coba lagi.');
        $this->assertSame(0, ic_claim::count());
    }

    // ---------- batal oleh anggota ----------

    public function test_anggota_membatalkan_penukaran_poin_dan_stok_kembali(): void
    {
        $m = $this->anggota(160000);
        $id = $this->idHadiah('Meet & Greet');
        ic_reward::whereKey($id)->update(['stok' => 2]);

        $this->tukar($m, $id);
        $k = ic_claim::first();
        $this->assertSame(1, ic_reward::find($id)->stok);
        $this->assertSame(10000, $m->fresh()->poin_total);

        $this->actingAs($m->fresh(), 'member')->delete(route('ic.penukaran.batal', $k->id_claim))
            ->assertRedirect(route('ic.hadiah') . '#klaim')->assertSessionHas('ic_toast');

        $this->assertSame('dibatalkan', $k->fresh()->status);
        $this->assertSame(160000, $m->fresh()->poin_total);
        $this->assertSame(2, ic_reward::find($id)->stok);
        $this->assertSame(['tukar', 'kembali'], ic_poin::where('claim_id', $k->id_claim)->orderBy('id_poin')->pluck('sumber')->all());
        $this->assertSame(['diajukan', 'dibatalkan'], $k->fresh()->logs->pluck('status')->all());
        $this->assertSame(160000, (int) ic_poin::where('member_id', $m->id_member)->sum('jumlah'));

        // Batal kedua tidak mengembalikan poin lagi.
        $this->actingAs($m->fresh(), 'member')->delete(route('ic.penukaran.batal', $k->id_claim))->assertSessionHas('ic_toast_galat');
        $this->assertSame(160000, $m->fresh()->poin_total);
        $this->assertSame(1, ic_poin::where('claim_id', $k->id_claim)->where('sumber', 'kembali')->count());
    }

    public function test_batal_ditolak_setelah_diperiksa_admin_atau_milik_orang_lain(): void
    {
        $a = $this->anggota(160000, 'a@contoh.test');
        $b = $this->anggota(160000, 'b@contoh.test');
        $this->tukar($a, $this->idHadiah('Meet & Greet'));
        $k = ic_claim::first();

        $this->actingAs($b, 'member')->delete(route('ic.penukaran.batal', $k->id_claim))->assertSessionHas('ic_toast_galat');
        $this->assertSame('diajukan', $k->fresh()->status);

        $k->update(['status' => 'ditinjau']);
        $this->actingAs($a->fresh(), 'member')->delete(route('ic.penukaran.batal', $k->id_claim))->assertSessionHas('ic_toast_galat');
        $this->assertSame('ditinjau', $k->fresh()->status);
        $this->assertSame(10000, $a->fresh()->poin_total);
    }

    // ---------- stok dan daftar tunggu ----------

    public function test_stok_habis_menolak_penukaran_dan_membuka_daftar_tunggu(): void
    {
        $m = $this->anggota(300000);
        $id = $this->idHadiah('Meet & Greet');
        ic_reward::whereKey($id)->update(['stok' => 0]);

        $this->tukar($m, $id)->assertSessionHas('ic_toast_galat');
        $this->assertSame(0, ic_claim::count());
        $this->actingAs($m, 'member')->get(route('ic.hadiah'))->assertOk()->assertSee('Stok habis')->assertSee('Masuk daftar tunggu');

        $this->actingAs($m, 'member')->post(route('ic.hadiah.tunggu', $id))->assertSessionHas('ic_toast');
        $this->actingAs($m, 'member')->post(route('ic.hadiah.tunggu', $id))->assertSessionHas('ic_toast'); // klik ganda aman
        $this->assertSame(1, ic_reward_tunggu::count());
        $this->actingAs($m, 'member')->get(route('ic.hadiah'))->assertSee(route('ic.hadiah.tunggu.keluar', $id), false); // tombol "Keluar daftar tunggu" (teksnya bisa terpotong baris oleh formatter)

        // Stok tersedia lagi: hadiah bertanda, lalu ditukar dan daftar tunggunya hilang.
        ic_reward::whereKey($id)->update(['stok' => 1]);
        $this->actingAs($m, 'member')->get(route('ic.hadiah'))->assertSee('Stok tersedia lagi');
        $this->tukar($m, $id)->assertSessionHas('ic_toast');
        $this->assertSame(0, ic_reward_tunggu::count());
        $this->assertSame(0, ic_reward::find($id)->stok);
    }

    public function test_daftar_tunggu_hanya_untuk_hadiah_yang_stoknya_habis_dan_bisa_keluar(): void
    {
        $m = $this->anggota(0);
        $id = $this->idHadiah('Merchandise');

        $this->actingAs($m, 'member')->post(route('ic.hadiah.tunggu', $id))->assertSessionHas('ic_toast_galat');
        $this->assertSame(0, ic_reward_tunggu::count());

        ic_reward::whereKey($id)->update(['stok' => 0]);
        $this->actingAs($m, 'member')->post(route('ic.hadiah.tunggu', $id));
        $this->assertSame(1, ic_reward_tunggu::count());

        $this->actingAs($m, 'member')->delete(route('ic.hadiah.tunggu.keluar', $id))->assertSessionHas('ic_toast');
        $this->assertSame(0, ic_reward_tunggu::count());
    }

    // ---------- admin: proses penukaran ----------

    public function test_admin_menolak_penukaran_mengembalikan_poin_dan_stok_sekali_saja(): void
    {
        Mail::fake();
        $m = $this->anggota(160000);
        $id = $this->idHadiah('Meet & Greet');
        ic_reward::whereKey($id)->update(['stok' => 1]);
        $this->tukar($m, $id);
        $k = ic_claim::first();
        $url = route('ic.admin.klaim.proses', $k->id_claim);

        $this->admin()->patch($url, ['aksi' => 'tolak', 'dari' => 'diajukan', 'catatan' => ' '])->assertSessionHas('error');
        $this->assertSame(10000, $m->fresh()->poin_total);

        $this->admin()->patch($url, ['aksi' => 'tolak', 'dari' => 'diajukan', 'catatan' => 'Stok habis'])->assertSessionHas('success');
        $this->assertSame('ditolak', $k->fresh()->status);
        $this->assertSame(160000, $m->fresh()->poin_total);
        $this->assertSame(1, ic_reward::find($id)->stok);
        $this->assertSame(['tukar', 'kembali'], ic_poin::where('claim_id', $k->id_claim)->orderBy('id_poin')->pluck('sumber')->all());

        // Klik ganda dengan layar basi: ditolak, tidak ada pengembalian kedua.
        $this->admin()->patch($url, ['aksi' => 'tolak', 'dari' => 'diajukan', 'catatan' => 'Stok habis'])->assertSessionHas('error');
        $this->assertSame(160000, $m->fresh()->poin_total);

        // Penukaran yang sudah mengembalikan poin tidak bisa dibuka kembali.
        $this->admin()->patch($url, ['aksi' => 'buka_kembali', 'dari' => 'ditolak'])->assertSessionHas('error');
        $this->assertSame('ditolak', $k->fresh()->status);

        $this->actingAs($m->fresh(), 'member')->get(route('ic.profil'))->assertSee('Stok habis')->assertSee('Ditolak');
        Mail::assertSent(\App\Mail\InnerCircle\StatusKlaimMail::class, 1);
    }

    public function test_selesai_memakai_poin_dan_tidak_mengembalikannya(): void
    {
        Mail::fake();
        $m = $this->anggota(160000);
        $this->tukar($m, $this->idHadiah('Meet & Greet'));
        $k = ic_claim::first();
        $url = route('ic.admin.klaim.proses', $k->id_claim);

        $this->admin()->patch($url, ['aksi' => 'lanjut', 'dari' => 'diajukan']);
        $this->admin()->patch($url, ['aksi' => 'lanjut', 'dari' => 'ditinjau', 'catatan' => 'Jadwal dikirim lewat email']);
        $this->admin()->patch($url, ['aksi' => 'lanjut', 'dari' => 'disetujui'])->assertSessionHas('success');

        $this->assertSame('selesai', $k->fresh()->status);
        $this->assertSame(10000, $m->fresh()->poin_total);
        $this->assertSame(0, ic_poin::where('claim_id', $k->id_claim)->where('sumber', 'kembali')->count());

        $dompet = app(ClaimService::class)->dompet($m->fresh());
        $this->assertSame([10000, 0, 150000], [$dompet->saldo, $dompet->tertahan, $dompet->dipakai]);

        // Selesai adalah akhir alur: tidak bisa ditolak (poin tidak bisa ditarik kembali lewat jalur ini).
        $this->admin()->patch($url, ['aksi' => 'tolak', 'dari' => 'selesai', 'catatan' => 'x'])->assertSessionHas('error');
        $this->assertSame(10000, $m->fresh()->poin_total);
    }

    public function test_penukaran_yang_dibatalkan_anggota_tidak_bisa_diproses_admin(): void
    {
        $m = $this->anggota(160000);
        $this->tukar($m, $this->idHadiah('Meet & Greet'));
        $k = ic_claim::first();
        $this->actingAs($m->fresh(), 'member')->delete(route('ic.penukaran.batal', $k->id_claim));

        $this->admin()->patch(route('ic.admin.klaim.proses', $k->id_claim), ['aksi' => 'lanjut', 'dari' => 'dibatalkan'])
            ->assertSessionHas('error');
        $this->admin()->get(route('ic.admin.klaim', ['status' => 'dibatalkan']))->assertOk()->assertSee('Anggota Uji')->assertSee('Dibatalkan');
        $this->assertSame(160000, $m->fresh()->poin_total);
    }

    public function test_perubahan_hadiah_tidak_mengubah_penukaran_yang_sudah_masuk(): void
    {
        Mail::fake();
        $m = $this->anggota(160000);
        $id = $this->idHadiah('Meet & Greet');
        $this->tukar($m, $id);

        $this->admin()->put(route('ic.admin.hadiah.ubah', $id), [
            'judul' => 'Meet & Greet',
            'deskripsi' => 'D',
            'ikon' => 'camera',
            'warna' => 'biru',
            'opsi' => '',
            'harga_poin' => 300000,
            'stok' => '',
        ])->assertSessionHas('success');

        $k = ic_claim::first();
        $this->assertSame(150000, $k->poin_ditahan);
        $this->admin()->patch(route('ic.admin.klaim.proses', $k->id_claim), ['aksi' => 'tolak', 'dari' => 'diajukan', 'catatan' => 'Batal']);
        $this->assertSame(160000, $m->fresh()->poin_total); // yang kembali = yang ditahan, bukan harga baru
    }

    // ---------- profil dan data anggota lain ----------

    public function test_profil_menampilkan_pengajuan_lama_dan_catatan(): void
    {
        $m = $this->anggota(30000);
        $k = $this->klaimLama($m, 10000, 'selesai');
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
        $this->klaimLama($a, 10000)->update(['catatan_admin' => 'Rahasia anggota A']);

        $this->actingAs($b, 'member')->get(route('ic.profil'))->assertOk()->assertDontSee('Rahasia anggota A');
        $this->assertSame(0, app(ClaimService::class)->klaimAnggota($b)->count());
    }

    // ---------- admin: antrean penukaran (klaim lama, tanpa poin ditahan) ----------

    public function test_admin_tab_penukaran_dan_ringkasan_memakai_data_asli(): void
    {
        $m = $this->anggota(30000);
        $this->klaimLama($m, 10000);
        $this->klaimLama($m, 25000, 'ditolak');
        $admin = $this->admin();

        $admin->get(route('ic.admin.klaim'))->assertOk()->assertSee('Anggota Uji')->assertSee('Hadiah Pertama')
            ->assertDontSee('Akses Eksklusif'); // 25.000 ditolak: tidak di antrean "menunggu"
        $admin->get(route('ic.admin.klaim', ['status' => 'ditolak']))->assertOk()->assertSee('Akses Eksklusif');
        $admin->get(route('ic.admin.klaim', ['status' => 'semua']))->assertOk()->assertSee('Akses Eksklusif')->assertSee('Hadiah Pertama');
        $admin->get(route('ic.admin.klaim', ['status' => ['x']]))->assertOk(); // parameter aneh jatuh ke bawaan

        $admin->get(route('ic.admin.ringkasan'))->assertOk()->assertSee('Anggota Uji');
    }

    public function test_alur_status_lengkap_klaim_lama_dan_tercatat_di_log(): void
    {
        $m = $this->anggota(30000);
        $k = $this->klaimLama($m, 10000);
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
        $this->assertSame(30000, $m->fresh()->poin_total); // klaim lama tidak memotong poin

        // Selesai adalah akhir alur.
        $admin->patch($url, ['aksi' => 'lanjut', 'dari' => 'selesai'])->assertSessionHas('error');
        $admin->patch($url, ['aksi' => 'tolak', 'dari' => 'selesai', 'catatan' => 'x'])->assertSessionHas('error');
        $this->assertSame('selesai', $k->fresh()->status);
        $this->assertCount(4, $k->fresh()->logs);
    }

    public function test_klik_ganda_atau_layar_basi_tidak_melompatkan_status(): void
    {
        $k = $this->klaimLama($this->anggota(30000), 10000);
        $admin = $this->admin();
        $url = route('ic.admin.klaim.proses', $k->id_claim);

        $admin->patch($url, ['aksi' => 'lanjut', 'dari' => 'diajukan'])->assertSessionHas('success');
        // Permintaan kedua masih membawa status lama di layarnya.
        $admin->patch($url, ['aksi' => 'lanjut', 'dari' => 'diajukan'])->assertSessionHas('error');

        $this->assertSame('ditinjau', $k->fresh()->status);
        $this->assertCount(2, $k->fresh()->logs);
    }

    public function test_tolak_klaim_lama_wajib_alasan_dan_tidak_mengembalikan_poin(): void
    {
        $m = $this->anggota(30000);
        $k = $this->klaimLama($m, 10000);
        $admin = $this->admin();
        $url = route('ic.admin.klaim.proses', $k->id_claim);

        $admin->patch($url, ['aksi' => 'tolak', 'dari' => 'diajukan', 'catatan' => '  '])->assertSessionHas('error');
        $this->assertSame('diajukan', $k->fresh()->status);

        $admin->patch($url, ['aksi' => 'tolak', 'dari' => 'diajukan', 'catatan' => 'Data belum cocok'])->assertSessionHas('success');
        $this->assertSame('ditolak', $k->fresh()->status);
        $this->assertSame(30000, $m->fresh()->poin_total); // tidak ada poin yang ditahan, jadi tidak ada yang kembali
        $this->assertSame(0, ic_poin::where('claim_id', $k->id_claim)->count());

        $this->actingAs($m, 'member')->get(route('ic.profil'))->assertSee('Data belum cocok')->assertSee('Ditolak');
    }

    public function test_catatan_saja_mengubah_catatan_tanpa_mengubah_status(): void
    {
        $k = $this->klaimLama($this->anggota(30000), 10000, 'selesai');
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
        $k = $this->klaimLama($this->anggota(30000), 10000);
        $url = route('ic.admin.klaim.proses', $k->id_claim);

        $this->patch($url, ['aksi' => 'lanjut', 'dari' => 'diajukan'])->assertRedirect(route('login'));

        $admin = $this->admin();
        $admin->patch($url, ['aksi' => 'hapus', 'dari' => 'diajukan'])->assertSessionHasErrors('aksi');
        $admin->patch($url, ['aksi' => 'lanjut', 'dari' => 'sembarang'])->assertSessionHasErrors('dari');
        $admin->patch($url, ['aksi' => 'catatan', 'dari' => 'diajukan', 'catatan' => str_repeat('x', 161)])->assertSessionHasErrors('catatan');
        $admin->patch(route('ic.admin.klaim.proses', 99999), ['aksi' => 'lanjut', 'dari' => 'diajukan'])->assertNotFound();
        $this->assertSame('diajukan', $k->fresh()->status);
    }

    // ---------- admin: katalog hadiah ----------

    public function test_admin_tab_hadiah_menampilkan_katalog_dan_membuka_hadiah_dari_url(): void
    {
        $admin = $this->admin();
        $id = $this->idHadiah('Merchandise');

        $admin->get(route('ic.admin.hadiah'))->assertOk()->assertSee('Meet & Greet')->assertSee('Merchandise');
        $admin->get(route('ic.admin.hadiah', ['hadiah' => $id]))->assertOk()
            ->assertSee(route('ic.admin.hadiah.ubah', $id), false);
        $admin->get(route('ic.admin.hadiah', ['baru' => 1]))->assertOk()
            ->assertSee(route('ic.admin.hadiah.simpan'), false)->assertSee('Hadiah baru');
        $admin->get(route('ic.admin.hadiah', ['hadiah' => 'ngawur']))->assertOk();
    }

    public function test_admin_mengubah_hadiah_termasuk_harga_dan_stok(): void
    {
        $admin = $this->admin();
        $id = $this->idHadiah('Meet & Greet');

        $admin->put(route('ic.admin.hadiah.ubah', $id), [
            'judul' => 'Meet & Greet VIP',
            'deskripsi' => 'Deskripsi baru',
            'ikon' => 'cd',
            'warna' => 'hijau',
            'opsi' => '',
            'harga_poin' => 175000,
            'stok' => '12',
        ])->assertRedirect(route('ic.admin.hadiah', ['hadiah' => $id]))->assertSessionHas('success');

        $r = ic_reward::find($id);
        $this->assertSame(['Meet & Greet VIP', 'Deskripsi baru', 'cd', 'hijau', null, 175000, 12], [$r->judul, $r->deskripsi, $r->ikon, $r->warna, $r->opsi, $r->harga_poin, $r->stok]);

        // Stok dikosongkan = tak terbatas. Dua pilihan, satu per baris (CRLF dari browser ikut dibersihkan).
        $admin->put(route('ic.admin.hadiah.ubah', $id), [
            'judul' => 'Meet & Greet VIP',
            'deskripsi' => 'X',
            'ikon' => 'cap',
            'warna' => 'kuning',
            'opsi' => "Pilihan A\r\n Pilihan B \r\n",
            'harga_poin' => 175000,
            'stok' => '',
        ])->assertSessionHas('success');
        $r = ic_reward::find($id);
        $this->assertSame([['Pilihan A', 'Pilihan B'], null], [$r->opsi, $r->stok]);
    }

    public function test_admin_menambah_hadiah_ke_katalog(): void
    {
        $this->admin()->post(route('ic.admin.hadiah.simpan'), [
            'judul' => 'Voucher Studio',
            'deskripsi' => 'Voucher sesi studio',
            'ikon' => 'headphone',
            'warna' => 'pink',
            'opsi' => '',
            'harga_poin' => 50000,
            'stok' => '3',
        ])->assertSessionHas('success');

        $r = ic_reward::where('judul', 'Voucher Studio')->firstOrFail();
        $this->assertSame([50000, 3, true], [$r->harga_poin, $r->stok, $r->aktif]);
        $this->assertSame(8, ic_reward::count());

        $m = $this->anggota(60000);
        $this->actingAs($m, 'member')->get(route('ic.hadiah'))->assertSee('Voucher Studio');
    }

    public function test_validasi_hadiah_dan_redirect_ke_hadiah_yang_diedit(): void
    {
        $admin = $this->admin();
        $id = $this->idHadiah('Merchandise');
        $dasar = ['judul' => 'Ok', 'deskripsi' => 'Ok', 'ikon' => 'gift', 'warna' => 'koral', 'opsi' => '', 'harga_poin' => 250000, 'stok' => ''];

        $admin->put(route('ic.admin.hadiah.ubah', $id), ['opsi' => 'Satu saja'] + $dasar)
            ->assertRedirect(route('ic.admin.hadiah', ['hadiah' => $id]))->assertSessionHasErrors('opsi');
        $admin->put(route('ic.admin.hadiah.ubah', $id), ['opsi' => "Sama\nSama"] + $dasar)->assertSessionHasErrors('opsi.1');
        $admin->put(route('ic.admin.hadiah.ubah', $id), ['opsi' => "A\nB\nC"] + $dasar)->assertSessionHasErrors('opsi');
        $admin->put(route('ic.admin.hadiah.ubah', $id), ['judul' => ''] + $dasar)->assertSessionHasErrors('judul');
        $admin->put(route('ic.admin.hadiah.ubah', $id), ['judul' => str_repeat('x', 41)] + $dasar)->assertSessionHasErrors('judul');
        $admin->put(route('ic.admin.hadiah.ubah', $id), ['ikon' => 'lock'] + $dasar)->assertSessionHasErrors('ikon');
        $admin->put(route('ic.admin.hadiah.ubah', $id), ['warna' => 'ungu'] + $dasar)->assertSessionHasErrors('warna');
        $admin->put(route('ic.admin.hadiah.ubah', $id), ['harga_poin' => 500] + $dasar)->assertSessionHasErrors('harga_poin');
        $admin->put(route('ic.admin.hadiah.ubah', $id), ['harga_poin' => 'banyak'] + $dasar)->assertSessionHasErrors('harga_poin');
        $admin->put(route('ic.admin.hadiah.ubah', $id), ['stok' => -1] + $dasar)->assertSessionHasErrors('stok');
        $admin->put(route('ic.admin.hadiah.ubah', 9999), $dasar)->assertNotFound();
        $admin->post(route('ic.admin.hadiah.simpan'), ['judul' => ''] + $dasar)
            ->assertRedirect(route('ic.admin.hadiah', ['baru' => 1]))->assertSessionHasErrors('judul');

        $this->assertSame('Merchandise', ic_reward::find($id)->judul);
    }

    public function test_isian_yang_gagal_validasi_tampil_lagi_di_hadiah_yang_sama(): void
    {
        $admin = $this->admin();
        $id = $this->idHadiah('Merchandise');

        $admin->from(route('ic.admin.hadiah', ['hadiah' => $id]))
            ->put(route('ic.admin.hadiah.ubah', $id), [
                'judul' => 'Judul Yang Diketik',
                'deskripsi' => 'D',
                'ikon' => 'gift',
                'warna' => 'koral',
                'opsi' => 'Satu saja',
                'harga_poin' => 250000,
                'stok' => '',
            ]);

        $admin->get(route('ic.admin.hadiah', ['hadiah' => $id]))->assertOk()->assertSee('Judul Yang Diketik');
    }

    public function test_detail_anggota_menampilkan_dompet_dan_riwayat_tahan_kembali(): void
    {
        $m = $this->anggota(160000);
        $this->tukar($m, $this->idHadiah('Meet & Greet'));

        $this->admin()->get(route('ic.admin.anggota.detail', $m->id_member))->assertOk()
            ->assertSee('Saldo poin')->assertSee('150.000 ditahan')->assertSee('Poin ditahan (tukar hadiah)');
    }
}