<?php

namespace App\Http\Controllers\InnerCircle;

use App\Http\Controllers\Controller;
use App\Support\InnerCircleDummy;
use Illuminate\Http\Request;

/**
 * Layar anggota WS Inner Circle.
 * Fase 2: semua layar sudah asli, datanya masih dari InnerCircleDummy.
 * Fase 4+ mengganti InnerCircleDummy dengan Eloquent; nama variabel ke view tidak berubah.
 */
class memberController extends Controller
{
    public function beranda()
    {
        return view('pages.inner-circle.beranda', [
            'member' => InnerCircleDummy::member(),
            'berikutnya' => InnerCircleDummy::berikutnya(),
            'aktivitas' => array_slice(InnerCircleDummy::aktivitas(), 0, 5),
        ]);
    }

    public function hadiah()
    {
        return view('pages.inner-circle.hadiah', [
            'member' => InnerCircleDummy::member(),
            'rewards' => InnerCircleDummy::rewards(),
        ]);
    }

    /**
     * Filter jadwal lewat GET (?bulan=&tipe=&q=) dan acara terpilih lewat ?acara=KODE,
     * jadi bisa dibagikan dan tetap jalan tanpa JavaScript.
     */
    public function checkin(Request $request)
    {
        $bulan = $this->teks($request, 'bulan');
        $tipe = $this->teks($request, 'tipe');

        $filter = [
            'bulan' => array_key_exists((int) $bulan, config('inner_circle.bulan_event')) ? (string) (int) $bulan : 'all',
            'tipe' => array_key_exists($tipe, config('inner_circle.tipe_event')) ? $tipe : 'all',
            'q' => mb_substr(trim($this->teks($request, 'q')), 0, 60),
        ];

        // Parameter filter yang bukan nilai bawaan, dipakai ulang di tautan tiap kartu acara.
        $parameterFilter = array_filter([
            'bulan' => $filter['bulan'] !== 'all' ? $filter['bulan'] : null,
            'tipe' => $filter['tipe'] !== 'all' ? $filter['tipe'] : null,
            'q' => $filter['q'] !== '' ? $filter['q'] : null,
        ]);

        $kode = $this->teks($request, 'acara');
        $eventDipilih = collect(InnerCircleDummy::events())
            ->first(fn($e) => $e->kode === $kode && $e->bisa_checkin);

        return view('pages.inner-circle.checkin', [
            'member' => InnerCircleDummy::member(),
            'events' => InnerCircleDummy::events($filter),
            'totalEvents' => InnerCircleDummy::totalEvents(),
            'filter' => $filter,
            'parameterFilter' => $parameterFilter,
            'eventDipilih' => $eventDipilih,
        ]);
    }

    /** Tab lewat ?tab=pengumuman|galeri dan kategori galeri lewat ?kategori=festival|club|personal. */
    public function komunitas(Request $request)
    {
        $tab = $this->teks($request, 'tab') === 'galeri' ? 'galeri' : 'pengumuman';

        $kategori = $this->teks($request, 'kategori');
        $kategori = array_key_exists($kategori, config('inner_circle.kategori_kenangan')) ? $kategori : 'all';

        return view('pages.inner-circle.komunitas', [
            'member' => InnerCircleDummy::member(),
            'tab' => $tab,
            'kategori' => $kategori,
            'unggulan' => InnerCircleDummy::pengumumanUnggulan(),
            'pengumuman' => InnerCircleDummy::pengumuman(),
            'kenangan' => InnerCircleDummy::kenangan($kategori === 'all' ? null : $kategori),
            'jumlahKenangan' => count(InnerCircleDummy::kenangan()),
        ]);
    }

    public function profil()
    {
        return view('pages.inner-circle.profil', [
            'member' => InnerCircleDummy::member(),
            'metrik' => InnerCircleDummy::metrik(),
            'riwayat' => InnerCircleDummy::riwayat(),
            'klaim' => InnerCircleDummy::klaim(),
        ]);
    }

    /** Ambil parameter query sebagai teks; array atau nilai aneh dianggap kosong. */
    private function teks(Request $request, string $kunci): string
    {
        $nilai = $request->query($kunci);

        return is_string($nilai) ? $nilai : '';
    }
}