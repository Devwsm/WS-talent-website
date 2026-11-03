<?php

namespace App\Services\InnerCircle;

use App\Support\HtmlSanitizer;
use Illuminate\Support\Facades\DB;

/**
 * Isi kebijakan privasi (/privasi) yang bisa diubah dari CMS (tab Konten, bagian Kebijakan privasi).
 *
 * Isinya HTML dari editor Quill, SELALU disaring HtmlSanitizer saat disimpan (dan sekali lagi saat dibaca, jaga-jaga bila
 * baris diubah manual di database). Tanpa baris `privasi.isi`, halaman memakai teks bawaan di
 * resources/views/pages/privasi-bawaan.blade.php. Tanggal "Diperbarui" terisi otomatis dengan hari penyimpanan.
 */
class IcPrivasi
{
    public const KUNCI_ISI = 'privasi.isi';
    public const KUNCI_TANGGAL = 'privasi.diperbarui';

    public function __construct(private readonly IcKonten $konten) {}

    /** HTML aman isi kustom, atau null bila belum ada (halaman memakai teks bawaan). */
    public function isi(): ?string
    {
        $mentah = $this->konten->nilaiMentah(self::KUNCI_ISI);
        if ($mentah === null) {
            return null;
        }

        $bersih = HtmlSanitizer::clean($mentah);

        return self::kosong($bersih) ? null : $bersih;
    }

    /** Tanggal "Diperbarui" (YYYY-MM-DD): hari penyimpanan terakhir, atau tanggal di config/site.php. */
    public function diperbarui(): string
    {
        $tanggal = $this->konten->nilaiMentah(self::KUNCI_TANGGAL);

        return ($tanggal !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) === 1 && strtotime($tanggal) !== false)
            ? $tanggal
            : (string) config('site.privasi_diperbarui');
    }

    public function sudahKustom(): bool
    {
        return $this->isi() !== null;
    }

    /** HTML bawaan (dari blade) sebagai isi awal editor. */
    public function bawaanHtml(): string
    {
        return HtmlSanitizer::clean(view('pages.privasi-bawaan', ['kontak' => config('site.kontak_privasi')])->render());
    }

    /** Teks kosong secara kasat mata (tanpa tag, spasi, dan &nbsp;)? */
    public static function kosong(?string $html): bool
    {
        $teks = html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(str_replace("\u{00A0}", ' ', $teks)) === '';
    }

    /** Simpan isi baru dan perbarui tanggalnya. @return string HTML yang tersimpan (sudah bersih) */
    public function simpan(string $html, ?string $oleh = null): string
    {
        $bersih = HtmlSanitizer::clean($html);
        $lama = $this->konten->nilaiMentah(self::KUNCI_ISI);

        DB::transaction(function () use ($bersih, $lama, $oleh) {
            $tulis = [self::KUNCI_ISI => $bersih];
            if ($lama !== $bersih) {
                $tulis[self::KUNCI_TANGGAL] = now()->toDateString();
            }
            $this->konten->simpanMentah($tulis, $oleh);
        });

        return $bersih;
    }

    public function kembalikanBawaan(?string $oleh = null): int
    {
        return $this->konten->simpanMentah([self::KUNCI_ISI => null, self::KUNCI_TANGGAL => null], $oleh);
    }
}