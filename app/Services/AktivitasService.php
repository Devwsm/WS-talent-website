<?php

namespace App\Services;

use App\Models\log_aktivitas;
use App\Services\InnerCircle\EmailService;
use App\Support\PerangkatPengguna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

/**
 * Jejak aktivitas admin untuk halaman Log (CMS Artis dan CMS Inner Circle).
 *
 * Yang dicatat:
 * - Perubahan di CMS (tambah/ubah/hapus/simpan): APA yang diubah (lengkap dengan nama butirnya), SIAPA, KAPAN,
 *   dan dari PERANGKAT apa. Isi form tidak disimpan, dan alamat IP tidak disimpan.
 * - Login gagal (kategori `akses`): satu-satunya yang menyimpan IP, karena menyangkut keamanan.
 *
 * Yang TIDAK dicatat, supaya log tidak penuh dan tidak ada catatan ganda:
 * - Login berhasil, logout, dan membuka halaman.
 * - Aksi yang gagal karena galat server (5xx) atau salah ketik di form: itu sudah masuk tab Error atau muncul di layar.
 * - Klik ganda: aksi yang persis sama dalam beberapa detik dicatat sekali; login gagal yang sama dari IP yang sama
 *   digabung jadi satu baris dengan penghitung.
 *
 * Kegagalan mencatat tidak boleh mengganggu aksi admin, jadi galat apa pun ditelan diam-diam.
 */
class AktivitasService
{
    // ---------- login gagal ----------

    /** Catat masalah saat masuk ke dashboard. $jenis = kunci di config('log_admin.akses'). Selalu berstatus gagal. */
    public function catatAkses(string $jenis, ?string $pelaku = null): void
    {
        $teks = config('log_admin.akses.' . $jenis);
        if (! is_array($teks)) {
            return;
        }

        $this->catat($teks[0], $teks[1] ?? null, 'gagal', 'akses', $pelaku, 'login.' . $jenis);
    }

    // ---------- perubahan di CMS ----------

    /**
     * Dipanggil middleware SEBELUM aksi berjalan: nama butir yang akan diubah/dihapus harus dibaca sekarang, karena
     * sesudah dihapus namanya tidak ada lagi. Mengembalikan konteks untuk catatRequest().
     *
     * @return array{nama: ?string}
     */
    public function siapkan(Request $request): array
    {
        try {
            $nama = (string) $request->route()?->getName();
            $d = $nama === '' || $this->diabaikan($nama) ? null : $this->deskriptor($nama);

            return ['nama' => $d ? $this->namaDariDatabase($request, $d) : null];
        } catch (Throwable) {
            return ['nama' => null];
        }
    }

    /** Catat satu request tulis di dashboard (dipanggil middleware CatatAktivitas). */
    public function catatRequest(Request $request, string $status, array $konteks = [], ?string $pesanGagal = null): void
    {
        $nama = (string) $request->route()?->getName();
        if ($nama === '' || $this->diabaikan($nama)) {
            return;
        }

        $d = $this->deskriptor($nama);
        if ($d === null) {
            $this->catat('Perubahan di CMS', null, $status, $this->kategoriRute($nama), null, $nama);

            return;
        }

        // Nama dari isian form (yang baru) diutamakan; untuk hapus dan aksi tanpa isian, nama dibaca dari database.
        $namaButir = $this->namaDariIsian($request, $d) ?? ($konteks['nama'] ?? null);

        [$judul, $keterangan] = $status === 'gagal'
            ? $this->teksGagal($d, $pesanGagal)
            : $this->teksBerhasil($request, $d, $namaButir);

        $this->catat($judul, $keterangan, $status, $this->kategoriRute($nama), null, $nama);
    }

    // ---------- inti pencatatan ----------

