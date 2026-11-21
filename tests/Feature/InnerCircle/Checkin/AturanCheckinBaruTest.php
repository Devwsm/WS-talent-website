<?php

namespace Tests\Feature\InnerCircle\Checkin;

use App\Models\ic_event;
use App\Models\ic_gl_antrean;
use App\Models\ic_poin;
use App\Models\member;
use App\Services\InnerCircle\AcaraService;
use App\Services\InnerCircle\CheckinGagal;
use App\Services\InnerCircle\CheckinService;
use App\Services\InnerCircle\IcAturan;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 5, aturan check-in baru: jendela 22.00 sampai H+1 04.00, lokasi wajib, poin GL (1.000) dan mandiri (2.000),
 * dan tombol check-in mandiri yang ditutup untuk pemenang GL.
 */
class AturanCheckinBaruTest extends TestCase
{
    use RefreshDatabase;

    private const LAT = -6.2088;
    private const LNG = 106.8456;

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

    private function anggota(string $email = 'a@contoh.test'): member
    {
        $m = member::create(['nama' => 'Anggota Uji', 'email' => $email, 'password' => 'rahasia123']);
        $m->kode_member = sprintf('IC-%05d', $m->id_member);
        $m->email_verified_at = now();
        $m->save();

        return $m;
    }

    private function acara(array $o = [], bool $bertitik = true): ic_event
    {
        $e = app(AcaraService::class)->buat($o + [
            'tanggal' => now()->toDateString(),
            'tipe' => 'club',
            'venue' => 'Superhouse Satrio',
            'kota' => 'Jakarta',
        ]);

        if ($bertitik) {
            $e->forceFill(['lokasi_lat' => self::LAT, 'lokasi_lng' => self::LNG, 'lokasi_radius' => 150])->save();
        }

        return $e;
    }

    private function menang(member $m, ic_event $e): void
    {
        ic_gl_antrean::create(['event_id' => $e->id_event, 'member_id' => $m->id_member, 'status' => ic_gl_antrean::MENANG]);
    }

    private function posisi(array $ubah = []): array
    {
        return $ubah + ['lat' => self::LAT, 'lng' => self::LNG, 'akurasi' => 20];
    }

    private function scan(member $m, ic_event $e, array $posisi = [])
    {
        return $this->actingAs($m, 'member')->post(route('ic.checkin.proses'), ['kode' => $e->token, 'acara' => $e->kode] + $this->posisi($posisi));
    }

    private function mandiri(member $m, ic_event $e, array $posisi = [])
    {
        return $this->actingAs($m, 'member')->post(route('ic.checkin.mandiri'), ['acara' => $e->kode] + $this->posisi($posisi));
    }

    private function admin()
    {
        return $this->withSession(['login' => true, 'user' => 'admin']);
    }

    private function aturan(array $ubah = []): array
    {
        return $ubah + ['jendela_buka' => '22:00', 'jendela_tutup' => '04:00', 'jendela_tutup_hari' => 1, 'radius_bawaan_m' => 150, 'akurasi_maks_m' => 150, 'poin_gl' => 1000, 'poin_mandiri' => 2000];
    }

    // ---------- bawaan ----------

    public function test_bawaan_jendela_dan_poin_aturan_baru(): void
    {
        $a = app(IcAturan::class)->efektif();

        $this->assertSame('22:00', $a['jendela_buka']);
        $this->assertSame('04:00', $a['jendela_tutup']);
        $this->assertSame(1, $a['jendela_tutup_hari']);
        $this->assertSame(1000, app(IcAturan::class)->poinGl());
        $this->assertSame(2000, app(IcAturan::class)->poinMandiri());
    }

    // ---------- poin menurut status GL ----------

    public function test_non_pemenang_check_in_lewat_lokasi_dan_pemenang_memindai_qr_untuk_poin_gl(): void
    {
        $e = $this->acara();
        $tiket = $this->anggota('tiket@contoh.test');
        $gl = $this->anggota('gl@contoh.test');
        $this->menang($gl, $e);

        $this->mandiri($tiket, $e)->assertRedirect(route('ic.beranda'));
        $this->scan($gl, $e)->assertRedirect(route('ic.beranda'));

        $this->assertSame(2000, $tiket->fresh()->poin_total);
        $this->assertSame(1000, $gl->fresh()->poin_total);
        $this->assertSame(2, ic_poin::count());
    }

