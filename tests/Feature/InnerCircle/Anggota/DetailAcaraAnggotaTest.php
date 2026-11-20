<?php

namespace Tests\Feature\InnerCircle\Anggota;

use App\Models\ic_event;
use App\Models\ic_gl_antrean;
use App\Models\ic_poin;
use App\Models\member;
use App\Services\InnerCircle\AcaraService;
use App\Services\InnerCircle\GlService;
use App\Services\InnerCircle\UndianService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Fase 7, aplikasi anggota: daftar acara (kisi poster), detail acara, tombol Antri GL dan Check-in,
 * kartu GL pemenang, check-in mandiri dari lembar, dan GL saya di Beranda.
 */
class DetailAcaraAnggotaTest extends TestCase
{
    use RefreshDatabase;

    private const LAT = -6.2088;
    private const LNG = 106.8456;

    private int $nomor = 0;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(now()->setTime(12, 0)); // siang hari H: antrean GL terbuka, check-in belum dibuka
        Mail::fake();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function anggota(bool $verif = true): member
    {
        $this->nomor++;
        $m = member::create(['nama' => 'Anggota ' . $this->nomor, 'email' => "d{$this->nomor}@contoh.test", 'password' => 'rahasia123']);
        $m->kode_member = sprintf('IC-%05d', $m->id_member);
        $m->email_verified_at = $verif ? now() : null;
        $m->save();

        return $m;
    }

    private function acara(array $o = []): ic_event
    {
        $e = app(AcaraService::class)->buat(['tanggal' => now()->toDateString(), 'tipe' => 'club', 'venue' => 'Superhouse Satrio', 'kota' => 'Jakarta']);
        $e->forceFill($o + [
            'kuota_gl' => 1,
            'username_acara' => 'wsgl-rahasia',
            'sandi_acara' => 'sandi-rahasia-9',
            'petunjuk_masuk' => 'Sebut nama di pintu VIP',
            'deskripsi' => 'Malam seru bersama Whisnu.',
            'lokasi_lat' => self::LAT,
            'lokasi_lng' => self::LNG,
            'lokasi_radius' => 150,
        ])->save();

        return $e->fresh();
    }

    private function sebagai(member $m): static
    {
        return $this->actingAs($m, 'member');
    }

    private function sesudahUndian(): void
    {
        Carbon::setTestNow(now()->setTime(18, 5));
        app(UndianService::class)->jalankanJatuhTempo();
    }

    private function bukaCheckin(): void
    {
        Carbon::setTestNow(now()->setTime(23, 0));
    }

    // ---------- daftar acara ----------

    public function test_daftar_acara_memakai_kisi_poster_yang_menaut_ke_detail(): void
    {
        $e = $this->acara();

        $this->sebagai($this->anggota())->get(route('ic.checkin'))
            ->assertOk()
            ->assertSee('ic-poster-grid', false)
            ->assertSee(route('ic.acara.detail', $e->kode), false)
            ->assertSee('Superhouse Satrio');
    }

    public function test_acara_privat_tampil_tanpa_tautan_dan_detailnya_404(): void
    {
        $e = $this->acara(['tipe' => 'private']);

        $this->sebagai($this->anggota())->get(route('ic.checkin'))->assertOk()->assertDontSee(route('ic.acara.detail', $e->kode), false);
        $this->sebagai($this->anggota())->get(route('ic.acara.detail', $e->kode))->assertNotFound();
    }

    public function test_dock_menyebut_acara_dan_tetap_aktif_di_detail(): void
    {
        $e = $this->acara();

        $html = $this->sebagai($this->anggota())->get(route('ic.acara.detail', $e->kode))->assertOk()->getContent();

        $this->assertStringContainsString('<span>Acara</span>', $html);
        $this->assertMatchesRegularExpression('/ic-dock__item is-aktif"[^>]*aria-current="page"[^>]*>\s*<i class="bi bi-calendar-event-fill"/', $html);
    }

    public function test_detail_butuh_login(): void
    {
        $e = $this->acara();

        $this->get(route('ic.acara.detail', $e->kode))->assertRedirect();
    }

    // ---------- detail ----------

    public function test_detail_menampilkan_info_acara_dan_dua_tombol(): void
    {
        $e = $this->acara();

        $this->sebagai($this->anggota())->get(route('ic.acara.detail', $e->kode))
            ->assertOk()
            ->assertSee('Superhouse Satrio')
            ->assertSee('Jakarta')
            ->assertSee('Malam seru bersama Whisnu.')
            ->assertSee('Antri GL')
            ->assertSee('Check-in dibuka')
            ->assertSee('data-ic-bagikan', false)
            ->assertDontSee('wsgl-rahasia')
            ->assertDontSee('sandi-rahasia-9');
    }

