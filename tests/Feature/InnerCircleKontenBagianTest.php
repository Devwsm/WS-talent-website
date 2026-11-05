<?php

namespace Tests\Feature;

use App\Models\ic_pengaturan;
use App\Services\InnerCircle\IcBagian;
use App\Services\InnerCircle\IcKonten;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Fase B dan C (UI/UX CMS): tab Konten dipecah per bagian dan modul kecil, dan pratinjau hanya menampilkan potongan yang diubah. */
class InnerCircleKontenBagianTest extends TestCase
{
    use RefreshDatabase;

    private function admin()
    {
        return $this->withSession(['login' => true, 'user' => 'admin']);
    }

    // ---------- Fase B: bagian dan navbar atas ----------

    public function test_semua_bagian_terdaftar_dan_punya_halaman(): void
    {
        $bagian = IcBagian::semua();

        // Grup teks + tipe, kategori, aturan, tampilan, privasi.
        $this->assertCount(count(config('ic_konten.grup')) + 5, $bagian);

        foreach (array_keys($bagian) as $slug) {
            $this->admin()->get(route('ic.admin.konten.bagian', $slug))->assertOk();
        }
    }

    public function test_butuh_login_dan_bagian_tidak_dikenal_404(): void
    {
        $this->get(route('ic.admin.konten.bagian', 'tentang'))->assertRedirect(route('login'));
        $this->get(route('ic.admin.konten'))->assertRedirect(route('login'));

        $this->admin()->get(route('ic.admin.konten.bagian', 'tidak-ada'))->assertNotFound();
    }

    public function test_halaman_konten_langsung_membuka_bagian_pertama_tanpa_halaman_pilihan(): void
    {
        $pertama = array_key_first(IcBagian::semua());

        $html = $this->admin()->get(route('ic.admin.konten'))->assertOk()->getContent();

        $this->assertStringContainsString('id="grup-' . $pertama . '"', $html);
        $this->assertStringContainsString('id="modul-' . $pertama . '-', $html);
        $this->assertStringNotContainsString('Semua bagian konten', $html);
        $this->assertStringNotContainsString('Sebelumnya', $html);
    }

    public function test_navbar_atas_memuat_semua_bagian_mengalir_dan_menandai_yang_aktif(): void
    {
        $html = $this->admin()->get(route('ic.admin.konten.bagian', 'beranda'))->assertOk()->getContent();

        foreach (IcBagian::semua() as $slug => $b) {
            $this->assertStringContainsString('href="' . IcBagian::url($slug) . '"', $html, $slug);
        }

        $this->assertStringContainsString('class="cms-chips"', $html);
        $this->assertSame(1, substr_count($html, 'aria-current="page"'));
        $this->assertStringNotContainsString('overflow-x-auto', $html);
    }

    public function test_navbar_atas_menampilkan_jumlah_isian_yang_sudah_diubah(): void
    {
        $this->admin()->post(route('ic.admin.konten.simpan', 'checkin'), ['k' => ['checkin__jadwal__judul' => 'Tur Akhir Tahun']]);

        $this->admin()->get(route('ic.admin.konten.bagian', 'tentang'))->assertOk()
            ->assertSee('title="1 isian sudah diubah"', false);
    }

    public function test_tautan_lama_dengan_jangkar_dialihkan_oleh_peta(): void
    {
        $peta = IcBagian::petaJangkarLama();

        $this->assertSame(IcBagian::url('tentang'), $peta['grup-tentang']);
        $this->assertSame(IcBagian::url('tentang', 'daftar-faq'), $peta['daftar-faq']);
        $this->assertSame(IcBagian::url('tipe-acara'), $peta['pilihan-tipe']);
        $this->assertSame(IcBagian::url('kategori-kenangan'), $peta['pilihan-kategori']);
        $this->assertSame(IcBagian::url('aturan-checkin'), $peta['aturan-checkin']);
        $this->assertSame(IcBagian::url('privasi'), $peta['privasi']);

        $html = $this->admin()->get(route('ic.admin.konten'))->assertOk()->getContent();
        $this->assertStringContainsString('"grup-tentang"', $html);
        $this->assertStringContainsString('location.replace', $html);
    }

    public function test_bagian_hanya_memuat_isi_topiknya_sendiri(): void
    {
        $html = $this->admin()->get(route('ic.admin.konten.bagian', 'tentang'))->assertOk()->getContent();

        $this->assertStringContainsString('id="grup-tentang"', $html);
        $this->assertStringNotContainsString('id="grup-beranda"', $html);
        $this->assertStringNotContainsString('id="privasi"', $html);
        $this->assertStringNotContainsString('id="tampilan"', $html);
    }

