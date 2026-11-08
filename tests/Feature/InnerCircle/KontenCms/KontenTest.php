<?php

namespace Tests\Feature\InnerCircle\KontenCms;

use App\Models\ic_item;
use App\Models\ic_pengaturan;
use App\Models\member;
use App\Services\InnerCircle\IcKonten;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Tab Konten: teks halaman anggota yang bisa diubah dari CMS.
 */
class KontenTest extends TestCase
{
    use RefreshDatabase;

    private function admin()
    {
        return $this->withSession(['login' => true, 'user' => 'admin']);
    }

    private function konten(): IcKonten
    {
        return app(IcKonten::class);
    }

    private function anggota(): member
    {
        $m = member::create(['nama' => 'Anggota Uji', 'email' => 'a@contoh.test', 'password' => 'rahasia123']);
        $m->kode_member = sprintf('IC-%05d', $m->id_member);
        $m->email_verified_at = now();
        $m->save();

        return $m;
    }

    private function kirim(array $isi, string $grup = 'checkin')
    {
        return $this->admin()->post(route('ic.admin.konten.simpan', $grup), ['k' => $isi]);
    }

    public function test_tabel_kosong_memakai_teks_bawaan(): void
    {
        $this->assertSame('Jadwal Pertunjukan WS', $this->konten()->teks('checkin.jadwal.judul'));
        $this->assertSame(0, ic_pengaturan::count());
    }

    public function test_simpan_mengubah_halaman_anggota_dan_kembalikan_bawaan_membatalkannya(): void
    {
        $m = $this->anggota();
        $this->kirim(['checkin__jadwal__judul' => 'Tur Akhir Tahun'])->assertSessionHasNoErrors();

        $this->actingAs($m, 'member')->get(route('ic.checkin'))->assertOk()->assertSee('Tur Akhir Tahun');
        $this->assertSame('Tur Akhir Tahun', ic_pengaturan::where('kunci', 'checkin.jadwal.judul')->value('nilai'));

        $this->admin()->post(route('ic.admin.konten.reset', ['checkin', 'checkin.jadwal.judul']))->assertSessionHasNoErrors();

        $this->assertSame(0, ic_pengaturan::count());
        $this->actingAs($m, 'member')->get(route('ic.checkin'))->assertSee('Jadwal Pertunjukan WS');
    }

    public function test_isian_kosong_atau_sama_dengan_bawaan_tidak_membuat_baris_dan_menghapus_yang_lama(): void
    {
        $this->kirim(['checkin__jadwal__judul' => 'Tur Akhir Tahun']);
        $this->assertSame(1, ic_pengaturan::count());

        // Disamakan dengan bawaan = pakai bawaan lagi: barisnya dihapus.
        $this->kirim(['checkin__jadwal__judul' => 'Jadwal Pertunjukan WS']);
        $this->assertSame(0, ic_pengaturan::count());

        $this->kirim(['checkin__jadwal__judul' => 'Tur Lagi']);
        $this->kirim(['checkin__jadwal__judul' => '']);
        $this->assertSame(0, ic_pengaturan::count());
    }

    public function test_perubahan_langsung_terbaca_meski_sudah_pernah_dibaca_dan_di_cache(): void
    {
        $this->assertSame('Jadwal Pertunjukan WS', $this->konten()->teks('checkin.jadwal.judul')); // mengisi cache
        $this->kirim(['checkin__jadwal__judul' => 'Judul Baru']);

        $this->assertSame('Judul Baru', $this->konten()->teks('checkin.jadwal.judul'));
    }

    public function test_tanda_kurang_dan_lebih_dari_ditolak(): void
    {
        $this->kirim(['checkin__jadwal__judul' => 'Halo <script>alert(1)</script>'])
            ->assertSessionHasErrors('k.checkin__jadwal__judul');

        $this->assertSame(0, ic_pengaturan::count());
    }

    public function test_batas_panjang_ditolak(): void
    {
        $this->kirim(['checkin__eyebrow' => str_repeat('a', 61)])->assertSessionHasErrors('k.checkin__eyebrow');
        $this->assertSame(0, ic_pengaturan::count());
    }

    public function test_placeholder_program_diganti_dan_placeholder_asing_ditolak(): void
    {
        $this->kirim(['checkin__eyebrow' => 'Selamat datang di {program}'])->assertSessionHasNoErrors();
        $this->assertSame('Selamat datang di WS Inner Circle', $this->konten()->teks('checkin.eyebrow'));

        $this->kirim(['checkin__eyebrow' => 'Halo {nama}'])->assertSessionHasErrors('k.checkin__eyebrow');
    }

