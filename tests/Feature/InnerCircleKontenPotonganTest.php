<?php

namespace Tests\Feature;

use App\Models\member;
use App\Services\InnerCircle\IcBagian;
use App\Services\InnerCircle\IcKonten;
use App\Services\InnerCircle\IcPotongan;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pratinjau tab Konten memakai potongan halaman ASLI (view yang sama dengan halaman anggota), bukan replika tulisan tangan,
 * supaya admin melihat teks di posisi yang sama dengan yang dilihat anggota.
 */
class InnerCircleKontenPotonganTest extends TestCase
{
    use RefreshDatabase;

    private function admin()
    {
        return $this->withSession(['login' => true, 'user' => 'admin']);
    }

    private function xpath(string $html): DOMXPath
    {
        $dok = new DOMDocument('1.0', 'UTF-8');
        $dulu = libxml_use_internal_errors(true);
        $dok->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        libxml_use_internal_errors($dulu);

        return new DOMXPath($dok);
    }

    public function test_setiap_isian_modul_punya_tempat_di_pratinjau_dan_penanda_tidak_bocor(): void
    {
        foreach (config('ic_konten.grup') as $kode => $info) {
            $slug = collect(IcBagian::semua())->search(fn($b) => $b['kode'] === $kode && $b['jenis'] === 'grup');
            $html = $this->admin()->get(route('ic.admin.konten.bagian', $slug))->assertOk()->getContent();

            $this->assertStringNotContainsString("\u{E000}", $html, "$kode: penanda bocor");
            $this->assertStringNotContainsString("\u{E001}", $html, "$kode: penanda bocor");
            $this->assertStringNotContainsString("\u{E002}", $html, "$kode: penanda bocor");

            $xp = $this->xpath($html);
            foreach (IcBagian::modul($kode) as $slugModul => $m) {
                foreach ($m['kunci'] as $kunci) {
                    $kolom = str_replace('.', '__', $kunci);
                    $this->assertGreaterThan(
                        0,
                        $xp->query('//section[@id="modul-' . $kode . '-' . $slugModul . '"]//aside//*[@data-replika-teks="' . $kolom . '"]')->length,
                        "$kode/$slugModul: isian $kunci tidak punya tempat di pratinjau",
                    );
                }
            }
        }
    }

    public function test_pratinjau_memakai_susunan_halaman_asli_dan_isian_tersimpan(): void
    {
        app(IcKonten::class)->simpanMentah(['tentang.hero.judul' => 'Judul Uji Coba']);
        app()->forgetInstance(IcKonten::class);

        $html = $this->admin()->get(route('ic.admin.konten.bagian', 'tentang'))->assertOk()->getContent();
        $xp = $this->xpath($html);

        // Susunan asli: judul hero ada di dalam section.ic-hero bersama tombolnya, bukan blok berlabel terpisah.
        $judul = $xp->query('//section[@id="modul-tentang-hero"]//aside//section[contains(@class,"ic-hero")]//h1//*[@data-replika-teks="tentang__hero__judul"]');
        $this->assertSame(1, $judul->length);
        /** @var \DOMElement $span */
        $span = $judul->item(0);
        $this->assertSame('Judul Uji Coba', trim($span->textContent));
        $this->assertSame('WS Inner Circle', $span->getAttribute('data-bawaan'));
        $this->assertGreaterThan(0, $xp->query('//section[@id="modul-tentang-hero"]//aside//section[contains(@class,"ic-hero")]//a[contains(@class,"ic-tombol")]')->length);
        // Kontrol di dalam pratinjau tidak bisa diklik.
        $this->assertGreaterThan(0, $xp->query('//section[@id="modul-tentang-hero"]//aside//div[@inert]')->length);
        // Pratinjau selalu berskala layar HP (lebar dikunci), bukan mengikuti lebar panel.
        $this->assertGreaterThan(0, $xp->query('//section[@id="modul-tentang-hero"]//aside//div[@inert and contains(@class,"ic-ponsel")]')->length);
    }