    public function test_bagian_dan_alamat_pengembali_untuk_daftar_dan_pilihan(): void
    {
        $this->assertSame('tentang', IcBagian::untukDaftar('faq'));
        $this->assertSame('tentang', IcBagian::untukDaftar('langkah'));
        $this->assertSame('teaser', IcBagian::untukDaftar('teaser_poin'));
        $this->assertSame('tipe-acara', IcBagian::untukPilihan('tipe'));
        $this->assertSame('kategori-kenangan', IcBagian::untukPilihan('kategori'));
    }

    // ---------- Fase B: modul ----------

    public function test_setiap_kunci_teks_ada_di_tepat_satu_modul_dan_daftar_terpasang(): void
    {
        // Kunci teks memuat titik, jadi dibaca dari array-nya (config('a.b.c') memecah titik menjadi tingkat bersarang).
        $definisi = config('ic_konten.kunci');
        $terpakai = [];
        $daftarTerpasang = [];

        foreach (config('ic_konten.grup') as $kode => $info) {
            $this->assertNotSame([], IcBagian::modul($kode), "grup $kode tidak punya modul");

            foreach (IcBagian::modul($kode) as $slug => $m) {
                foreach ($m['kunci'] as $kunci) {
                    $this->assertArrayNotHasKey($kunci, $terpakai, "$kunci ada di dua modul");
                    $terpakai[$kunci] = $kode;
                    $this->assertSame($kode, $definisi[$kunci]['grup'] ?? null, "$kunci bukan milik grup $kode");
                }
                if ($m['daftar']) {
                    $daftarTerpasang[] = $m['daftar'];
                }
                // Potongan pratinjau ditentukan kunci ini, jadi harus kunci yang nyata.
                $this->assertArrayHasKey($m['fokus'], $definisi, "fokus $kode/$slug tidak dikenal");
            }

            $this->assertEqualsCanonicalizing($info['daftar'] ?? [], array_values(array_filter(array_map(fn($m) => $m['daftar'], IcBagian::modul($kode)))), "daftar grup $kode");
        }

        $this->assertEqualsCanonicalizing(array_keys(config('ic_konten.kunci')), array_keys($terpakai), 'ada kunci teks yang tidak punya modul');
    }

    public function test_tiap_modul_punya_form_dan_pratinjau_sendiri(): void
    {
        $html = $this->admin()->get(route('ic.admin.konten.bagian', 'tentang'))->assertOk()->getContent();
        $modul = IcBagian::modul('tentang');

        foreach ($modul as $slug => $m) {
            $this->assertStringContainsString('id="modul-tentang-' . $slug . '"', $html);
            if ($m['kunci'] !== []) {
                $this->assertStringContainsString('id="form-tentang-' . $slug . '"', $html);
            }
        }
        $this->assertSame(count($modul), substr_count($html, '<iframe data-pratinjau-frame'));
        // Modul tidak punya menu pilihan halaman (menu hanya untuk pratinjau seluruh grup).
        $this->assertStringNotContainsString('id="pilih-pratinjau-', $html);
    }

    public function test_menyimpan_satu_modul_tidak_menyentuh_isian_modul_lain(): void
    {
        $this->admin()->post(route('ic.admin.konten.simpan', 'checkin'), ['modul' => 'jadwal', 'k' => ['checkin__jadwal__judul' => 'Tur Akhir Tahun']]);
        $this->admin()->post(route('ic.admin.konten.simpan', 'checkin'), ['modul' => 'lokasi', 'k' => ['checkin__lokasi__judul' => 'Sudah sampai']]);

        $konten = app(IcKonten::class);
        $this->assertSame('Tur Akhir Tahun', $konten->teks('checkin.jadwal.judul'));
        $this->assertSame('Sudah sampai', $konten->teks('checkin.lokasi.judul'));
    }

    public function test_setelah_menyimpan_admin_kembali_ke_modul_yang_sama(): void
    {
        $this->admin()->post(route('ic.admin.konten.simpan', 'checkin'), ['modul' => 'jadwal', 'k' => ['checkin__jadwal__judul' => 'Tur']])
            ->assertRedirect(IcBagian::url('checkin', 'modul-checkin-jadwal'))->assertSessionHas('success');

        // Nama modul yang aneh diabaikan: kembali ke bagian saja, tanpa jangkar.
        $this->admin()->post(route('ic.admin.konten.simpan', 'checkin'), ['modul' => '"><x', 'k' => ['checkin__jadwal__judul' => 'Tur 2']])
            ->assertRedirect(IcBagian::url('checkin'));

        $this->admin()->post(route('ic.admin.konten.reset', ['checkin', 'checkin.jadwal.judul']), ['modul' => 'jadwal'])
            ->assertRedirect(IcBagian::url('checkin', 'modul-checkin-jadwal'));
    }

    // ---------- Fase C: pratinjau potongan ----------