    // ---------- antri GL ----------

    public function test_antri_dan_keluar_antrean(): void
    {
        $e = $this->acara();
        $m = $this->anggota();

        $this->sebagai($m)->post(route('ic.acara.gl.antri', $e->kode))
            ->assertRedirect(route('ic.acara.detail', $e->kode))
            ->assertSessionHas('ic_toast');
        $this->assertSame(1, ic_gl_antrean::query()->where('status', 'antri')->count());

        $this->sebagai($m)->get(route('ic.acara.detail', $e->kode))->assertSee('Keluar antrean')->assertSee('Kamu dalam antrean');

        $this->sebagai($m)->delete(route('ic.acara.gl.keluar', $e->kode))->assertRedirect(route('ic.acara.detail', $e->kode));
        $this->assertSame(0, ic_gl_antrean::query()->count());
    }

    public function test_antri_dua_kali_tidak_menggandakan(): void
    {
        $e = $this->acara();
        $m = $this->anggota();

        $this->sebagai($m)->post(route('ic.acara.gl.antri', $e->kode));
        $this->sebagai($m)->post(route('ic.acara.gl.antri', $e->kode));

        $this->assertSame(1, ic_gl_antrean::query()->count());
    }

    public function test_antri_butuh_email_terverifikasi(): void
    {
        $e = $this->acara();
        $m = $this->anggota(false);

        $this->sebagai($m)->post(route('ic.acara.gl.antri', $e->kode));

        $this->assertSame(0, ic_gl_antrean::query()->count());
    }

    public function test_antri_setelah_antrean_ditutup_ditolak_dengan_pesan(): void
    {
        $e = $this->acara();
        $m = $this->anggota();
        Carbon::setTestNow(now()->setTime(18, 30));

        $this->sebagai($m)->post(route('ic.acara.gl.antri', $e->kode))
            ->assertRedirect(route('ic.acara.detail', $e->kode))
            ->assertSessionHas('ic_toast_galat');
        $this->assertSame(0, ic_gl_antrean::query()->count());
    }

    public function test_acara_tanpa_kuota_gl_tombolnya_mati(): void
    {
        $e = $this->acara(['kuota_gl' => null]);

        $this->sebagai($this->anggota())->get(route('ic.acara.detail', $e->kode))->assertOk()->assertSee('Tidak ada GL');
    }

    // ---------- hasil undian dan kartu GL ----------

    public function test_pemenang_melihat_kartu_gl_lengkap_dan_yang_kalah_tidak(): void
    {
        $e = $this->acara(['kuota_gl' => 1]);
        $a = $this->anggota();
        $b = $this->anggota();
        $this->sebagai($a)->post(route('ic.acara.gl.antri', $e->kode));
        $this->sebagai($b)->post(route('ic.acara.gl.antri', $e->kode));
        $this->sesudahUndian();

        $pemenang = ic_gl_antrean::query()->where('status', 'menang')->first()->anggota;
        $kalah = $pemenang->is($a) ? $b : $a;

        $this->sebagai($pemenang)->get(route('ic.acara.detail', $e->kode))
            ->assertOk()
            ->assertSee('Kartu GL')
            ->assertSee($pemenang->kode_member)
            ->assertSee('wsgl-rahasia')
            ->assertSee('sandi-rahasia-9')
            ->assertSee('Sebut nama di pintu VIP')
            ->assertSee('Kamu menang GL');

        $this->sebagai($kalah)->get(route('ic.acara.detail', $e->kode))
            ->assertOk()
            ->assertDontSee('Kartu GL')
            ->assertDontSee('wsgl-rahasia')
            ->assertDontSee('sandi-rahasia-9')
            ->assertSee('tidak terpilih', false);
    }

    public function test_anggota_bukan_peserta_tidak_melihat_kartu(): void
    {
        $e = $this->acara();
        $this->sebagai($this->anggota())->post(route('ic.acara.gl.antri', $e->kode));
        $this->sesudahUndian();

        $this->sebagai($this->anggota())->get(route('ic.acara.detail', $e->kode))
            ->assertOk()
            ->assertDontSee('wsgl-rahasia')
            ->assertDontSee('sandi-rahasia-9');
    }

