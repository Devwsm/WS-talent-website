<?php

namespace Tests\Unit;

use App\Support\HtmlSanitizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * HtmlSanitizer menjaga teks rich-text (berita dan bio) dari XSS. Daftar payload di sini sengaja memuat kasus
 * yang dulu lolos: elemen tak dikenal yang "dibuka bungkusnya" (svg, math, noscript) tidak ikut dibersihkan.
 */
class HtmlSanitizerTest extends TestCase
{
    public static function payloadBerbahaya(): array
    {
        return [
            'img onerror' => ['<img src=x onerror=alert(1)>'],
            'script biasa' => ['<script>alert(1)</script>'],
            'script di dalam svg' => ['<svg><script>alert(1)</script></svg>'],
            'mathml dan style' => ['<math><mtext><table><mglyph><style><img src=x onerror=alert(1)>'],
            'noscript dengan atribut' => ['<noscript><p title="</noscript><img src=x onerror=alert(1)>">x</p>'],
            'iframe' => ['<iframe src="https://evil.test"></iframe>'],
            'form dan details' => ['<form action=x><input></form><details open ontoggle=alert(1)>d</details>'],
            'atribut event di tag yang diizinkan' => ['<p onclick="alert(1)" onmouseover="alert(2)">x</p>'],
            'atribut event di tag yang dibuka' => ['<div onclick="alert(1)"><p>x</p></div>'],
        ];
    }

    #[DataProvider('payloadBerbahaya')]
    public function test_payload_berbahaya_dibersihkan(string $payload): void
    {
        $hasil = strtolower(HtmlSanitizer::clean($payload));

        foreach (['<script', '<svg', '<math', '<iframe', '<img', '<style', '<form', '<input', 'onerror', 'onclick', 'onmouseover', 'ontoggle', 'javascript:'] as $terlarang) {
            $this->assertStringNotContainsString($terlarang, $hasil, "Hasil masih memuat {$terlarang}: {$hasil}");
        }
    }

    public function test_atribut_di_tag_yang_dibuka_tetap_diperiksa(): void
    {
        // Dulu: <p> di dalam tag tak dikenal lolos membawa atribut title/onclick karena tidak diperiksa ulang.
        $hasil = HtmlSanitizer::clean('<section><p title="x" onclick="y()" class="ql-align-center evil">teks</p></section>');

        $this->assertSame('<p class="ql-align-center">teks</p>', $hasil);
    }

    public function test_link_berbahaya_kehilangan_href_tapi_link_aman_tetap(): void
    {
        $this->assertSame('<a>x</a>', HtmlSanitizer::clean('<a href="javascript:alert(1)">x</a>'));
        $this->assertSame('<a>x</a>', HtmlSanitizer::clean('<a href="jav&#x09;ascript:alert(1)">x</a>'));
        $this->assertSame('<a>x</a>', HtmlSanitizer::clean('<a href="data:text/html;base64,PHNjcmlwdD4=">x</a>'));
        $this->assertSame('<a href="https://contoh.test">x</a>', HtmlSanitizer::clean('<a href="https://contoh.test">x</a>'));
        $this->assertSame('<a href="mailto:a@b.test">x</a>', HtmlSanitizer::clean('<a href="mailto:a@b.test">x</a>'));
    }

    public function test_link_tab_baru_selalu_noopener(): void
    {
        $hasil = HtmlSanitizer::clean('<a href="https://contoh.test" target="_blank">x</a>');

        $this->assertStringContainsString('rel="noopener noreferrer"', $hasil);
    }

    public function test_style_hanya_warna_yang_aman(): void
    {
        $this->assertSame('<span style="color: red">x</span>', HtmlSanitizer::clean('<span style="color:red;background-image:url(javascript:alert(1))">x</span>'));
        $this->assertSame('<span>x</span>', HtmlSanitizer::clean('<span style="color:expression(alert(1))">x</span>'));
    }

    public function test_format_quill_yang_sah_tidak_rusak(): void
    {
        $html = '<h2>Judul</h2><p class="ql-align-center">Halo <strong>dunia</strong> dan <em>semua</em></p><ul><li>satu</li><li>dua</li></ul>';

        $this->assertSame($html, HtmlSanitizer::clean($html));
    }

    public function test_masukan_kosong(): void
    {
        $this->assertSame('', HtmlSanitizer::clean(null));
        $this->assertSame('', HtmlSanitizer::clean('   '));
    }
}