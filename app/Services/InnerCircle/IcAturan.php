<?php

namespace App\Services\InnerCircle;

use App\Models\ic_event;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Aturan bawaan check-in yang bisa diubah dari CMS (tab Konten, bagian "Aturan check-in"):
 * jam jendela check-in bawaan, radius bawaan, dan batas akurasi GPS.
 *
 * Nilai disimpan di `ic_pengaturan` dengan kunci `aturan.*` dan HANYA bila berbeda dari config/inner_circle.php, jadi
 * tabel kosong = perilaku persis seperti sebelum fitur ini ada. Nilai yang rusak (diubah manual di database) diabaikan
 * dan config yang dipakai.
 *
 * Aturan ini bawaan, bukan paksaan: acara yang punya jendela atau radius sendiri tidak terpengaruh. Supaya mengubah
 * bawaan juga TIDAK menggeser acara yang sudah ada, saat jendela atau radius bawaan diubah, acara yang masih memakai
 * bawaan "dibekukan" dulu pada nilai lamanya (disalin ke kolomnya sendiri). Jadi bawaan baru hanya berlaku untuk acara
 * yang dibuat sesudahnya. Batas akurasi adalah aturan umum (bukan per acara), jadi berlaku langsung.
 */
class IcAturan
{
    public const KUNCI = [
        'jendela_buka' => 'aturan.jendela_buka',
        'jendela_tutup' => 'aturan.jendela_tutup',
        'jendela_tutup_hari' => 'aturan.jendela_tutup_hari',
        'radius_bawaan_m' => 'aturan.radius_bawaan_m',
        'akurasi_maks_m' => 'aturan.akurasi_maks_m',
    ];

    public function __construct(private readonly IcKonten $konten) {}

    /** Nilai bawaan dari config (bukan dari CMS). @return array{jendela_buka: string, jendela_tutup: string, jendela_tutup_hari: int, radius_bawaan_m: int, akurasi_maks_m: int} */
    public function bawaan(): array
    {
        return [
            'jendela_buka' => (string) config('inner_circle.jendela.buka'),
            'jendela_tutup' => (string) config('inner_circle.jendela.tutup'),
            'jendela_tutup_hari' => (int) config('inner_circle.jendela.tutup_hari'),
            'radius_bawaan_m' => (int) config('inner_circle.lokasi.radius_bawaan_m'),
            'akurasi_maks_m' => (int) config('inner_circle.lokasi.akurasi_maks_m'),
        ];
    }

    /** Nilai yang berlaku sekarang: dari CMS bila ada dan masuk akal, kalau tidak dari config. @return array<string, string|int> */
    public function efektif(): array
    {
        $a = $this->bawaan();
        $cfg = config('inner_circle.lokasi');

        $buka = $this->konten->nilaiMentah(self::KUNCI['jendela_buka']);
        $tutup = $this->konten->nilaiMentah(self::KUNCI['jendela_tutup']);
        $hari = $this->konten->nilaiMentah(self::KUNCI['jendela_tutup_hari']);
        $radius = $this->konten->nilaiMentah(self::KUNCI['radius_bawaan_m']);
        $akurasi = $this->konten->nilaiMentah(self::KUNCI['akurasi_maks_m']);

        return [
            'jendela_buka' => self::jamValid($buka) ? $buka : $a['jendela_buka'],
            'jendela_tutup' => self::jamValid($tutup) ? $tutup : $a['jendela_tutup'],
            'jendela_tutup_hari' => in_array($hari, ['0', '1'], true) ? (int) $hari : $a['jendela_tutup_hari'],
            'radius_bawaan_m' => $this->dalamBatas($radius, (int) $cfg['radius_min_m'], (int) $cfg['radius_maks_m']) ?? $a['radius_bawaan_m'],
            'akurasi_maks_m' => $this->dalamBatas($akurasi, (int) $cfg['akurasi_isian_min_m'], (int) $cfg['akurasi_isian_maks_m']) ?? $a['akurasi_maks_m'],
        ];
    }

    public function radiusBawaan(): int
    {
        return (int) $this->efektif()['radius_bawaan_m'];
    }

    public function akurasiMaks(): int
    {
        return (int) $this->efektif()['akurasi_maks_m'];
    }

    /** Saat check-in dibuka untuk acara bertanggal $tanggal, menurut aturan bawaan saat ini (atau $aturan bila diberikan). */
    public function bukaUntuk(Carbon $tanggal, ?array $aturan = null): Carbon
    {
        $aturan ??= $this->efektif();

        return $this->pada($tanggal->copy(), (string) $aturan['jendela_buka']);
    }

