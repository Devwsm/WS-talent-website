<?php

namespace App\Http\Controllers\InnerCircle;

use App\Http\Controllers\Controller;
use App\Http\Requests\InnerCircle\imporEventRequest;
use App\Http\Requests\InnerCircle\simpanEventRequest;
use App\Http\Requests\InnerCircle\simpanHadiahRequest;
use App\Http\Requests\InnerCircle\stempelManualRequest;
use App\Http\Requests\InnerCircle\ubahKlaimRequest;
use App\Models\ic_claim;
use App\Models\ic_event;
use App\Models\ic_reward;
use App\Models\ic_stempel;
use App\Models\member;
use App\Services\InnerCircle\AcaraService;
use App\Services\InnerCircle\CheckinService;
use App\Services\InnerCircle\ClaimService;
use App\Services\InnerCircle\KlaimGagal;
use App\Services\InnerCircle\QrService;
use App\Services\InnerCircle\StempelService;
use App\Support\InnerCircleDummy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Panel admin WS Inner Circle di dashboard CMS (7 tab).
 *
 * Fase 4-5: tab Ringkasan, Anggota, Acara & QR, Klaim, dan Hadiah sudah memakai database
 * (dengan route tulis). Tab Pengumuman dan Kenangan (Fase 6) masih memakai InnerCircleDummy;
 * nama variabel ke view tidak berubah.
 */
class adminController extends Controller
{
    private const PER_HALAMAN_ANGGOTA = 15;
    private const PER_HALAMAN_ACARA = 10;
    private const PER_HALAMAN_KLAIM = 15;

    public function ringkasan(CheckinService $checkin)
    {
        $sekarang = now();

        $berikutnya = ic_event::query()
            ->where('tanggal', '>=', $sekarang->copy()->subDay()->toDateString())
            ->orderBy('tanggal')
            ->orderBy('id_event')
            ->get()
            ->first(fn(ic_event $e) => $e->bisa_checkin && ($e->status_waktu !== 'lewat' || $e->dalamJendela($sekarang)));

        return $this->tampil('ringkasan', [
            'ringkasan' => (object) [
                'anggota_total' => member::query()->count(),
                'kehadiran_terverifikasi' => ic_stempel::query()->where('sumber', 'checkin')->count(),
                'klaim_menunggu' => ic_claim::query()->whereIn('status', ic_claim::statusMenunggu())->count(),
                'acara_aktif' => $checkin->eventTerbuka($sekarang)->count(),
                'acara_berikutnya' => $berikutnya,
            ],
            // Lima pengajuan tertua yang menunggu (yang paling lama dulu), array supaya `empty()` di view bekerja.
            'klaimMenunggu' => ic_claim::query()
                ->with(['reward', 'member'])
                ->whereIn('status', ic_claim::statusMenunggu())
                ->orderBy('created_at')
                ->orderBy('id_claim')
                ->limit(5)
                ->get()
                ->all(),
        ]);
    }

    /** Cari lewat ?q= (nama, email, atau kode anggota). */
    public function anggota(Request $request)
    {
        $q = mb_substr(trim($this->teks($request, 'q')), 0, 60);

        $query = member::query()
            ->withMax(['stempel as terakhir_checkin' => fn($s) => $s->where('sumber', 'checkin')], 'created_at')
            ->orderByDesc('id_member');

        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $query->where(function ($w) use ($like) {
                $w->where('nama', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('kode_member', 'like', $like);
            });
        }

        return $this->tampil('anggota', [
            'anggota' => $query->paginate(self::PER_HALAMAN_ANGGOTA)->withQueryString(),
            'totalAnggota' => member::query()->count(),
            'q' => $q,
        ]);
    }

