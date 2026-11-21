<?php

namespace App\Http\Controllers\InnerCircle;

use App\Http\Controllers\Controller;
use App\Http\Requests\InnerCircle\kirimPengaduanRequest;
use App\Models\ic_event;
use App\Models\member;
use App\Services\InnerCircle\PengaduanGagal;
use App\Services\InnerCircle\PengaduanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/** Pengaduan di area anggota (Fase 9): formulir dan daftar pengaduan milik sendiri. Dilindungi `auth:member`. */
class pengaduanController extends Controller
{
    public function index(PengaduanService $pengaduan)
    {
        /** @var member $anggota */
        $anggota = Auth::guard('member')->user();

        return view('pages.inner-circle.pengaduan', [
            'member' => $anggota,
            'daftar' => $pengaduan->milik($anggota),
            'sisaHariIni' => $pengaduan->sisaHariIni($anggota),
            // Acara 90 hari terakhir sampai yang akan datang, terbaru dulu, untuk pilihan "acara terkait".
            'acara' => ic_event::query()
                ->where('tanggal', '>=', now()->subDays(90)->toDateString())
                ->orderByDesc('tanggal')
                ->limit(60)
                ->get(),
        ]);
    }

    public function kirim(kirimPengaduanRequest $request, PengaduanService $pengaduan): RedirectResponse
    {
        /** @var member $anggota */
        $anggota = Auth::guard('member')->user();

        try {
            $pengaduan->kirim(
                $anggota,
                $request->validated('kategori'),
                $request->validated('event_id') !== null ? (int) $request->validated('event_id') : null,
                $request->validated('isi'),
            );
        } catch (PengaduanGagal $e) {
            return redirect(route('ic.pengaduan') . '#form-pengaduan')->withInput()->with('ic_toast_galat', $e->getMessage());
        }

        return redirect(route('ic.pengaduan'))->with('ic_toast', 'Pengaduan terkirim. Tim WSM akan menindaklanjuti dan memberi kabar lewat halaman ini dan email.');
    }
}