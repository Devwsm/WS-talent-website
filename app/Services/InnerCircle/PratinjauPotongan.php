<?php

namespace App\Services\InnerCircle;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Pratinjau potongan (CMS Inner Circle, tab Konten).
 *
 * Halaman asli dirender dengan penanda tak terlihat di depan tiap teks konten (IcKonten::pakaiPenanda), lalu kelas ini
 * memotong HTML-nya: hanya bagian halaman yang memuat teks yang sedang diubah yang dipertahankan, lengkap dengan
 * pembungkus aslinya (supaya CSS tetap kena) tetapi tanpa saudara-saudaranya. Teks itu diberi garis putus-putus.
 *
 * Kelas ini murni PHP (DOMDocument): tidak menyentuh database, request, maupun config.
 */
class PratinjauPotongan
{
    public const AWAL = "\u{E000}";

    public const AKHIR = "\u{E001}";

    /** Elemen yang layak jadi "satu potongan": cukup besar untuk dimengerti, cukup kecil untuk tidak memuat seluruh halaman. */
    private const BLOK = ['section', 'header', 'footer', 'article', 'aside', 'form', 'li', 'dialog'];

    /** Teks yang ditampilkan sebagai kartu kecil bila isinya hanya ada di tab browser atau deskripsi pencarian. */
    public const CATATAN_ATAS = 'Teks ini tidak tampil di badan halaman, jadi yang ditampilkan adalah bagian atas halaman.';

    public const CATATAN_MESIN_PENCARI = 'Teks ini tampil di tab browser dan hasil pencarian.';

    /** Kata kunci hasil: html yang sudah dipotong dan catatan (kosong bila potongan tepat sasaran). */
    public static function potong(string $html, string $kunci): array
    {
        $dok = self::muat($html);
        $penanda = self::AWAL . $kunci . self::AKHIR;
        $xpath = new DOMXPath($dok);

        $simpul = $xpath->query('//body//text()[contains(., ' . self::literal($penanda) . ')]');
        if ($simpul !== false && $simpul->length > 0) {
            $teks = $simpul->item(0);
            $sorot = $teks->parentNode;
            $blok = self::cariBlok($sorot);
            if ($sorot instanceof DOMElement) {
                $sorot->setAttribute('data-ic-sorot', '1');
            }

            return ['html' => self::keluarkan($dok, $blok), 'catatan' => ''];
        }

        // Hanya ada di <title> atau <meta> (judul tab dan deskripsi pencarian): tampilkan kartu hasil pencarian.
        if (self::adaDiKepala($xpath, $penanda)) {
            return ['html' => self::kartuMesinPencari($dok), 'catatan' => self::CATATAN_MESIN_PENCARI];
        }

        return ['html' => self::potongAtas($html), 'catatan' => self::CATATAN_ATAS];
    }

    /** Potongan bagian atas halaman: dipakai saat tidak ada teks yang bisa ditunjuk (mis. pratinjau warna dan merek). */
    public static function potongAtas(string $html): string
    {
        $dok = self::muat($html);
        $xpath = new DOMXPath($dok);

        $kandidat = $xpath->query('//body//*[self::header or self::section or self::article or self::form][string-length(normalize-space(.)) > 20]');
        $blok = $kandidat !== false && $kandidat->length > 0 ? $kandidat->item(0) : null;

        if ($blok === null) {
            $anak = $xpath->query('//body/*[string-length(normalize-space(.)) > 0]');
            $blok = $anak !== false && $anak->length > 0 ? $anak->item(0) : null;
        }

        return self::keluarkan($dok, $blok);
    }

    /** Buang semua penanda dari HTML akhir (juga yang berada di dalam atribut). */
    public static function bersihkan(string $html): string
    {
        return preg_replace('/\x{E000}[a-z0-9_.]*\x{E001}/u', '', $html) ?? $html;
    }

