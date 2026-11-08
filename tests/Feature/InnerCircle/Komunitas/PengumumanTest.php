<?php

namespace Tests\Feature\InnerCircle\Komunitas;

use App\Models\ic_broadcast;
use App\Models\member;
use App\Support\ImageProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Pengumuman komunitas: terbit terjadwal, kabar pilihan, escape teks, dan CMS pengumuman.
 */
class PengumumanTest extends TestCase
{
    use RefreshDatabase;
    // ---------- skema ----------
    // ---------- pengumuman: sisi anggota ----------
    // ---------- pengumuman: admin ----------
    // ---------- kenangan: unggah ----------
    // ---------- kenangan: tampilan galeri ----------
    // ---------- suka ----------
    // ---------- hapus oleh pemilik ----------
    // ---------- moderasi admin ----------
    // ---------- foto profil ----------

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->pakaiPemrosesPalsu();
    }

    /** @param  \Closure|null  $perilaku  fn(string $sumber, int $sisiMaks): string; bawaan mengembalikan byte WebP palsu */
    private function pakaiPemrosesPalsu(?\Closure $perilaku = null): void
    {
        $this->app->instance(ImageProcessor::class, new class($perilaku) extends ImageProcessor {
            public function __construct(private readonly ?\Closure $perilaku) {}

            public function keWebp(string $sumber, int $sisiMaks, int $kualitas = 82): string
            {
                return $this->perilaku ? ($this->perilaku)($sumber, $sisiMaks) : 'RIFF0000WEBPfake-' . $sisiMaks;
            }
        });
    }

    private function anggota(string $nama = 'Anggota Uji', string $email = 'a@contoh.test'): member
    {
        $m = member::create(['nama' => $nama, 'email' => $email, 'password' => 'rahasia123']);
        $m->kode_member = sprintf('IC-%05d', $m->id_member);
        $m->email_verified_at = now(); // check-in, kenangan, dan suka butuh email terverifikasi
        $m->save();

        return $m->fresh();
    }

    private function admin()
    {
        return $this->withSession(['login' => true, 'user' => 'admin']);
    }

    private function siar(array $o = []): ic_broadcast
    {
        return ic_broadcast::create($o + [
            'judul' => 'Pengumuman uji',
            'isi' => 'Isi pengumuman uji.',
            'tag' => 'UJI',
            'ikon' => 'sparkles',
            'warna' => 'kuning',
            'cta_label' => 'Lihat',
            'cta_tujuan' => 'ic.checkin',
            'unggulan' => false,
            'terbit_pada' => today()->subDay()->toDateString(),
            'aktif' => true,
        ]);
    }

    private function dataPengumuman(array $o = []): array
    {
        return $o + [
            'tag' => 'MUSIK',
            'terbit_pada' => today()->toDateString(),
            'judul' => 'Judul uji',
            'isi' => 'Isi uji.',
            'ikon' => 'cd',
            'warna' => 'biru',
            'cta_label' => 'Lihat hadiah',
            'cta_tujuan' => 'ic.hadiah',
        ];
    }

    public function test_tiga_tabel_komunitas_ada(): void
    {
        foreach (['ic_broadcasts', 'ic_memories', 'ic_memory_likes'] as $tabel) {
            $this->assertTrue(Schema::hasTable($tabel), $tabel);
        }
    }

    public function test_anggota_hanya_melihat_pengumuman_aktif_yang_sudah_terbit(): void
    {
        $this->siar(['judul' => 'Kabar Pilihan Uji', 'unggulan' => true]);
        $this->siar(['judul' => 'Pengumuman Biasa Uji']);
        $this->siar(['judul' => 'Pengumuman Besok', 'terbit_pada' => today()->addDay()->toDateString()]);
        $this->siar(['judul' => 'Pengumuman Nonaktif', 'aktif' => false]);

        $this->actingAs($this->anggota(), 'member')->get(route('ic.komunitas'))
            ->assertOk()
            ->assertSee('Kabar Pilihan Uji')
            ->assertSee('Pengumuman Biasa Uji')
            ->assertDontSee('Pengumuman Besok')
            ->assertDontSee('Pengumuman Nonaktif');
    }

    public function test_pengumuman_terjadwal_tampil_setelah_tanggalnya_tiba(): void
    {
        $this->siar(['judul' => 'Pengumuman Terjadwal', 'terbit_pada' => today()->addDays(2)->toDateString()]);
        $m = $this->anggota();

        $this->actingAs($m, 'member')->get(route('ic.komunitas'))->assertDontSee('Pengumuman Terjadwal');

        $this->travel(2)->days();
        $this->actingAs($m, 'member')->get(route('ic.komunitas'))->assertSee('Pengumuman Terjadwal');
    }

    public function test_komunitas_kosong_tidak_error(): void
    {
        $this->actingAs($this->anggota(), 'member')->get(route('ic.komunitas'))
            ->assertOk()
            ->assertSee('Belum ada pengumuman');

        $this->get(route('ic.komunitas', ['tab' => 'galeri']))
            ->assertOk()
            ->assertSee('Belum ada kenangan');
    }

    public function test_teks_pengumuman_di_escape(): void
    {
        $this->siar(['judul' => 'Uji <script>alert(1)</script>']);

        $this->actingAs($this->anggota(), 'member')->get(route('ic.komunitas'))
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_tombol_ke_route_yang_sudah_tidak_ada_tidak_membuat_halaman_error(): void
    {
        $this->siar(['judul' => 'Tujuan Hilang', 'cta_tujuan' => 'ic.sudah-dihapus']);

        $this->actingAs($this->anggota(), 'member')->get(route('ic.komunitas'))
            ->assertOk()
            ->assertSee('Tujuan Hilang')
            ->assertSee(route('ic.komunitas'), false);
    }

    public function test_tab_admin_komunitas_butuh_login_admin(): void
    {
        $this->get(route('ic.admin.pengumuman'))->assertRedirect(route('login'));
        $this->get(route('ic.admin.kenangan'))->assertRedirect(route('login'));
        $this->post(route('ic.admin.pengumuman.simpan'), $this->dataPengumuman())->assertRedirect(route('login'));
        $this->assertSame(0, ic_broadcast::count());
    }

    public function test_admin_menerbitkan_mengubah_dan_menghapus_pengumuman(): void
    {
        $this->admin()->post(route('ic.admin.pengumuman.simpan'), $this->dataPengumuman(['judul' => 'Judul Awal']))
            ->assertRedirect(route('ic.admin.pengumuman'))
            ->assertSessionHas('success');

        $p = ic_broadcast::firstOrFail();
        $this->assertSame('Judul Awal', $p->judul);
        $this->assertTrue($p->aktif);
        $this->assertFalse($p->unggulan);

        $this->admin()->put(route('ic.admin.pengumuman.ubah', $p->id_broadcast), $this->dataPengumuman(['judul' => 'Judul Baru', 'tag' => '']))
            ->assertRedirect(route('ic.admin.pengumuman'));
        $this->assertSame('Judul Baru', $p->fresh()->judul);
        $this->assertNull($p->fresh()->tag, 'kategori kosong disimpan sebagai null');

        $this->admin()->delete(route('ic.admin.pengumuman.hapus', $p->id_broadcast))->assertRedirect(route('ic.admin.pengumuman'));
        $this->assertSame(0, ic_broadcast::count());
    }

    public function test_hanya_satu_kabar_pilihan_dan_yang_lama_menjadi_biasa(): void
    {
        $lama = $this->siar(['judul' => 'Unggulan Lama', 'unggulan' => true]);

        $this->admin()->post(route('ic.admin.pengumuman.simpan'), $this->dataPengumuman(['judul' => 'Unggulan Baru', 'unggulan' => '1']))
            ->assertSessionHasNoErrors();

        $baru = ic_broadcast::where('judul', 'Unggulan Baru')->firstOrFail();
        $this->assertTrue($baru->unggulan);
        $this->assertFalse($lama->fresh()->unggulan);
        $this->assertSame(1, ic_broadcast::where('unggulan', true)->count());

        // Mengubah yang lama menjadi unggulan membalik keadaan, tetap satu.
        $this->admin()->put(route('ic.admin.pengumuman.ubah', $lama->id_broadcast), $this->dataPengumuman(['judul' => 'Unggulan Lama', 'unggulan' => '1']));
        $this->assertTrue($lama->fresh()->unggulan);
        $this->assertFalse($baru->fresh()->unggulan);
        $this->assertSame(1, ic_broadcast::where('unggulan', true)->count());

        // Menyimpan ulang pengumuman unggulan tanpa mengubah apa pun tidak menurunkan dirinya sendiri.
        $this->admin()->put(route('ic.admin.pengumuman.ubah', $lama->id_broadcast), $this->dataPengumuman(['judul' => 'Unggulan Lama', 'unggulan' => '1']));
        $this->assertTrue($lama->fresh()->unggulan);

        // Tidak mencentang = bukan unggulan.
        $this->admin()->put(route('ic.admin.pengumuman.ubah', $lama->id_broadcast), $this->dataPengumuman(['judul' => 'Unggulan Lama']));
        $this->assertFalse($lama->fresh()->unggulan);
    }

    public function test_pengumuman_tidak_valid_ditolak_dan_kembali_ke_mode_yang_sama(): void
    {
        $p = $this->siar();

        $this->admin()->post(route('ic.admin.pengumuman.simpan'), $this->dataPengumuman([
            'judul' => str_repeat('a', 81),
            'isi' => str_repeat('b', 221),
            'cta_tujuan' => 'ic.admin.ringkasan',
            'ikon' => 'tidak-ada',
            'warna' => 'ungu',
            'terbit_pada' => '23-10-2026',
            'cta_label' => '',
        ]))
            ->assertSessionHasErrors(['judul', 'isi', 'cta_tujuan', 'ikon', 'warna', 'terbit_pada', 'cta_label']);
        $this->assertSame(1, ic_broadcast::count());

        $respons = $this->admin()->put(route('ic.admin.pengumuman.ubah', $p->id_broadcast), $this->dataPengumuman(['judul' => '']));
        $respons->assertSessionHasErrors('judul');
        $this->assertStringContainsString('ubah=' . $p->id_broadcast, $respons->headers->get('Location'));
        $this->assertSame('Pengumuman uji', $p->fresh()->judul);

        // Halaman mode ubah memuat form PUT dengan isi pengumumannya.
        $this->admin()->get(route('ic.admin.pengumuman', ['ubah' => $p->id_broadcast]))
            ->assertOk()
            ->assertSee('Ubah pengumuman')
            ->assertSee('name="_method" value="PUT"', false)
            ->assertSee('Isi pengumuman uji.');
    }

    public function test_admin_menandai_pengumuman_terjadwal(): void
    {
        $this->siar(['judul' => 'Nanti Saja', 'terbit_pada' => today()->addWeek()->toDateString()]);

        $this->admin()->get(route('ic.admin.pengumuman'))->assertOk()->assertSee('Nanti Saja')->assertSee('Terjadwal');

        $this->admin()->post(route('ic.admin.pengumuman.simpan'), $this->dataPengumuman(['terbit_pada' => today()->addDays(3)->toDateString()]))
            ->assertSessionHas('success', fn($s) => str_contains($s, 'dijadwalkan'));
    }
}
