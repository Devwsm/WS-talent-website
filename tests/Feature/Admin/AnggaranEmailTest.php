<?php

namespace Tests\Feature\Admin;

use App\Mail\Admin\KodeLoginAdminMail;
use App\Mail\Admin\PeringatanErrorMail;
use App\Mail\InnerCircle\SelamatDatangMail;
use App\Models\log_error;
use App\Models\member;
use App\Services\AnggaranEmail;
use App\Services\InnerCircle\EmailService;
use App\Services\LogErrorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Anggaran email harian bersama: email terkirim dihitung, email berprioritas rendah/biasa berhenti lebih dulu,
 * email penting tidak pernah ditahan, hitungan bertahan saat cache dikosongkan, dan peringatan error diperketat.
 */
class AnggaranEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_yang_terkirim_dihitung_otomatis(): void
    {
        $this->assertSame(0, AnggaranEmail::terpakai());

        Mail::to('admin@contoh.test')->send(new KodeLoginAdminMail('admin', '123456', 10));

        $this->assertSame(1, AnggaranEmail::terpakai());
    }

    public function test_rendah_berhenti_dulu_lalu_biasa_dan_penting_tidak_pernah_ditahan(): void
    {
        config(['email_anggaran.harian' => 10]); // rendah berhenti di 6, biasa di 9

        AnggaranEmail::catat(5);
        $this->assertTrue(AnggaranEmail::tersedia(AnggaranEmail::RENDAH));

        AnggaranEmail::catat(1);
        $this->assertFalse(AnggaranEmail::tersedia(AnggaranEmail::RENDAH));
        $this->assertTrue(AnggaranEmail::tersedia(AnggaranEmail::BIASA));

        AnggaranEmail::catat(3);
        $this->assertFalse(AnggaranEmail::tersedia(AnggaranEmail::BIASA));

        AnggaranEmail::catat(100);
        $this->assertTrue(AnggaranEmail::tersedia(AnggaranEmail::PENTING));
    }

    public function test_hitungan_bergulir_24_jam(): void
    {
        AnggaranEmail::catat(8);
        $this->assertSame(8, AnggaranEmail::terpakai());

        $this->travel(25)->hours();

        $this->assertSame(0, AnggaranEmail::terpakai());
    }

    public function test_hitungan_bertahan_saat_cache_dikosongkan_seperti_saat_deploy(): void
    {
        AnggaranEmail::catat(3);

        Cache::flush(); // optimize:clear saat deploy

        $this->assertSame(3, AnggaranEmail::terpakai());
    }

    public function test_selamat_datang_ditahan_saat_anggaran_menipis_dan_terkirim_saat_longgar(): void
    {
        Mail::fake();
        config(['email_anggaran.harian' => 10]);
        $m = member::create(['nama' => 'Anggota Uji', 'email' => 'uji@contoh.test', 'password' => null]);

        AnggaranEmail::catat(6);
        app(EmailService::class)->kirimSelamatDatang($m);
        Mail::assertNothingSent();

        @unlink(AnggaranEmail::berkas());
        app(EmailService::class)->kirimSelamatDatang($m);
        Mail::assertSent(SelamatDatangMail::class, 1);
    }

    public function test_peringatan_error_ditahan_saat_anggaran_menipis_tetapi_tetap_tercatat_di_log(): void
    {
        Mail::fake();
        config(['site.peringatan_email' => 'owner@contoh.test', 'email_anggaran.harian' => 10]);
        AnggaranEmail::catat(6);

        app(LogErrorService::class)->catat(new \RuntimeException('a'));

        Mail::assertNothingSent();
        $this->assertSame(1, log_error::count());
    }

    public function test_peringatan_error_dibatasi_per_hari(): void
    {
        Mail::fake();
        config(['site.peringatan_email' => 'owner@contoh.test', 'log_admin.email_maks_per_jam' => 100, 'log_admin.email_maks_per_hari' => 2]);

        app(LogErrorService::class)->catat(new \RuntimeException('a'));
        app(LogErrorService::class)->catat(new \LogicException('b'));
        app(LogErrorService::class)->catat(new \DomainException('c'));

        Mail::assertSent(PeringatanErrorMail::class, 2);
        $this->assertSame(3, log_error::count());
    }

    public function test_error_berulang_hanya_sekali_per_periode_tetapi_error_dibuka_lagi_dikabari(): void
    {
        Mail::fake();
        config(['site.peringatan_email' => 'owner@contoh.test']);
        $a = fn() => new \RuntimeException('a');

        app(LogErrorService::class)->catat($a());
        app(LogErrorService::class)->catat($a()); // masih terbuka, sama: tidak dikirim lagi
        Mail::assertSent(PeringatanErrorMail::class, 1);

        log_error::query()->update(['selesai' => true]); // admin menandai selesai
        app(LogErrorService::class)->catat($a()); // muncul lagi: dibuka lagi, dikabari
        Mail::assertSent(PeringatanErrorMail::class, 2);
    }

    public function test_batas_bawaan_peringatan_dan_verifikasi_sudah_diperketat(): void
    {
        $this->assertSame(360, (int) config('log_admin.email_per_error_menit'));
        $this->assertSame(3, (int) config('log_admin.email_maks_per_jam'));
        $this->assertSame(20, (int) config('log_admin.email_maks_per_hari'));
        $this->assertSame(3, (int) config('inner_circle.batas_verifikasi_per_hari'));
    }
}