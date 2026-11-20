<?php

namespace Tests\Feature\InnerCircle\Checkin;

use App\Models\ic_event;
use App\Models\ic_gl_antrean;
use App\Models\ic_pemeriksaan;
use App\Models\ic_penanda;
use App\Models\ic_poin;
use App\Models\member;
use App\Services\InnerCircle\AcaraService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 5B: pengajuan pemeriksaan kehadiran (GPS gagal) dan penanda check-in yang mencurigakan.
 */
class PemeriksaanDanPenandaTest extends TestCase
{
    use RefreshDatabase;

    private const LAT = -6.1754;
    private const LNG = 106.8272;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(now()->setTime(23, 0));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function anggota(string $email = 'a@contoh.test', string $nama = 'Anggota Uji'): member
    {
        $m = member::create(['nama' => $nama, 'email' => $email, 'password' => 'rahasia123']);
        $m->kode_member = sprintf('IC-%05d', $m->id_member);
        $m->email_verified_at = now();
        $m->save();

        return $m;
    }

    private function acara(string $venue = 'Superhouse', float $lat = self::LAT, float $lng = self::LNG): ic_event
    {
        $e = app(AcaraService::class)->buat(['tanggal' => now()->toDateString(), 'tipe' => 'club', 'venue' => $venue, 'kota' => 'Jakarta']);

        return tap($e)->update(['lokasi_lat' => $lat, 'lokasi_lng' => $lng, 'lokasi_radius' => 150]);
    }

    private function mandiri(member $m, ic_event $e, array $posisi = [])
    {
        return $this->actingAs($m, 'member')->post(route('ic.checkin.mandiri'), ['acara' => $e->kode] + $posisi);
    }

    private function dekat(array $ubah = []): array
    {
        return $ubah + ['lat' => self::LAT + 0.0004, 'lng' => self::LNG, 'akurasi' => 20];
    }

    private function admin(): static
    {
        return $this->withSession(['login' => true, 'user' => 'admin-uji']);
    }

    // ---------- pengajuan ----------

    public function test_gps_gagal_di_luar_radius_membuka_formulir_ajukan_lalu_pengajuan_tersimpan_dengan_ukuran_server(): void
    {
        $m = $this->anggota();
        $e = $this->acara();

        $this->mandiri($m, $e, ['lat' => self::LAT + 0.01, 'lng' => self::LNG, 'akurasi' => 20, 'kembali' => 'detail'])
            ->assertRedirect(route('ic.acara.detail', $e->kode));

        $this->actingAs($m, 'member')->get(route('ic.acara.detail', $e->kode))
            ->assertOk()
            ->assertSee('Ajukan pemeriksaan');

        $this->actingAs($m, 'member')->post(route('ic.pemeriksaan.ajukan'), [
            'acara' => $e->kode,
            'alasan' => 'izin_ditolak', // isian form tidak dipercaya bila server punya ukuran sendiri
            'catatan' => 'Aku di depan pintu',
        ])->assertRedirect(route('ic.acara.detail', $e->kode));

        $p = ic_pemeriksaan::firstOrFail();
        $this->assertSame('di_luar_radius', $p->alasan);
        $this->assertGreaterThan(900, $p->jarak_m);
        $this->assertSame(20, $p->akurasi_m);
        $this->assertSame('menunggu', $p->status);
        $this->assertSame('Aku di depan pintu', $p->catatan_anggota);
        $this->assertNotNull($p->sidik_perangkat);
    }

    public function test_gps_tidak_terbaca_di_browser_tetap_bisa_diajukan_dengan_alasan_dari_form(): void
    {
        $m = $this->anggota();
        $e = $this->acara();

        $this->actingAs($m, 'member')->post(route('ic.pemeriksaan.ajukan'), ['acara' => $e->kode, 'alasan' => 'izin_ditolak'])
            ->assertRedirect();

        $p = ic_pemeriksaan::firstOrFail();
        $this->assertSame('izin_ditolak', $p->alasan);
        $this->assertNull($p->jarak_m);
    }

