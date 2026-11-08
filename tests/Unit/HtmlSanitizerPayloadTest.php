<?php

namespace Tests\Unit;

use App\Support\HtmlSanitizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Payload XSS yang umum dipakai: semuanya harus lolos disaring (konten bio, news, dan kebijakan privasi
 * ditulis mentah ke halaman publik lewat HtmlSanitizer).
 */
class HtmlSanitizerPayloadTest extends TestCase
{
    public static function payload(): array
    {
        return [
            'img onerror' => ['<img src=x onerror=alert(1)>'],
            'href javascript dengan tab' => ['<a href="jav&#x09;ascript:alert(1)">x</a>'],
            'href javascript huruf campur' => ['<a href="  JaVaScRiPt:alert(1)">x</a>'],
            'href data' => ['<a href="data:text/html;base64,PHNjcmlwdD4=">x</a>'],
            'href protokol-relatif' => ['<a href="//evil.test">x</a>'],
            'style url javascript' => ['<p style="background:url(javascript:alert(1))">x</p>'],
            'style expression' => ['<span style="background-color:expression(alert(1))">x</span>'],
            'svg script' => ['<svg><script>alert(1)</script></svg>'],
            'mutation math/style' => ['<math><mtext><table><mglyph><style><img src=x onerror=alert(1)>'],
            'noscript' => ['<noscript><p title="</noscript><img src=x onerror=alert(1)>">'],
            'form autofocus' => ['<form><input autofocus onfocus=alert(1)></form>'],
            'script di komentar' => ['<div><b>teks</b></div><!-- komentar --><script>alert(1)</script>'],
            'onclick di tautan sah' => ['<a href="https://ok.test" onclick="x()">x</a>'],
        ];
    }

    #[DataProvider('payload')]
    public function test_payload_xss_dinetralkan(string $masukan): void
    {
        $hasil = strtolower(HtmlSanitizer::clean($masukan));

        foreach (['<script', '<img', '<svg', '<math', '<form', '<input', '<style', 'onerror', 'onclick', 'onfocus', 'javascript:', 'data:', 'expression('] as $terlarang) {
            $this->assertStringNotContainsString($terlarang, $hasil, "Sisa berbahaya `{$terlarang}` pada: {$masukan}");
        }
        $this->assertDoesNotMatchRegularExpression('/href="\/\//', $hasil);
    }

    public function test_tautan_sah_dan_gaya_warna_tetap_lolos(): void
    {
        $hasil = HtmlSanitizer::clean('<a href="https://ok.test" target="_blank">x</a><span style="color:red; font-size:99px">y</span>');

        $this->assertStringContainsString('href="https://ok.test"', $hasil);
        $this->assertStringContainsString('rel="noopener noreferrer"', $hasil);
        $this->assertStringContainsString('color: red', $hasil);
        $this->assertStringNotContainsString('font-size', $hasil);
    }
}
