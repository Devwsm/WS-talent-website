<?php

namespace Tests\Feature;

use App\Models\ic_event;
use App\Models\ic_stempel;
use App\Models\member;
use App\Services\InnerCircle\AcaraService;
use App\Services\InnerCircle\LokasiService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Check-in dengan lokasi: radius per acara, akurasi GPS, dan pengaturan titik oleh petugas di dashboard. */
class InnerCircleLokasiTest extends TestCase
{
    use RefreshDatabase;

    // Titik uji: sekitar Monas, Jakarta. 0,001 derajat lintang kira-kira 111 m.
    private const LAT = -6.1754;
    private const LNG = 106.8272;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(now()->setTime(20, 0));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function anggota(): member
    {
        $m = member::create(['nama' => 'Anggota Uji', 'email' => 'a@contoh.test', 'password' => 'rahasia123']);
        $m->kode_member = sprintf('IC-%05d', $m->id_member);
        $m->email_verified_at = now();
        $m->save();

        return $m;
    }

    private function acara(array $lokasi = []): ic_event
    {
        $e = app(AcaraService::class)->buat([
            'tanggal' => now()->toDateString(),
            'tipe' => 'club',
            'venue' => 'Superhouse Satrio',
            'kota' => 'Jakarta',
        ]);

        return $lokasi ? tap($e)->update($lokasi + ['lokasi_radius' => 150]) : $e;
    }

    private function titik(): array
    {
        return ['lokasi_lat' => self::LAT, 'lokasi_lng' => self::LNG];
    }

    private function checkin(member $m, ic_event $e, array $posisi = [])
    {
        return $this->actingAs($m, 'member')->post(route('ic.checkin.proses'), [
            'kode' => $e->token,
            'acara' => $e->kode,
        ] + $posisi);
    }

    private function admin()
    {
        return $this->withSession(['login' => true, 'user' => 'admin']);
    }

    public function test_jarak_haversine_masuk_akal(): void
    {
        $this->assertEqualsWithDelta(111, LokasiService::jarakMeter(-6.1754, 106.8272, -6.1764, 106.8272), 2);
        $this->assertSame(0.0, LokasiService::jarakMeter(1.0, 2.0, 1.0, 2.0));
        $this->assertSame('340 m', LokasiService::formatJarak(338));
        $this->assertSame('2,4 km', LokasiService::formatJarak(2400));
    }

    public function test_acara_tanpa_titik_tetap_bisa_check_in_tanpa_lokasi(): void
    {
        $m = $this->anggota();
        $e = $this->acara();

        $this->checkin($m, $e)->assertRedirect(route('ic.beranda'));
        $this->assertNull(ic_stempel::first()->jarak_m);
    }

    public function test_acara_bertitik_menolak_check_in_tanpa_lokasi(): void
    {
        $m = $this->anggota();
        $e = $this->acara($this->titik());

        $this->checkin($m, $e)->assertSessionHasErrors('kode');
        $this->assertSame(0, ic_stempel::count());
    }

    public function test_di_dalam_radius_berhasil_dan_hanya_jarak_yang_dicatat(): void
    {
        $m = $this->anggota();
        $e = $this->acara($this->titik());

        // ~55 m dari titik, akurasi 20 m.
        $this->checkin($m, $e, ['lat' => self::LAT - 0.0005, 'lng' => self::LNG, 'akurasi' => 20])
            ->assertRedirect(route('ic.beranda'));

        $baris = ic_stempel::first();
        $this->assertEqualsWithDelta(55, $baris->jarak_m, 3);
        $this->assertSame(20, $baris->akurasi_m);
        $this->assertArrayNotHasKey('lat', $baris->getAttributes());
    }

    public function test_di_luar_radius_ditolak_dengan_jarak_di_pesan(): void
    {
        $m = $this->anggota();
        $e = $this->acara($this->titik());

        // ~1,1 km dari titik.
        $this->checkin($m, $e, ['lat' => self::LAT - 0.01, 'lng' => self::LNG, 'akurasi' => 10])
            ->assertSessionHasErrors('kode');

        $this->assertStringContainsString('km', session('errors')->first('kode'));
        $this->assertSame(0, ic_stempel::count());
    }

    public function test_akurasi_gps_terlalu_kasar_atau_tidak_dilaporkan_ditolak(): void
    {
        $m = $this->anggota();
        $e = $this->acara($this->titik());

        $this->checkin($m, $e, ['lat' => self::LAT, 'lng' => self::LNG, 'akurasi' => 900])->assertSessionHasErrors('kode');
        $this->checkin($m, $e, ['lat' => self::LAT, 'lng' => self::LNG])->assertSessionHasErrors('kode');
        $this->assertSame(0, ic_stempel::count());
    }

    public function test_keringanan_akurasi_menolong_gps_kasar_di_pinggir_radius(): void
    {
        $m = $this->anggota();
        $e = $this->acara($this->titik());

        // ~166 m dari titik (radius 150) dengan akurasi 40 m: 166 - 40 <= 150, lolos.
        $this->checkin($m, $e, ['lat' => self::LAT - 0.0015, 'lng' => self::LNG, 'akurasi' => 40])
            ->assertRedirect(route('ic.beranda'));
    }

