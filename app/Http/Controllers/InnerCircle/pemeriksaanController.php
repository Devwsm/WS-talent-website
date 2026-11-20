<?php

namespace App\Http\Controllers\InnerCircle;

use App\Http\Controllers\Controller;
use App\Models\ic_event;
use App\Models\member;
use App\Services\InnerCircle\PemeriksaanGagal;
use App\Services\InnerCircle\PemeriksaanService;
use App\Support\SidikPerangkat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Anggota mengajukan pemeriksaan kehadiran bila GPS-nya gagal saat check-in (Fase 5B).
 * Tinjauan dan persetujuannya ada di CMS (acaraPemeriksaanController).
 */
class pemeriksaanController extends Controller
{
    public function ajukan(Request $request, PemeriksaanService $layanan): RedirectResponse
    {
        $data = $request->validate([
            'acara' => ['required', 'string', 'max:20'],
            'alasan' => ['nullable', 'string', 'max:20'],
            'catatan' => ['nullable', 'string', 'max:' . (int) config('inner_circle.pemeriksaan.catatan_maks', 300)],
        ], [
            'acara.required' => 'Pilih acara yang mau diajukan pemeriksaannya.',
            'catatan.max' => 'Catatan terlalu panjang. Tulis singkat saja.',
        ]);

        $event = ic_event::query()->where('kode', $data['acara'])->first();
        if (! $event || ! $event->bisa_checkin) {
            return redirect()->route('ic.checkin')->with('ic_toast_galat', 'Acara tidak ditemukan.');
        }

        // Alasan dan angka ukur dari server (kegagalan check-in barusan) lebih dipercaya daripada isian form.
        $gagal = $layanan->gagalSesi($event->kode);
        $alasan = (string) ($gagal['alasan'] ?? $data['alasan'] ?? '');

        try {
            $layanan->ajukan(
                $this->anggota(),
                $event,
                $alasan,
                $data['catatan'] ?? null,
                $gagal ? ['jarak' => $gagal['jarak'] ?? null, 'akurasi' => $gagal['akurasi'] ?? null] : null,
                SidikPerangkat::untuk($request),
            );
        } catch (PemeriksaanGagal $e) {
            return redirect()->route('ic.acara.detail', $event->kode)->with('ic_toast_galat', $e->getMessage());
        }

        $layanan->bersihkanGagalSesi($event->kode);

        return redirect()->route('ic.acara.detail', $event->kode)
            ->with('ic_toast', 'Pengajuan terkirim. Tim WSM akan memeriksa kehadiranmu; statusnya tampil di halaman acara ini.');
    }

    private function anggota(): member
    {
        return Auth::guard('member')->user();
    }
}