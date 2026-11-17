<?php

namespace Tests\Feature\InnerCircle\Admin;

use App\Models\member;
use App\Services\InnerCircle\AcaraService;
use App\Services\InnerCircle\StempelService;
use App\Services\InnerCircle\TrenRingkasan;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Grafik tren di Ringkasan CMS Inner Circle. */
class TrenRingkasanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-11-18 10:00:00', 'Asia/Jakarta')); // Rabu; minggu ini mulai Senin 16 Nov
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** Teks halaman tanpa tag, spasi dan baris baru dirapatkan: aman dari auto-formatter yang membungkus baris HTML. */
    private function teks($respons): string
    {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($respons->getContent()), ENT_QUOTES | ENT_HTML5)));
    }

    private function anggotaDibuat(string $kapan, string $email): member
    {
        $m = member::create(['nama' => 'A ' . $email, 'email' => $email, 'password' => 'rahasia123']);
        $m->forceFill(['created_at' => Carbon::parse($kapan, 'Asia/Jakarta')])->save();

        return $m;
    }

    public function test_pendaftaran_per_minggu_dikelompokkan_senin_sampai_minggu_dan_minggu_kosong_bernilai_nol(): void
    {
        $this->anggotaDibuat('2026-11-16 08:00', 'a@t.test'); // minggu ini (Senin)
        $this->anggotaDibuat('2026-11-18 09:00', 'b@t.test'); // minggu ini
        $this->anggotaDibuat('2026-11-15 23:30', 'c@t.test'); // Minggu malam = minggu lalu
        $this->anggotaDibuat('2026-11-01 12:00', 'd@t.test'); // dua minggu sebelumnya
        $this->anggotaDibuat('2026-01-01 12:00', 'e@t.test'); // di luar 12 minggu: tidak dihitung

        $data = app(TrenRingkasan::class)->pendaftaranMingguan(12);

        $this->assertCount(12, $data);
        $this->assertSame(2, end($data)['nilai']);                       // 16-22 Nov
        $this->assertSame(1, $data[10]['nilai']);                        // 9-15 Nov
        $this->assertSame(0, $data[9]['nilai']);                         // 2-8 Nov, tetap muncul
        $this->assertSame(1, $data[8]['nilai']);                         // 26 Okt-1 Nov
        $this->assertSame(4, array_sum(array_column($data, 'nilai')));
        $this->assertSame('31 Agt', $data[0]['label']);                  // 12 minggu: dimulai Senin 31 Agustus
    }

    public function test_checkin_per_acara_memakai_acara_terakhir_yang_sudah_tiba_lama_ke_baru(): void
    {
        $m1 = $this->anggotaDibuat('2026-10-01 08:00', 'a@t.test');
        $m2 = $this->anggotaDibuat('2026-10-01 08:00', 'b@t.test');
        $acara = app(AcaraService::class);
        $lama = $acara->buat(['tanggal' => '2026-11-01', 'tipe' => 'club', 'venue' => 'Lama', 'kota' => 'Jakarta']);
        $baru = $acara->buat(['tanggal' => '2026-11-14', 'tipe' => 'club', 'venue' => 'Baru', 'kota' => 'Bandung']);
        $acara->buat(['tanggal' => '2026-12-25', 'tipe' => 'club', 'venue' => 'Depan', 'kota' => 'Bali']); // belum tiba
        $svc = app(StempelService::class);
        $svc->tambah($m1, 1, 'checkin', 'Check-in', 'pin', $lama);
        $svc->tambah($m1, 1, 'checkin', 'Check-in', 'pin', $baru);
        $svc->tambah($m2, 1, 'checkin', 'Check-in', 'pin', $baru);
        $svc->tambahManual($m2, 5, 'Bukan check-in', 'admin'); // tidak dihitung

        $data = app(TrenRingkasan::class)->checkinPerAcara(8);

        $this->assertCount(2, $data);
        $this->assertSame([1, 2], array_column($data, 'nilai'));
        $this->assertSame('1 Nov', $data[0]['label']);
        $this->assertStringContainsString('Baru, Bandung', $data[1]['keterangan']);
    }

    public function test_tanpa_acara_hasilnya_kosong_dan_ringkasan_tetap_tampil(): void
    {
        $this->assertSame([], app(TrenRingkasan::class)->checkinPerAcara());

        $this->withSession(['login' => true, 'user' => 'admin-uji'])->get(route('ic.admin.ringkasan'))
            ->assertOk()->assertSee('Pendaftaran per minggu')->assertSee('Check-in per acara')
            ->assertSee('Belum ada acara yang sudah berlangsung.');
    }

    public function test_ringkasan_menggambar_batang_dengan_teks_untuk_pembaca_layar(): void
    {
        $this->anggotaDibuat('2026-11-17 08:00', 'a@t.test');

        $halaman = $this->withSession(['login' => true, 'user' => 'admin-uji'])->get(route('ic.admin.ringkasan'))->assertOk();

        $this->assertStringContainsString('Total 1 pendaftar pada rentang ini.', $this->teks($halaman));
        $this->assertStringContainsString('Minggu 16 Nov – 22 Nov 2026: 1 pendaftar', $this->teks($halaman));
    }
}