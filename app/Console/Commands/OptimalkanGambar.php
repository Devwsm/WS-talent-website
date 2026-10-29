<?php

namespace App\Console\Commands;

use App\Models\albums;
use App\Models\banner;
use App\Models\header;
use App\Models\merchandise;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Mengubah gambar LAMA (PNG/JPG yang diunggah sebelum konversi WebP ada, mis. banner 6 MB) menjadi WebP
 * berukuran wajar, lalu mengarahkan kolom database ke berkas baru.
 *
 * Aman dijalankan berulang: berkas yang sudah .webp dilewati. Tanpa --terapkan hanya simulasi (tidak
 * mengubah apa pun), jadi hasilnya bisa dilihat dulu.
 */
class OptimalkanGambar extends Command
{
    protected $signature = 'gambar:optimalkan
        {--terapkan : Benar-benar membuat WebP dan mengubah database (tanpa opsi ini hanya simulasi)}
        {--hapus-asli : Hapus berkas lama setelah berhasil (hanya berlaku bersama --terapkan)}';

    protected $description = 'Ubah gambar lama (PNG/JPG) di albums, merchandise, banner, dan logo header menjadi WebP berukuran wajar.';

    /** Sama dengan batas di controller unggahan: di atas ini decode bisa menghabiskan memori hosting. */
    private const MAKS_PIKSEL = 16_000_000;

    /** @return array<int, array{0: class-string, 1: string, 2: string, 3: int}> [model, kolom, folder di disk public, lebar maks px] */
    private function target(): array
    {
        return [
            [albums::class, 'albums_cover', 'albums', 800],
            [merchandise::class, 'merchandise_cover', 'merchandise', 800],
            [banner::class, 'banner_cover', 'banner', 2400],
            [header::class, 'header_img', 'header/img', 800],
        ];
    }

    public function handle(): int
    {
        if (! function_exists('imagewebp')) {
            $this->error('Ekstensi PHP GD dengan dukungan WebP tidak tersedia di server ini.');

            return self::FAILURE;
        }

        $terapkan = (bool) $this->option('terapkan');
        $hapusAsli = $terapkan && (bool) $this->option('hapus-asli');
        $disk = Storage::disk('public');

        $baris = [];
        $totalSebelum = 0;
        $totalSesudah = 0;

        foreach ($this->target() as [$model, $kolom, $folder, $lebarMaks]) {
            foreach ($model::all() as $rekam) {
                $nama = (string) $rekam->{$kolom};

                if ($nama === '' || strtolower(pathinfo($nama, PATHINFO_EXTENSION)) === 'webp') {
                    continue;
                }

                $path = $folder . '/' . $nama;

                if (! $disk->exists($path)) {
                    $baris[] = [$path, '-', '-', 'berkas tidak ditemukan di storage'];
                    continue;
                }

                $data = $this->konversi($disk->path($path), $lebarMaks);

                if ($data === null) {
                    $baris[] = [$path, '-', '-', 'dilewati (format tidak didukung / resolusi terlalu besar)'];
                    continue;
                }

                $sebelum = (int) $disk->size($path);
                $sesudah = strlen($data);

                if ($sesudah >= $sebelum) {
                    $baris[] = [$path, $this->ukuran($sebelum), $this->ukuran($sesudah), 'dilewati (sudah efisien)'];
                    continue;
                }

                $namaBaru = pathinfo($nama, PATHINFO_FILENAME) . '.webp';
                $pathBaru = $folder . '/' . $namaBaru;

                // Jangan menimpa berkas lain yang kebetulan sudah memakai nama itu.
                if ($disk->exists($pathBaru)) {
                    $namaBaru = pathinfo($nama, PATHINFO_FILENAME) . '-opt.webp';
                    $pathBaru = $folder . '/' . $namaBaru;
                }

                if ($terapkan) {
                    $disk->put($pathBaru, $data);
                    $rekam->{$kolom} = $namaBaru;
                    $rekam->save();

                    if ($hapusAsli) {
                        $disk->delete($path);
                    }
                }

                $totalSebelum += $sebelum;
                $totalSesudah += $sesudah;
                $baris[] = [$path, $this->ukuran($sebelum), $this->ukuran($sesudah), $terapkan ? "-> {$namaBaru}" : "akan jadi {$namaBaru}"];
            }
        }

        if ($baris === []) {
            $this->info('Tidak ada gambar lama yang perlu dioptimalkan.');

            return self::SUCCESS;
        }

        $this->table(['Berkas', 'Sebelum', 'Sesudah', 'Keterangan'], $baris);
        $this->line('Total hemat: ' . $this->ukuran(max(0, $totalSebelum - $totalSesudah)));

        if (! $terapkan) {
            $this->warn('Ini hanya SIMULASI. Jalankan lagi dengan --terapkan untuk benar-benar mengubah.');
        } elseif (! $hapusAsli) {
            $this->line('Berkas lama masih disimpan. Setelah situs dicek aman, jalankan lagi dengan --terapkan --hapus-asli bila ingin menghapusnya.');
        }

        return self::SUCCESS;
    }

    /** Baca PNG/JPG, kecilkan bila lebih lebar dari $lebarMaks, lalu kembalikan data WebP (null bila gagal). */
    private function konversi(string $sumber, int $lebarMaks): ?string
    {
        $info = @getimagesize($sumber);

        if ($info === false || $info[0] < 1 || $info[1] < 1 || $info[0] * $info[1] > self::MAKS_PIKSEL) {
            return null;
        }

        $gambar = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($sumber),
            IMAGETYPE_PNG => @imagecreatefrompng($sumber),
            default => false,
        };

        if ($gambar === false) {
            return null;
        }

        imagepalettetotruecolor($gambar);
        imagealphablending($gambar, false);
        imagesavealpha($gambar, true);

        if ($info[0] > $lebarMaks) {
            $kecil = imagescale($gambar, $lebarMaks, -1, IMG_BICUBIC);

            if ($kecil !== false) {
                imagedestroy($gambar);
                $gambar = $kecil;
                imagealphablending($gambar, false);
                imagesavealpha($gambar, true);
            }
        }

        ob_start();
        imagewebp($gambar, null, 82);
        $data = ob_get_clean();
        imagedestroy($gambar);

        return ($data === false || $data === '') ? null : $data;
    }

    private function ukuran(int $byte): string
    {
        return $byte >= 1048576
            ? number_format($byte / 1048576, 2) . ' MB'
            : number_format($byte / 1024, 0) . ' KB';
    }
}