    public function test_teks_ditampilkan_ter_escape_di_halaman_anggota(): void
    {
        $this->kirim(['checkin__jadwal__judul' => 'Tom & "Jerry"'])->assertSessionHasNoErrors();

        $html = $this->actingAs($this->anggota(), 'member')->get(route('ic.checkin'))->assertOk()->getContent();

        $this->assertStringContainsString('Tom &amp; &quot;Jerry&quot;', $html);
        $this->assertStringNotContainsString('Tom & "Jerry"', $html);
    }

    public function test_spasi_berlebih_dirapikan_dan_isian_satu_baris_tidak_berbaris(): void
    {
        $this->kirim(['checkin__eyebrow' => "  Datang   \n  lebih awal  "]);

        $this->assertSame('Datang lebih awal', $this->konten()->teks('checkin.eyebrow'));
    }

    public function test_hanya_admin_dan_grup_tidak_dikenal_404(): void
    {
        $this->get(route('ic.admin.konten'))->assertRedirect(route('login'));
        $this->post(route('ic.admin.konten.simpan', 'checkin'), ['k' => ['checkin__eyebrow' => 'x']])->assertRedirect(route('login'));

        $this->admin()->post(route('ic.admin.konten.simpan', 'tidak_ada'), ['k' => ['x' => 'y']])->assertNotFound();
        $this->admin()->post(route('ic.admin.konten.reset', ['checkin', 'tidak.ada']))->assertNotFound();
    }

    public function test_halaman_konten_menampilkan_isian_bawaan_dan_penanda_diubah(): void
    {
        $this->admin()->get(route('ic.admin.konten.bagian', 'checkin'))
            ->assertOk()
            ->assertSee('Halaman Check-in')
            ->assertSee('Jadwal Pertunjukan WS')
            ->assertDontSee('Diubah');

        $this->kirim(['checkin__jadwal__judul' => 'Tur Akhir Tahun']);

        $this->admin()->get(route('ic.admin.konten.bagian', 'checkin'))
            ->assertOk()
            ->assertSee('Tur Akhir Tahun')
            ->assertSee('Diubah')
            ->assertSee('Kembalikan bawaan');
    }

    public function test_tabel_belum_ada_tidak_menjatuhkan_halaman(): void
    {
        Schema::drop('ic_pengaturan');

        $this->assertSame('Jadwal Pertunjukan WS', $this->konten()->teks('checkin.jadwal.judul'));
    }

    public function test_kunci_tidak_terdaftar_ketahuan_saat_pengembangan(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->konten()->teks('kunci.salah.ketik');
    }

    public function test_daftar_item_aktif_berurutan_dan_ikut_diperbarui(): void
    {
        // Grup khusus tes: daftar langkah/faq/teaser_poin sudah berisi bawaan dari migrasi (Fase 3).
        ic_item::create(['grup' => 'uji', 'urutan' => 2, 'judul' => 'Kedua']);
        ic_item::create(['grup' => 'uji', 'urutan' => 1, 'judul' => 'Pertama']);
        ic_item::create(['grup' => 'uji', 'urutan' => 3, 'judul' => 'Tersembunyi', 'aktif' => false]);
        ic_item::create(['grup' => 'uji_lain', 'urutan' => 1, 'judul' => 'Lain grup']);

        $this->assertSame(['Pertama', 'Kedua'], $this->konten()->daftar('uji')->pluck('judul')->all());
        $this->assertSame([], $this->konten()->daftar('tidak-ada')->all());

        ic_item::where('judul', 'Kedua')->update(['judul' => 'Kedua (ubah)']);
        $this->konten()->lupakan();
        $this->assertSame('Kedua (ubah)', $this->konten()->daftar('uji')[1]->judul);
    }

    public function test_setiap_kunci_di_view_terdaftar_dan_setiap_kunci_config_dipakai_view(): void
    {
        $dipakai = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views')));
        foreach ($iterator as $berkas) {
            if (! str_ends_with((string) $berkas, '.blade.php')) {
                continue;
            }
            preg_match_all("/ic_(?:teks|html)\\(\\s*'([^']+)'/", file_get_contents((string) $berkas), $cocok);
            foreach ($cocok[1] as $kunci) {
                $dipakai[$kunci] = (string) $berkas;
            }
        }

        $terdaftar = array_keys(config('ic_konten.kunci'));

        // Kunci di view yang tidak ada di config akan melempar galat saat pengembangan.
        $this->assertSame([], array_values(array_diff(array_keys($dipakai), $terdaftar)));
        // Kunci di config yang tidak dipakai view = isian CMS yang tidak berpengaruh apa-apa.
        $this->assertSame([], array_values(array_diff($terdaftar, array_keys($dipakai))));
    }

    public function test_tab_konten_ada_di_menu_cms_inner_circle(): void
    {
        $html = $this->admin()->get(route('ic.admin.konten'))->assertOk()->getContent();

        $this->assertStringContainsString('href="' . route('ic.admin.konten') . '"', $html);
        $this->assertStringContainsString('class="cms-ic', $html);
    }
}
