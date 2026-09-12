<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * Sanitizer HTML buat konten rich-text dari Quill (news_description & bio.konten).
 *
 * Sengaja dibikin sendiri (pure PHP, cuma pakai ext-dom bawaan) daripada narik
 * package composer kayak ezyang/htmlpurifier — soalnya hosting-nya (Rumahweb,
 * shared, tanpa akses terminal) bikin `composer install` di server nggak bisa
 * dijalankan; vendor/ package baru harus di-generate lokal terus di-upload
 * manual, ribet buat 1 fitur kecil. Kalau ke depannya emang perlu HTMLPurifier
 * beneran, tinggal ganti isi method clean() di bawah ini.
 *
 * Strategi: default-deny. Cuma tag & atribut yang EKSPLISIT di-allowlist yang
 * lolos. Semua yang lain — <script>, <iframe>, atribut onclick/onerror/dst,
 * href="javascript:...", dll — otomatis kebuang karena bukan bagian allowlist,
 * bukan karena di-detect satu-satu.
 *
 * Allowlist tag di bawah disesuaikan sama toolbar Quill yang dipakai di
 * project ini (lihat bio.blade.php & dashboard-pages/news.blade.php):
 * bold/italic/underline/strike, header 1-3, list, indent, align, color,
 * background, blockquote, code-block, link.
 */
class HtmlSanitizer
{
    /** Tag yang boleh lewat → daftar atribut yang boleh dipakai di tag itu. */
    private const ALLOWED_TAGS = [
        'p' => ['class'],
        'br' => [],
        'strong' => [],
        'b' => [],
        'em' => [],
        'i' => [],
        'u' => [],
        's' => [],
        'strike' => [],
        'h1' => ['class'],
        'h2' => ['class'],
        'h3' => ['class'],
        'ol' => ['class'],
        'ul' => ['class'],
        'li' => ['class'],
        'blockquote' => [],
        'pre' => [],
        'code' => [],
        'span' => ['style', 'class'],
        'a' => ['href', 'target', 'rel'],
    ];

    /** Tag yang kalau ketemu, dibuang TOTAL beserta isinya (bukan cuma tag-nya). */
    private const STRIP_WITH_CONTENT = ['script', 'style', 'iframe', 'object', 'embed', 'form'];

    /** Prefix class yang diizinkan — cuma class bawaan Quill (align/indent). */
    private const ALLOWED_CLASS_PREFIXES = ['ql-align-', 'ql-indent-', 'ql-'];

    /** Properti CSS yang diizinkan di atribut style (buat warna teks/background Quill). */
    private const ALLOWED_STYLE_PROPS = ['color', 'background-color'];

    public static function clean(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $dom = new DOMDocument();

        libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<?xml encoding="utf-8"?><html>

<body>' . $html . '</body>

</html>',
            LIBXML_NOERROR | LIBXML_NOWARNING
        );
        libxml_clear_errors();

        $body = $dom->getElementsByTagName('body')->item(0);
        if (!$body) {
            return '';
        }

        self::cleanChildren($body);

        $output = '';
        foreach (iterator_to_array($body->childNodes) as $child) {
            $output .= $dom->saveHTML($child);
        }

        return trim($output);
    }

    private static function cleanChildren(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMText) {
                continue; // teks polos aman, biarin lewat
            }

            if (!$child instanceof DOMElement) {
                $node->removeChild($child); // comment/cdata/dst — buang
                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::STRIP_WITH_CONTENT, true)) {
                $node->removeChild($child);
                continue;
            }

            if (!array_key_exists($tag, self::ALLOWED_TAGS)) {
                // Tag nggak dikenal/nggak diizinkan → buang tag-nya doang,
                // isinya (teks di dalamnya) diangkat naik biar nggak hilang.
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }

            self::cleanAttributes($child, self::ALLOWED_TAGS[$tag]);
            self::cleanChildren($child);
        }
    }

    private static function cleanAttributes(DOMElement $el, array $allowedAttrs): void
    {
        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->name);

            if (!in_array($name, $allowedAttrs, true)) {
                $el->removeAttribute($attr->name);
                continue;
            }

            match ($name) {
                'href' => self::cleanHref($el),
                'class' => self::cleanClass($el),
                'style' => self::cleanStyle($el),
                default => null,
            };
        }

        // Link yang buka tab baru wajib rel="noopener noreferrer" (anti tabnabbing)
        if ($el->tagName === 'a' && $el->getAttribute('target') === '_blank') {
            $el->setAttribute('rel', 'noopener noreferrer');
        }
    }

    private static function cleanHref(DOMElement $el): void
    {
        $value = trim($el->getAttribute('href'));
        if (!preg_match('/^(https?:|mailto:)/i', $value)) {
            $el->removeAttribute('href'); // buang javascript:, data:, vbscript:, dll
        }
    }

    private static function cleanClass(DOMElement $el): void
    {
        $classes = array_filter(explode(' ', $el->getAttribute('class')), function ($class) {
            foreach (self::ALLOWED_CLASS_PREFIXES as $prefix) {
                if ($class !== '' && str_starts_with($class, $prefix)) {
                    return true;
                }
            }
            return false;
        });

        if (empty($classes)) {
            $el->removeAttribute('class');
        } else {
            $el->setAttribute('class', implode(' ', $classes));
        }
    }

    private static function cleanStyle(DOMElement $el): void
    {
        $safeDeclarations = [];

        foreach (explode(';', $el->getAttribute('style')) as $declaration) {
            $parts = array_map('trim', explode(':', $declaration, 2));
            if (count($parts) !== 2) {
                continue;
            }

            [$prop, $value] = $parts;
            $prop = strtolower($prop);

            $isSafeValue = preg_match('/^#[0-9a-f]{3,8}$|^rgba?\([0-9,.\s%]+\)$|^[a-z]+$/i', $value);

            if (in_array($prop, self::ALLOWED_STYLE_PROPS, true) && $isSafeValue) {
                $safeDeclarations[] = "{$prop}: {$value}";
            }
        }

        if (empty($safeDeclarations)) {
            $el->removeAttribute('style');
        } else {
            $el->setAttribute('style', implode('; ', $safeDeclarations));
        }
    }
}
