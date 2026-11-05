<?php

namespace Tests\Feature;

use App\Models\ic_broadcast;
use App\Models\ic_memory;
use App\Models\ic_pengaturan;
use App\Models\log_aktivitas;
use App\Models\member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 11: pratinjau langsung di tab Konten, lencana "belum dibaca" di Komunitas, logo/favicon/Open Graph resmi.
 */
class InnerCircleFase11Test extends TestCase
{
    use RefreshDatabase;

    private function admin()
    {
        return $this->withSession(['login' => true, 'user' => 'admin']);
    }

    private function anggota(string $nama = 'Ancha Uji', string $email = 'a@contoh.test'): member
    {
        $m = member::create(['nama' => $nama, 'email' => $email, 'password' => 'rahasia123']);
        $m->kode_member = sprintf('IC-%05d', $m->id_member);
        $m->email_verified_at = now();
        $m->save();

        return $m->fresh();
    }

    private function pengumuman(array $ubah = []): ic_broadcast
    {
        return ic_broadcast::create($ubah + [
            'judul' => 'Kabar uji',
            'isi' => 'Isi uji',
            'cta_label' => 'Lihat',
            'terbit_pada' => today()->toDateString(),
            'aktif' => true,
        ]);
    }

    private function kenangan(member $pemilik, array $ubah = []): ic_memory
    {
        return ic_memory::create($ubah + ['member_id' => $pemilik->id_member, 'caption' => 'Seru', 'kategori' => 'festival', 'status' => 'tayang']);
    }

    // ---------- Pratinjau ----------

    public function test_pratinjau_butuh_login_admin(): void
    {
        $this->post(route('ic.admin.konten.pratinjau'), ['grup' => 'beranda'])->assertRedirect(route('login'));
    }

    public function test_pratinjau_menampilkan_draf_tanpa_menyimpan_apa_pun(): void
    {
        $this->admin()->post(route('ic.admin.konten.pratinjau'), [
            'grup' => 'beranda',
            'k' => ['beranda__eyebrow' => 'Draf Eyebrow Baru', 'beranda__salam' => 'Halo {nama}!'],
        ])->assertOk()
            ->assertSee('Draf Eyebrow Baru')
            ->assertSee('Halo Anggota!') // anggota contoh
            ->assertSee('IC-00000');

        $this->assertSame(0, ic_pengaturan::count());
        $this->assertSame(0, member::count());
        $this->assertSame(0, log_aktivitas::count(), 'pratinjau bukan aksi tulis, jangan masuk log aktivitas');

        // Permintaan berikutnya tanpa draf kembali ke teks bawaan.
        $this->admin()->post(route('ic.admin.konten.pratinjau'), ['grup' => 'beranda'])
            ->assertSee('Whisnu Santika Official Fanbase')->assertDontSee('Draf Eyebrow Baru');
    }

