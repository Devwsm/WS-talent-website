<?php

namespace App\Services\InnerCircle;

use App\Models\ic_event;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Menarik jadwal tour dari Bandsintown (API publik, read-only) ke tabel `ic_events`, sehingga jadwal Inner Circle
 * sama dengan widget Tour di situs publik tanpa input ganda.
 *
 * Aturan sinkron (dirancang agar aman dijalankan berulang):
 * - Acara dikenali lewat `bit_id`. Acara baru dibuat dengan token QR sendiri; token acara yang sudah ada TIDAK berubah.
 * - Acara buatan admin (manual) dengan tanggal dan venue yang sama hanya DITAUTKAN (bit_id diisi), isinya tidak ditimpa.
 * - Hanya acara bersumber `bandsintown` yang tanggal/kota/venue-nya diperbarui. Begitu admin mengubah salah satunya
 *   lewat dashboard, acara itu menjadi `manual` dan tidak ditimpa lagi (lihat adminController@ubahAcara).
 * - Tipe acara tidak pernah ditimpa. Acara baru ditebak dari namanya (festival atau club), admin bisa mengubahnya.
 * - Tidak ada penghapusan otomatis (QR yang sudah dicetak tidak boleh hilang diam-diam). Acara yang tidak lagi ada di
 *   Bandsintown hanya dilaporkan di hasil.
 */
class BandsintownService
{
    private const URL_API = 'https://rest.bandsintown.com/artists/%s/events';

    private const POLA_FESTIVAL = '/festival|\bfest\b|open air/i';

    public function __construct(private AcaraService $acara) {}

    public static function tersedia(): bool
    {
        return filled(config('services.bandsintown.app_id')) && filled(config('services.bandsintown.artist'));
    }

    /**
     * @return array{total: int, dibuat: int, diperbarui: int, terhubung: int, hilang: array<int, string>}
     *
     * @throws SinkronGagal
     */
    public function sinkron(): array
    {
        $daftar = $this->ambil();
        $sekarang = now();

        $hasil = ['total' => 0, 'dibuat' => 0, 'diperbarui' => 0, 'terhubung' => 0, 'hilang' => []];
        $idTerlihat = [];

        DB::transaction(function () use ($daftar, $sekarang, &$hasil, &$idTerlihat) {
            foreach ($daftar as $mentah) {
                $p = $this->petakan($mentah);
                if ($p === null) {
                    continue;
                }

                $idTerlihat[] = $p['bit_id'];
                $hasil['total']++;

                $ada = ic_event::query()->where('bit_id', $p['bit_id'])->first();
                if ($ada) {
                    $berubah = false;

                    if ($ada->sumber === 'bandsintown') {
                        if ($ada->tanggal->toDateString() !== $p['tanggal']) {
                            $ada->tanggal = $p['tanggal'];
                            $berubah = true;
                        }
                        foreach (['kota', 'venue'] as $kolom) {
                            if ($ada->{$kolom} !== $p[$kolom]) {
                                $ada->{$kolom} = $p[$kolom];
                                $berubah = true;
                            }
                        }
                    }

                    $ada->bit_url = $p['bit_url'];
                    $ada->disinkron_pada = $sekarang;
                    $ada->save();

                    $berubah && $hasil['diperbarui']++;

                    continue;
                }

                $kembar = ic_event::query()
                    ->whereNull('bit_id')
                    ->whereDate('tanggal', $p['tanggal'])
                    ->get()
                    ->first(fn(ic_event $e) => $this->mirip($e->venue, $p['venue']));

                if ($kembar) {
                    $kembar->forceFill(['bit_id' => $p['bit_id'], 'bit_url' => $p['bit_url'], 'disinkron_pada' => $sekarang])->save();
                    $hasil['terhubung']++;

                    continue;
                }

                $baru = $this->acara->buat([
                    'tanggal' => $p['tanggal'],
                    'tipe' => $p['tipe'],
                    'kota' => $p['kota'],
                    'venue' => $p['venue'],
                ]);
                $baru->forceFill([
                    'sumber' => 'bandsintown',
                    'bit_id' => $p['bit_id'],
                    'bit_url' => $p['bit_url'],
                    'disinkron_pada' => $sekarang,
                ])->save();
                $hasil['dibuat']++;
            }
        });

        $hasil['hilang'] = ic_event::query()
            ->where('sumber', 'bandsintown')
            ->whereNotNull('bit_id')
            ->whereNotIn('bit_id', $idTerlihat)
            ->whereDate('tanggal', '>=', $sekarang->toDateString())
            ->orderBy('tanggal')
            ->get()
            ->map(fn(ic_event $e) => $e->tanggal->format('d-m-Y') . ' · ' . ($e->venue ?? $e->kode))
            ->all();

        return $hasil;
    }

