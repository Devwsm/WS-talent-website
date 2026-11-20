<?php

namespace Tests\Feature\InnerCircle\Gl;

use App\Mail\InnerCircle\KartuGlMail;
use App\Models\ic_event;
use App\Models\ic_gl_antrean;
use App\Models\log_aktivitas;
use App\Models\member;
use App\Services\InnerCircle\AcaraService;
use App\Services\InnerCircle\GlGagal;
use App\Services\InnerCircle\GlService;
use App\Services\InnerCircle\UndianService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Fase 6A, mesin undian GL: antrean (ikut, keluar, ditutup 18.00), undian otomatis dan manual, status tertunda,
 * kartu GL hanya untuk pemenang, email pemenang tanpa sandi, dan undian yang tidak pernah jalan dua kali.
 */
class UndianGlTest extends TestCase
{
    use RefreshDatabase;

    private int $nomor = 0;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(now()->setTime(12, 0)); // hari H, siang: antrean masih terbuka
        Mail::fake();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function anggota(array $o = []): member
    {
        $this->nomor++;
        $m = member::create(['nama' => 'Anggota ' . $this->nomor, 'email' => "a{$this->nomor}@contoh.test", 'password' => 'rahasia123']);
        $m->kode_member = sprintf('IC-%05d', $m->id_member);
        $m->email_verified_at = array_key_exists('verifikasi', $o) ? $o['verifikasi'] : now();
        $m->diblokir_pada = $o['diblokir'] ?? null;
        $m->save();

        return $m;
    }

    private function acara(array $o = []): ic_event
    {
        $e = app(AcaraService::class)->buat([
            'tanggal' => now()->toDateString(),
            'tipe' => 'club',
            'venue' => 'Superhouse Satrio',
            'kota' => 'Jakarta',
        ]);
        $e->forceFill($o + ['kuota_gl' => 2, 'username_acara' => 'wsgl', 'sandi_acara' => 'rahasia-acara'])->save();

        return $e->fresh();
    }

    private function gl(): GlService
    {
        return app(GlService::class);
    }

    private function undian(): UndianService
    {
        return app(UndianService::class);
    }

    private function sesudah18(): void
    {
        Carbon::setTestNow(now()->setTime(18, 1));
    }

    // ---------- antrean ----------

    public function test_anggota_bisa_antri_dan_tidak_menggandakan(): void
    {
        $e = $this->acara();
        $m = $this->anggota();

        $this->gl()->antri($m, $e);
        $this->gl()->antri($m, $e);

        $this->assertSame(1, ic_gl_antrean::query()->count());
        $this->assertSame('antri', $this->gl()->statusAnggota($m, $e));
    }

    public function test_acara_tanpa_kuota_tidak_punya_antrean(): void
    {
        $e = $this->acara(['kuota_gl' => null]);

        $this->expectException(GlGagal::class);
        $this->gl()->antri($this->anggota(), $e);
    }

    public function test_email_belum_terverifikasi_dan_akun_diblokir_tidak_bisa_antri(): void
    {
        $e = $this->acara();

        foreach ([$this->anggota(['verifikasi' => null]), $this->anggota(['diblokir' => now()])] as $m) {
            try {
                $this->gl()->antri($m, $e);
                $this->fail('Seharusnya ditolak');
            } catch (GlGagal) {
                $this->assertTrue(true);
            }
        }
        $this->assertSame(0, ic_gl_antrean::query()->count());
    }

    public function test_antrean_ditutup_pukul_18_hari_h(): void
    {
        $e = $this->acara();
        $m = $this->anggota();

        Carbon::setTestNow(now()->setTime(17, 59));
        $this->assertFalse($this->gl()->antreanTutup($e));

        Carbon::setTestNow(now()->setTime(18, 0));
        $this->assertTrue($this->gl()->antreanTutup($e));
        $this->assertSame('ditutup', $this->gl()->statusAnggota($m, $e));

        $this->expectException(GlGagal::class);
        $this->gl()->antri($m, $e);
    }