    public function test_koordinat_tidak_valid_ditolak_validasi(): void
    {
        $m = $this->anggota();
        $e = $this->acara($this->titik());

        $this->checkin($m, $e, ['lat' => 999, 'lng' => self::LNG, 'akurasi' => 10])->assertSessionHasErrors('lat');
        $this->checkin($m, $e, ['lat' => 'abc', 'lng' => self::LNG, 'akurasi' => 10])->assertSessionHasErrors('lat');
    }

    public function test_titik_acara_tidak_bocor_ke_halaman_anggota(): void
    {
        $m = $this->anggota();
        $e = $this->acara($this->titik());

        $html = $this->actingAs($m, 'member')->get(route('ic.checkin', ['kode' => $e->token]))->assertOk()->getContent();

        $this->assertStringContainsString('data-lokasi="1"', $html);
        $this->assertStringNotContainsString('6.1754', $html);
        $this->assertStringNotContainsString('106.8272', $html);
    }

    // ---------- dashboard ----------

    public function test_petugas_menyimpan_titik_dan_lokasi_tercatat_per_acara(): void
    {
        $e = $this->acara();

        $this->admin()->put(route('ic.admin.acara.lokasi', $e->kode), [
            'lat' => '-6,1754',
            'lng' => '106.8272',
            'radius' => 200,
            'sumber' => 'petugas',
            'petugas' => 'Budi',
        ])->assertSessionHasNoErrors();

        $e->refresh();
        $this->assertTrue($e->lokasi_aktif);
        $this->assertEqualsWithDelta(-6.1754, $e->lokasi_lat, 0.00001);
        $this->assertSame(200, $e->radius_efektif);
        $this->assertSame('Budi', $e->lokasi_diatur_oleh);
    }

    public function test_koordinat_boleh_ditempel_satu_kolom_dari_google_maps(): void
    {
        $e = $this->acara();

        $this->admin()->put(route('ic.admin.acara.lokasi', $e->kode), [
            'lat' => '-6.1754, 106.8272',
            'lng' => '',
            'radius' => 150,
            'sumber' => 'manual',
        ])->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(106.8272, $e->fresh()->lokasi_lng, 0.00001);
    }

    public function test_radius_di_luar_batas_ditolak(): void
    {
        $e = $this->acara();

        $this->admin()->put(route('ic.admin.acara.lokasi', $e->kode), [
            'lat' => self::LAT,
            'lng' => self::LNG,
            'radius' => 5,
            'sumber' => 'manual',
        ])->assertSessionHasErrors('radius');
        $this->assertFalse($e->fresh()->lokasi_aktif);
    }

    public function test_memindahkan_titik_jauh_butuh_konfirmasi_agar_petugas_lain_tidak_menimpa(): void
    {
        $e = $this->acara($this->titik());
        $baru = ['lat' => -6.9, 'lng' => 107.6, 'radius' => 150, 'sumber' => 'petugas']; // Bandung

        $this->admin()->put(route('ic.admin.acara.lokasi', $e->kode), $baru)->assertSessionHasErrors('lat');
        $this->assertEqualsWithDelta(self::LAT, $e->fresh()->lokasi_lat, 0.00001);

        $this->admin()->put(route('ic.admin.acara.lokasi', $e->kode), $baru + ['konfirmasi_geser' => 1])
            ->assertSessionHasNoErrors();
        $this->assertEqualsWithDelta(-6.9, $e->fresh()->lokasi_lat, 0.00001);
    }

    public function test_menghapus_titik_mematikan_pemeriksaan_lokasi(): void
    {
        $m = $this->anggota();
        $e = $this->acara($this->titik());

        $this->admin()->delete(route('ic.admin.acara.lokasi.hapus', $e->kode))->assertRedirect();

        $this->assertFalse($e->fresh()->lokasi_aktif);
        $this->checkin($m, $e->fresh())->assertRedirect(route('ic.beranda'));
    }

    public function test_cari_alamat_mengembalikan_saran_dan_menangani_galat(): void
    {
        $e = $this->acara();

        // Permintaan pertama sukses, kedua gagal (stub pertama yang cocok yang dipakai, jadi pakai urutan).
        Http::fake(['nominatim.openstreetmap.org/*' => Http::sequence()
            ->push([
                ['display_name' => 'Superhouse Satrio, Jakarta', 'lat' => '-6.2250', 'lon' => '106.8200'],
                ['display_name' => 'rusak', 'lat' => 'x', 'lon' => 'y'],
            ])
            ->push('', 500)]);

        $this->admin()->postJson(route('ic.admin.acara.lokasi.cari', $e->kode))
            ->assertOk()
            ->assertJsonCount(1, 'hasil')
            ->assertJsonPath('hasil.0.lat', -6.225)
            ->assertJsonPath('kueri', 'Superhouse Satrio, Jakarta');

        $this->admin()->postJson(route('ic.admin.acara.lokasi.cari', $e->kode))->assertStatus(502);
    }

    public function test_rute_lokasi_hanya_untuk_admin(): void
    {
        $e = $this->acara();

        $this->put(route('ic.admin.acara.lokasi', $e->kode), ['lat' => 1, 'lng' => 1, 'radius' => 100, 'sumber' => 'manual'])
            ->assertRedirect(route('login'));
        $this->post(route('ic.admin.acara.lokasi.cari', $e->kode))->assertRedirect(route('login'));
    }
}