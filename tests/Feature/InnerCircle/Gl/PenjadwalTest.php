<?php

namespace Tests\Feature\InnerCircle\Gl;

use App\Models\ic_event;
use App\Models\log_error;
use App\Services\InnerCircle\JadwalGagal;
use App\Services\InnerCircle\PenjadwalService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Jadwal otomatis (cron): detak cron, kegagalan sinkron yang dilaporkan ke halaman Log, dan kunci anti-tumpang-tindih yang tidak menahan undian 18.00 sampai 24 jam.
 */
class PenjadwalTest extends TestCase
{
    use RefreshDatabase;

    private function fakeBit(array $isi, int $status = 200): void
    {
        config(['services.bandsintown.app_id' => 'abc', 'services.bandsintown.artist' => 'id_1']);
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(['rest.bandsintown.com/*' => Http::response($isi, $status)]);
    }

    private function baris(bool $ic = false): array
    {
        return collect(PenjadwalService::ringkasan($ic))->keyBy('label')->all();
    }

    public function test_tanpa_detak_cron_ditandai_belum_terdeteksi_dan_dengan_detak_normal(): void
    {
        Cache::flush();
        $this->assertSame('peringatan', $this->baris()['Cron']['tingkat']);

        PenjadwalService::detak();
        $this->assertSame('ok', $this->baris()['Cron']['tingkat']);

        $this->travel(5)->minutes();
        $this->assertSame('gagal', $this->baris()['Cron']['tingkat']);
    }

    public function test_penanda_jadwal_hanya_di_cms_inner_circle_dan_menandai_sudah_diatur(): void
    {
        config(['services.bandsintown.app_id' => 'abc', 'services.bandsintown.artist' => 'id_1']);

        $this->assertCount(1, PenjadwalService::ringkasan(false));

        $b = $this->baris(true);
        $this->assertCount(3, $b);
        $this->assertSame('terjadwal', $b['Sinkron Bandsintown']['tingkat']);
        $this->assertSame('terjadwal', $b['Undian GL otomatis']['tingkat']);
        $this->assertStringContainsString('18.00', $b['Undian GL otomatis']['teks']);
    }

    public function test_penanda_bandsintown_belum_diatur_bila_app_id_kosong(): void
    {
        config(['services.bandsintown.app_id' => null]);

        $this->assertSame('peringatan', $this->baris(true)['Sinkron Bandsintown']['tingkat']);
    }

    public function test_sinkron_otomatis_berhasil_membuat_acara(): void
    {
        $this->fakeBit([[
            'id' => '501',
            'datetime' => '2030-06-01T22:00:00',
            'title' => '',
            'url' => 'https://www.bandsintown.com/e/501?app_id=rahasia',
            'venue' => ['name' => 'Beach Club', 'city' => 'Bali'],
        ]]);

        app(PenjadwalService::class)->sinkronBandsintown();

        $this->assertSame(1, ic_event::where('bit_id', '501')->count());
    }

    public function test_sinkron_otomatis_gagal_dilaporkan_ke_halaman_log(): void
    {
        $this->fakeBit(['errors' => ['App ID tidak valid']], 403);

        app(PenjadwalService::class)->sinkronBandsintown();

        $this->assertSame(1, log_error::where('kelas', JadwalGagal::class)->count());
        $this->assertSame('inner-circle', log_error::first()->area);
    }

    public function test_sinkron_otomatis_tanpa_app_id_dilewati_tanpa_error(): void
    {
        config(['services.bandsintown.app_id' => null]);

        app(PenjadwalService::class)->sinkronBandsintown();

        $this->assertSame(0, ic_event::count());
        $this->assertSame(0, log_error::count());
    }

    public function test_jadwal_terdaftar_dengan_detak_dan_kunci_tumpang_tindih_singkat(): void
    {
        $jadwal = collect(app(Schedule::class)->events())->keyBy('description');

        $this->assertSame('* * * * *', $jadwal['cron:detak']->expression);
        $this->assertSame('0 4 * * *', $jadwal['ic:sinkron-bandsintown']->expression);
        $this->assertSame('* * * * *', $jadwal['ic:undi-gl']->expression);

        // Kunci anti-tumpang-tindih bawaan 24 jam: proses yang mati saat 18.00 akan menahan undian seharian.
        $this->assertLessThanOrEqual(30, $jadwal['ic:sinkron-bandsintown']->expiresAt);
        $this->assertLessThanOrEqual(10, $jadwal['ic:undi-gl']->expiresAt);

        // Sisa Kartu GL dikirim bertahap tiap menit.
        $this->assertSame('* * * * *', $jadwal['ic:kartu-gl-sisa']->expression);
        $this->assertLessThanOrEqual(10, $jadwal['ic:kartu-gl-sisa']->expiresAt);
    }

    public function test_halaman_log_menampilkan_status_jadwal(): void
    {
        PenjadwalService::detak();

        $this->withSession(['login' => true, 'user' => 'admin'])
            ->get(route('log.index'))
            ->assertOk()
            ->assertSee('Jadwal otomatis')
            ->assertSee('Jalan normal');
    }
}