    public function test_isian_yang_tidak_tampil_di_contoh_dilaporkan_dan_tetap_punya_pratinjau(): void
    {
        // Tombol "buka" hanya tampil untuk anggota yang sudah masuk, jadi tidak ada di potongan tamu.
        $html = $this->admin()->get(route('ic.admin.konten.bagian', 'tentang'))->assertOk()->getContent();
        $xp = $this->xpath($html);

        $this->assertGreaterThan(0, $xp->query('//section[@id="modul-tentang-hero"]//aside//*[@data-replika-teks="tentang__tombol__buka"]')->length);
        $this->assertStringContainsString('hanya tampil pada kondisi tertentu', $html);
    }

    public function test_membuka_pratinjau_tidak_menulis_ke_database(): void
    {
        $this->admin()->get(route('ic.admin.konten.bagian', 'komunitas'))->assertOk();
        $this->admin()->get(route('ic.admin.konten.bagian', 'profil'))->assertOk();
        $this->admin()->get(route('ic.admin.konten.bagian', 'beranda'))->assertOk();

        $this->assertSame(0, member::query()->count());
    }

    public function test_teaser_tetap_memakai_replika_sederhana(): void
    {
        $html = $this->admin()->get(route('ic.admin.konten.bagian', 'teaser'))->assertOk()->getContent();

        $this->assertStringContainsString('data-replika-blok', $html);
        $this->assertStringNotContainsString('ic-replika--halaman', $html);
    }

    // ---------- IcPotongan (tanpa database) ----------

    public function test_jadikan_kait_membuang_penanda_di_atribut_dan_membuat_span(): void
    {
        $svc = app(IcPotongan::class);
        $A = IcPotongan::AWAL;
        $B = IcPotongan::AKHIR;
        $C = IcPotongan::TUTUP;

        $html = "<html><head><title>{$A}tentang.meta.judul{$B}Judul{$C}</title></head><body>"
            . "<a aria-label=\"{$A}tentang.hero.judul{$B}Label{$C}\" href=\"#\">x</a>"
            . "<h1>{$A}tentang.hero.judul{$B}Halo<br>Dunia{$C}</h1></body></html>";

        $hasil = $svc->jadikanKait($html);

        $this->assertStringNotContainsString("\u{E000}", $hasil);
        $this->assertStringNotContainsString("\u{E002}", $hasil);
        $this->assertStringContainsString('aria-label="Label"', $hasil);
        $this->assertStringContainsString('<span data-replika-teks="tentang__hero__judul" data-bawaan="WS Inner Circle">Halo<br>Dunia</span>', $hasil);
        $this->assertStringNotContainsString('<title>', $hasil);
    }

    public function test_potong_hanya_menyisakan_blok_yang_memuat_isian_beserta_pembungkusnya(): void
    {
        $svc = app(IcPotongan::class);
        $html = '<div class="ic-shell"><header>Atas</header><main class="ic-konten">'
            . '<section class="ic-hero"><h1 id="x" autofocus><span data-replika-teks="tentang__hero__judul" data-bawaan="b">Judul</span></h1></section>'
            . '<section class="lain"><p>Bagian lain</p></section><script>alert(1)</script></main></div>';

        $hasil = $svc->potong($html, ['tentang.hero.judul', 'tentang.tombol.buka']);

        $this->assertNotNull($hasil);
        $this->assertSame(['tentang.tombol.buka'], $hasil['hilang']);
        $this->assertStringContainsString('class="ic-shell"', $hasil['html']);
        $this->assertStringContainsString('class="ic-hero"', $hasil['html']);
        $this->assertStringNotContainsString('Bagian lain', $hasil['html']);
        $this->assertStringNotContainsString('Atas', $hasil['html']);
        $this->assertStringNotContainsString('<script', $hasil['html']);
        $this->assertStringNotContainsString('autofocus', $hasil['html']);
        $this->assertStringNotContainsString('id="x"', $hasil['html']);

        $this->assertNull($svc->potong($html, ['tentang.tombol.buka']));
        $this->assertNull($svc->potong('', ['tentang.hero.judul']));
    }
}