    private static function muat(string $html): DOMDocument
    {
        $dok = new DOMDocument('1.0', 'UTF-8');
        $dulu = libxml_use_internal_errors(true);
        $dok->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($dulu);

        // Buang instruksi <?xml ...> yang kita tambahkan sendiri supaya UTF-8 terbaca.
        foreach (iterator_to_array($dok->childNodes) as $anak) {
            if ($anak->nodeType === XML_PI_NODE) {
                $dok->removeChild($anak);
            }
        }

        return $dok;
    }

    private static function literal(string $teks): string
    {
        // Kunci hanya berisi huruf kecil, angka, titik, dan garis bawah, jadi kutip tunggal aman.
        return "'" . str_replace("'", '', $teks) . "'";
    }

    private static function cariBlok(DOMNode $dari): DOMNode
    {
        $atas = $dari;
        for ($node = $dari; $node !== null && $node->nodeName !== 'body'; $node = $node->parentNode) {
            if (in_array(strtolower($node->nodeName), self::BLOK, true)) {
                return $node;
            }
            if ($node->parentNode !== null && strtolower($node->parentNode->nodeName) === 'body') {
                $atas = $node;
            }
        }

        // Tidak ada pembungkus yang dikenal: pakai anak langsung <main> bila ada, kalau tidak anak langsung <body>.
        for ($node = $dari; $node !== null; $node = $node->parentNode) {
            $induk = $node->parentNode;
            if ($induk !== null && strtolower($induk->nodeName) === 'main') {
                return $node;
            }
        }

        return $atas;
    }

    private static function adaDiKepala(DOMXPath $xpath, string $penanda): bool
    {
        $literal = self::literal($penanda);
        $jumlah = $xpath->query('//head//title[contains(., ' . $literal . ')] | //head//meta[contains(@content, ' . $literal . ')]');

        return $jumlah !== false && $jumlah->length > 0;
    }

    /** Hanya mempertahankan $blok dan rantai pembungkusnya; saudara-saudaranya dibuang, begitu pula skrip halaman. */
    private static function keluarkan(DOMDocument $dok, ?DOMNode $blok): string
    {
        $xpath = new DOMXPath($dok);
        foreach (iterator_to_array($xpath->query('//script | //noscript') ?: []) as $skrip) {
            $skrip->parentNode?->removeChild($skrip);
        }

        $body = $dok->getElementsByTagName('body')->item(0);
        if ($blok !== null && $body !== null) {
            for ($node = $blok; $node !== null && $node !== $body; $node = $node->parentNode) {
                $induk = $node->parentNode;
                if ($induk === null) {
                    break;
                }
                foreach (iterator_to_array($induk->childNodes) as $saudara) {
                    if ($saudara !== $node) {
                        $induk->removeChild($saudara);
                    }
                }
            }
        }

        // saveHTML() pada elemen akar menghasilkan UTF-8 apa adanya (pada dokumen penuh, huruf non-ASCII jadi entitas).
        $akar = $dok->documentElement;

        return self::bersihkan("<!DOCTYPE html>\n" . ($akar !== null ? $dok->saveHTML($akar) : ''));
    }

    private static function kartuMesinPencari(DOMDocument $dok): string
    {
        $xpath = new DOMXPath($dok);
        $judul = trim((string) $xpath->evaluate('string(//head/title)'));
        $deskripsi = trim((string) $xpath->evaluate('string(//head/meta[@name="description"]/@content)'));
        $judul = self::bersihkan($judul);
        $deskripsi = self::bersihkan($deskripsi);

        $kartu = '<div style="font-family:system-ui,sans-serif;padding:20px;max-width:640px">'
            . '<p style="margin:0 0 4px;font-size:12px;color:#5f6368">Hasil pencarian</p>'
            . '<p style="margin:0;font-size:20px;line-height:1.3;color:#1a0dab" data-ic-sorot="1">' . self::esc($judul) . '</p>'
            . '<p style="margin:6px 0 0;font-size:14px;line-height:1.5;color:#4d5156" data-ic-sorot="1">' . self::esc($deskripsi) . '</p>'
            . '</div>';

        return '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body style="margin:0;background:#fff">' . $kartu . '</body></html>';
    }

    private static function esc(string $teks): string
    {
        return htmlspecialchars($teks, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}