    public function tutupUntuk(Carbon $tanggal, ?array $aturan = null): Carbon
    {
        $aturan ??= $this->efektif();

        return $this->pada($tanggal->copy()->addDays((int) $aturan['jendela_tutup_hari']), (string) $aturan['jendela_tutup']);
    }

    /** Kalimat siap tampil, mis. "tanggal show pukul 12.00 sampai hari berikutnya pukul 06.00 WIB". */
    public function ringkasan(): string
    {
        $a = $this->efektif();
        $jam = fn(string $j) => str_replace(':', '.', $j);

        return 'tanggal show pukul ' . $jam($a['jendela_buka']) . ' sampai '
            . ($a['jendela_tutup_hari'] > 0 ? 'hari berikutnya' : 'hari yang sama') . ' pukul ' . $jam($a['jendela_tutup']) . ' WIB';
    }

    /**
     * Simpan aturan baru (sudah divalidasi). Nilai yang sama dengan config tidak disimpan (barisnya dihapus).
     * Acara yang masih memakai bawaan dibekukan pada nilai lama bila bawaannya berubah (lihat catatan kelas).
     *
     * @param  array<string, string|int>  $baru  kunci pendek (jendela_buka, ...) => nilai
     * @return array{berubah: int, dibekukan: int}
     */
    public function simpan(array $baru, ?string $oleh = null): array
    {
        $lama = $this->efektif();
        $bawaan = $this->bawaan();

        $tulis = [];
        foreach (self::KUNCI as $pendek => $kunci) {
            if (! array_key_exists($pendek, $baru)) {
                continue;
            }
            $tulis[$kunci] = (string) $baru[$pendek] === (string) $bawaan[$pendek] ? null : (string) $baru[$pendek];
        }

        return DB::transaction(function () use ($tulis, $oleh, $lama) {
            $dibekukan = 0;
            $berubah = $this->konten->simpanMentah($tulis, $oleh);

            $kini = $this->efektif();
            $jendelaBerubah = $kini['jendela_buka'] !== $lama['jendela_buka']
                || $kini['jendela_tutup'] !== $lama['jendela_tutup']
                || $kini['jendela_tutup_hari'] !== $lama['jendela_tutup_hari'];

            if ($jendelaBerubah) {
                // get() lebih dulu (bukan each/chunk): baris yang sudah dibekukan keluar dari kondisi whereNull dan akan terlewat.
                ic_event::query()->whereNull('jendela_mulai')->whereNull('jendela_selesai')->orderBy('id_event')->get()
                    ->each(function (ic_event $e) use ($lama, &$dibekukan) {
                        $e->forceFill([
                            'jendela_mulai' => $this->bukaUntuk($e->tanggal, $lama),
                            'jendela_selesai' => $this->tutupUntuk($e->tanggal, $lama),
                        ])->save();
                        $dibekukan++;
                    });
            }

            if ($kini['radius_bawaan_m'] !== $lama['radius_bawaan_m']) {
                $cfg = config('inner_circle.lokasi');
                $radiusLama = max((int) $cfg['radius_min_m'], min((int) $cfg['radius_maks_m'], (int) $lama['radius_bawaan_m']));

                $dibekukan += ic_event::query()->whereNotNull('lokasi_lat')->whereNotNull('lokasi_lng')->whereNull('lokasi_radius')
                    ->update(['lokasi_radius' => $radiusLama]);
            }

            return ['berubah' => $berubah, 'dibekukan' => $dibekukan];
        });
    }

    /** Kembalikan semua aturan ke config (acara yang sudah ada tetap pada nilainya, sama seperti saat mengubah). */
    public function kembalikanBawaan(?string $oleh = null): array
    {
        return $this->simpan($this->bawaan(), $oleh);
    }

    public static function jamValid(?string $jam): bool
    {
        return $jam !== null && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $jam) === 1;
    }

    private function pada(Carbon $tanggal, string $jam): Carbon
    {
        [$j, $m] = array_map('intval', explode(':', $jam));

        return $tanggal->setTime($j, $m);
    }

    private function dalamBatas(?string $nilai, int $min, int $maks): ?int
    {
        if ($nilai === null || ! ctype_digit($nilai)) {
            return null;
        }

        $angka = (int) $nilai;

        return ($angka >= $min && $angka <= $maks) ? $angka : null;
    }
}