<?php

namespace App\Services\InnerCircle;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cari koordinat dari alamat/nama venue lewat Nominatim (OpenStreetMap, gratis, tanpa kunci API).
 * Dipakai petugas di dashboard untuk mengisi titik lokasi acara. Hasilnya hanya saran: petugas yang memutuskan
 * dan sebaiknya memeriksanya lewat tautan peta sebelum menyimpan.
 *
 * Kebijakan Nominatim: maksimal 1 permintaan per detik dan wajib mengirim User-Agent yang jelas. Pemakaian di sini
 * dipicu klik petugas dan dibatasi route (lihat config inner_circle.lokasi.cari_alamat_per_menit).
 */
class GeocodeService
{
    private const URL = 'https://nominatim.openstreetmap.org/search';

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