    public function test_membuka_detail_sesudah_18_memicu_pengaman_undian(): void
    {
        $e = $this->acara(['kuota_gl' => 1]);
        $m = $this->anggota();
        $this->sebagai($m)->post(route('ic.acara.gl.antri', $e->kode));

        Carbon::setTestNow(now()->setTime(18, 10));
        $this->sebagai($m)->get(route('ic.acara.detail', $e->kode))->assertOk()->assertSee('Kartu GL');

        $this->assertSame('selesai', $e->fresh()->status_undian);
    }

    // ---------- check-in dari halaman detail ----------

    public function test_tombol_check_in_aktif_di_jendela_dan_membuka_lembar_dengan_mandiri(): void
    {
        $e = $this->acara();
        $this->bukaCheckin();

        $this->sebagai($this->anggota())->get(route('ic.acara.detail', $e->kode))
            ->assertOk()
            ->assertSee('data-ic-modal-buka="modal-checkin"', false)
            ->assertSee('id="ic-checkin-mandiri"', false)
            ->assertSee('name="kembali" value="detail"', false);
    }

    public function test_pemenang_tidak_melihat_tombol_mandiri_hanya_pindai_qr(): void
    {
        $e = $this->acara();
        $m = $this->anggota();
        $this->sebagai($m)->post(route('ic.acara.gl.antri', $e->kode));
        $this->sesudahUndian();
        $this->bukaCheckin();

        $this->sebagai($m)->get(route('ic.acara.detail', $e->kode))
            ->assertOk()
            ->assertSee('Check-in (pindai QR)')
            ->assertDontSee('id="ic-checkin-mandiri"', false);
    }

    public function test_check_in_mandiri_dari_lembar_memberi_poin_dan_detail_menandai_sudah(): void
    {
        $e = $this->acara();
        $m = $this->anggota();
        $this->bukaCheckin();

        $this->sebagai($m)->post(route('ic.checkin.mandiri'), ['acara' => $e->kode, 'kembali' => 'detail', 'lat' => self::LAT, 'lng' => self::LNG, 'akurasi' => 20])
            ->assertRedirect(route('ic.beranda'));

        $this->assertSame(2000, (int) ic_poin::query()->where('member_id', $m->id_member)->sum('jumlah'));
        $this->sebagai($m)->get(route('ic.acara.detail', $e->kode))->assertOk()->assertSee('Sudah check-in');
    }

    public function test_check_in_gagal_dari_detail_kembali_ke_detail_dan_menampilkan_galat(): void
    {
        $e = $this->acara();
        $m = $this->anggota();
        $this->bukaCheckin();

        $this->sebagai($m)->post(route('ic.checkin.mandiri'), ['acara' => $e->kode, 'kembali' => 'detail'])
            ->assertRedirect(route('ic.acara.detail', $e->kode))
            ->assertSessionHasErrors('kode');

        $this->sebagai($m)->followingRedirects()->post(route('ic.checkin.mandiri'), ['acara' => $e->kode, 'kembali' => 'detail'])
            ->assertOk()
            ->assertSee('ic-galat', false);
    }

    public function test_check_in_gagal_dari_daftar_tetap_kembali_ke_daftar(): void
    {
        $e = $this->acara();
        $m = $this->anggota();
        $this->bukaCheckin();

        $this->sebagai($m)->post(route('ic.checkin.mandiri'), ['acara' => $e->kode])
            ->assertRedirect(route('ic.checkin', ['acara' => $e->kode]));
    }

    // ---------- Beranda: GL saya ----------

    public function test_beranda_menampilkan_gl_saya_hanya_bila_ada(): void
    {
        $e = $this->acara();
        $m = $this->anggota();

        $this->sebagai($m)->get(route('ic.beranda'))->assertOk()->assertDontSee('GL gratis saya');

        app(GlService::class)->antri($m, $e);

        $this->sebagai($m)->get(route('ic.beranda'))
            ->assertOk()
            ->assertSee('GL gratis saya')
            ->assertSee('Dalam antrean')
            ->assertSee(route('ic.acara.detail', $e->kode), false);

        $this->sesudahUndian();
        $this->sebagai($m)->get(route('ic.beranda'))->assertSee('Menang · lihat kartu');
    }

    public function test_beranda_tidak_menampilkan_gl_anggota_lain(): void
    {
        $e = $this->acara();
        app(GlService::class)->antri($this->anggota(), $e);

        $this->sebagai($this->anggota())->get(route('ic.beranda'))->assertOk()->assertDontSee('GL gratis saya');
    }
}