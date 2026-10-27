<?php

namespace App\Http\Controllers\InnerCircle;

use App\Http\Controllers\Controller;
use App\Http\Requests\InnerCircle\imporEventRequest;
use App\Http\Requests\InnerCircle\moderasiKenanganRequest;
use App\Http\Requests\InnerCircle\simpanEventRequest;
use App\Http\Requests\InnerCircle\simpanHadiahRequest;
use App\Http\Requests\InnerCircle\simpanPengumumanRequest;
use App\Http\Requests\InnerCircle\stempelManualRequest;
use App\Http\Requests\InnerCircle\ubahKlaimRequest;
use App\Models\ic_broadcast;
use App\Models\ic_claim;
use App\Models\ic_event;
use App\Models\ic_memory;
use App\Models\ic_reward;
use App\Models\ic_stempel;
use App\Models\member;
use App\Services\InnerCircle\AcaraService;
use App\Services\InnerCircle\BandsintownService;
use App\Services\InnerCircle\EmailService;
use App\Services\InnerCircle\CheckinService;
use App\Services\InnerCircle\ClaimService;
use App\Services\InnerCircle\KenanganService;
use App\Services\InnerCircle\KlaimGagal;
use App\Services\InnerCircle\PengumumanService;
use App\Services\InnerCircle\QrService;
use App\Services\InnerCircle\SinkronGagal;
use App\Services\InnerCircle\StempelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Panel admin WS Inner Circle di dashboard CMS (7 tab).
 *
 * Fase 4-6: semua tab sudah memakai database (dengan route tulis); nama variabel ke view tidak
 * berubah dari Fase 3.
 */
class adminController extends Controller
{
    private const PER_HALAMAN_ANGGOTA = 15;
    private const PER_HALAMAN_ACARA = 10;
    private const PER_HALAMAN_KLAIM = 15;
    private const PER_HALAMAN_PENGUMUMAN = 15;
    private const PER_HALAMAN_KENANGAN = 12;

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
            // Token yang tidak terbaca (APP_KEY berbeda) tidak bisa dijadikan QR: tampilkan peringatan, bukan galat.
            'qrSvg' => $acaraDipilih && ! $acaraDipilih->tokenRusak() ? $qr->svgInline($acaraDipilih, 400) : null,
            'tautanQr' => $acaraDipilih && ! $acaraDipilih->tokenRusak() ? $qr->tautan($acaraDipilih) : null,
            'jumlahTokenRusak' => ic_event::query()->get()->filter(fn(ic_event $e) => $e->tokenRusak())->count(),
            'bitTersedia' => BandsintownService::tersedia(),
            'bitTerakhir' => ($terakhir = ic_event::query()->max('disinkron_pada')) ? \Carbon\Carbon::parse($terakhir) : null,
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
        $data = $request->validated();

        // Begitu admin mengubah tanggal, kota, atau venue acara hasil Bandsintown, sinkron berikutnya tidak menimpanya lagi.
        if (
            $event->sumber === 'bandsintown'
            && ($event->tanggal->toDateString() !== $data['tanggal'] || $event->kota !== $data['kota'] || $event->venue !== $data['venue'])
        ) {
            $data['sumber'] = 'manual';
        }

        $event->update($data);

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

