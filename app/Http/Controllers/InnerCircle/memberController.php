<?php

namespace App\Http\Controllers\InnerCircle;

use App\Http\Controllers\Controller;
use App\Support\InnerCircleDummy;

/**
 * Layar anggota WS Inner Circle.
 * Fase 1: semua layar masih placeholder + pratinjau komponen (data dummy).
 * Fase 2 mengganti placeholder dengan layar asli; Fase 4+ mengganti dummy dengan Eloquent.
 */
class memberController extends Controller
{
    public function beranda()
    {
        return $this->placeholder('Beranda', 'Musik seru. Teman baru.');
    }

    public function hadiah()
    {
        return $this->placeholder('Hadiah', 'Kumpulkan • Buka • Rasakan');
    }

    public function checkin()
    {
        return $this->placeholder('Check-in', 'Datang. Kumpulkan stempel.');
    }

    public function komunitas()
    {
        return $this->placeholder('Komunitas', 'Lebih banyak musik • Teman yang sama');
    }

    public function profil()
    {
        return $this->placeholder('Profil', 'Perjalanan Inner Circle-mu');
    }

    /** Empat event contoh yang mewakili tiap kondisi: lewat, sudah check-in, privat, tanpa venue. */
    private function contohEvent(): array
    {
        $kode = ['WS26-1001', 'WS26-1008', 'WS26-1021', 'WS26-1023'];

        return array_values(array_filter(
            InnerCircleDummy::events(['bulan' => 10]),
            fn($e) => in_array($e->kode, $kode, true),
        ));
    }

    private function placeholder(string $judul, string $eyebrow)
    {
        return view('pages.inner-circle.placeholder', [
            'judul' => $judul,
            'eyebrow' => $eyebrow,
            'member' => InnerCircleDummy::member(),
            'rewards' => InnerCircleDummy::rewards(),
            'berikutnya' => InnerCircleDummy::berikutnya(),
            'aktivitas' => InnerCircleDummy::aktivitas(),
            'events' => $this->contohEvent(),
            'unggulan' => InnerCircleDummy::pengumumanUnggulan(),
            'pengumuman' => array_slice(InnerCircleDummy::pengumuman(), 0, 2),
            'kenangan' => array_slice(InnerCircleDummy::kenangan(), 0, 2),
        ]);
    }
}