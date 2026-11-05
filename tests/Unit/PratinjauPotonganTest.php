<?php

namespace Tests\Unit;

use App\Services\InnerCircle\PratinjauPotongan;
use PHPUnit\Framework\TestCase;

/** Pemotong pratinjau (tab Konten): murni DOM, tanpa database dan tanpa Laravel. */
class PratinjauPotonganTest extends TestCase
{
    private function halaman(): string
    {
        $a = PratinjauPotongan::AWAL;
        $z = PratinjauPotongan::AKHIR;

        return "<!DOCTYPE html><html lang=\"id\"><head><meta charset=\"utf-8\">"
            . "<title>{$a}tentang.judul_tab{$z}Judul Tab</title>"
            . "<meta name=\"description\" content=\"{$a}tentang.deskripsi{$z}Deskripsi Pencarian\">"
            . "<link rel=\"stylesheet\" href=\"/gaya.css\"><script>alert('kepala')</script></head>"
            . "<body class=\"ic-app tema\"><nav>Menu atas</nav><main class=\"konten\">"
            . "<section id=\"hero\"><h1>{$a}tentang.judul{$z}Halo é — Dunia</h1><p>{$a}tentang.isi{$z}Penjelasan</p></section>"
            . "<section id=\"faq\"><h2>{$a}tentang.faq{$z}Tanya Jawab</h2></section>"
            . "<div>{$a}tentang.lepas{$z}Teks lepas</div></main><footer>Kaki halaman</footer>"
            . "<div class=\"dock\">DOCK</div><script>alert('badan')</script></body></html>";
    }

    public function test_hanya_bagian_yang_memuat_teks_yang_dipertahankan(): void
    {
        $hasil = PratinjauPotongan::potong($this->halaman(), 'tentang.judul');

        $this->assertSame('', $hasil['catatan']);
        $this->assertStringContainsString('Halo é — Dunia', $hasil['html']);
        $this->assertStringContainsString('Penjelasan', $hasil['html']); // satu section yang sama
        foreach (['Tanya Jawab', 'Menu atas', 'Kaki halaman', 'DOCK', 'Teks lepas'] as $buang) {
            $this->assertStringNotContainsString($buang, $hasil['html'], $buang);
        }
    }

    public function test_pembungkus_asli_dan_css_dipertahankan(): void
    {
        $html = PratinjauPotongan::potong($this->halaman(), 'tentang.faq')['html'];

        $this->assertStringContainsString('class="ic-app tema"', $html);
        $this->assertStringContainsString('<main class="konten">', $html);
        $this->assertStringContainsString('/gaya.css', $html);
    }

    public function test_teks_yang_diubah_diberi_sorotan(): void
    {
        $html = PratinjauPotongan::potong($this->halaman(), 'tentang.judul')['html'];

        $this->assertStringContainsString('data-ic-sorot="1"', $html);
    }

    public function test_skrip_halaman_dibuang_dan_penanda_tidak_bocor(): void
    {
        foreach (['tentang.judul', 'tentang.faq', 'tentang.lepas', 'tentang.judul_tab', 'tidak.ada'] as $kunci) {
            $html = PratinjauPotongan::potong($this->halaman(), $kunci)['html'];

            $this->assertStringNotContainsString("alert('", $html, $kunci);
            $this->assertDoesNotMatchRegularExpression('/\x{E000}|\x{E001}/u', $html, $kunci);
        }
    }

    public function test_teks_lepas_di_main_tetap_bisa_dipotong(): void
    {
        $html = PratinjauPotongan::potong($this->halaman(), 'tentang.lepas')['html'];

        $this->assertStringContainsString('Teks lepas', $html);
        $this->assertStringNotContainsString('Tanya Jawab', $html);
    }

    public function test_teks_yang_hanya_ada_di_tab_browser_jadi_kartu_pencarian(): void
    {
        $hasil = PratinjauPotongan::potong($this->halaman(), 'tentang.judul_tab');

        $this->assertSame(PratinjauPotongan::CATATAN_MESIN_PENCARI, $hasil['catatan']);
        $this->assertStringContainsString('Judul Tab', $hasil['html']);
        $this->assertStringContainsString('Deskripsi Pencarian', $hasil['html']);
        $this->assertStringNotContainsString('Menu atas', $hasil['html']);
    }

    public function test_teks_yang_tidak_tampil_jatuh_ke_bagian_atas_halaman_dengan_catatan(): void
    {
        $hasil = PratinjauPotongan::potong($this->halaman(), 'tentang.tidak_ada');

        $this->assertSame(PratinjauPotongan::CATATAN_ATAS, $hasil['catatan']);
        $this->assertStringContainsString('Halo é — Dunia', $hasil['html']);
        $this->assertStringNotContainsString('Kaki halaman', $hasil['html']);
    }

    public function test_potong_atas_mengambil_bagian_pertama_yang_berisi(): void
    {
        $html = PratinjauPotongan::potongAtas($this->halaman());

        $this->assertStringContainsString('Halo é — Dunia', $html);
        $this->assertStringNotContainsString('Tanya Jawab', $html);
    }

    public function test_html_buruk_tidak_menjatuhkan_pemotong(): void
    {
        $hasil = PratinjauPotongan::potong('<div><p>tanpa body & <b>tidak ditutup', 'a.b');

        $this->assertIsString($hasil['html']);
        $this->assertSame(PratinjauPotongan::CATATAN_ATAS, $hasil['catatan']);
    }
}