    public function test_keluar_antrean_selama_terbuka_dan_tidak_setelah_ditutup(): void
    {
        $e = $this->acara();
        $m = $this->anggota();
        $this->gl()->antri($m, $e);

        $this->gl()->keluar($m, $e);
        $this->assertSame(0, ic_gl_antrean::query()->count());

        $this->gl()->antri($m, $e);
        $this->sesudah18();
        $this->expectException(GlGagal::class);
        $this->gl()->keluar($m, $e);
    }

    // ---------- undian otomatis ----------

    public function test_belum_waktunya_tidak_diundi(): void
    {
        $e = $this->acara();
        $this->gl()->antri($this->anggota(), $e);

        $this->assertSame([], $this->undian()->jalankanJatuhTempo());
        $this->assertSame('belum', $e->fresh()->status_undian);
    }

    public function test_undian_otomatis_memilih_sebanyak_kuota_dan_mengunci_hasil(): void
    {
        $e = $this->acara(['kuota_gl' => 2]);
        $semua = collect(range(1, 5))->map(fn() => $this->anggota());
        $semua->each(fn($m) => $this->gl()->antri($m, $e));

        $this->sesudah18();
        $this->assertSame([$e->kode => 'selesai'], $this->undian()->jalankanJatuhTempo());

        $e->refresh();
        $this->assertSame('selesai', $e->status_undian);
        $this->assertNotNull($e->undian_selesai_pada);
        $this->assertSame(2, ic_gl_antrean::query()->where('status', 'menang')->count());
        $this->assertSame(3, ic_gl_antrean::query()->where('status', 'tidak_terpilih')->count());
        $this->assertSame([1, 2], ic_gl_antrean::query()->where('status', 'menang')->orderBy('urutan_undi')->pluck('urutan_undi')->all());
    }

    public function test_kuota_lebih_besar_dari_peserta_semua_menang(): void
    {
        $e = $this->acara(['kuota_gl' => 10]);
        foreach ([1, 2, 3] as $_) {
            $this->gl()->antri($this->anggota(), $e);
        }

        $this->sesudah18();
        $this->undian()->jalankanJatuhTempo();

        $this->assertSame(3, ic_gl_antrean::query()->where('status', 'menang')->count());
    }

    public function test_undian_tidak_berulang_walau_dipicu_dua_kali(): void
    {
        $e = $this->acara(['kuota_gl' => 1]);
        foreach ([1, 2, 3] as $_) {
            $this->gl()->antri($this->anggota(), $e);
        }

        $this->sesudah18();
        $this->undian()->jalankanJatuhTempo();
        $pemenang = ic_gl_antrean::query()->where('status', 'menang')->pluck('member_id')->all();

        $this->assertSame([], $this->undian()->jalankanJatuhTempo());
        $this->assertSame($pemenang, ic_gl_antrean::query()->where('status', 'menang')->pluck('member_id')->all());
        $this->assertSame(1, log_aktivitas::query()->where('aksi', 'Undian GL otomatis')->count());

        $this->expectException(GlGagal::class);
        $this->undian()->undi($e->fresh(), 'admin', 'coba lagi setelah selesai');
    }

    public function test_peserta_yang_diblokir_sebelum_undian_tidak_ikut(): void
    {
        $e = $this->acara(['kuota_gl' => 5]);
        $baik = $this->anggota();
        $jahat = $this->anggota();
        $this->gl()->antri($baik, $e);
        $this->gl()->antri($jahat, $e);
        $jahat->forceFill(['diblokir_pada' => now()])->save();

        $this->sesudah18();
        $this->undian()->jalankanJatuhTempo();

        $this->assertSame('menang', $this->gl()->statusAnggota($baik, $e->fresh()));
        $this->assertSame('tidak_terpilih', $this->gl()->statusAnggota($jahat, $e->fresh()));
    }

    // ---------- tertunda, tunda, undi sekarang ----------