    /** Tambah stempel manual (mis. kehadiran di acara privat yang diverifikasi tim WSM). */
    public function tambahStempel(int $id, stempelManualRequest $request, StempelService $stempel): RedirectResponse
    {
        $anggota = member::query()->findOrFail($id);
        $jumlah = (int) $request->validated('jumlah');

        $stempel->tambahManual($anggota, $jumlah, $request->validated('catatan'), session('user'));

        return back()->with('success', "{$jumlah} stempel ditambahkan untuk {$anggota->nama}.");
    }

    /**
     * Filter jadwal lewat GET (?bulan=&tipe=&q=) seperti di area anggota, acara untuk panel QR
     * lewat ?acara=KODE. Jadwal dipaginasi (?page=).
     */
    public function acara(Request $request, QrService $qr)
    {
        $bulan = $this->teks($request, 'bulan');
        $tipe = $this->teks($request, 'tipe');

        $filter = [
            'bulan' => array_key_exists((int) $bulan, config('inner_circle.bulan_event')) ? (string) (int) $bulan : 'all',
            'tipe' => array_key_exists($tipe, config('inner_circle.tipe_event')) ? $tipe : 'all',
            'q' => mb_substr(trim($this->teks($request, 'q')), 0, 60),
        ];

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

        $jadwal = ic_event::query()
            ->withCount(['stempel as jumlah_checkin' => fn($s) => $s->where('sumber', 'checkin')])
            ->orderBy('tanggal')
            ->orderBy('id_event');
        if ($filter['bulan'] !== 'all') {
            $jadwal->whereMonth('tanggal', (int) $filter['bulan']);
        }
        if ($filter['tipe'] !== 'all') {
            $jadwal->where('tipe', $filter['tipe']);
        }
        if ($filter['q'] !== '') {
            $like = '%' . addcslashes($filter['q'], '%_\\') . '%';
            $jadwal->where(function ($w) use ($like) {
                $w->where('kota', 'like', $like)
                    ->orWhere('venue', 'like', $like)
                    ->orWhere('tipe', 'like', $like);
            });
        }

        return $this->tampil('acara', [
            'jadwal' => $jadwal->paginate(self::PER_HALAMAN_ACARA)->withQueryString()->fragment('jadwal'),
            'totalAcara' => ic_event::query()->count(),
            'filter' => $filter,
            'acaraQr' => $acaraQr,
            'acaraDipilih' => $acaraDipilih,
            'qrSvg' => $acaraDipilih ? $qr->svgInline($acaraDipilih, 400) : null,
            'tautanQr' => $acaraDipilih ? $qr->tautan($acaraDipilih) : null,
            'ikonPeta' => $this->ikonPeta(),
        ]);
    }

    public function simpanAcara(simpanEventRequest $request, AcaraService $acara): RedirectResponse
    {
        $event = $acara->buat($request->validated());

        return redirect(route('ic.admin.acara', ['acara' => $event->kode]) . '#jadwal')
            ->with('success', 'Acara ' . ($event->venue ?? $event->kode) . ' ditambahkan.');
    }

    public function ubahAcara(string $kode, simpanEventRequest $request): RedirectResponse
    {
        $event = ic_event::query()->where('kode', $kode)->firstOrFail();

        // Token dan kode tidak ikut berubah: QR yang sudah dicetak tetap berlaku.
        $event->update($request->validated());

        return back()->with('success', 'Acara ' . ($event->venue ?? $event->kode) . ' diperbarui.');
    }

    public function hapusAcara(string $kode): RedirectResponse
    {
        $event = ic_event::query()->where('kode', $kode)->firstOrFail();

        if ($event->stempel()->exists()) {
            return back()->with('error', 'Acara ini sudah punya check-in anggota, jadi tidak bisa dihapus. Ubah datanya saja bila ada yang salah.');
        }

        $event->delete();

        return back()->with('success', 'Acara dihapus.');
    }

