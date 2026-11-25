<?php

namespace App\Http\Controllers\InnerCircle;

use App\Http\Controllers\Concerns\PembantuHalamanAdminIc;
use App\Http\Controllers\Controller;
use App\Http\Requests\InnerCircle\simpanLokasiRequest;
use App\Models\ic_event;
use App\Models\ic_gl_antrean;
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

        // Daftar acara Hari H: acara 7 hari terakhir dan yang akan datang; ?semua=1 menampilkan seluruhnya.
        $semua = $this->teks($request, 'semua') === '1';
        $daftar = ic_event::query()
            ->where('tipe', '!=', 'private')
            ->whereNotNull('venue')
            ->where('venue', '!=', '')
            ->withCount(['poin as jumlah_hadir' => fn($q) => $q->where('sumber', 'checkin')])
            ->when(! $semua, fn($q) => $q->whereDate('tanggal', '>=', now()->subDays(7)->toDateString()))
            ->orderBy('tanggal')
            ->orderBy('id_event')
            ->paginate(10)
            ->withQueryString()
            ->fragment('daftar');

        return $this->tampil('acara.hari-h', [
            'daftar' => $daftar,
            'semuaAcara' => $semua,
            'acaraQr' => $acaraQr,
            'acaraDipilih' => $acaraDipilih,
            // Token yang tidak terbaca (APP_KEY berbeda) tidak bisa dijadikan QR: tampilkan peringatan, bukan galat.
            'qrSvg' => $acaraDipilih && ! $acaraDipilih->tokenRusak() ? $qr->svgInline($acaraDipilih, 400) : null,
            'tautanQr' => $acaraDipilih && ! $acaraDipilih->tokenRusak() ? $qr->tautan($acaraDipilih) : null,
            'jumlahTokenRusak' => ic_event::query()->get()->filter(fn(ic_event $e) => $e->tokenRusak())->count(),
        ]);
    }

    /**
     * Unduh daftar yang check-in di satu acara (CSV): bahan laporan setelah event. ?jenis=gl (pemenang GL gratis) atau
     * ?jenis=tiket (beli tiket sendiri) menyaring; tanpa jenis = semuanya. Kolom terakhir menandai sumber kehadiran.
     */
    public function eksporKehadiran(string $kode, Request $request, AktivitasService $aktivitas)
    {
        $acara = ic_event::query()->where('kode', $kode)->firstOrFail();
        $jenis = $this->jenisKehadiran($request);

        $query = $this->kehadiran($acara, $jenis)
            ->with('member')
            ->orderBy('created_at')
            ->orderBy('id_poin');

        $pemenang = $this->idPemenang($acara)->pluck('member_id')->flip();
        $jumlah = (clone $query)->count();
        $label = trim($acara->venue . ', ' . $acara->kota, ', ') . ' (' . $acara->tanggal->toDateString() . ')';
        $aktivitas->catat('Kehadiran acara diunduh', "Ekspor CSV kehadiran {$label}" . ($jenis ? " ({$jenis})" : '') . ": {$jumlah} anggota.", 'berhasil', 'inner-circle', null, 'ic.admin.acara.kehadiran');

        $baris = (function () use ($query, $pemenang) {
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
                    $pemenang->has($s->member_id) ? 'GL gratis' : 'Beli tiket sendiri',
                ];
            }
        })();

        return CsvEkspor::unduh(
            'kehadiran-' . $acara->kode . ($jenis ? '-' . $jenis : '') . '-' . $acara->tanggal->toDateString() . '.csv',
            ['No', 'Kode anggota', 'Nama', 'Email', 'Waktu check-in', 'Jarak dari titik acara (m)', 'Akurasi lokasi (m)', 'Poin', 'Sumber kehadiran'],
            $baris,
        );
    }

    /**
     * Halaman detail Hari H satu acara: ringkasan, info GL, dan daftar yang hadir dengan dua tab
     * (?tab=tiket beli tiket sendiri, ?tab=gl pemenang GL gratis). Pemenang GL = status `menang` di antrean acara itu.
     */
    public function detail(string $kode, Request $request)
    {
        $event = ic_event::query()->where('kode', $kode)->firstOrFail();
        abort_unless($event->bisa_checkin, 404);

        $tab = $this->teks($request, 'tab') === 'gl' ? 'gl' : 'tiket';

        $hadirGl = $this->kehadiran($event, 'gl')->count();
        $hadirTiket = $this->kehadiran($event, 'tiket')->count();

        $hadir = $this->kehadiran($event, $tab)
            ->with('member')
            ->orderBy('created_at')
            ->orderBy('id_poin')
            ->paginate(20)
            ->withQueryString();

        return $this->tampil('acara.hari-h-detail', [
            'event' => $event,
            'tab' => $tab,
            'hadir' => $hadir,
            'jumlahGl' => $hadirGl,
            'jumlahTiket' => $hadirTiket,
            'totalPoin' => (int) $this->kehadiran($event, null)->sum('jumlah'),
            'antrean' => $event->antreanGl()->count(),
            'pemenang' => $event->antreanGl()->where('status', ic_gl_antrean::MENANG)->count(),
            'tokenRusak' => $event->tokenRusak(),
        ]);
    }

    /** Baris poin check-in satu acara; $jenis `gl` = hanya pemenang GL, `tiket` = selain pemenang, null = semua. */
    private function kehadiran(ic_event $acara, ?string $jenis)
    {
        $query = ic_poin::query()
            ->where('sumber', 'checkin')
            ->where('event_id', $acara->getKey());

        if ($jenis === 'gl') {
            $query->whereIn('member_id', $this->idPemenang($acara));
        } elseif ($jenis === 'tiket') {
            $query->whereNotIn('member_id', $this->idPemenang($acara));
        }

        return $query;
    }

    private function idPemenang(ic_event $acara)
    {
        return ic_gl_antrean::query()
            ->where('event_id', $acara->getKey())
            ->where('status', ic_gl_antrean::MENANG)
            ->select('member_id');
    }

    private function jenisKehadiran(Request $request): ?string
    {
        $jenis = $this->teks($request, 'jenis');

        return in_array($jenis, ['gl', 'tiket'], true) ? $jenis : null;
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

        $sebelumLokasi = $event->lokasi_aktif
            ? ['lat' => $event->lokasi_lat, 'lng' => $event->lokasi_lng, 'radius' => $event->lokasi_radius]
            : ['lat' => null, 'lng' => null, 'radius' => null];

        $event->update([
            'lokasi_lat' => $lat,
            'lokasi_lng' => $lng,
            'lokasi_radius' => (int) $d['radius'],
            'lokasi_sumber' => $d['sumber'],
            'lokasi_diatur_oleh' => $d['petugas'] ?? null,
            'lokasi_diatur_pada' => now(),
        ]);

        $baris = [];
        if ($sebelumLokasi['lat'] === null) {
            $baris[] = "Titik lokasi: baru diatur ({$lat}, {$lng})";
        } else {
            $geser = LokasiService::jarakMeter((float) $sebelumLokasi['lat'], (float) $sebelumLokasi['lng'], $lat, $lng);
            if ($geser >= 1) {
                $baris[] = 'Titik lokasi: dipindah ' . LokasiService::formatJarak($geser) . " ke ({$lat}, {$lng})";
            }
        }
        if ((int) $sebelumLokasi['radius'] !== (int) $d['radius']) {
            $baris[] = 'Radius: ' . ($sebelumLokasi['radius'] === null ? 'kosong' : (int) $sebelumLokasi['radius'] . ' m') . ' → ' . (int) $d['radius'] . ' m';
        }
        $baris[] = 'Sumber titik: ' . (['manual' => 'klik peta atau ketik koordinat', 'alamat' => 'pencarian alamat', 'petugas' => 'GPS HP petugas'][$d['sumber']] ?? $d['sumber']);
        request()->attributes->set('log_rincian', $baris); // request asli (FormRequest adalah salinan), dibaca CatatAktivitas

        return redirect($this->kembaliLokasi($request, $event))
            ->with('success', 'Titik lokasi check-in ' . ($event->venue ?? $event->kode) . ' disimpan (radius ' . $event->radius_efektif . ' m).');
    }

    /** Matikan pemeriksaan lokasi untuk satu acara: QR-nya kembali bisa dipakai dari mana saja. */
    public function hapusLokasi(string $kode, Request $request): RedirectResponse
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

        return redirect($this->kembaliLokasi($request, $event))
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

    /**
     * Saran alamat saat petugas mengetik (autocomplete) di popup Atur acara. JSON: {hasil: [{judul, detail, nama, lat, lng}]}.
     * Condong ke titik acara bila sudah ada. Hanya saran: petugas yang memilih dan memeriksa posisinya di peta.
     */
    public function saranLokasi(string $kode, Request $request, GeocodeService $geo): JsonResponse
    {
        $event = ic_event::query()->where('kode', $kode)->firstOrFail();
        abort_unless($event->bisa_checkin, 404);

        $q = mb_substr(trim((string) $request->query('q')), 0, 150);

        try {
            $hasil = $geo->saran(
                $q,
                6,
                $event->lokasi_aktif ? (float) $event->lokasi_lat : null,
                $event->lokasi_aktif ? (float) $event->lokasi_lng : null,
            );
        } catch (SinkronGagal $e) {
            return response()->json(['pesan' => $e->getMessage(), 'hasil' => []], 502);
        }

        return response()->json(['kueri' => $q, 'hasil' => $hasil])->header('Cache-Control', 'no-store, private');
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

    /** Titik lokasi bisa disimpan dari halaman Hari H atau dari popup Atur acara (field tersembunyi `kembali`). */
    private function kembaliLokasi(Request $request, ic_event $event): string
    {
        return $request->input('kembali') === 'atur'
            ? $this->urlDaftarAcara($request)
            : route('ic.admin.acara.harih', ['acara' => $event->kode]) . '#lokasi';
    }
}