    public function test_pratinjau_kosong_berarti_teks_bawaan_dan_draf_di_escape(): void
    {
        $this->admin()->post(route('ic.admin.konten.pratinjau'), [
            'grup' => 'beranda',
            'k' => ['beranda__eyebrow' => '   ', 'beranda__stiker' => '<script>alert(1)</script>'],
        ])->assertOk()
            ->assertSee('Whisnu Santika Official Fanbase')
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_semua_halaman_pratinjau_tampil(): void
    {
        foreach (config('ic_konten.grup') as $kode => $grup) {
            foreach (array_keys($grup['pratinjau']) as $halaman) {
                $this->admin()->post(route('ic.admin.konten.pratinjau'), ['grup' => $kode, 'halaman' => $halaman])
                    ->assertOk()
                    ->assertSee('</html>', false);
            }
        }
        $this->assertSame(0, member::count());
    }

    public function test_pratinjau_komunitas_tidak_menandai_dibaca_dan_menyiapkan_anggota_contoh(): void
    {
        $a = $this->anggota();
        $this->kenangan($a);

        $this->admin()->post(route('ic.admin.konten.pratinjau'), ['grup' => 'komunitas', 'halaman' => 'galeri'])->assertOk();

        $this->assertNull($a->fresh()->galeri_dibaca_pada);
        $this->assertSame(1, member::count());
    }

    public function test_pratinjau_menolak_grup_atau_halaman_tidak_dikenal_dan_tautan_dimatikan(): void
    {
        $this->admin()->post(route('ic.admin.konten.pratinjau'), ['grup' => 'tidak_ada'])->assertNotFound();
        $this->admin()->post(route('ic.admin.konten.pratinjau'), ['grup' => 'beranda', 'halaman' => 'hadiah'])->assertNotFound();
        $this->admin()->post(route('ic.admin.konten.pratinjau'), ['grup' => 'BAD!'])->assertSessionHasErrors('grup');

        $this->admin()->post(route('ic.admin.konten.pratinjau'), ['grup' => 'teaser', 'halaman' => 'teaser'])
            ->assertOk()->assertSee('getElementById("inner-circle")', false)->assertSee('preventDefault', false);
    }

    public function test_tiap_modul_konten_memuat_panel_pratinjau_dan_formnya_sendiri(): void
    {
        // Tiap modul (formulir kecil) punya satu panel pratinjau; tampilan/merek punya satu panel di bagiannya sendiri (Fase 6).
        foreach (array_keys(config('ic_konten.grup')) as $kode) {
            $modul = \App\Services\InnerCircle\IcBagian::modul($kode);
            $html = $this->admin()->get(route('ic.admin.konten.bagian', $kode))->assertOk()->getContent();
            $this->assertSame(count($modul), substr_count($html, '<iframe data-pratinjau-frame'), $kode);
            $this->assertStringContainsString(route('ic.admin.konten.pratinjau'), $html);
            foreach ($modul as $slug => $m) {
                if ($m['kunci'] !== []) {
                    $this->assertStringContainsString('id="form-' . $kode . '-' . $slug . '"', $html, "$kode/$slug");
                }
            }
        }

        $tampilan = $this->admin()->get(route('ic.admin.konten.bagian', 'tampilan'))->assertOk()->getContent();
        $this->assertSame(1, substr_count($tampilan, '<iframe data-pratinjau-frame'));
        $this->assertStringContainsString('id="form-tampilan"', $tampilan);
    }

    // ---------- Lencana belum dibaca ----------

    public function test_lencana_tersembunyi_bila_tidak_ada_yang_baru(): void
    {
        $m = $this->anggota();

        $this->actingAs($m, 'member')->get(route('ic.komunitas'))->assertOk()->assertDontSee('ic-tab__lencana');
    }

    public function test_isi_lama_sebelum_bergabung_tidak_jadi_lencana(): void
    {
        $penulis = $this->anggota('Penulis', 'p@contoh.test');
        $lama = $this->kenangan($penulis);
        $lama->forceFill(['created_at' => now()->subDay()])->save();
        $this->pengumuman()->forceFill(['created_at' => now()->subDay(), 'terbit_pada' => today()->subDay()->toDateString()])->save();

        $baru = $this->anggota('Anggota Baru', 'b@contoh.test');

        $this->actingAs($baru, 'member')->get(route('ic.komunitas', ['tab' => 'galeri']))->assertDontSee('ic-tab__lencana');
    }

    public function test_lencana_muncul_untuk_isi_baru_dan_hilang_setelah_tabnya_dibuka(): void
    {
        $m = $this->anggota('Pembaca', 'r@contoh.test');
        $m->forceFill(['created_at' => now()->subWeek(), 'pengumuman_dibaca_pada' => now()->subDay(), 'galeri_dibaca_pada' => now()->subDay()])->save();

        $penulis = $this->anggota('Penulis', 'p@contoh.test');
        $this->kenangan($penulis);
        $this->kenangan($penulis);
        $this->kenangan($m); // milik sendiri tidak dihitung
        $this->kenangan($penulis, ['status' => 'disembunyikan']); // tidak tayang tidak dihitung
        $this->pengumuman();

        // Tab Pengumuman terbuka: lencana pengumuman hilang, galeri masih menunjuk 2 kenangan baru.
        $html = $this->actingAs($m, 'member')->get(route('ic.komunitas'))->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, 'ic-tab__lencana'));
        $this->assertStringContainsString('>2<span class="ic-sr-saja"> belum dibaca', $html);
        $this->assertNotNull($m->fresh()->pengumuman_dibaca_pada);
        $this->assertTrue($m->fresh()->pengumuman_dibaca_pada->isToday());

