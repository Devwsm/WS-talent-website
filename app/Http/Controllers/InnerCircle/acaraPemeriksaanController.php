<?php

namespace App\Http\Controllers\InnerCircle;

use App\Http\Controllers\Concerns\PembantuHalamanAdminIc;
use App\Http\Controllers\Controller;
use App\Models\ic_event;
use App\Models\ic_pemeriksaan;
use App\Models\ic_penanda;
use App\Services\InnerCircle\CheckinService;
use App\Services\InnerCircle\PemeriksaanGagal;
use App\Services\InnerCircle\PemeriksaanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * CMS Inner Circle, Acara > Pemeriksaan (Fase 5B). Dua daftar dalam satu halaman:
 * - Pengajuan: anggota yang GPS-nya gagal meminta kehadirannya diperiksa. Disetujui = poin check-in masuk; ditolak = alasan.
 * - Penanda: check-in yang tampak janggal (perangkat sama, lokasi terlalu sempurna, perjalanan mustahil, banyak
 *   percobaan gagal). Check-in sudah tercatat; admin menandai aman atau ditindak (koreksi poin / nonaktifkan akun
 *   dilakukan lewat detail anggota yang sudah ada).
 *
 * Filter lewat query: ?tab=pengajuan|penanda, ?status=..., ?acara=KODE.
 */
class acaraPemeriksaanController extends Controller
{
    use PembantuHalamanAdminIc;

    private const PER_HALAMAN = 20;

    public function index(Request $request, CheckinService $checkin)
    {
        $tab = $this->teks($request, 'tab') === 'penanda' ? 'penanda' : 'pengajuan';

        $kodeAcara = $this->teks($request, 'acara');
        $acara = $kodeAcara !== '' ? ic_event::query()->where('kode', $kodeAcara)->first() : null;

        $statusPengajuan = $this->teks($request, 'status');
        $statusPengajuan = in_array($statusPengajuan, ['semua', ic_pemeriksaan::DISETUJUI, ic_pemeriksaan::DITOLAK], true)
            ? $statusPengajuan
            : ic_pemeriksaan::MENUNGGU;

        $statusPenanda = $this->teks($request, 'status');
        $statusPenanda = in_array($statusPenanda, ['semua', ic_penanda::AMAN, ic_penanda::DITINDAK], true)
            ? $statusPenanda
            : ic_penanda::BARU;

        $dasarPengajuan = ic_pemeriksaan::query()->when($acara, fn($q) => $q->where('event_id', $acara->getKey()));
        $dasarPenanda = ic_penanda::query()->when($acara, fn($q) => $q->where('event_id', $acara->getKey()));

        $pengajuan = null;
        $penanda = null;

        if ($tab === 'pengajuan') {
            $pengajuan = (clone $dasarPengajuan)
                ->with(['anggota', 'event'])
                ->when($statusPengajuan !== 'semua', fn($q) => $q->where('status', $statusPengajuan))
                ->when(
                    $statusPengajuan === ic_pemeriksaan::MENUNGGU,
                    fn($q) => $q->orderBy('created_at')->orderBy('id_pemeriksaan'),
                    fn($q) => $q->orderByDesc('created_at')->orderByDesc('id_pemeriksaan'),
                )
                ->paginate(self::PER_HALAMAN)
                ->withQueryString()
                ->fragment('daftar');

            // Poin yang akan diberikan bila disetujui: sama seperti check-in biasa (poin GL untuk pemenang GL).
            $pengajuan->getCollection()->each(function (ic_pemeriksaan $p) use ($checkin) {
                if (! $p->anggota || ! $p->event) {
                    return;
                }
                $pemenang = $checkin->pemenangGl($p->anggota, $p->event);
                $p->setAttribute('pemenang_gl', $pemenang);
                $p->setAttribute('poin_akan', $checkin->poinUntuk($p->event, $pemenang));
            });
        } else {
            $penanda = (clone $dasarPenanda)
                ->with(['anggota', 'event'])
                ->when($statusPenanda !== 'semua', fn($q) => $q->where('status', $statusPenanda))
                ->orderByDesc('created_at')
                ->orderByDesc('id_penanda')
                ->paginate(self::PER_HALAMAN)
                ->withQueryString()
                ->fragment('daftar');
        }

        return $this->tampil('acara.pemeriksaan', [
            'tab' => $tab,
            'acara' => $acara,
            'statusPengajuan' => $statusPengajuan,
            'statusPenanda' => $statusPenanda,
            'pengajuan' => $pengajuan,
            'penanda' => $penanda,
            'jumlahPengajuanMenunggu' => (clone $dasarPengajuan)->where('status', ic_pemeriksaan::MENUNGGU)->count(),
            'jumlahPenandaBaru' => (clone $dasarPenanda)->where('status', ic_penanda::BARU)->count(),
        ]);
    }

    public function setujui(int $id, Request $request, PemeriksaanService $layanan): RedirectResponse
    {
        $data = $request->validate(['catatan' => ['nullable', 'string', 'max:300']], [
            'catatan.max' => 'Catatan maksimal 300 karakter.',
        ]);

        try {
            $poin = $layanan->setujui($id, session('user'), $data['catatan'] ?? null);
        } catch (PemeriksaanGagal $e) {
            return back()->withErrors(['pemeriksaan' => $e->getMessage()]);
        }

        return back()->with('success', 'Pengajuan disetujui. ' . ic_angka($poin->jumlah) . ' poin masuk ke anggota.');
    }

    public function tolak(int $id, Request $request, PemeriksaanService $layanan): RedirectResponse
    {
        $data = $request->validate(['alasan' => ['required', 'string', 'min:5', 'max:300']], [
            'alasan.required' => 'Isi alasan penolakan supaya anggota tahu sebabnya.',
            'alasan.min' => 'Alasan penolakan minimal 5 huruf.',
            'alasan.max' => 'Alasan penolakan maksimal 300 karakter.',
        ]);

        try {
            $layanan->tolak($id, session('user'), $data['alasan']);
        } catch (PemeriksaanGagal $e) {
            return back()->withErrors(['pemeriksaan' => $e->getMessage()]);
        }

        return back()->with('success', 'Pengajuan ditolak. Alasannya tampil ke anggota di halaman acara.');
    }

    /** Ubah status penanda: aman (bukan masalah), ditindak (sudah diurus), atau baru (buka kembali). */
    public function ubahPenanda(int $id, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:' . implode(',', [ic_penanda::BARU, ic_penanda::AMAN, ic_penanda::DITINDAK])],
            'catatan' => ['nullable', 'string', 'max:200'],
        ], [
            'status.*' => 'Pilih status penanda yang valid.',
            'catatan.max' => 'Catatan maksimal 200 karakter.',
        ]);

        $penanda = ic_penanda::query()->findOrFail($id);
        $baru = $data['status'] === ic_penanda::BARU;
        $catatan = isset($data['catatan']) ? trim($data['catatan']) : null;

        $penanda->update([
            'status' => $data['status'],
            'catatan_admin' => $baru ? null : ($catatan === '' ? null : $catatan),
            'ditinjau_oleh' => $baru ? null : mb_substr((string) session('user'), 0, 60),
            'ditinjau_pada' => $baru ? null : now(),
        ]);

        return back()->with('success', match ($data['status']) {
            ic_penanda::AMAN => 'Penanda ditandai aman.',
            ic_penanda::DITINDAK => 'Penanda ditandai sudah ditindak.',
            default => 'Penanda dibuka kembali.',
        });
    }
}