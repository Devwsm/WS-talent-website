<?php

namespace App\Services\InnerCircle;

use App\Models\ic_event;
use App\Models\ic_poin;
use App\Models\member;
use Carbon\CarbonInterface;

/**
 * Data grafik tren di Ringkasan CMS Inner Circle. Hanya menghitung; tampilannya di komponen grafik-batang.
 */
class TrenRingkasan
{
    /**
     * Pendaftaran anggota baru per minggu (Senin sampai Minggu), `$minggu` minggu terakhir termasuk minggu ini.
     * Minggu tanpa pendaftaran tetap muncul dengan nilai 0 supaya sumbu waktunya tidak bolong.
     *
     * @return list<array{label: string, nilai: int, keterangan: string}>
     */
    public function pendaftaranMingguan(int $minggu = 12, ?CarbonInterface $sekarang = null): array
    {
        $awalIni = ($sekarang ?? now())->copy()->startOfWeek();
        $awal = $awalIni->copy()->subWeeks($minggu - 1);

        // Dikelompokkan di PHP (bukan SQL) supaya sama di MySQL dan SQLite; hanya kolom tanggal yang dibaca.
        $perMinggu = member::query()
            ->where('created_at', '>=', $awal)
            ->pluck('created_at')
            ->countBy(fn($t) => $t->copy()->startOfWeek()->toDateString());

        $hasil = [];
        for ($i = 0; $i < $minggu; $i++) {
            $mulai = $awal->copy()->addWeeks($i);
            $nilai = (int) ($perMinggu[$mulai->toDateString()] ?? 0);
            $selesai = $mulai->copy()->endOfWeek();
            $hasil[] = [
                'label' => $mulai->locale('id')->translatedFormat('j M'),
                'nilai' => $nilai,
                'keterangan' => 'Minggu ' . $mulai->locale('id')->translatedFormat('j M') . ' – ' . $selesai->locale('id')->translatedFormat('j M Y'),
            ];
        }

        return $hasil;
    }

    /**
     * Jumlah check-in per acara untuk `$acara` acara terakhir yang tanggalnya sudah tiba (lama ke baru, kiri ke kanan).
     *
     * @return list<array{label: string, nilai: int, keterangan: string}>
     */
    public function checkinPerAcara(int $acara = 8, ?CarbonInterface $sekarang = null): array
    {
        $hariIni = ($sekarang ?? now())->toDateString();

        $daftar = ic_event::query()
            ->where('tanggal', '<=', $hariIni)
            ->orderByDesc('tanggal')
            ->orderByDesc('id_event')
            ->limit($acara)
            ->get()
            ->reverse()
            ->values();

        if ($daftar->isEmpty()) {
            return [];
        }

        $jumlah = ic_poin::query()
            ->where('sumber', 'checkin')
            ->whereIn('event_id', $daftar->pluck('id_event'))
            ->selectRaw('event_id, count(*) as total')
            ->groupBy('event_id')
            ->pluck('total', 'event_id');

        return $daftar->map(fn(ic_event $e) => [
            'label' => $e->tanggal->locale('id')->translatedFormat('j M'),
            'nilai' => (int) ($jumlah[$e->id_event] ?? 0),
            'keterangan' => trim($e->venue . ', ' . $e->kota, ', ') . ' · ' . $e->tanggal->locale('id')->translatedFormat('j M Y'),
        ])->all();
    }
}