    public function catat(
        string $aksi,
        ?string $keterangan = null,
        string $status = 'berhasil',
        string $kategori = 'konten',
        ?string $pelaku = null,
        ?string $rute = null,
    ): void {
        try {
            $request = request();
            $status = $status === 'gagal' ? 'gagal' : 'berhasil';
            $pelaku = $this->potong($pelaku ?? $this->pelakuSaatIni($request), 100);
            $aksi = (string) $this->potong($aksi, 80);
            $keterangan = $this->potong($keterangan, 255);
            $berisiko = in_array($kategori, (array) config('log_admin.kategori_berisiko', []), true);
            $ip = $berisiko ? $request?->ip() : null; // IP hanya disimpan untuk kondisi berisiko

            if ($berisiko ? $this->gabungkanLoginGagal($kategori, $aksi, $ip, $pelaku) : $this->kembar($pelaku, $rute, $aksi, $keterangan, $status)) {
                return;
            }

            log_aktivitas::create([
                'pelaku' => $pelaku,
                'kategori' => $kategori,
                'aksi' => $aksi,
                'keterangan' => $keterangan,
                'rute' => $this->potong($rute, 120),
                'perangkat' => PerangkatPengguna::ringkas($request?->userAgent()),
                'ip' => $ip,
                'status' => $status,
                'jumlah' => 1,
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

    /** Kejadian yang sama (jenis + IP + pelaku) dalam jendela waktu digabung: penghitung naik, waktu jadi yang terbaru. */
    private function gabungkanLoginGagal(string $kategori, string $aksi, ?string $ip, ?string $pelaku): bool
    {
        $menit = (int) config('log_admin.gabung_login_gagal_menit', 60);

        $query = log_aktivitas::query()
            ->where('kategori', $kategori)
            ->where('aksi', $aksi)
            ->where('created_at', '>=', now()->subMinutes($menit));

        $ip === null ? $query->whereNull('ip') : $query->where('ip', $ip);
        $pelaku === null ? $query->whereNull('pelaku') : $query->where('pelaku', $pelaku);

        $ada = $query->orderByDesc('id_log_aktivitas')->first();
        if (! $ada) {
            return false;
        }

        $ada->increment('jumlah', 1, ['created_at' => now()]);

        return true;
    }

    /** Klik ganda: aksi yang persis sama dari admin yang sama dalam beberapa detik dicatat sekali. */
    private function kembar(?string $pelaku, ?string $rute, string $aksi, ?string $keterangan, string $status): bool
    {
        $detik = (int) config('log_admin.redam_ganda_detik', 5);
        if ($detik <= 0) {
            return false;
        }

        try {
            return ! Cache::add('log-aktivitas:' . sha1(implode('|', [$pelaku, $rute, $aksi, $keterangan, $status])), 1, now()->addSeconds($detik));
        } catch (Throwable) {
            return false; // cache bermasalah: lebih baik tercatat dua kali daripada tidak tercatat
        }
    }

    // ---------- menyusun teks ----------

    /** @return array{0: string, 1: ?string} */
    private function teksBerhasil(Request $request, array $d, ?string $namaButir): array
    {
        $konteks = $this->konteks($request, $d, $namaButir);
        $judul = $this->isiTeks((string) ($d['judul'] ?? 'Perubahan di CMS'), $konteks);

        $ket = $d['ket'] ?? null;
        if ($ket !== null && $namaButir === null && str_contains($ket, '{nama}')) {
            $ket = $d['ket_tanpa_nama'] ?? null;
        }

        return [$judul, $ket === null ? null : $this->isiTeks($ket, $konteks)];
    }

    /** @return array{0: string, 1: ?string} */
    private function teksGagal(array $d, ?string $pesan): array
    {
        $judul = $this->isiTeks((string) ($d['gagal'] ?? ($d['judul'] ?? 'Perubahan di CMS') . ' (gagal)'), ['objek' => (string) ($d['objek'] ?? '')]);

        // Pesan penolakan dari sistem sudah berbahasa awam; route tertentu punya kalimat tetap karena pesannya teknis.
        $ket = $d['gagal_ket'] ?? ($pesan !== null && trim($pesan) !== '' ? Str::limit(trim($pesan), 200, '…') : 'Perubahan tidak tersimpan.');

        return [$judul, $ket];
    }

    /** Penanda yang dikenali teks: {objek}, {nama}, {input.xxx}, {param.xxx}. */
    private function konteks(Request $request, array $d, ?string $namaButir): array
    {
        $diizinkan = (array) config('log_admin.input_dicatat', []);
        $peta = array_replace_recursive((array) config('log_admin.nilai', []), (array) ($d['nilai'] ?? []));
        $input = [];

        foreach ($diizinkan as $kunci) {
            $nilai = $request->input($kunci);
            if (! is_scalar($nilai) || (string) $nilai === '') {
                continue;
            }

            $teks = $kunci === 'aktif' ? (filter_var($nilai, FILTER_VALIDATE_BOOLEAN) ? '1' : '0') : (string) $nilai;
            $input[$kunci] = $peta[$kunci][$teks] ?? Str::limit($teks, 30, '');
        }

        $param = [];
        foreach ($request->route()?->parameters() ?? [] as $kunci => $nilai) {
            if (is_scalar($nilai)) {
                $param[$kunci] = $this->labelParam((string) $kunci, (string) $nilai);
            }
        }

        return ['objek' => (string) ($d['objek'] ?? ''), 'nama' => $namaButir ?? '', 'input' => $input, 'param' => $param];
    }

    /** Bagian alamat yang berupa kunci teknis (mis. "faq") dibaca sebagai namanya (mis. "Pertanyaan umum (FAQ)"). */
    private function labelParam(string $kunci, string $nilai): string
    {
        $label = match ($kunci) {
            'grup' => config('ic_konten.grup.' . $nilai . '.label'),
            'daftar' => config('ic_konten.daftar.' . $nilai . '.label'),
            'jenis' => EmailService::JENIS[$nilai]['label'] ?? null,
            default => null,
        };

        return is_string($label) && $label !== '' ? $label : $nilai;
    }

    /** Ganti penanda; penanda yang tidak punya nilai jadi kosong. */
    private function isiTeks(string $teks, array $konteks): string
    {
        $hasil = preg_replace_callback('/\{([a-z_]+)(?:\.([a-z_]+))?\}/i', function (array $m) use ($konteks) {
            $nilai = isset($m[2]) ? ($konteks[$m[1]][$m[2]] ?? '') : ($konteks[$m[1]] ?? '');

            return is_scalar($nilai) ? (string) $nilai : '';
        }, $teks) ?? $teks;

        return trim((string) preg_replace('/\s{2,}/u', ' ', $hasil));
    }

    // ---------- mencari nama butir ----------

    /** Nama butir dari isian form (aksi tambah dan ubah). Null bila tidak ada isian yang cocok. */
    private function namaDariIsian(Request $request, array $d): ?string
    {
        $data = [];
        foreach ($request->all() as $kunci => $nilai) {
            if (is_string($kunci) && is_scalar($nilai) && trim((string) $nilai) !== '') {
                $data[$kunci] = trim((string) $nilai);
            }
        }

        return $this->pilihNama((array) ($d['nama'] ?? []), fn(string $k) => $data[$k] ?? null);
    }

    /** Nama butir dari database, dibaca sebelum aksi berjalan (perlu untuk hapus, dan untuk aksi tanpa isian nama). */
    private function namaDariDatabase(Request $request, array $d): ?string
    {
        $s = $d['sumber'] ?? null;
        if (! is_array($s) || empty($d['nama'])) {
            return null;
        }

        $nilai = $request->route()?->parameter($s['param'] ?? 'id');
        if (! is_scalar($nilai)) {
            return null;
        }

        $query = $s['model']::query();
        if (! empty($s['relasi'])) {
            $query->with($s['relasi']);
        }
        foreach ((array) ($s['saring'] ?? []) as $kolom => $isi) {
            $query->where($kolom, $isi);
        }

        $baris = $query->where($s['kolom'], $nilai)->first();

        return $baris ? $this->pilihNama($d['nama'], fn(string $k) => data_get($baris, $k)) : null;
    }

    /**
     * Dari daftar pola, pakai yang pertama yang semua penandanya terisi. Contoh: ['{venue}, {kota}', '{venue}'] jadi
     * "Istora, Jakarta" bila keduanya ada, atau "Istora" bila kota kosong.
     *
     * @param  array<int, string>  $pola
     * @param  \Closure(string): mixed  $ambil
     */
    private function pilihNama(array $pola, \Closure $ambil): ?string
    {
        foreach ($pola as $satu) {
            $lengkap = true;

            $hasil = preg_replace_callback('/\{([a-z_.]+)\}/i', function (array $m) use ($ambil, &$lengkap) {
                $nilai = $ambil($m[1]);
                if (! is_scalar($nilai) || trim((string) $nilai) === '') {
                    $lengkap = false;

                    return '';
                }

                return trim((string) $nilai);
            }, $satu);

            if ($lengkap && $hasil !== null && trim($hasil) !== '') {
                return Str::limit(trim($hasil), 60, '…');
            }
        }

        return null;
    }

    // ---------- peta route ----------

    /** Teks untuk sebuah route: yang ditulis khusus di `rute`, atau dirakit dari jenis data + kata kerja ("albums.tambah"). */
    private function deskriptor(string $nama): ?array
    {
        // Nama route memuat titik, jadi tidak boleh dipakai langsung sebagai kunci config() bertitik.
        $khusus = (array) config('log_admin.rute', []);
        $khusus = $khusus[$nama] ?? null;
        if (is_array($khusus)) {
            return $khusus;
        }

        [$modul, $aksi] = array_pad(explode('.', $nama, 2), 2, '');
        $m = config('log_admin.modul.' . $modul);
        $a = config('log_admin.aksi.' . $aksi);

        if (! is_array($m) || ! is_array($a)) {
            return null;
        }

        return $a + [
            'objek' => $m['objek'],
            'nama' => $m['nama'] ?? [],
            'sumber' => ['model' => $m['model'], 'param' => 'id', 'kolom' => $m['kunci']],
        ];
    }

    private function diabaikan(string $nama): bool
    {
        return Str::is((array) config('log_admin.abaikan_rute', []), $nama);
    }

    private function kategoriRute(string $nama): string
    {
        return str_starts_with($nama, 'ic.admin.') ? 'inner-circle' : 'konten';
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