        // Sudah dibaca: membuka lagi tidak memunculkan lencana pengumuman.
        $this->actingAs($m, 'member')->get(route('ic.komunitas'))->assertSee('>2<span class="ic-sr-saja">', false);

        // Membuka galeri menghabiskan lencana galeri.
        $this->actingAs($m, 'member')->get(route('ic.komunitas', ['tab' => 'galeri']))->assertOk();
        $this->actingAs($m, 'member')->get(route('ic.komunitas'))->assertDontSee('ic-tab__lencana');
    }

    public function test_pengumuman_terjadwal_baru_dihitung_setelah_tanggalnya_tiba(): void
    {
        $m = $this->anggota();
        $m->forceFill(['pengumuman_dibaca_pada' => now()->subDay()])->save();
        $this->pengumuman(['terbit_pada' => today()->addDays(3)->toDateString()])->forceFill(['created_at' => now()->subDays(5)])->save();

        $this->actingAs($m, 'member')->get(route('ic.komunitas', ['tab' => 'galeri']))->assertDontSee('ic-tab__lencana');

        $this->travel(4)->days();
        $this->actingAs($m->fresh(), 'member')->get(route('ic.komunitas', ['tab' => 'galeri']))->assertSee('>1<span class="ic-sr-saja">', false);
    }

    // ---------- Logo, favicon, Open Graph ----------

    public function test_berkas_aset_resmi_ada(): void
    {
        foreach (
            [
                'aset/og/og-ws-artis.jpg',
                'aset/og/og-ws-inner-circle.jpg',
                'aset/inner-circle/logo/wsic-wordmark.webp',
                'aset/inner-circle/logo/wsic-wordmark-kompak.webp',
                'favicon.ico',
            ] as $berkas
        ) {
            $this->assertFileExists(public_path($berkas));
            $this->assertGreaterThan(0, filesize(public_path($berkas)), $berkas);
        }
        foreach (['ws-artis', 'ws-inner-circle'] as $merek) {
            foreach (['favicon.ico', 'favicon-16x16.png', 'favicon-32x32.png', 'apple-touch-icon.png', 'android-chrome-192x192.png', 'android-chrome-512x512.png', 'site.webmanifest'] as $berkas) {
                $this->assertFileExists(public_path("aset/favicon/$merek/$berkas"));
            }
        }
        [$lebar, $tinggi] = getimagesize(public_path('aset/og/og-ws-inner-circle.jpg'));
        $this->assertSame([1200, 630], [$lebar, $tinggi]);
    }

    public function test_situs_artis_memakai_og_dan_favicon_artis(): void
    {
        $this->get(route('home'))->assertOk()
            ->assertSee('<meta property="og:image" content="' . asset('aset/og/og-ws-artis.jpg') . '">', false)
            ->assertSee('aset/favicon/ws-artis/favicon-32x32.png', false)
            ->assertSee('aset/favicon/ws-artis/apple-touch-icon.png', false)
            ->assertDontSee('aset/favicon/ws-inner-circle', false);
    }

    public function test_inner_circle_memakai_og_logo_dan_favicon_sendiri(): void
    {
        $this->get(route('ic.tentang'))->assertOk()
            ->assertSee('<meta property="og:image" content="' . asset('aset/og/og-ws-inner-circle.jpg') . '">', false)
            ->assertSee('twitter:image', false)
            ->assertSee('aset/favicon/ws-inner-circle/favicon.ico', false)
            ->assertSee('aset/inner-circle/logo/wsic-wordmark.webp', false);

        $this->get(route('ic.masuk'))->assertOk()
            ->assertSee('aset/favicon/ws-inner-circle/apple-touch-icon.png', false)
            ->assertSee('og-ws-inner-circle.jpg', false);

        $this->actingAs($this->anggota(), 'member')->get(route('ic.beranda'))->assertOk()
            ->assertSee('aset/favicon/ws-inner-circle/site.webmanifest', false);
    }
}