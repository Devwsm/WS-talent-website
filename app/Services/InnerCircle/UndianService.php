<?php

namespace App\Services\InnerCircle;

use App\Models\ic_event;
use App\Models\ic_gl_antrean;
use App\Services\AktivitasService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Mesin undian GL gratis.
 *
 * - Otomatis: `jalankanJatuhTempo()` dipanggil perintah `ic:undi-gl` (jadwal tiap menit, butuh cron) dan sebagai
 *   pengaman dari kunjungan anggota (`pengamanKunjungan()`), jadi undian tetap jalan walau cron belum terpasang.
 * - Manual: `undi()` oleh admin ("Undi sekarang", wajib alasan), `tunda()` dan `lanjutkan()`.
 *
 * Undian hanya sah sekali per acara: baris acara dikunci dan status dibaca ulang di dalam transaksi, hasilnya
 * (status_undian = selesai) terkunci. Pemilihan memakai random_int (acak yang aman), bukan urutan daftar.
 */
class UndianService
{
    /** Acara yang tanggalnya lebih tua dari ini (hari) tidak diundi otomatis lagi. */
    private const BATAS_HARI_LALU = 2;

    public function __construct(private EmailService $email, private AktivitasService $aktivitas) {}

    /**
     * Undi semua acara yang waktunya sudah tiba: status `belum`, punya kuota GL, dan waktu undian <= sekarang.
     * Acara yang datanya belum lengkap (kuota, username, sandi) tidak diundi, tetapi ditandai `tertunda` dan antreannya
     * ditutup; undian susulan dilakukan admin lewat undi() dengan alasan.
     *
     * @return array<string, string> kode acara => selesai | tertunda | gagal
     */
    public function jalankanJatuhTempo(?Carbon $sekarang = null): array
    {
        $sekarang ??= now();
        $hasil = [];

        $kandidat = ic_event::query()
            ->where('status_undian', ic_event::UNDIAN_BELUM)
            ->where('kuota_gl', '>', 0)
            ->whereDate('tanggal', '>=', $sekarang->copy()->subDays(self::BATAS_HARI_LALU)->toDateString())
            ->get()
            ->filter(fn(ic_event $e) => $e->undian_efektif->lessThanOrEqualTo($sekarang));

        foreach ($kandidat as $event) {
            try {
                $hasil[$event->kode] = $this->undiOtomatis($event, $sekarang);
            } catch (Throwable $e) {
                Log::error('Undian GL otomatis gagal', ['acara' => $event->kode, 'pesan' => $e->getMessage()]);
                report($e); // supaya muncul di halaman Log + email peringatan, bukan hanya storage/logs
                $hasil[$event->kode] = 'gagal';
            }
        }

        return $hasil;
    }

    /**
     * Pengaman bila cron belum terpasang: kunjungan anggota ke halaman acara memicu pengecekan, paling sering sekali
     * per menit untuk seluruh situs. Galat apa pun tidak boleh mengganggu halaman anggota.
     */
    public function pengamanKunjungan(): void
    {
        try {
            if (Cache::add('ic-undi-gl-cek', 1, 60)) {
                $this->jalankanJatuhTempo();
            }
        } catch (Throwable $e) {
            Log::warning('Pengaman undian GL gagal', ['pesan' => $e->getMessage()]);
        }
    }

    /**
     * Undi sekarang oleh admin. `alasan` wajib (tercatat di Log), karena ini mengundi di luar jadwal biasa atau
     * menyusul undian yang tertunda. `$pelaku` null = admin yang sedang masuk (dibaca dari sesi). Boleh dari status `belum`, `ditunda`, atau `tertunda`; tidak boleh bila sudah selesai.
     *
     * @return array{peserta: int, pemenang: int}
     */
    public function undi(ic_event $event, ?string $pelaku, string $alasan): array
    {
        $alasan = trim($alasan);
        if (mb_strlen($alasan) < 5) {
            throw new GlGagal('Isi alasan undian (minimal 5 huruf), supaya tercatat di Log.');
        }

        $ringkas = $this->jalankan($event, now(), true);
        $this->catat($pelaku, 'Undi GL sekarang', $event, "Alasan: {$alasan}. Peserta {$ringkas['peserta']}, pemenang {$ringkas['pemenang']}.");

        return $ringkas;
    }

    /** Tunda undian (antrean tetap terbuka, undian otomatis tidak jalan) sampai admin melanjutkan atau mengundi. */
    public function tunda(ic_event $event, ?string $pelaku): void
    {
        if ($event->status_undian === ic_event::UNDIAN_SELESAI) {
            throw new GlGagal('Undian acara ini sudah selesai dan hasilnya terkunci.');
        }

        $event->forceFill(['status_undian' => ic_event::UNDIAN_DITUNDA])->save();
        $this->catat($pelaku, 'Tunda undian GL', $event);
    }

    /** Lanjutkan undian yang ditunda: kembali ke jadwal. Bila waktunya sudah lewat, undian jalan di menit berikutnya. */
    public function lanjutkan(ic_event $event, ?string $pelaku): void
    {
        if (! in_array($event->status_undian, [ic_event::UNDIAN_DITUNDA, ic_event::UNDIAN_TERTUNDA], true)) {
            throw new GlGagal('Undian acara ini tidak sedang ditunda.');
        }

        $event->forceFill(['status_undian' => ic_event::UNDIAN_BELUM])->save();
        $this->catat($pelaku, 'Lanjutkan undian GL', $event);
    }