    public function test_pengajuan_ditolak_bila_alasan_asing_di_luar_jendela_sudah_hadir_atau_ganda(): void
    {
        $m = $this->anggota();
        $e = $this->acara();

        $this->actingAs($m, 'member')->post(route('ic.pemeriksaan.ajukan'), ['acara' => $e->kode, 'alasan' => 'ngasal']);
        $this->assertSame(0, ic_pemeriksaan::count());

        $this->actingAs($m, 'member')->post(route('ic.pemeriksaan.ajukan'), ['acara' => $e->kode, 'alasan' => 'izin_ditolak']);
        $this->actingAs($m, 'member')->post(route('ic.pemeriksaan.ajukan'), ['acara' => $e->kode, 'alasan' => 'izin_ditolak']);
        $this->assertSame(1, ic_pemeriksaan::count());

        Carbon::setTestNow(now()->setTime(12, 0));
        $lain = $this->anggota('b@contoh.test', 'Lain');
        $this->actingAs($lain, 'member')->post(route('ic.pemeriksaan.ajukan'), ['acara' => $e->kode, 'alasan' => 'izin_ditolak']);
        $this->assertSame(1, ic_pemeriksaan::count());

        Carbon::setTestNow(now()->setTime(23, 0));
        $this->mandiri($lain, $e, $this->dekat())->assertSessionHasNoErrors();
        $this->actingAs($lain, 'member')->post(route('ic.pemeriksaan.ajukan'), ['acara' => $e->kode, 'alasan' => 'izin_ditolak']);
        $this->assertSame(1, ic_pemeriksaan::count());
    }

    public function test_acara_tanpa_titik_tidak_bisa_diajukan(): void
    {
        $m = $this->anggota();
        $e = app(AcaraService::class)->buat(['tanggal' => now()->toDateString(), 'tipe' => 'club', 'venue' => 'Tanpa Titik', 'kota' => 'Jakarta']);

        $this->actingAs($m, 'member')->post(route('ic.pemeriksaan.ajukan'), ['acara' => $e->kode, 'alasan' => 'izin_ditolak']);
        $this->assertSame(0, ic_pemeriksaan::count());
    }

    // ---------- tinjauan admin ----------

    public function test_admin_menyetujui_dan_poin_mandiri_masuk_sekali(): void
    {
        $m = $this->anggota();
        $e = $this->acara();
        $p = ic_pemeriksaan::create(['member_id' => $m->id_member, 'event_id' => $e->id_event, 'alasan' => 'izin_ditolak']);

        $this->admin()->get(route('ic.admin.acara.pemeriksaan'))->assertOk()->assertSee('Anggota Uji')->assertSee('2.000');

        $this->admin()->post(route('ic.admin.acara.pemeriksaan.setujui', $p->id_pemeriksaan))->assertSessionHas('success');

        $this->assertSame(2000, $m->fresh()->poin_total);
        $poin = ic_poin::where('member_id', $m->id_member)->firstOrFail();
        $this->assertSame('checkin', $poin->sumber);
        $this->assertSame('admin-uji', $poin->dicatat_oleh);
        $this->assertSame('disetujui', $p->fresh()->status);
        $this->assertSame($poin->id_poin, $p->fresh()->poin_id);

        // Klik ganda: tidak menambah poin lagi.
        $this->admin()->post(route('ic.admin.acara.pemeriksaan.setujui', $p->id_pemeriksaan))->assertSessionHasErrors('pemeriksaan');
        $this->assertSame(2000, $m->fresh()->poin_total);
        $this->assertSame(1, ic_poin::where('member_id', $m->id_member)->count());
    }

    public function test_pemenang_gl_mendapat_poin_gl_saat_disetujui(): void
    {
        $m = $this->anggota();
        $e = $this->acara();
        ic_gl_antrean::create(['event_id' => $e->id_event, 'member_id' => $m->id_member, 'status' => ic_gl_antrean::MENANG]);
        $p = ic_pemeriksaan::create(['member_id' => $m->id_member, 'event_id' => $e->id_event, 'alasan' => 'sinyal_lemah']);

        $this->admin()->post(route('ic.admin.acara.pemeriksaan.setujui', $p->id_pemeriksaan));

        $this->assertSame(1000, $m->fresh()->poin_total);
    }