    public function test_data_belum_lengkap_jadi_tertunda_dan_antrean_ditutup(): void
    {
        $e = $this->acara(['sandi_acara' => null]);
        $m = $this->anggota();
        $this->gl()->antri($m, $e);

        $this->sesudah18();
        $this->assertSame([$e->kode => 'tertunda'], $this->undian()->jalankanJatuhTempo());

        $this->assertSame('tertunda', $e->fresh()->status_undian);
        $this->assertSame(0, ic_gl_antrean::query()->where('status', '!=', 'antri')->count());
        $this->assertSame('tertunda', $this->gl()->statusAnggota($m, $e->fresh()));
        // Sudah tertunda: menit berikutnya tidak mencatat ulang.
        $this->undian()->jalankanJatuhTempo();
        $this->assertSame(1, log_aktivitas::query()->where('aksi', 'Undian GL tertunda')->count());
    }

    public function test_undian_susulan_butuh_alasan_dan_data_lengkap(): void
    {
        $e = $this->acara(['sandi_acara' => null]);
        $m = $this->anggota();
        $this->gl()->antri($m, $e);
        $this->sesudah18();
        $this->undian()->jalankanJatuhTempo();

        try {
            $this->undian()->undi($e->fresh(), 'admin', '');
            $this->fail('Alasan kosong harus ditolak');
        } catch (GlGagal) {
        }
        try {
            $this->undian()->undi($e->fresh(), 'admin', 'sandi belum diisi tim show');
            $this->fail('Data belum lengkap harus ditolak');
        } catch (GlGagal) {
        }

        $e->forceFill(['sandi_acara' => 'baru-123'])->save();
        $hasil = $this->undian()->undi($e->fresh(), 'admin', 'sandi baru diterima dari tim show');

        $this->assertSame(['peserta' => 1, 'pemenang' => 1], $hasil);
        $this->assertSame('menang', $this->gl()->statusAnggota($m, $e->fresh()));
        $this->assertSame(1, log_aktivitas::query()->where('aksi', 'Undi GL sekarang')->where('keterangan', 'like', '%sandi baru diterima%')->count());
    }

    public function test_ditunda_menahan_undian_otomatis_dan_antrean_tetap_terbuka(): void
    {
        $e = $this->acara();
        $m = $this->anggota();
        $this->undian()->tunda($e, 'admin');

        $this->sesudah18();
        $this->assertSame([], $this->undian()->jalankanJatuhTempo());
        $this->assertSame('ditunda', $e->fresh()->status_undian);

        $this->gl()->antri($m, $e->fresh()); // masih terbuka
        $this->assertSame('antri', $this->gl()->statusAnggota($m, $e->fresh()));

        $this->undian()->lanjutkan($e->fresh(), 'admin');
        $this->assertSame('belum', $e->fresh()->status_undian);
        $this->assertSame([$e->kode => 'selesai'], $this->undian()->jalankanJatuhTempo());
    }

    public function test_undi_sekarang_sebelum_18_boleh_dengan_alasan(): void
    {
        $e = $this->acara(['kuota_gl' => 1]);
        $m = $this->anggota();
        $this->gl()->antri($m, $e);

        $this->undian()->undi($e, 'admin', 'acara dimajukan, antrean ditutup lebih awal');

        $this->assertSame('menang', $this->gl()->statusAnggota($m, $e->fresh()));
    }

    // ---------- kartu dan email ----------

    public function test_kartu_gl_hanya_untuk_pemenang_setelah_undian_selesai(): void
    {
        $e = $this->acara(['kuota_gl' => 1, 'petunjuk_masuk' => 'Sebut nama di pintu VIP']);
        $a = $this->anggota();
        $this->gl()->antri($a, $e);

        $this->assertNull($this->gl()->kartu($a, $e->fresh()), 'belum diundi');

        $this->sesudah18();
        $this->undian()->jalankanJatuhTempo();
        $e->refresh();

        $kartu = $this->gl()->kartu($a, $e);
        $this->assertSame('wsgl', $kartu['username']);
        $this->assertSame('rahasia-acara', $kartu['sandi']);
        $this->assertSame($a->kode_member, $kartu['kode_member']);
        $this->assertSame('Sebut nama di pintu VIP', $kartu['petunjuk']);
        $this->assertStringContainsString('Superhouse Satrio', $kartu['acara']);

        $lain = $this->anggota();
        $this->assertNull($this->gl()->kartu($lain, $e), 'bukan peserta');
    }