    /** Jalur otomatis: data belum lengkap jadi `tertunda`, selain itu diundi. */
    private function undiOtomatis(ic_event $event, Carbon $sekarang): string
    {
        $ringkas = $this->jalankan($event, $sekarang, false);

        if ($ringkas === null) {
            return 'tertunda';
        }

        $this->catat('sistem', 'Undian GL otomatis', $event, "Peserta {$ringkas['peserta']}, pemenang {$ringkas['pemenang']}.");

        return 'selesai';
    }

    /**
     * Inti undian, satu transaksi. Mengembalikan ringkasan, atau null bila otomatis dan datanya belum lengkap
     * (acara ditandai tertunda). Jalur manual (`$manual`) melempar GlGagal bila data belum lengkap.
     *
     * @return array{peserta: int, pemenang: int}|null
     */
    private function jalankan(ic_event $event, Carbon $sekarang, bool $manual): ?array
    {
        $pemenangId = [];

        $ringkas = DB::transaction(function () use ($event, $sekarang, $manual, &$pemenangId) {
            // Kunci baris acara, lalu baca ulang statusnya: dua proses yang bersamaan tidak bisa mengundi dua kali.
            $e = ic_event::query()->whereKey($event->getKey())->lockForUpdate()->firstOrFail();

            if ($e->status_undian === ic_event::UNDIAN_SELESAI) {
                if ($manual) {
                    throw new GlGagal('Undian acara ini sudah selesai dan hasilnya terkunci.');
                }

                return null;
            }
            if (! $manual && $e->status_undian !== ic_event::UNDIAN_BELUM) {
                return null; // ditunda atau tertunda: otomatis tidak menyentuhnya
            }
            if (! $e->glLengkap()) {
                if ($manual) {
                    throw new GlGagal('Lengkapi kuota GL, username acara, dan sandi acara dulu sebelum mengundi.');
                }

                $e->forceFill(['status_undian' => ic_event::UNDIAN_TERTUNDA])->save();
                $this->catat('sistem', 'Undian GL tertunda', $e, 'Kuota, username, atau sandi acara belum lengkap saat waktu undian tiba. Antrean ditutup.');

                return null;
            }

            $antrean = ic_gl_antrean::query()
                ->with('anggota')
                ->where('event_id', $e->getKey())
                ->where('status', ic_gl_antrean::ANTRI)
                ->lockForUpdate()
                ->get();

            // Akun yang dinonaktifkan atau belum terverifikasi sejak mendaftar tidak ikut diundi.
            [$layak, $gugur] = $antrean->partition(fn(ic_gl_antrean $b) => $b->anggota
                && ! $b->anggota->diblokir()
                && $b->anggota->email_verified_at !== null);

            $urutan = $this->acak($layak->pluck('id_antrean')->all());
            $menang = array_slice($urutan, 0, (int) $e->kuota_gl);

            foreach ($menang as $i => $id) {
                ic_gl_antrean::query()->whereKey($id)->update(['status' => ic_gl_antrean::MENANG, 'urutan_undi' => $i + 1]);
            }
            $kalah = array_merge(array_slice($urutan, count($menang)), $gugur->pluck('id_antrean')->all());
            if ($kalah) {
                ic_gl_antrean::query()->whereIn('id_antrean', $kalah)->update(['status' => ic_gl_antrean::TIDAK_TERPILIH]);
            }

            $e->forceFill([
                'status_undian' => ic_event::UNDIAN_SELESAI,
                'undian_selesai_pada' => $sekarang,
            ])->save();

            $pemenangId = $menang;

            return ['peserta' => $antrean->count(), 'pemenang' => count($menang)];
        });

        // Email setelah transaksi selesai: gagal kirim tidak membatalkan hasil undian.
        if ($ringkas !== null) {
            $event->refresh();
            // Hanya batch pertama yang dikirim sekarang; sisanya dikirim bertahap oleh jadwal ic:kartu-gl-sisa.
            ic_gl_antrean::query()->with('anggota')->whereIn('id_antrean', $pemenangId)->get()
                ->take((int) config('inner_circle.gl.kartu_per_menit', 30))
                ->each(fn(ic_gl_antrean $b) => $this->email->kirimKartuGl($b, $event));
        }

        return $ringkas;
    }

    /**
     * Urutan acak yang adil (Fisher-Yates dengan random_int), bukan shuffle() yang tidak dijamin aman secara kriptografi.
     *
     * @param  array<int, int>  $daftar
     * @return array<int, int>
     */
    private function acak(array $daftar): array
    {
        $daftar = array_values($daftar);

        for ($i = count($daftar) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$daftar[$i], $daftar[$j]] = [$daftar[$j], $daftar[$i]];
        }

        return $daftar;
    }

    private function catat(?string $pelaku, string $aksi, ic_event $event, ?string $tambahan = null): void
    {
        $this->aktivitas->catat(
            $aksi,
            trim(GlService::namaAcara($event) . ' (' . $event->kode . ')' . ($tambahan ? '. ' . $tambahan : '')),
            'berhasil',
            'inner-circle',
            $pelaku,
        );
    }
}