        return redirect(route('ic.admin.acara') . '#qr')->with('success', $jumlah > 0
            ? "{$jumlah} token acara diperbaiki. Unduh atau cetak ulang QR untuk acara tersebut."
            : 'Semua token acara sudah terbaca, tidak ada yang perlu diperbaiki.');
    }

    /** Tarik jadwal tour dari Bandsintown. Hasilnya dirangkum dalam satu pesan. */
    public function sinkronBandsintown(BandsintownService $bit): RedirectResponse
    {
        if (! BandsintownService::tersedia()) {
            return back()->with('error', 'BANDSINTOWN_APP_ID belum diisi di .env (lihat README, bagian Bandsintown).');
        }

        try {
            $hasil = $bit->sinkron();
        } catch (SinkronGagal $e) {
            return back()->with('error', $e->getMessage());
        }

        $pesan = "Bandsintown: {$hasil['total']} acara dibaca, {$hasil['dibuat']} baru, {$hasil['diperbarui']} diperbarui, {$hasil['terhubung']} ditautkan ke acara yang sudah ada.";
        if ($hasil['hilang']) {
            $pesan .= ' Tidak lagi ada di Bandsintown (cek manual, tidak dihapus otomatis): ' . implode('; ', array_slice($hasil['hilang'], 0, 5)) . '.';
        }

        return redirect(route('ic.admin.acara') . '#jadwal')->with('success', $pesan);
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
    public function ubahKlaim(int $id, ubahKlaimRequest $request, ClaimService $klaim, EmailService $email): RedirectResponse
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

        // Anggota diberi kabar saat status berubah (lanjut atau tolak). Mengubah catatan saja tidak mengirim email.
        if (in_array($request->validated('aksi'), ['lanjut', 'tolak'], true)) {
            $email->kirimStatusKlaim($hasil);
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

    /**
     * Daftar pengumuman (terbaru dulu, termasuk yang terjadwal) dan form tambah/ubah. Mode ubah lewat
     * ?ubah=ID, jadi bisa ditautkan dan tetap jalan tanpa JavaScript.
     */
    public function pengumuman(Request $request)
    {
        $idUbah = (int) $this->teks($request, 'ubah');

        return $this->tampil('pengumuman', [
            'daftar' => ic_broadcast::query()
                ->orderByDesc('terbit_pada')
                ->orderByDesc('id_broadcast')
                ->paginate(self::PER_HALAMAN_PENGUMUMAN)
                ->withQueryString(),
            'ubah' => $idUbah > 0 ? ic_broadcast::query()->find($idUbah) : null,
            'ikonPeta' => $this->ikonPeta(),
        ]);
    }

    public function simpanPengumuman(simpanPengumumanRequest $request, PengumumanService $pengumuman): RedirectResponse
    {
        $baru = $pengumuman->simpan($request->validated());

        return redirect()->route('ic.admin.pengumuman')
            ->with('success', $baru->terjadwal
                ? 'Pengumuman dijadwalkan: tampil ke anggota mulai ' . $baru->terbit_pada->locale('id')->translatedFormat('j F Y') . '.'
                : 'Pengumuman diterbitkan.');
    }

    public function ubahPengumuman(int $id, simpanPengumumanRequest $request, PengumumanService $pengumuman): RedirectResponse
    {
        $pengumuman->simpan($request->validated(), ic_broadcast::query()->findOrFail($id));

        return redirect()->route('ic.admin.pengumuman')->with('success', 'Pengumuman diperbarui.');
    }

    public function hapusPengumuman(int $id, PengumumanService $pengumuman): RedirectResponse
    {
        $pengumuman->hapus(ic_broadcast::query()->findOrFail($id));

        return redirect()->route('ic.admin.pengumuman')->with('success', 'Pengumuman dihapus.');
    }

    /** Moderasi galeri: filter lewat ?kategori=festival|club|personal dan ?status=tayang|disembunyikan, dipaginasi (?page=). */
    public function kenangan(Request $request)
    {
        $kategori = $this->teks($request, 'kategori');
        $kategori = array_key_exists($kategori, config('inner_circle.kategori_kenangan')) ? $kategori : 'all';

        $status = $this->teks($request, 'status');
        $status = in_array($status, [ic_memory::TAYANG, ic_memory::DISEMBUNYIKAN], true) ? $status : 'all';

        $query = ic_memory::query()
            ->with('member')
            ->withCount('penyuka as jumlah_like')
            ->orderByDesc('created_at')
            ->orderByDesc('id_memory');
        if ($kategori !== 'all') {
            $query->where('kategori', $kategori);
        }
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        return $this->tampil('kenangan', [
            'kenangan' => $query->paginate(self::PER_HALAMAN_KENANGAN)->withQueryString(),
            'kategori' => $kategori,
            'status' => $status,
            'jumlahSemua' => ic_memory::query()->count(),
            'jumlahDisembunyikan' => ic_memory::query()->where('status', ic_memory::DISEMBUNYIKAN)->count(),
        ]);
    }

    /** Sembunyikan kenangan dari galeri anggota atau tayangkan lagi. Tidak menghapus foto maupun tanda suka. */
    public function moderasiKenangan(int $id, moderasiKenanganRequest $request, KenanganService $kenangan): RedirectResponse
    {
        $dipilih = ic_memory::query()->with('member')->findOrFail($id);
        $sembunyi = $request->validated('aksi') === 'sembunyikan';

        $kenangan->atur($dipilih, $sembunyi);

        $nama = $dipilih->member->nama;

        return back()->with('success', $sembunyi
            ? "Kenangan dari {$nama} disembunyikan dari galeri."
            : "Kenangan dari {$nama} tayang kembali di galeri.");
    }

    /** Acara yang boleh punya QR (bukan privat, ada venue); selain itu 404. */
    private function eventDenganQr(string $kode): ic_event
    {
        $event = ic_event::query()->where('kode', $kode)->firstOrFail();
        abort_unless($event->bisa_checkin, 404);
        abort_if($event->tokenRusak(), 409, 'Token acara ini tidak terbaca. Buka Acara & QR lalu pakai tombol Perbaiki token.');

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