    public function test_setuju_gagal_bila_anggota_sudah_tercatat_hadir(): void
    {
        $m = $this->anggota();
        $e = $this->acara();
        $p = ic_pemeriksaan::create(['member_id' => $m->id_member, 'event_id' => $e->id_event, 'alasan' => 'izin_ditolak']);
        $this->mandiri($m, $e, $this->dekat());
        $sebelum = $m->fresh()->poin_total;

        $this->admin()->post(route('ic.admin.acara.pemeriksaan.setujui', $p->id_pemeriksaan))->assertSessionHasErrors('pemeriksaan');

        $this->assertSame($sebelum, $m->fresh()->poin_total);
        $this->assertSame('menunggu', $p->fresh()->status);
    }

    public function test_penolakan_wajib_alasan_dan_tampil_ke_anggota(): void
    {
        $m = $this->anggota();
        $e = $this->acara();
        $p = ic_pemeriksaan::create(['member_id' => $m->id_member, 'event_id' => $e->id_event, 'alasan' => 'izin_ditolak']);

        $this->admin()->post(route('ic.admin.acara.pemeriksaan.tolak', $p->id_pemeriksaan), ['alasan' => ''])->assertSessionHasErrors('alasan');
        $this->assertSame('menunggu', $p->fresh()->status);

        $this->admin()->post(route('ic.admin.acara.pemeriksaan.tolak', $p->id_pemeriksaan), ['alasan' => 'Tidak terlihat di venue'])->assertSessionHas('success');
        $this->assertSame('ditolak', $p->fresh()->status);
        $this->assertSame(0, $m->fresh()->poin_total);

        $this->actingAs($m, 'member')->get(route('ic.acara.detail', $e->kode))->assertSee('Tidak terlihat di venue');
    }

    public function test_halaman_dan_aksi_pemeriksaan_hanya_untuk_admin(): void
    {
        $this->get(route('ic.admin.acara.pemeriksaan'))->assertRedirect();
        $this->post(route('ic.admin.acara.pemeriksaan.setujui', 1))->assertRedirect();
        $this->patch(route('ic.admin.acara.penanda.ubah', 1), ['status' => 'aman'])->assertRedirect();
    }

    // ---------- penanda ----------

    public function test_perangkat_sama_di_dua_akun_menandai_keduanya_tanpa_menahan_check_in(): void
    {
        $e = $this->acara();
        $a = $this->anggota('a@contoh.test', 'Ani');
        $b = $this->anggota('b@contoh.test', 'Budi');

        $r = $this->mandiri($a, $e, $this->dekat());
        $cookie = collect($r->headers->getCookies())->first(fn($c) => $c->getName() === 'ic_perangkat');
        $this->assertNotNull($cookie);
        $this->assertSame(0, ic_penanda::count());

        // Browser yang sama (cookie sama) dipakai akun lain.
        $this->withCookie('ic_perangkat', $this->tokenCookie($cookie))
            ->mandiri($b, $e, $this->dekat())->assertSessionHasNoErrors();

        $this->assertSame(2000, $a->fresh()->poin_total);
        $this->assertSame(2000, $b->fresh()->poin_total);
        $this->assertSame(2, ic_penanda::where('jenis', 'perangkat_sama')->count());
        $this->assertStringContainsString('Ani', ic_penanda::first()->detail);
    }

    public function test_perangkat_berbeda_tidak_ditandai(): void
    {
        $e = $this->acara();
        $this->mandiri($this->anggota('a@contoh.test', 'Ani'), $e, $this->dekat());
        $this->mandiri($this->anggota('b@contoh.test', 'Budi'), $e, $this->dekat());

        $this->assertSame(0, ic_penanda::count());
    }

    public function test_lokasi_terlalu_sempurna_ditandai(): void
    {
        $e = $this->acara();
        $m = $this->anggota();

        $this->mandiri($m, $e, ['lat' => self::LAT, 'lng' => self::LNG, 'akurasi' => 1])->assertSessionHasNoErrors();

        $this->assertSame(['lokasi_janggal'], ic_penanda::pluck('jenis')->all());
        $this->assertSame(2000, $m->fresh()->poin_total);
    }

