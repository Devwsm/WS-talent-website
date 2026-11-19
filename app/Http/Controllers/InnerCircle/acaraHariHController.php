<?php

namespace App\Http\Controllers\InnerCircle;

use App\Http\Controllers\Concerns\PembantuHalamanAdminIc;
use App\Http\Controllers\Controller;
use App\Http\Requests\InnerCircle\simpanLokasiRequest;
use App\Models\ic_event;
use App\Models\ic_poin;
use App\Services\AktivitasService;
use App\Services\InnerCircle\GeocodeService;
use App\Services\InnerCircle\LokasiService;
use App\Services\InnerCircle\QrService;
use App\Services\InnerCircle\SinkronGagal;
use App\Support\CsvEkspor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * CMS Inner Circle, menu Acara bagian 2: Hari H. Saat ini berisi pembuat dan cetak QR check-in, titik lokasi, dan
 * unduhan kehadiran per acara. Daftar yang hadir (GL gratis dan beli tiket sendiri) dan panel undian menyusul
 * di fase berikutnya pada halaman ini.
 */
class acaraHariHController extends Controller
{
    use PembantuHalamanAdminIc;

    /** Acara untuk panel QR dipilih lewat ?acara=KODE. */
    public function index(Request $request, QrService $qr)
    {
        // Hanya acara yang bisa check-in (bukan privat, ada venue) yang punya QR.
        $acaraQr = ic_event::query()
            ->where('tipe', '!=', 'private')
            ->whereNotNull('venue')
            ->where('venue', '!=', '')
            ->orderBy('tanggal')
            ->orderBy('id_event')
            ->get();

        $kode = $this->teks($request, 'acara');
        $acaraDipilih = $acaraQr->first(fn($e) => $e->kode === $kode)
            ?? $acaraQr->first(fn($e) => $e->status_waktu !== 'lewat')
            ?? $acaraQr->last();

        return $this->tampil('acara.hari-h', [
            'acaraQr' => $acaraQr,
            'acaraDipilih' => $acaraDipilih,
            // Token yang tidak terbaca (APP_KEY berbeda) tidak bisa dijadikan QR: tampilkan peringatan, bukan galat.
            'qrSvg' => $acaraDipilih && ! $acaraDipilih->tokenRusak() ? $qr->svgInline($acaraDipilih, 400) : null,
            'tautanQr' => $acaraDipilih && ! $acaraDipilih->tokenRusak() ? $qr->tautan($acaraDipilih) : null,
            'jumlahTokenRusak' => ic_event::query()->get()->filter(fn(ic_event $e) => $e->tokenRusak())->count(),
        ]);
    }

    /** Unduh daftar yang check-in di satu acara (CSV): bahan laporan setelah event. */
    public function eksporKehadiran(string $kode, AktivitasService $aktivitas)
    {
        $acara = ic_event::query()->where('kode', $kode)->firstOrFail();

        $query = ic_poin::query()
            ->with('member')
            ->where('sumber', 'checkin')
            ->where('event_id', $acara->getKey())
            ->orderBy('created_at')
            ->orderBy('id_poin');

        $jumlah = (clone $query)->count();
        $label = trim($acara->venue . ', ' . $acara->kota, ', ') . ' (' . $acara->tanggal->toDateString() . ')';
        $aktivitas->catat('Kehadiran acara diunduh', "Ekspor CSV kehadiran {$label}: {$jumlah} anggota.", 'berhasil', 'inner-circle', null, 'ic.admin.acara.kehadiran');

        $baris = (function () use ($query) {
            $no = 0;
            foreach ($query->cursor() as $s) {
                yield [
                    ++$no,
                    $s->member?->kode_member,
                    $s->member?->nama,
                    $s->member?->email,
                    $s->created_at?->format('Y-m-d H:i:s'),
                    $s->jarak_m,
                    $s->akurasi_m,
                    $s->jumlah,
                ];
            }
        })();

        return CsvEkspor::unduh(
            'kehadiran-' . $acara->kode . '-' . $acara->tanggal->toDateString() . '.csv',
            ['No', 'Kode anggota', 'Nama', 'Email', 'Waktu check-in', 'Jarak dari titik acara (m)', 'Akurasi lokasi (m)', 'Poin'],
            $baris,
        );
    }

    /**
     * Token acara yang tersimpan tetapi tidak bisa dibuka (biasanya APP_KEY berbeda dari saat dibuat) diganti baru.
     * Token lama memang tidak bisa dipulihkan tanpa kunci aslinya; QR acara itu harus dicetak ulang.
     */
    public function perbaikiToken(): RedirectResponse
    {
        $jumlah = 0;

        ic_event::query()->get()->each(function (ic_event $event) use (&$jumlah) {
            if ($event->tokenRusak()) {
                $event->forceFill(['token' => ic_event::buatToken(), 'token_dibuat_pada' => now()])->save();
                $jumlah++;
            }
        });

        return redirect(route('ic.admin.acara.harih') . '#qr')->with('success', $jumlah > 0
            ? "{$jumlah} token acara diperbaiki. Unduh atau cetak ulang QR untuk acara tersebut."
            : 'Semua token acara sudah terbaca, tidak ada yang perlu diperbaiki.');
    }