    /** Ganti token: QR lama langsung tidak berlaku. */
    public function tokenBaru(string $kode): RedirectResponse
    {
        $event = ic_event::query()->where('kode', $kode)->firstOrFail();
        abort_unless($event->bisa_checkin, 404);

        $event->forceFill(['token' => ic_event::buatToken(), 'token_dibuat_pada' => now()])->save();

        return redirect(route('ic.admin.acara', ['acara' => $event->kode]) . '#qr')
            ->with('success', 'QR baru dibuat. QR yang lama sudah tidak berlaku.');
    }

    public function imporAcara(imporEventRequest $request, AcaraService $acara): RedirectResponse
    {
        $hasil = $acara->imporCsv($request->file('csv')->getRealPath());

        if ($hasil['galat']) {
            $galat = array_slice($hasil['galat'], 0, 10);
            if (count($hasil['galat']) > 10) {
                $galat[] = 'Dan ' . (count($hasil['galat']) - 10) . ' galat lainnya. Tidak ada jadwal yang diimpor.';
            } else {
                $galat[] = 'Tidak ada jadwal yang diimpor. Perbaiki berkasnya lalu coba lagi.';
            }

            return back()->withErrors(['csv' => $galat]);
        }

        return redirect(route('ic.admin.acara') . '#jadwal')
            ->with('success', "Impor selesai: {$hasil['dibuat']} acara baru, {$hasil['diperbarui']} diperbarui.");
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

    /**
     * Antrean klaim. Filter lewat ?status=menunggu (bawaan, yang tertua dulu) | semua | selesai | ditolak.
     * Dipaginasi (?page=).
     */
    public function klaim(Request $request)
    {
        $status = $this->teks($request, 'status');
        $status = in_array($status, ['semua', 'selesai', 'ditolak'], true) ? $status : 'menunggu';

        $query = ic_claim::query()->with(['reward', 'member', 'logs']);
        if ($status === 'menunggu') {
            $query->whereIn('status', ic_claim::statusMenunggu())->orderBy('created_at')->orderBy('id_claim');
        } else {
            if ($status !== 'semua') {
                $query->where('status', $status);
            }
            $query->orderByDesc('created_at')->orderByDesc('id_claim');
        }

        $perStatus = ic_claim::query()
            ->selectRaw('status, count(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        return $this->tampil('klaim', [
            'klaim' => $query->paginate(self::PER_HALAMAN_KLAIM)->withQueryString(),
            'filterStatus' => $status,
            'hitung' => [
                'menunggu' => (int) collect(ic_claim::statusMenunggu())->sum(fn($s) => $perStatus[$s] ?? 0),
                'semua' => (int) $perStatus->sum(),
                'selesai' => (int) ($perStatus['selesai'] ?? 0),
                'ditolak' => (int) ($perStatus['ditolak'] ?? 0),
            ],
        ]);
    }

    /** Lanjutkan, tolak, atau ubah catatan satu pengajuan. Aturan alurnya di ClaimService. */
    public function ubahKlaim(int $id, ubahKlaimRequest $request, ClaimService $klaim): RedirectResponse
    {
        $pengajuan = ic_claim::query()->findOrFail($id);

        try {
            $hasil = $klaim->proses(
                $pengajuan,
                $request->validated('aksi'),
                $request->validated('dari'),
                $request->validated('catatan'),
                session('user'),
            );
        } catch (KlaimGagal $e) {
            return back()->with('error', $e->getMessage());
        }

        $nama = $hasil->nama_member;
        $pesan = match ($request->validated('aksi')) {
            'lanjut' => "Pengajuan {$nama} sekarang berstatus " . config('inner_circle.status_klaim.' . $hasil->status . '.label') . '.',
            'tolak' => "Pengajuan {$nama} ditolak. Alasannya tampil di profil anggota.",
            default => "Catatan untuk {$nama} disimpan.",
        };

        return back()->with('success', $pesan);
    }

