<?php

namespace App\Services\InnerCircle;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cari koordinat dari alamat/nama venue lewat Nominatim (OpenStreetMap, gratis, tanpa kunci API).
 * Dipakai petugas di dashboard untuk mengisi titik lokasi acara. Hasilnya hanya saran: petugas yang memutuskan
 * dan sebaiknya memeriksanya lewat tautan peta sebelum menyimpan.
 *
 * Kebijakan Nominatim: maksimal 1 permintaan per detik dan wajib mengirim User-Agent yang jelas. Pemakaian di sini
 * dipicu klik petugas dan dibatasi route (lihat config inner_circle.lokasi.cari_alamat_per_menit).
 * Nominatim MELARANG autocomplete (saran saat mengetik), jadi saran saat mengetik memakai Photon (photon.komoot.io),
 * mesin pencari OpenStreetMap yang memang dibuat untuk itu. Dua-duanya memakai data OpenStreetMap, jadi venue yang
 * belum ada di OSM tidak akan muncul; petugas tetap bisa klik peta atau menempel koordinat.
 */
class GeocodeService
{
    private const URL = 'https://nominatim.openstreetmap.org/search';

    private const URL_SARAN = 'https://photon.komoot.io/api/';

    /** Saran yang sama dipakai ulang sebentar: mengurangi permintaan ke layanan gratis ini. */
    private const CACHE_SARAN_MENIT = 15;

    /**
     * Saran alamat saat petugas mengetik. Dipanggil lewat server kita (bukan langsung dari browser) supaya
     * CSP tetap rapat, hasilnya bisa di-cache, dan pemakaiannya bisa dibatasi.
     *
     * @return array<int, array{judul: string, detail: string, nama: string, lat: float, lng: float}>
     *
     * @throws SinkronGagal  bila layanan tidak bisa dihubungi
     */
    public function saran(string $kueri, int $maks = 6, ?float $biasLat = null, ?float $biasLng = null): array
    {
        $kueri = trim((string) preg_replace('/\s+/u', ' ', $kueri));
        if (mb_strlen($kueri) < 3) {
            return [];
        }
        $kueri = mb_substr($kueri, 0, 150);
        $maks = max(1, min(8, $maks));

        // Condong ke titik acara bila sudah ada; bila tidak, ke tengah Indonesia.
        $lat = $biasLat ?? -2.5;
        $lng = $biasLng ?? 118.0;

        $kunci = 'geocode-saran:' . sha1(mb_strtolower($kueri) . '|' . $maks . '|' . round($lat, 1) . '|' . round($lng, 1));
        $tersimpan = Cache::get($kunci);
        if (is_array($tersimpan)) {
            return $tersimpan;
        }

        try {
            $respons = Http::acceptJson()
                ->timeout(5)
                ->withHeaders(['User-Agent' => 'WS-Inner-Circle/1.0 (' . config('app.url') . ')'])
                ->get(self::URL_SARAN, [
                    'q' => $kueri,
                    'limit' => $maks,
                    'lat' => $lat,
                    'lon' => $lng,
                ]);
        } catch (ConnectionException $e) {
            Log::warning('Geocode: gagal menghubungi layanan saran alamat', ['pesan' => $e->getMessage()]);
            throw new SinkronGagal('Saran alamat tidak bisa dimuat sekarang. Tekan Cari, klik peta, atau tempel koordinat.');
        }

        if ($respons->failed() || ! is_array($respons->json('features'))) {
            Log::warning('Geocode: respons saran gagal', ['status' => $respons->status()]);
            throw new SinkronGagal('Saran alamat sedang bermasalah. Tekan Cari, klik peta, atau tempel koordinat.');
        }

        $hasil = [];
        $dilihat = [];
        foreach ($respons->json('features') as $fitur) {
            $p = is_array($fitur['properties'] ?? null) ? $fitur['properties'] : [];
            $koordinat = is_array($fitur['geometry']['coordinates'] ?? null) ? $fitur['geometry']['coordinates'] : [];
            $b = $koordinat[1] ?? null;
            $l = $koordinat[0] ?? null;
            if (! LokasiService::koordinatValid($b, $l)) {
                continue;
            }

            $jalan = trim(((string) ($p['street'] ?? '')) . ' ' . ((string) ($p['housenumber'] ?? '')));
            $judul = trim((string) ($p['name'] ?? '')) !== '' ? trim((string) $p['name']) : $jalan;
            $bagian = [];
            foreach ([$judul !== $jalan ? $jalan : '', $p['district'] ?? '', $p['locality'] ?? '', $p['city'] ?? '', $p['county'] ?? '', $p['state'] ?? '', $p['country'] ?? ''] as $b2) {
                $b2 = trim((string) $b2);
                if ($b2 !== '' && $b2 !== $judul && ! in_array($b2, $bagian, true)) {
                    $bagian[] = $b2;
                }
            }
            if ($judul === '') {
                $judul = $bagian !== [] ? array_shift($bagian) : 'Tanpa nama';
            }

            $nama = mb_substr(implode(', ', array_merge([$judul], $bagian)), 0, 180);
            if (isset($dilihat[$nama])) {
                continue;
            }
            $dilihat[$nama] = true;

            $hasil[] = [
                'judul' => mb_substr($judul, 0, 90),
                'detail' => mb_substr(implode(', ', $bagian), 0, 150),
                'nama' => $nama,
                'lat' => round((float) $b, 7),
                'lng' => round((float) $l, 7),
            ];
        }

        Cache::put($kunci, $hasil, now()->addMinutes(self::CACHE_SARAN_MENIT));

        return $hasil;
    }

    /**
     * @return array<int, array{nama: string, lat: float, lng: float}>
     *
     * @throws SinkronGagal  bila layanan tidak bisa dihubungi
     */
    public function cari(string $kueri, int $maks = 4): array
    {
        $kueri = trim($kueri);
        if ($kueri === '') {
            return [];
        }

        try {
            $respons = Http::acceptJson()
                ->timeout(8)
                ->withHeaders(['User-Agent' => 'WS-Inner-Circle/1.0 (' . config('app.url') . ')'])
                ->get(self::URL, [
                    'q' => mb_substr($kueri, 0, 200),
                    'format' => 'jsonv2',
                    'limit' => max(1, min(5, $maks)),
                    'accept-language' => 'id',
                ]);
        } catch (ConnectionException $e) {
            Log::warning('Geocode: gagal menghubungi layanan alamat', ['pesan' => $e->getMessage()]);
            throw new SinkronGagal('Layanan pencarian alamat tidak bisa dihubungi. Pakai tombol lokasi HP atau ketik koordinat.');
        }

        if ($respons->failed() || ! is_array($respons->json())) {
            Log::warning('Geocode: respons gagal', ['status' => $respons->status()]);
            throw new SinkronGagal('Layanan pencarian alamat sedang bermasalah. Coba lagi nanti, atau ketik koordinat.');
        }

        $hasil = [];
        foreach ($respons->json() as $baris) {
            if (! is_array($baris) || ! LokasiService::koordinatValid($baris['lat'] ?? null, $baris['lon'] ?? null)) {
                continue;
            }

            $hasil[] = [
                'nama' => mb_substr((string) ($baris['display_name'] ?? 'Tanpa nama'), 0, 180),
                'lat' => round((float) $baris['lat'], 7),
                'lng' => round((float) $baris['lon'], 7),
            ];
        }

        return $hasil;
    }
}