    /** Ganti token: QR lama langsung tidak berlaku. */
    public function tokenBaru(string $kode): RedirectResponse
    {
        $event = ic_event::query()->where('kode', $kode)->firstOrFail();
        abort_unless($event->bisa_checkin, 404);

        $event->forceFill(['token' => ic_event::buatToken(), 'token_dibuat_pada' => now()])->save();

        return redirect(route('ic.admin.acara.harih', ['acara' => $event->kode]) . '#qr')
            ->with('success', 'QR baru dibuat. QR yang lama sudah tidak berlaku.');
    }

    /**
     * Simpan titik lokasi check-in. Titik dan radius milik ACARA: satu akun CMS yang dipakai banyak petugas tidak bikin
     * radius ganda, karena yang tersimpan hanya satu titik per acara (yang terakhir disimpan berlaku).
     * Memindahkan titik yang sudah ada ke tempat yang jauh harus ditegaskan, supaya petugas yang sedang tidak di venue
     * tidak menimpa titik yang benar tanpa sengaja.
     */
    public function simpanLokasi(string $kode, simpanLokasiRequest $request): RedirectResponse
    {
        $event = ic_event::query()->where('kode', $kode)->firstOrFail();
        abort_unless($event->bisa_checkin, 404);

        $d = $request->validated();
        $lat = round((float) $d['lat'], 7);
        $lng = round((float) $d['lng'], 7);

        if ($event->lokasi_aktif && ! $d['konfirmasi_geser']) {
            $geser = LokasiService::jarakMeter((float) $event->lokasi_lat, (float) $event->lokasi_lng, $lat, $lng);
            if ($geser > (float) config('inner_circle.lokasi.geser_konfirmasi_m')) {
                return back()->withInput()->withErrors([
                    'lat' => 'Titik baru berjarak ' . LokasiService::formatJarak($geser) . ' dari titik yang sudah tersimpan. '
                        . 'Bila memang ingin memindahkannya, centang konfirmasi pemindahan lalu simpan lagi.',
                ]);
            }
        }

        $event->update([
            'lokasi_lat' => $lat,
            'lokasi_lng' => $lng,
            'lokasi_radius' => (int) $d['radius'],
            'lokasi_sumber' => $d['sumber'],
            'lokasi_diatur_oleh' => $d['petugas'] ?? null,
            'lokasi_diatur_pada' => now(),
        ]);

        return redirect(route('ic.admin.acara.harih', ['acara' => $event->kode]) . '#lokasi')
            ->with('success', 'Titik lokasi check-in ' . ($event->venue ?? $event->kode) . ' disimpan (radius ' . $event->radius_efektif . ' m).');
    }

    /** Matikan pemeriksaan lokasi untuk satu acara: QR-nya kembali bisa dipakai dari mana saja. */
    public function hapusLokasi(string $kode): RedirectResponse
    {
        $event = ic_event::query()->where('kode', $kode)->firstOrFail();

        $event->update([
            'lokasi_lat' => null,
            'lokasi_lng' => null,
            'lokasi_radius' => null,
            'lokasi_sumber' => null,
            'lokasi_diatur_oleh' => null,
            'lokasi_diatur_pada' => null,
        ]);

        return redirect(route('ic.admin.acara.harih', ['acara' => $event->kode]) . '#lokasi')
            ->with('success', 'Titik lokasi dihapus. Check-in acara ini tidak lagi memeriksa lokasi.');
    }

    /** Cari koordinat dari nama venue (atau alamat yang diketik petugas). Hasilnya hanya saran, JSON untuk tombol di panel. */
    public function cariLokasi(string $kode, Request $request, GeocodeService $geo): JsonResponse
    {
        $event = ic_event::query()->where('kode', $kode)->firstOrFail();
        abort_unless($event->bisa_checkin, 404);

        $alamat = mb_substr(trim((string) $request->input('alamat')), 0, 150);
        $kueri = $alamat !== '' ? $alamat : trim(($event->venue ?? '') . ', ' . ($event->kota ?? ''), ', ');

        try {
            $hasil = $geo->cari($kueri);
        } catch (SinkronGagal $e) {
            return response()->json(['pesan' => $e->getMessage()], 502);
        }

        return response()->json(['kueri' => $kueri, 'hasil' => $hasil]);
    }

    public function unduhQr(string $kode, QrService $qr): Response
    {
        $event = $this->eventDenganQr($kode);

        return response($qr->svgBerkas($event), 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="qr-' . $event->kode . '.svg"',
            'Cache-Control' => 'no-store',
        ]);
    }

    /** Halaman cetak satu lembar: QR besar + nama acara + kode cadangan untuk diketik. */
    public function cetakQr(string $kode, QrService $qr)
    {
        $event = $this->eventDenganQr($kode);

        return response()
            ->view('pages.dashboard-pages.inner-circle.cetak-qr', [
                'event' => $event,
                'qrSvg' => $qr->svgInline($event, 800),
                'tautanQr' => $qr->tautan($event),
            ])
            ->header('Cache-Control', 'no-store');
    }

    /** Acara yang boleh punya QR (bukan privat, ada venue); selain itu 404. */
    private function eventDenganQr(string $kode): ic_event
    {
        $event = ic_event::query()->where('kode', $kode)->firstOrFail();
        abort_unless($event->bisa_checkin, 404);
        abort_if($event->tokenRusak(), 409, 'Token acara ini tidak terbaca. Buka Acara > Hari H lalu pakai tombol Perbaiki token.');

        return $event;
    }
}