    public function test_scan_qr_ditolak_untuk_non_pemenang_gl_dan_tidak_memberi_poin(): void
    {
        $e = $this->acara();
        $m = $this->anggota();

        $this->scan($m, $e)->assertSessionHasErrors(['kode' => 'Pemindaian QR hanya untuk pemenang GL gratis. Check-in biasa tidak perlu QR: tekan Check-in dan izinkan akses lokasi.']);
        $this->assertSame(0, ic_poin::count());
        $this->assertSame(0, $m->fresh()->poin_total);

        // Pemenang GL di acara LAIN tetap bukan pemenang di acara ini.
        $lain = $this->acara(['tanggal' => now()->addDay()->toDateString()]);
        $this->menang($m, $lain);
        $this->scan($m, $e)->assertSessionHasErrors('kode');
    }

    public function test_antri_atau_menang_di_acara_lain_tidak_dihitung_pemenang_di_sini(): void
    {
        $e = $this->acara();
        $lain = $this->acara(['tanggal' => now()->addDay()->toDateString()]);
        $m = $this->anggota();
        $this->menang($m, $lain);
        ic_gl_antrean::create(['event_id' => $e->id_event, 'member_id' => $m->id_member]); // masih antri

        $this->assertFalse(app(CheckinService::class)->pemenangGl($m, $e));
        $this->mandiri($m, $e)->assertRedirect(route('ic.beranda'));
        $this->assertSame(2000, $m->fresh()->poin_total);
    }

    public function test_poin_milik_acara_menimpa_bawaan(): void
    {
        $e = $this->acara();
        $e->update(['poin_gl' => 1500, 'poin_mandiri' => 3000]);
        $tiket = $this->anggota('tiket@contoh.test');
        $gl = $this->anggota('gl@contoh.test');
        $this->menang($gl, $e);

        $this->mandiri($tiket, $e);
        $this->scan($gl, $e);

        $this->assertSame(3000, $tiket->fresh()->poin_total);
        $this->assertSame(1500, $gl->fresh()->poin_total);
    }

    public function test_poin_bawaan_dari_cms_dipakai_dan_poin_lama_tidak_berubah(): void
    {
        $e1 = $this->acara();
        $m = $this->anggota();
        $this->mandiri($m, $e1);
        $this->assertSame(2000, $m->fresh()->poin_total);

        $this->admin()->post(route('ic.admin.konten.aturan.simpan'), $this->aturan(['poin_gl' => 1500, 'poin_mandiri' => 2500]))
            ->assertSessionHasNoErrors();

        $e2 = $this->acara(['tanggal' => now()->addDay()->toDateString(), 'jendela_mulai' => now()->subHour(), 'jendela_selesai' => now()->addHours(3)]);
        $this->mandiri($m->fresh(), $e2);

        $this->assertSame(2000 + 2500, $m->fresh()->poin_total);
        $this->assertSame(2000, ic_poin::where('event_id', $e1->id_event)->value('jumlah'));
    }

    public function test_validasi_poin_aturan(): void
    {
        $this->admin()->post(route('ic.admin.konten.aturan.simpan'), $this->aturan(['poin_gl' => 0]))->assertSessionHasErrorsIn('aturan', 'poin_gl');
        $this->admin()->post(route('ic.admin.konten.aturan.simpan'), $this->aturan(['poin_mandiri' => 1000001]))->assertSessionHasErrorsIn('aturan', 'poin_mandiri');
    }

    // ---------- lokasi wajib ----------

    public function test_acara_tanpa_titik_menutup_check_in_lewat_qr_maupun_tombol(): void
    {
        $e = $this->acara([], false);
        $m = $this->anggota();

        $this->scan($m, $e)->assertSessionHasErrors('kode');
        $this->mandiri($m, $e)->assertSessionHasErrors('kode');
        $this->assertSame(0, ic_poin::count());
    }

    public function test_di_luar_radius_atau_tanpa_lokasi_ditolak_juga_untuk_tombol(): void
    {
        $e = $this->acara();
        $m = $this->anggota();

        $this->mandiri($m, $e, ['lat' => self::LAT - 0.01])->assertSessionHasErrors('kode');
        $this->actingAs($m, 'member')->post(route('ic.checkin.mandiri'), ['acara' => $e->kode])->assertSessionHasErrors('kode');
        $this->assertSame(0, ic_poin::count());
    }