    public function test_peserta_yang_kalah_tidak_mendapat_kartu(): void
    {
        $e = $this->acara(['kuota_gl' => 1]);
        $semua = collect([1, 2, 3])->map(fn() => $this->anggota());
        $semua->each(fn($m) => $this->gl()->antri($m, $e));

        $this->sesudah18();
        $this->undian()->jalankanJatuhTempo();
        $e->refresh();

        $kartu = $semua->map(fn($m) => $this->gl()->kartu($m, $e))->filter();
        $this->assertCount(1, $kartu);
    }

    public function test_email_pemenang_terkirim_sekali_tanpa_sandi(): void
    {
        $e = $this->acara(['kuota_gl' => 1]);
        $m = $this->anggota();
        $this->gl()->antri($m, $e);

        $this->sesudah18();
        $this->undian()->jalankanJatuhTempo();

        Mail::assertSent(KartuGlMail::class, 1);
        Mail::assertSent(KartuGlMail::class, function (KartuGlMail $mail) use ($m) {
            $html = $mail->render();

            return $mail->hasTo($m->email) && ! str_contains($html, 'rahasia-acara') && str_contains($html, 'Superhouse Satrio');
        });
        $this->assertNotNull(ic_gl_antrean::query()->first()->kartu_dikirim_pada);

        // Mengirim ulang untuk baris yang sama tidak menggandakan.
        app(\App\Services\InnerCircle\EmailService::class)->kirimKartuGl(ic_gl_antrean::query()->first(), $e->fresh());
        Mail::assertSent(KartuGlMail::class, 1);
    }

    // ---------- perintah, jadwal, dan pengaman ----------

    public function test_perintah_artisan_dan_jadwal_tiap_menit(): void
    {
        $e = $this->acara(['kuota_gl' => 1]);
        $this->gl()->antri($this->anggota(), $e);
        $this->sesudah18();

        $this->artisan('ic:undi-gl')->expectsOutputToContain($e->kode . ': selesai')->assertSuccessful();
        $this->artisan('ic:undi-gl')->expectsOutputToContain('Tidak ada undian yang jatuh tempo.')->assertSuccessful();

        $terjadwal = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
            ->first(fn($ev) => str_contains((string) $ev->command, 'ic:undi-gl'));
        $this->assertNotNull($terjadwal);
        $this->assertSame('* * * * *', $terjadwal->expression);
    }

    public function test_pengaman_kunjungan_mengundi_tanpa_cron_dan_dibatasi_sekali_per_menit(): void
    {
        $e = $this->acara(['kuota_gl' => 1]);
        $m = $this->anggota();
        $this->gl()->antri($m, $e);
        $this->sesudah18();

        $this->undian()->pengamanKunjungan();
        $this->assertSame('selesai', $e->fresh()->status_undian);

        // Acara baru yang jatuh tempo di menit yang sama baru diundi pada pengecekan berikutnya (kunci cache 60 detik).
        $e2 = $this->acara(['kuota_gl' => 1, 'tanggal' => now()->toDateString()]);
        $this->undian()->pengamanKunjungan();
        $this->assertSame('belum', $e2->fresh()->status_undian);
    }

    public function test_acara_lama_tidak_diundi_otomatis(): void
    {
        $e = $this->acara(['tanggal' => now()->subDays(5)->toDateString()]);

        $this->assertSame([], $this->undian()->jalankanJatuhTempo());
        $this->assertSame('belum', $e->fresh()->status_undian);
    }
}