    public function test_pratinjau_dengan_fokus_hanya_menampilkan_potongan_yang_memuat_teks_itu(): void
    {
        $penuh = $this->admin()->post(route('ic.admin.konten.pratinjau'), [
            'grup' => 'beranda',
            'k' => ['beranda__eyebrow' => 'Draf Eyebrow Potongan'],
        ])->assertOk()->getContent();

        $res = $this->admin()->post(route('ic.admin.konten.pratinjau'), [
            'grup' => 'beranda',
            'fokus' => 'beranda__eyebrow',
            'k' => ['beranda__eyebrow' => 'Draf Eyebrow Potongan'],
        ])->assertOk()->assertSee('Draf Eyebrow Potongan')->assertSee('data-ic-sorot', false);

        $potongan = $res->getContent();
        $this->assertLessThan(strlen($penuh), strlen($potongan));
        $this->assertStringNotContainsString("\u{E000}", $potongan);
        $this->assertStringNotContainsString("\u{E001}", $potongan);
        $this->assertStringContainsString('icPratinjauTinggi', $potongan);
        $this->assertStringNotContainsString('ic-dock', $potongan);
    }

    public function test_potongan_memakai_teks_bawaan_bila_isian_kosong(): void
    {
        $this->admin()->post(route('ic.admin.konten.pratinjau'), [
            'grup' => 'beranda',
            'fokus' => 'beranda__eyebrow',
            'k' => ['beranda__eyebrow' => ''],
        ])->assertOk()->assertSee('Whisnu Santika Official Fanbase');
    }

    public function test_pratinjau_tanpa_halaman_mencari_halaman_yang_memuat_teksnya(): void
    {
        // "daftar.judul" hanya ada di halaman Daftar (bukan halaman Masuk yang dicoba lebih dulu).
        $this->admin()->post(route('ic.admin.konten.pratinjau'), [
            'grup' => 'masuk',
            'fokus' => 'daftar__judul',
            'k' => ['daftar__judul' => 'Judul Daftar Draf'],
        ])->assertOk()->assertSee('Judul Daftar Draf');
    }

    public function test_pratinjau_potongan_tidak_menulis_apa_pun_dan_penanda_mati_lagi(): void
    {
        $this->admin()->post(route('ic.admin.konten.pratinjau'), [
            'grup' => 'beranda',
            'fokus' => 'beranda__eyebrow',
            'k' => ['beranda__eyebrow' => 'Draf'],
        ])->assertOk();

        $this->assertSame(0, ic_pengaturan::count());

        $konten = app(IcKonten::class);
        $this->assertStringNotContainsString("\u{E000}", $konten->teks('beranda.eyebrow'));
        $konten->pakaiPenanda();
        $this->assertStringContainsString("\u{E000}beranda.eyebrow\u{E001}", $konten->teks('beranda.eyebrow'));
        $konten->lupakanDraf();
        $this->assertStringNotContainsString("\u{E000}", $konten->teks('beranda.eyebrow'));
    }

    public function test_fokus_tidak_dikenal_atau_tidak_valid_ditolak(): void
    {
        $this->admin()->post(route('ic.admin.konten.pratinjau'), ['grup' => 'beranda', 'fokus' => 'tidak__ada'])->assertNotFound();
        $this->admin()->post(route('ic.admin.konten.pratinjau'), ['grup' => 'beranda', 'fokus' => 'BAD!'])->assertSessionHasErrors('fokus');
    }

    public function test_pratinjau_tampilan_menampilkan_bagian_atas_halaman_contoh(): void
    {
        $this->admin()->post(route('ic.admin.konten.pratinjau'), [
            'grup' => 'tampilan',
            'fokus' => 'tampilan__program',
            'k' => ['tampilan__program' => 'Whisnu Crew'],
        ])->assertOk()->assertSee('Whisnu Crew');
    }

    public function test_panel_pratinjau_modul_siap_untuk_potongan(): void
    {
        $html = $this->admin()->get(route('ic.admin.konten.bagian', 'beranda'))->assertOk()->getContent();

        $this->assertStringContainsString('Tampilan di halaman', $html);
        $this->assertStringContainsString('data-pratinjau-catatan', $html);
        $this->assertStringContainsString('data-fokus="beranda__eyebrow"', $html);
        $this->assertStringContainsString('data-form="form-beranda-sapaan"', $html);
        $this->assertStringContainsString("data.set('fokus', fokus)", $html);
        $this->assertStringContainsString('icPratinjauTinggi', $html);
    }

    public function test_modul_hanya_daftar_tetap_punya_pratinjau_tanpa_form(): void
    {
        $html = $this->admin()->get(route('ic.admin.konten.bagian', 'teaser'))->assertOk()->getContent();

        $this->assertStringContainsString('data-fokus="teaser__judul"', $html);
        $this->assertStringNotContainsString('id="form-teaser-poin"', $html);
        $this->assertStringContainsString('id="daftar-teaser_poin"', $html);
    }
}