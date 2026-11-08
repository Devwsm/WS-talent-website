<?php

namespace Tests\Feature\InnerCircle\Acara;

use App\Models\ic_event;
use App\Services\InnerCircle\AcaraService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Sinkron jadwal dari Bandsintown: tanpa duplikat, tidak menimpa acara manual, dan kegagalan ditampilkan sebagai pesan.
 */
class BandsintownTest extends TestCase
{
    use RefreshDatabase;
    // ---------- Token rusak ("The MAC is invalid") ----------
    // ---------- Bandsintown ----------
    // ---------- Lupa kata sandi ----------
    // ---------- Tab Email ----------
    // ---------- Validasi link CMS ----------

    private function admin(): static
    {
        return $this->withSession(['login' => true, 'user' => 'admin']);
    }

    private function acara(string $tanggal = '2030-05-01', string $venue = 'Club Satu'): ic_event
    {
        return app(AcaraService::class)->buat(['tanggal' => $tanggal, 'tipe' => 'club', 'kota' => 'Jakarta', 'venue' => $venue]);
    }

    private function fakeBit(array $acara): void
    {
        config(['services.bandsintown.app_id' => 'abc', 'services.bandsintown.artist' => 'id_1']);
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(['rest.bandsintown.com/*' => Http::response($acara)]);
    }

    private function itemBit(string $id, string $tgl, string $venue, string $kota = 'Bali', string $judul = ''): array
    {
        return [
            'id' => $id,
            'datetime' => $tgl . 'T22:00:00',
            'title' => $judul,
            'url' => "https://www.bandsintown.com/e/{$id}?app_id=rahasia&came_from=1",
            'venue' => ['name' => $venue, 'city' => $kota]
        ];
    }

    public function test_sinkron_membuat_acara_dan_tidak_dobel(): void
    {
        $this->fakeBit([
            $this->itemBit('11', '2030-06-01', 'Beach Club'),
            $this->itemBit('12', '2030-06-02', 'Rock Open Air', 'Bandung', 'Rock Fest'),
        ]);

        $this->admin()->post(route('ic.admin.acara.bandsintown'))->assertRedirect()->assertSessionHas('success');
        $this->assertSame(2, ic_event::count());
        $this->assertSame('festival', ic_event::where('bit_id', '12')->first()->tipe);
        $this->assertSame('club', ic_event::where('bit_id', '11')->first()->tipe);
        $this->assertSame('https://www.bandsintown.com/e/11', ic_event::where('bit_id', '11')->first()->bit_url);

        $token = ic_event::where('bit_id', '11')->first()->token;
        $this->admin()->post(route('ic.admin.acara.bandsintown'));
        $this->assertSame(2, ic_event::count());
        $this->assertSame($token, ic_event::where('bit_id', '11')->first()->token);
    }

    public function test_sinkron_menautkan_acara_manual_tanpa_menimpa(): void
    {
        $manual = $this->acara('2030-06-01', 'Beach Club');
        $this->fakeBit([$this->itemBit('11', '2030-06-01', 'BEACH CLUB!', 'Kota Lain')]);

        $this->admin()->post(route('ic.admin.acara.bandsintown'));

        $this->assertSame(1, ic_event::count());
        $manual = $manual->fresh();
        $this->assertSame('11', $manual->bit_id);
        $this->assertSame('Beach Club', $manual->venue);
        $this->assertSame('Jakarta', $manual->kota);
        $this->assertSame('manual', $manual->sumber);
    }

    public function test_sinkron_memperbarui_hanya_sumber_bandsintown_dan_tidak_menghapus(): void
    {
        $this->fakeBit([$this->itemBit('11', '2030-06-01', 'Beach Club'), $this->itemBit('12', '2030-07-01', 'Lama')]);
        $this->admin()->post(route('ic.admin.acara.bandsintown'));

        $this->fakeBit([$this->itemBit('11', '2030-06-05', 'Beach Club Baru')]);
        $this->admin()->post(route('ic.admin.acara.bandsintown'))->assertSessionHas('success');

        $e = ic_event::where('bit_id', '11')->first();
        $this->assertSame('2030-06-05', $e->tanggal->toDateString());
        $this->assertSame('Beach Club Baru', $e->venue);
        $this->assertNotNull(ic_event::where('bit_id', '12')->first(), 'acara yang hilang dari Bandsintown tidak dihapus');

        // Admin mengubah venue: sinkron berikutnya tidak menimpa lagi.
        $this->admin()->put(route('ic.admin.acara.ubah', $e->kode), ['tanggal' => '2030-06-05', 'tipe' => 'club', 'venue' => 'Venue Admin', 'kota' => 'Bali']);
        $this->assertSame('manual', $e->fresh()->sumber);
        $this->fakeBit([$this->itemBit('11', '2030-06-05', 'Beach Club Baru')]);
        $this->admin()->post(route('ic.admin.acara.bandsintown'));
        $this->assertSame('Venue Admin', $e->fresh()->venue);
    }

    public function test_sinkron_gagal_menampilkan_pesan_bukan_galat(): void
    {
        config(['services.bandsintown.app_id' => 'abc', 'services.bandsintown.artist' => 'id_1']);
        Http::fake(['rest.bandsintown.com/*' => Http::response(['errors' => ['x']], 200)]);

        $this->admin()->post(route('ic.admin.acara.bandsintown'))->assertSessionHas('error');

        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(['rest.bandsintown.com/*' => Http::response('', 403)]);
        $this->admin()->post(route('ic.admin.acara.bandsintown'))->assertSessionHas('error');
    }

    public function test_sinkron_tanpa_app_id_ditolak(): void
    {
        config(['services.bandsintown.app_id' => null]);
        $this->admin()->post(route('ic.admin.acara.bandsintown'))->assertSessionHas('error');
    }
}
