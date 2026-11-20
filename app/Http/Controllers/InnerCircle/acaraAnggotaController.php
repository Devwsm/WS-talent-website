<?php

namespace App\Http\Controllers\InnerCircle;

use App\Http\Controllers\Controller;
use App\Models\ic_event;
use App\Models\ic_poin;
use App\Models\member;
use App\Services\InnerCircle\CheckinService;
use App\Services\InnerCircle\GlGagal;
use App\Services\InnerCircle\GlService;
use App\Services\InnerCircle\PemeriksaanService;
use App\Services\InnerCircle\UndianService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Halaman detail acara untuk anggota (Fase 7): poster, info, kartu GL pemenang, dan dua tombol di dasar layar,
 * kiri Antri GL dan kanan Check-in. Check-in tetap memakai lembar check-in (pemindai QR, lokasi) yang dibuka
 * dari tombol Check-in. Tampilan mengikuti docs/referensi-ui/ (README 6.8).
 */
class acaraAnggotaController extends Controller
{
    public function detail(string $kode, GlService $gl, CheckinService $checkin)
    {
        $anggota = $this->anggota();
        $event = $this->acara($kode);

        // Pengaman undian bila cron belum terpasang: anggota yang membuka acara sesudah 18.00 tetap melihat hasilnya.
        app(UndianService::class)->pengamanKunjungan();
        $event->refresh();

        $event->setAttribute('sudah_checkin', ic_poin::query()
            ->where('member_id', $anggota->getKey())
            ->where('event_id', $event->getKey())
            ->exists());

        $statusGl = $gl->statusAnggota($anggota, $event);
        $pemenang = $statusGl === 'menang';

        return view('pages.inner-circle.acara-detail', [
            'member' => $anggota,
            'event' => $event,
            'statusGl' => $statusGl,
            'terverifikasi' => $anggota->email_verified_at !== null,
            'kartu' => $pemenang ? $gl->kartu($anggota, $event) : null,
            'statusCheckin' => $event->statusCheckin(),
            'pemenangGl' => $pemenang,
            // Tombol mandiri hanya untuk non-pemenang; pemenang check-in dengan memindai QR di pintu.
            'bolehMandiri' => ! $pemenang,
            'waktuUndian' => $event->undian_efektif,
            'poinMandiri' => $checkin->poinUntuk($event, false),
            'poinGl' => $checkin->poinUntuk($event, true),
            // Pemeriksaan kehadiran (Fase 5B): formulir ajukan bila GPS barusan gagal, dan status pengajuan yang sudah ada.
            'ajukan' => app(PemeriksaanService::class)->tawaran($anggota, $event),
        ]);
    }

    public function antri(string $kode, GlService $gl): RedirectResponse
    {
        $event = $this->acara($kode);

        try {
            $gl->antri($this->anggota(), $event);
        } catch (GlGagal $e) {
            return $this->kembali($event)->with('ic_toast_galat', $e->getMessage());
        }

        return $this->kembali($event)->with('ic_toast', 'Kamu masuk antrean GL. Hasil undian diumumkan ' . $event->undian_efektif->locale('id')->translatedFormat('D, j M \p\u\k\u\l H.i') . ' WIB.');
    }

    public function keluar(string $kode, GlService $gl): RedirectResponse
    {
        $event = $this->acara($kode);

        try {
            $gl->keluar($this->anggota(), $event);
        } catch (GlGagal $e) {
            return $this->kembali($event)->with('ic_toast_galat', $e->getMessage());
        }

        return $this->kembali($event)->with('ic_toast', 'Kamu keluar dari antrean GL.');
    }

    /** Acara privat dan acara tanpa venue tidak punya halaman detail (sama seperti check-in). */
    private function acara(string $kode): ic_event
    {
        $event = ic_event::query()->where('kode', $kode)->firstOrFail();
        abort_unless($event->bisa_checkin, 404);

        return $event;
    }

    private function kembali(ic_event $event): RedirectResponse
    {
        return redirect()->route('ic.acara.detail', $event->kode);
    }

    private function anggota(): member
    {
        return Auth::guard('member')->user();
    }
}