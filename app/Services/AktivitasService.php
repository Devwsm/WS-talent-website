<?php

namespace App\Services;

use App\Models\log_aktivitas;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

/**
 * Jejak aktivitas admin untuk halaman Log. Mencatat SIAPA, KAPAN, dan APA; isi form tidak disimpan.
 * Kegagalan mencatat tidak boleh mengganggu aksi admin, jadi galat apa pun ditelan diam-diam.
 */
class AktivitasService
{
    public function catat(
        string $aksi,
        ?string $keterangan = null,
        string $status = 'berhasil',
        string $kategori = 'akses',
        ?string $pelaku = null,
        ?string $rute = null,
    ): void {
        try {
            $request = request();

            log_aktivitas::create([
                'pelaku' => $this->potong($pelaku ?? $this->pelakuSaatIni($request), 100),
                'kategori' => $kategori,
                'aksi' => $this->potong($aksi, 80),
                'keterangan' => $this->potong($keterangan, 255),
                'rute' => $this->potong($rute, 120),
                'ip' => $request?->ip(),
                'status' => $status === 'gagal' ? 'gagal' : 'berhasil',
                'created_at' => now(),
            ]);

            if (random_int(1, 50) === 1) {
                log_aktivitas::query()
                    ->where('created_at', '<', now()->subDays((int) config('log_admin.simpan_aktivitas_hari', 180)))
                    ->delete();
            }
        } catch (Throwable) {
            // Sengaja diam: log aktivitas bukan alasan sebuah aksi gagal.
        }
    }

    /** Catat satu request tulis di dashboard (dipanggil middleware CatatAktivitas). */
    public function catatRequest(Request $request, string $status): void
    {
        $nama = (string) $request->route()?->getName();
        if ($nama === '') {
            return;
        }

        $this->catat(
            $this->labelRute($nama),
            $this->keteranganRequest($request),
            $status,
            str_starts_with($nama, 'ic.admin.') ? 'inner-circle' : 'konten',
            null,
            $nama,
        );
    }

    /** "albums.tambah" -> "Tambah Album"; route Inner Circle memakai peta di config/log_admin.php. */
    public function labelRute(string $nama): string
    {
        $peta = config('log_admin.rute', []);
        if (isset($peta[$nama])) {
            return $peta[$nama];
        }

        [$modul, $aksi] = array_pad(explode('.', $nama, 2), 2, '');
        $namaModul = config('log_admin.modul.' . $modul);
        $namaAksi = config('log_admin.aksi.' . $aksi);

        if ($namaModul && $namaAksi) {
            return $namaAksi . ' ' . $namaModul;
        }

        return Str::of($nama)->replace(['.', '_'], ' ')->ucfirst()->toString();
    }

    private function keteranganRequest(Request $request): ?string
    {
        $bagian = [];

        foreach ($request->route()?->parameters() ?? [] as $kunci => $nilai) {
            if (is_scalar($nilai)) {
                $bagian[] = "{$kunci}={$nilai}";
            }
        }

        foreach (config('log_admin.input_dicatat', []) as $kunci) {
            $nilai = $request->input($kunci);
            if (is_scalar($nilai) && (string) $nilai !== '') {
                $bagian[] = "{$kunci}=" . Str::limit((string) $nilai, 30, '');
            }
        }

        return $bagian === [] ? null : implode(', ', $bagian);
    }

    private function pelakuSaatIni(?Request $request): ?string
    {
        try {
            return $request && $request->hasSession() ? $request->session()->get('user') : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function potong(?string $teks, int $maks): ?string
    {
        return $teks === null ? null : mb_substr($teks, 0, $maks);
    }
}