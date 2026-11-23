<?php

namespace Tests\Feature\InnerCircle\Gl;

use App\Mail\InnerCircle\KartuGlMail;
use App\Models\ic_event;
use App\Models\ic_gl_antrean;
use App\Models\log_error;
use App\Models\member;
use App\Services\AnggaranEmail;
use App\Services\InnerCircle\AcaraService;
use App\Services\InnerCircle\EmailService;
use App\Services\InnerCircle\GlService;
use App\Services\InnerCircle\JadwalGagal;
use App\Services\InnerCircle\UndianService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Kartu GL dikirim bertahap: batch pertama saat undian, sisanya per menit; ditahan saat anggaran email menipis;
 * kartu yang terus gagal dilaporkan sekali lalu dilepas; undian lama tidak dikirim ulang.
 */
class KartuGlBertahapTest extends TestCase
{
    use RefreshDatabase;

    private int $nomor = 0;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(now()->setTime(12, 0));
        Mail::fake();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function anggota(): member
    {
        $this->nomor++;
        $m = member::create(['nama' => 'Anggota ' . $this->nomor, 'email' => "k{$this->nomor}@contoh.test", 'password' => 'rahasia123']);
        $m->kode_member = sprintf('IC-%05d', $m->id_member);
        $m->email_verified_at = now();
        $m->save();

        return $m;
    }

    /** Acara dengan $kuota pemenang dan $peserta anggota yang antri, lalu diundi sesudah 18.00. */
    private function undiAcara(int $kuota, int $peserta): ic_event
    {
        $e = app(AcaraService::class)->buat([
            'tanggal' => now()->toDateString(),
            'tipe' => 'club',
            'venue' => 'Superhouse Satrio',
            'kota' => 'Jakarta',
        ]);
        $e->forceFill(['kuota_gl' => $kuota, 'username_acara' => 'wsgl', 'sandi_acara' => 'rahasia-acara'])->save();
        $e = $e->fresh();

        for ($i = 0; $i < $peserta; $i++) {
            app(GlService::class)->antri($this->anggota(), $e);
        }

        Carbon::setTestNow(now()->setTime(18, 1));
        app(UndianService::class)->jalankanJatuhTempo();

        return $e->fresh();
    }

    public function test_batch_pertama_saat_undian_lalu_sisanya_bertahap(): void
    {
        config(['inner_circle.gl.kartu_per_menit' => 3]);

        $this->undiAcara(7, 8);
        Mail::assertSent(KartuGlMail::class, 3);

        $this->assertSame(3, app(EmailService::class)->kirimSisaKartuGl());
        Mail::assertSent(KartuGlMail::class, 6);

        $this->assertSame(1, app(EmailService::class)->kirimSisaKartuGl());
        Mail::assertSent(KartuGlMail::class, 7);

        $this->assertSame(0, app(EmailService::class)->kirimSisaKartuGl());
        Mail::assertSent(KartuGlMail::class, 7);
        $this->assertSame(7, ic_gl_antrean::query()->whereNotNull('kartu_dikirim_pada')->count());
    }

    public function test_kartu_ditahan_saat_anggaran_menipis_lalu_terkirim_setelah_longgar(): void
    {
        config(['email_anggaran.harian' => 10]);
        AnggaranEmail::catat(9); // email biasa berhenti di 9 dari 10

        $this->undiAcara(2, 3);
        Mail::assertNotSent(KartuGlMail::class);
        $this->assertSame(0, ic_gl_antrean::query()->whereNotNull('kartu_dikirim_pada')->count());
        $this->assertSame(0, app(EmailService::class)->kirimSisaKartuGl());

        @unlink(AnggaranEmail::berkas()); // anggaran longgar lagi
        $this->assertSame(2, app(EmailService::class)->kirimSisaKartuGl());
        Mail::assertSent(KartuGlMail::class, 2);
    }

    public function test_kartu_yang_terus_gagal_dilaporkan_sekali_lalu_dilepas(): void
    {
        config(['inner_circle.gl.kartu_per_menit' => 0, 'inner_circle.gl.kartu_coba_maks' => 0]);

        $this->undiAcara(1, 1);

        $this->assertSame(0, app(EmailService::class)->kirimSisaKartuGl(30));
        $this->assertSame(1, log_error::where('kelas', JadwalGagal::class)->count());
        $this->assertSame(1, (int) log_error::first()->jumlah);

        app(EmailService::class)->kirimSisaKartuGl(30); // tidak dilaporkan dua kali
        $this->assertSame(1, (int) log_error::first()->jumlah);
        Mail::assertNotSent(KartuGlMail::class);
    }

    public function test_undian_lama_di_luar_jendela_tidak_dikirim_ulang(): void
    {
        config(['inner_circle.gl.kartu_per_menit' => 0]);

        $e = $this->undiAcara(1, 1);
        $e->forceFill(['undian_selesai_pada' => now()->subDays(5)])->save();

        $this->assertSame(0, app(EmailService::class)->kirimSisaKartuGl(30));
        Mail::assertNotSent(KartuGlMail::class);
    }
}