    public function test_perjalanan_mustahil_antar_acara_ditandai(): void
    {
        $jakarta = $this->acara('Jakarta Club');
        $surabaya = $this->acara('Surabaya Club', -7.2575, 112.7521);
        $m = $this->anggota();

        $this->mandiri($m, $jakarta, $this->dekat())->assertSessionHasNoErrors();
        Carbon::setTestNow(now()->addMinutes(20));
        $this->mandiri($m, $surabaya, ['lat' => -7.2575, 'lng' => 112.7521 + 0.0004, 'akurasi' => 20])->assertSessionHasNoErrors();

        $penanda = ic_penanda::where('jenis', 'perjalanan_mustahil')->firstOrFail();
        $this->assertSame($surabaya->id_event, $penanda->event_id);
    }

    public function test_banyak_percobaan_gagal_sebelum_berhasil_ditandai(): void
    {
        $e = $this->acara();
        $m = $this->anggota();

        for ($i = 0; $i < 6; $i++) {
            $this->mandiri($m, $e, ['lat' => self::LAT + 0.01, 'lng' => self::LNG, 'akurasi' => 20]);
        }
        $this->mandiri($m, $e, $this->dekat())->assertSessionHasNoErrors();

        $this->assertSame(1, ic_penanda::where('jenis', 'banyak_gagal')->count());
    }

    public function test_admin_meninjau_penanda(): void
    {
        $e = $this->acara();
        $m = $this->anggota();
        $n = ic_penanda::create(['member_id' => $m->id_member, 'event_id' => $e->id_event, 'jenis' => 'lokasi_janggal', 'detail' => 'Akurasi 1 m.']);

        $this->admin()->get(route('ic.admin.acara.pemeriksaan', ['tab' => 'penanda']))->assertOk()->assertSee('Lokasi terlalu sempurna');

        $this->admin()->patch(route('ic.admin.acara.penanda.ubah', $n->id_penanda), ['status' => 'aman', 'catatan' => 'GPS bagus'])->assertSessionHas('success');
        $n->refresh();
        $this->assertSame('aman', $n->status);
        $this->assertSame('admin-uji', $n->ditinjau_oleh);

        $this->admin()->patch(route('ic.admin.acara.penanda.ubah', $n->id_penanda), ['status' => 'baru']);
        $this->assertSame('baru', $n->fresh()->status);
        $this->assertNull($n->fresh()->ditinjau_pada);

        $this->admin()->patch(route('ic.admin.acara.penanda.ubah', $n->id_penanda), ['status' => 'ngawur'])->assertSessionHasErrors('status');
    }

    public function test_menghapus_akun_ikut_menghapus_pengajuan_dan_penanda(): void
    {
        $e = $this->acara();
        $m = $this->anggota();
        ic_pemeriksaan::create(['member_id' => $m->id_member, 'event_id' => $e->id_event, 'alasan' => 'izin_ditolak']);
        ic_penanda::create(['member_id' => $m->id_member, 'event_id' => $e->id_event, 'jenis' => 'lokasi_janggal', 'detail' => 'x']);

        $m->delete();

        $this->assertSame(0, ic_pemeriksaan::count());
        $this->assertSame(0, ic_penanda::count());
    }

    /** Nilai asli cookie perangkat: respons membawanya terenkripsi, sedangkan withCookie() menerima nilai polos. */
    private function tokenCookie($cookie): string
    {
        $mentah = app('encrypter')->decrypt($cookie->getValue(), false);

        return \Illuminate\Cookie\CookieValuePrefix::remove($mentah);
    }

    // ---------- poster bawaan ----------

    public function test_acara_tanpa_poster_memakai_poster_bawaan_yang_rapi(): void
    {
        $m = $this->anggota();
        $e = $this->acara();

        $this->actingAs($m, 'member')->get(route('ic.checkin'))
            ->assertOk()
            ->assertSee('ic-poster-kosong', false)
            ->assertDontSee('ic-ikon--event', false);

        $this->actingAs($m, 'member')->get(route('ic.acara.detail', $e->kode))
            ->assertOk()
            ->assertSee('ic-poster-kosong--besar', false);
    }
}