    /**
     * @return array<int, mixed>
     *
     * @throws SinkronGagal
     */
    private function ambil(): array
    {
        $artis = rawurlencode((string) config('services.bandsintown.artist'));

        try {
            $respons = Http::acceptJson()->timeout(15)->get(sprintf(self::URL_API, $artis), [
                'app_id' => config('services.bandsintown.app_id'),
                'date' => 'upcoming',
            ]);
        } catch (ConnectionException $e) {
            Log::warning('Bandsintown: gagal menghubungi server', ['pesan' => $e->getMessage()]);
            throw new SinkronGagal('Tidak bisa menghubungi Bandsintown saat ini. Coba lagi sebentar lagi.');
        }

        if (in_array($respons->status(), [401, 403], true)) {
            throw new SinkronGagal('Bandsintown menolak App ID ini. Periksa BANDSINTOWN_APP_ID di .env (lihat README, bagian Bandsintown).');
        }
        if ($respons->failed()) {
            Log::warning('Bandsintown: respons gagal', ['status' => $respons->status()]);
            throw new SinkronGagal('Bandsintown menolak permintaan (status ' . $respons->status() . '). Coba lagi nanti.');
        }

        $data = $respons->json();

        // Galat dari Bandsintown datang sebagai objek ({"errors": [...]} atau {"message": ...}), daftar acara sebagai array.
        if (! is_array($data) || isset($data['errors']) || isset($data['message']) || ($data !== [] && ! array_is_list($data))) {
            throw new SinkronGagal('Balasan Bandsintown tidak sesuai harapan. Periksa BANDSINTOWN_ARTIST dan BANDSINTOWN_APP_ID di .env.');
        }

        return $data;
    }

    /** @return array{bit_id: string, tanggal: string, kota: ?string, venue: ?string, tipe: string, bit_url: ?string}|null */
    private function petakan(mixed $e): ?array
    {
        if (! is_array($e)) {
            return null;
        }

        $id = trim((string) ($e['id'] ?? ''));
        // `datetime` berisi waktu setempat di venue (tanpa zona), jadi tanggalnya langsung dipakai apa adanya.
        $tanggal = substr((string) ($e['datetime'] ?? ''), 0, 10);

        if ($id === '' || mb_strlen($id) > 40 || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
            return null;
        }

        try {
            $valid = Carbon::createFromFormat('!Y-m-d', $tanggal);
        } catch (\Throwable) {
            return null;
        }
        if (! $valid || $valid->format('Y-m-d') !== $tanggal) {
            return null;
        }

        $tempat = is_array($e['venue'] ?? null) ? $e['venue'] : [];
        $venue = trim((string) ($tempat['name'] ?? ''));
        $kota = trim((string) ($tempat['city'] ?? ''));

        return [
            'bit_id' => $id,
            'tanggal' => $tanggal,
            'kota' => $kota !== '' ? mb_substr($kota, 0, 60) : null,
            'venue' => $venue !== '' ? mb_substr($venue, 0, 80) : null,
            'tipe' => preg_match(self::POLA_FESTIVAL, (string) ($e['title'] ?? '') . ' ' . $venue) ? 'festival' : 'club',
            'bit_url' => $this->bersihkanUrl((string) ($e['url'] ?? '')),
        ];
    }

    /** Tautan acara dari Bandsintown memuat app_id di query string; dibuang sebelum disimpan. */
    private function bersihkanUrl(string $url): ?string
    {
        $url = (string) strtok($url, '?');
        $host = (string) parse_url($url, PHP_URL_HOST);

        if (! str_starts_with($url, 'https://') || ! preg_match('/(^|\.)bandsintown\.com$/i', $host)) {
            return null;
        }

        return mb_substr($url, 0, 255);
    }

    private function mirip(?string $a, ?string $b): bool
    {
        $a = $this->bakukan($a);
        $b = $this->bakukan($b);

        if ($a === '' || $b === '') {
            return false;
        }

        return $a === $b || (min(strlen($a), strlen($b)) >= 4 && (str_contains($a, $b) || str_contains($b, $a)));
    }

    private function bakukan(?string $teks): string
    {
        return (string) preg_replace('/[^a-z0-9]+/', '', mb_strtolower((string) $teks));
    }
}