    // ---------- check-in mandiri ----------

    public function test_tombol_mandiri_memberi_poin_mandiri_dengan_jarak_tercatat(): void
    {
        $e = $this->acara();
        $m = $this->anggota();

        $this->mandiri($m, $e)->assertRedirect(route('ic.beranda'));

        $this->assertSame(2000, $m->fresh()->poin_total);
        $this->assertNotNull(ic_poin::first()->jarak_m);
    }

    public function test_tombol_mandiri_ditutup_untuk_pemenang_gl_dan_poin_tidak_dobel(): void
    {
        $e = $this->acara();
        $m = $this->anggota();
        $this->menang($m, $e);

        $this->mandiri($m, $e)->assertSessionHasErrors(['kode' => 'Kamu terpilih GL gratis di acara ini, jadi check-in dilakukan dengan memindai QR di pintu. Tombol check-in mandiri ditutup supaya poinmu tidak dobel.']);
        $this->assertSame(0, ic_poin::count());

        // Lewat QR pemenang tetap bisa, dan hanya satu kali.
        $this->scan($m, $e)->assertRedirect(route('ic.beranda'));
        $this->scan($m, $e)->assertSessionHasErrors('kode');
        $this->mandiri($m, $e)->assertSessionHasErrors('kode');
        $this->assertSame(1, ic_poin::count());
        $this->assertSame(1000, $m->fresh()->poin_total);
    }

    public function test_tombol_mandiri_dan_scan_tidak_bisa_dua_kali_untuk_acara_sama(): void
    {
        $e = $this->acara();
        $m = $this->anggota();

        $this->mandiri($m, $e)->assertRedirect(route('ic.beranda'));
        $this->scan($m, $e)->assertSessionHasErrors('kode');
        $this->assertSame(1, ic_poin::count());
    }

    public function test_tombol_mandiri_butuh_login_acara_valid_dan_acara_privat_ditolak(): void
    {
        $e = $this->acara();
        $privat = $this->acara(['tipe' => 'private']);
        $m = $this->anggota();

        $this->post(route('ic.checkin.mandiri'), ['acara' => $e->kode] + $this->posisi())->assertRedirect(route('ic.masuk'));
        $this->mandiri($m, $privat)->assertSessionHasErrors('kode');
        $this->actingAs($m, 'member')->post(route('ic.checkin.mandiri'), ['acara' => 'KODE-TIDAK-ADA'] + $this->posisi())->assertSessionHasErrors('kode');
        $this->actingAs($m, 'member')->post(route('ic.checkin.mandiri'), $this->posisi())->assertSessionHasErrors('acara');
        $this->assertSame(0, ic_poin::count());
    }

    public function test_service_mandiri_melempar_gagal_untuk_pemenang(): void
    {
        $e = $this->acara();
        $m = $this->anggota();
        $this->menang($m, $e);

        $this->expectException(CheckinGagal::class);
        app(CheckinService::class)->checkinMandiri($m, $e->kode, null, $this->posisi());
    }

    // ---------- jendela ----------

    public function test_batas_jendela_bawaan_21_59_22_00_03_59_04_01(): void
    {
        $e = $this->acara(['tanggal' => '2026-10-10']);

        $coba = function (string $waktu) use ($e) {
            Carbon::setTestNow($waktu);

            return $this->mandiri($this->anggota('u' . md5($waktu) . '@contoh.test'), $e);
        };

        $coba('2026-10-10 21:59:00')->assertSessionHasErrors('kode');
        $coba('2026-10-11 04:01:00')->assertSessionHasErrors('kode');
        $this->assertSame(0, ic_poin::count());

        $coba('2026-10-10 22:00:00')->assertRedirect(route('ic.beranda'));
        $coba('2026-10-11 03:59:00')->assertRedirect(route('ic.beranda'));
        $this->assertSame(2, ic_poin::count());
    }

    public function test_jendela_dan_tombol_mandiri_mengikuti_jendela_acara(): void
    {
        $e = $this->acara(['tanggal' => '2026-10-10']);
        $m = $this->anggota();

        Carbon::setTestNow('2026-10-10 21:00:00');
        $this->mandiri($m, $e)->assertSessionHasErrors('kode');

        Carbon::setTestNow('2026-10-10 23:30:00');
        $this->mandiri($m, $e)->assertRedirect(route('ic.beranda'));
    }
}