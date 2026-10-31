<?php

namespace App\Http\Controllers\InnerCircle;

use App\Http\Controllers\Controller;
use App\Models\ic_event;
use App\Models\ic_reward;
use Illuminate\Support\Facades\Auth;

/**
 * Halaman publik yang menjelaskan WS Inner Circle untuk calon anggota (tanpa login, boleh diindeks mesin pencari).
 * Tamu yang membuka /innercircle dialihkan ke sini (lihat bootstrap/app.php). Hanya data yang memang boleh dilihat
 * publik yang ditampilkan: tahap hadiah aktif dan jadwal acara mendatang yang tidak privat.
 */
class tentangController extends Controller
{
    public function index()
    {
        $hadiah = ic_reward::query()
            ->where('aktif', true)
            ->orderBy('urutan')
            ->orderBy('target_stempel')
            ->get();

        // Acara mendatang yang bisa dihadiri (bukan privat, venue sudah diumumkan), paling dekat dulu.
        $acara = ic_event::query()
            ->where('tanggal', '>=', today()->toDateString())
            ->where('tipe', '!=', 'private')
            ->orderBy('tanggal')
            ->limit(12)
            ->get()
            ->filter(fn(ic_event $e) => $e->bisa_checkin)
            ->take(4)
            ->each(fn(ic_event $e) => $e->setAttribute('sudah_checkin', false))
            ->values();

        return view('pages.inner-circle.tentang', [
            'hadiah' => $hadiah,
            'acara' => $acara,
            'sudahMasuk' => Auth::guard('member')->check(),
        ]);
    }
}