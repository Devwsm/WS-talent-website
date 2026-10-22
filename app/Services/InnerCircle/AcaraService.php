<?php

namespace App\Services\InnerCircle;

use App\Models\ic_event;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Pembuatan acara dan impor jadwal dari CSV (dipakai dashboard CMS dan seeder contoh).
 */
class AcaraService
{
    public const KOLOM_CSV = ['kode', 'tanggal', 'kota', 'venue', 'tipe'];

    /** Kode otomatis seperti di data contoh: WS26-1008 (tahun 2 digit, bulan, hari); kembar diberi akhiran -B, -C, ... */
    public function buatKode(Carbon $tanggal): string
    {
        $dasar = 'WS' . $tanggal->format('y') . '-' . $tanggal->format('md');
        $kode = $dasar;
        $akhiran = 'B';

        while (ic_event::query()->where('kode', $kode)->exists()) {
            $kode = $dasar . '-' . $akhiran++;
            if ($akhiran > 'Z') {
                throw new InvalidArgumentException('Terlalu banyak acara di tanggal yang sama.');
            }
        }

        return $kode;
    }

    /** @param  array{tanggal: string, tipe: string, kota?: ?string, venue?: ?string, jendela_mulai?: ?string, jendela_selesai?: ?string}  $data */
    public function buat(array $data, ?string $kode = null): ic_event
    {
        $tanggal = Carbon::parse($data['tanggal'])->startOfDay();

        return ic_event::create([
            'kode' => $kode ?: $this->buatKode($tanggal),
            'tanggal' => $tanggal->toDateString(),
            'kota' => $data['kota'] ?? null,
            'venue' => $data['venue'] ?? null,
            'tipe' => $data['tipe'],
            'token' => ic_event::buatToken(),
            'token_dibuat_pada' => now(),
            'jendela_mulai' => $data['jendela_mulai'] ?? null,
            'jendela_selesai' => $data['jendela_selesai'] ?? null,
        ]);
    }

    /**
     * Impor jadwal dari CSV (kolom: kode, tanggal, kota, venue, tipe). Semua atau tidak sama sekali:
     * satu baris salah membatalkan seluruh impor. Kode yang sudah ada diperbarui (token tidak berubah).
     *
     * @return array{dibuat: int, diperbarui: int, galat: array<int, string>}
     */
    public function imporCsv(string $jalur, int $maksBaris = 500): array
    {
        $buka = fopen($jalur, 'r');
        if ($buka === false) {
            return ['dibuat' => 0, 'diperbarui' => 0, 'galat' => ['Berkas tidak bisa dibaca.']];
        }

        $baris = [];
        $galat = [];
        $judul = null;
        $nomor = 0;

        while (($kolom = fgetcsv($buka, 0, ',', '"', '\\')) !== false) {
            $nomor++;
            if ($kolom === [null] || $kolom === []) {
                continue;
            }

            if ($judul === null) {
                // Buang tanda urutan byte (BOM) yang sering ditambahkan Excel di awal berkas.
                $kolom[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $kolom[0]);
                $judul = array_map(fn($k) => strtolower(trim((string) $k)), $kolom);
                $hilang = array_diff(self::KOLOM_CSV, $judul);
                if ($hilang) {
                    fclose($buka);

                    return ['dibuat' => 0, 'diperbarui' => 0, 'galat' => ['Kolom wajib belum lengkap. Harus ada: ' . implode(', ', self::KOLOM_CSV) . '.']];
                }

                continue;
            }

            if (count($baris) >= $maksBaris) {
                $galat[] = "Terlalu banyak baris (maksimal {$maksBaris}).";
                break;
            }

            $kolom = array_pad($kolom, count($judul), '');
            $data = array_combine($judul, array_slice($kolom, 0, count($judul)));
            $data = array_map(fn($v) => trim((string) $v), $data);

            $tanggal = null;
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['tanggal'])) {
                try {
                    $tanggal = Carbon::createFromFormat('!Y-m-d', $data['tanggal']);
                } catch (\Throwable) {
                    $tanggal = null;
                }
            }

            if (! $tanggal || $tanggal->format('Y-m-d') !== $data['tanggal']) {
                $galat[] = "Baris {$nomor}: tanggal harus berformat YYYY-MM-DD.";
            }
            if (! array_key_exists(strtolower($data['tipe']), config('inner_circle.tipe_event'))) {
                $galat[] = "Baris {$nomor}: tipe harus festival, club, atau private.";
            }
            if (mb_strlen($data['kode']) > 20 || mb_strlen($data['kota']) > 60 || mb_strlen($data['venue']) > 80) {
                $galat[] = "Baris {$nomor}: kode, kota, atau venue terlalu panjang.";
            }

            $baris[] = [
                'kode' => $data['kode'],
                'tanggal' => $tanggal,
                'kota' => $data['kota'] !== '' ? $data['kota'] : null,
                'venue' => $data['venue'] !== '' ? $data['venue'] : null,
                'tipe' => strtolower($data['tipe']),
            ];
        }
        fclose($buka);

        if ($judul === null) {
            return ['dibuat' => 0, 'diperbarui' => 0, 'galat' => ['Berkas kosong.']];
        }
        if (! $baris && ! $galat) {
            return ['dibuat' => 0, 'diperbarui' => 0, 'galat' => ['Tidak ada baris jadwal di berkas ini.']];
        }
        if ($galat) {
            return ['dibuat' => 0, 'diperbarui' => 0, 'galat' => $galat];
        }

        $dibuat = 0;
        $diperbarui = 0;

        DB::transaction(function () use ($baris, &$dibuat, &$diperbarui) {
            foreach ($baris as $b) {
                $ada = $b['kode'] !== '' ? ic_event::query()->where('kode', $b['kode'])->first() : null;

                if ($ada) {
                    $ada->update([
                        'tanggal' => $b['tanggal']->toDateString(),
                        'kota' => $b['kota'],
                        'venue' => $b['venue'],
                        'tipe' => $b['tipe'],
                    ]);
                    $diperbarui++;
                } else {
                    $this->buat($b, $b['kode'] !== '' ? $b['kode'] : null);
                    $dibuat++;
                }
            }
        });

        return ['dibuat' => $dibuat, 'diperbarui' => $diperbarui, 'galat' => []];
    }
}