    /** Pilih tahap yang dibuka di form lewat ?tahap=10|25|50|100|250. */
    public function hadiah(Request $request)
    {
        $rewards = ic_reward::query()
            ->where('aktif', true)
            ->orderBy('urutan')
            ->orderBy('target_stempel')
            ->get()
            ->all();

        $tahap = (int) $this->teks($request, 'tahap');
        $tahapAwal = collect($rewards)->first(fn($r) => $r->target_stempel === $tahap)?->target_stempel
            ?? ($rewards[0]->target_stempel ?? null);

        return $this->tampil('hadiah', [
            'rewards' => $rewards,
            'tahapAwal' => $tahapAwal,
            'ikonPeta' => $this->ikonPeta(),
        ]);
    }

    /** Ubah teks dan tampilan satu tahap. Target stempel tidak bisa diubah (kunci di URL dan aturan klaim). */
    public function ubahHadiah(int $target, simpanHadiahRequest $request): RedirectResponse
    {
        $reward = ic_reward::query()->where('target_stempel', $target)->firstOrFail();

        $data = $request->validated();
        $data['opsi'] = $data['opsi'] ?: null;
        $reward->update($data);

        return redirect(route('ic.admin.hadiah', ['tahap' => $target]))
            ->with('success', "Tahap {$target} stempel diperbarui.");
    }

    public function pengumuman()
    {
        return $this->tampil('pengumuman', [
            'daftar' => InnerCircleDummy::pengumumanAdmin(),
            'ikonPeta' => $this->ikonPeta(),
        ]);
    }

    /** Moderasi galeri: filter lewat ?kategori=festival|club|personal dan ?status=tayang|disembunyikan. */
    public function kenangan(Request $request)
    {
        $kategori = $this->teks($request, 'kategori');
        $kategori = array_key_exists($kategori, config('inner_circle.kategori_kenangan')) ? $kategori : 'all';

        $status = $this->teks($request, 'status');
        $status = in_array($status, ['tayang', 'disembunyikan'], true) ? $status : 'all';

        $semua = InnerCircleDummy::kenanganAdmin();

        return $this->tampil('kenangan', [
            'kenangan' => InnerCircleDummy::kenanganAdmin(
                $kategori === 'all' ? null : $kategori,
                $status === 'all' ? null : $status
            ),
            'kategori' => $kategori,
            'status' => $status,
            'jumlahSemua' => count($semua),
            'jumlahDisembunyikan' => count(array_filter($semua, fn($k) => $k->disembunyikan)),
        ]);
    }

    /** Acara yang boleh punya QR (bukan privat, ada venue); selain itu 404. */
    private function eventDenganQr(string $kode): ic_event
    {
        $event = ic_event::query()->where('kode', $kode)->firstOrFail();
        abort_unless($event->bisa_checkin, 404);

        return $event;
    }

    /** Render satu tab; angka lencana di bar tab selalu ikut dikirim. */
    private function tampil(string $tab, array $data)
    {
        return view('pages.dashboard-pages.inner-circle.' . $tab, $data + [
            'jumlahKlaimMenunggu' => ic_claim::query()->whereIn('status', ic_claim::statusMenunggu())->count(),
        ]);
    }

    /**
     * Peta ikon -> alamat gambar (atau emoji bila berkas WebP belum ada) untuk JavaScript preview.
     * Logikanya sama dengan komponen <x-inner-circle.ikon>.
     *
     * @return array<string, array{url?: string, emoji?: string}>
     */
    private function ikonPeta(): array
    {
        $peta = [];
        foreach (config('inner_circle.ikon') as $nama => $emoji) {
            $berkas = 'aset/inner-circle/ikon/' . $nama . '.webp';
            $peta[$nama] = file_exists(public_path($berkas))
                ? ['url' => asset($berkas)]
                : ['emoji' => $emoji];
        }

        return $peta;
    }

    /** Ambil parameter query sebagai teks; array atau nilai aneh dianggap kosong. */
    private function teks(Request $request, string $kunci): string
    {
        $nilai = $request->query($kunci);

        return is_string($nilai) ? $nilai : '';
    }
}