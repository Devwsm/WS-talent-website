<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Unduhan CSV untuk laporan admin, dibuat agar langsung rapi saat dibuka di Excel dan Google Sheets:
 * - BOM UTF-8 supaya huruf beraksen dan emoji terbaca benar di Excel.
 * - Pemisah titik koma: Excel dengan pengaturan wilayah Indonesia memakai `;` (koma dipakai sebagai desimal).
 *   Google Sheets mengenali keduanya.
 * - Sel teks yang diawali = + - @ (atau tab / enter) diberi tanda petik di depan, supaya isian pengguna (mis. nama
 *   "=HYPERLINK(...)") tidak dijalankan sebagai rumus saat file dibuka ("CSV injection"). Angka tidak diubah.
 * - Ditulis bertahap (stream), jadi daftar besar tidak menghabiskan memori.
 */
class CsvEkspor
{
    public const PEMISAH = ';';

    /**
     * @param  string[]  $judulKolom
     * @param  iterable<array<int, scalar|null>>  $baris
     */
    public static function unduh(string $namaFile, array $judulKolom, iterable $baris): StreamedResponse
    {
        $namaFile = preg_replace('/[^A-Za-z0-9._-]+/', '-', $namaFile) ?: 'ekspor.csv';

        return response()->streamDownload(function () use ($judulKolom, $baris) {
            $keluar = fopen('php://output', 'w');
            fwrite($keluar, "\xEF\xBB\xBF");
            fputcsv($keluar, array_map([self::class, 'aman'], $judulKolom), self::PEMISAH, '"', '\\', "\r\n");
            foreach ($baris as $b) {
                fputcsv($keluar, array_map([self::class, 'aman'], $b), self::PEMISAH, '"', '\\', "\r\n");
            }
            fclose($keluar);
        }, $namaFile, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, private', // berisi data pribadi anggota: jangan disimpan cache perantara
        ]);
    }

    /** Netralkan awalan rumus pada teks; angka dan nilai kosong dibiarkan. */
    public static function aman(mixed $nilai): mixed
    {
        if (! is_string($nilai) || $nilai === '') {
            return $nilai;
        }

        return in_array($nilai[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'" . $nilai : $nilai;
    }
}