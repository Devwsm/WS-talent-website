<?php

namespace App\Http\Controllers\InnerCircle;

use App\Http\Controllers\Controller;
use App\Http\Requests\InnerCircle\blokirAnggotaRequest;
use App\Http\Requests\InnerCircle\imporEventRequest;
use App\Http\Requests\InnerCircle\kurangiStempelRequest;
use App\Http\Requests\InnerCircle\moderasiKenanganRequest;
use App\Http\Requests\InnerCircle\simpanEventRequest;
use App\Http\Requests\InnerCircle\simpanHadiahRequest;
use App\Http\Requests\InnerCircle\simpanLokasiRequest;
use App\Http\Requests\InnerCircle\simpanPengumumanRequest;
use App\Http\Requests\InnerCircle\stempelManualRequest;
use App\Http\Requests\InnerCircle\ubahKlaimRequest;
use App\Models\ic_broadcast;
use App\Models\ic_claim;
use App\Models\ic_event;
use App\Models\ic_memory;
use App\Models\ic_reward;
use App\Models\ic_stempel;
use App\Models\log_aktivitas;
use App\Models\log_error;
use App\Models\member;
use App\Services\AktivitasService;
use App\Services\InnerCircle\AcaraService;
use App\Services\InnerCircle\AkunAnggotaService;
use App\Services\InnerCircle\BandsintownService;
use App\Services\InnerCircle\EmailService;
use App\Services\InnerCircle\CheckinService;
use App\Services\InnerCircle\ClaimService;
use App\Services\InnerCircle\GeocodeService;
use App\Services\InnerCircle\LokasiService;
use App\Services\InnerCircle\KenanganService;
use App\Services\InnerCircle\KlaimGagal;
use App\Services\InnerCircle\PengumumanService;
use App\Services\InnerCircle\QrService;
use App\Services\InnerCircle\SinkronGagal;
use App\Services\InnerCircle\StempelService;
use App\Services\InnerCircle\TrenRingkasan;
use App\Support\AreaLog;
use App\Support\CsvEkspor;
use Illuminate\Http\JsonResponse;
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

    public function ringkasan(CheckinService $checkin, TrenRingkasan $tren)
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
            // Grafik tren: pendaftaran per minggu (12 minggu) dan check-in per acara (8 acara terakhir).
            'tren' => (object) [
                'pendaftaran' => $tren->pendaftaranMingguan(12, $sekarang),
                'checkin' => $tren->checkinPerAcara(8, $sekarang),
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
            // Ringkasan bagian lain, supaya halaman ini jadi gambaran lengkap seperti Dashboard CMS Artis.
            'angka' => (object) [
                'pengumuman_tayang' => ic_broadcast::query()->tayang()->count(),
                'kenangan_tayang' => ic_memory::query()->tayang()->count(),
                'kenangan_disembunyikan' => ic_memory::query()->where('status', ic_memory::DISEMBUNYIKAN)->count(),
            ],
            'anggotaBaru' => member::query()->orderByDesc('id_member')->limit(5)->get(),
            'hadiahTahap' => ic_reward::query()
                ->withCount(['klaim as klaim_menunggu' => fn($q) => $q->whereIn('status', ic_claim::statusMenunggu())])
                ->orderBy('target_stempel')
                ->get(),
            'pengumumanTerbaru' => ic_broadcast::query()->orderByDesc('terbit_pada')->orderByDesc('id_broadcast')->limit(3)->get(),
            'kenanganTerbaru' => ic_memory::query()->with('member')->orderByDesc('id_memory')->limit(3)->get(),
            // Log CMS Inner Circle: jumlah masalah yang masih terbuka dan tiga aktivitas admin terakhir.
            'logRingkas' => (object) [
                'error_terbuka' => log_error::query()->where('area', AreaLog::INNER_CIRCLE)->where('selesai', false)->count(),
                'aktivitas' => log_aktivitas::query()
                    ->where('kategori', 'inner-circle')
                    ->orderByDesc('created_at')
                    ->orderByDesc('id_log_aktivitas')
                    ->limit(3)
                    ->get(),
            ],
        ]);
    }

    /**
     * Cari dan saring anggota dari ?q= (nama, email, atau kode), ?status= (email), ?via= (cara daftar pertama), dan
     * ?akun= (aktif atau dinonaktifkan). Nilai di luar daftar dianggap "semua" (tautan lama/ngawur aman). Dipakai daftar
     * di layar dan ekspor CSV, jadi file yang diunduh selalu sama dengan yang sedang terlihat.
     *
     * @return array{0: \Illuminate\Database\Eloquent\Builder, 1: string, 2: string, 3: string, 4: string}
     */
    private function saringAnggota(Request $request): array
    {
        $q = mb_substr(trim($this->teks($request, 'q')), 0, 60);
        $status = in_array($this->teks($request, 'status'), ['terverifikasi', 'belum'], true) ? $this->teks($request, 'status') : '';
        $via = in_array($this->teks($request, 'via'), [member::VIA_GOOGLE, member::VIA_MANUAL], true) ? $this->teks($request, 'via') : '';
        $akun = in_array($this->teks($request, 'akun'), ['aktif', 'nonaktif'], true) ? $this->teks($request, 'akun') : '';

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

        if ($status !== '') {
            $status === 'terverifikasi' ? $query->whereNotNull('email_verified_at') : $query->whereNull('email_verified_at');
        }
        if ($via !== '') {
            $query->where('daftar_via', $via);
        }
        if ($akun !== '') {
            $akun === 'nonaktif' ? $query->whereNotNull('diblokir_pada') : $query->whereNull('diblokir_pada');
        }

        return [$query, $q, $status, $via, $akun];
    }

    public function anggota(Request $request)
    {
        [$query, $q, $status, $via, $akun] = $this->saringAnggota($request);

        return $this->tampil('anggota', [
            'anggota' => $query->paginate(self::PER_HALAMAN_ANGGOTA)->withQueryString(),
            'totalAnggota' => member::query()->count(),
            'q' => $q,
            'status' => $status,
            'via' => $via,
            'akun' => $akun,
        ]);
    }

    /** Unduh daftar anggota (CSV) dengan pencarian dan filter yang sedang dipakai. */
    public function eksporAnggota(Request $request, AktivitasService $aktivitas)
    {
        [$query,,,,] = $this->saringAnggota($request);

        $query->withCount(['stempel as total_checkin' => fn($s) => $s->where('sumber', 'checkin')])->reorder('id_member');
        $jumlah = (clone $query)->count();

        $aktivitas->catat('Daftar anggota diunduh', "Ekspor CSV: {$jumlah} anggota.", 'berhasil', 'inner-circle', null, 'ic.admin.anggota.ekspor');

        $baris = (function () use ($query) {
            foreach ($query->cursor() as $m) {
                yield [
                    $m->kode_member,
                    $m->nama,
                    $m->email,
                    $m->email_verified_at ? 'Ya' : 'Belum',
                    $m->daftar_via === member::VIA_GOOGLE ? 'Google' : 'Manual (sandi)',
                    $m->google_id ? 'Ya' : 'Tidak',
                    $m->created_at?->format('Y-m-d H:i'),
                    $m->stempel_total,
                    $m->total_checkin,
                    $m->terakhir_checkin?->format('Y-m-d H:i'),
                    $m->diblokir() ? 'Dinonaktifkan' : 'Aktif',
                ];
            }
        })();

        return CsvEkspor::unduh(
            'anggota-inner-circle-' . now()->format('Ymd-His') . '.csv',
            ['Kode', 'Nama', 'Email', 'Email terverifikasi', 'Daftar via', 'Google terhubung', 'Bergabung', 'Total stempel', 'Jumlah check-in', 'Check-in terakhir', 'Status akun'],
            $baris,
        );
    }

    /** Unduh daftar yang check-in di satu acara (CSV): bahan laporan setelah event. */
    public function eksporKehadiran(string $kode, AktivitasService $aktivitas)
    {
        $acara = ic_event::query()->where('kode', $kode)->firstOrFail();

        $query = ic_stempel::query()
            ->with('member')
            ->where('sumber', 'checkin')
            ->where('event_id', $acara->getKey())
            ->orderBy('created_at')
            ->orderBy('id_stempel');

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
            ['No', 'Kode anggota', 'Nama', 'Email', 'Waktu check-in', 'Jarak dari titik acara (m)', 'Akurasi lokasi (m)', 'Stempel'],
            $baris,
        );
    }

    /** Profil satu anggota: data akun, tindakan admin, riwayat stempel, dan klaim hadiah. */
    public function detailAnggota(int $id)
    {
        $anggota = member::query()
            ->withCount(['stempel as total_checkin' => fn($s) => $s->where('sumber', 'checkin')])
            ->withMax(['stempel as terakhir_checkin' => fn($s) => $s->where('sumber', 'checkin')], 'created_at')
            ->findOrFail($id);

        return $this->tampil('anggota-detail', [
            'anggota' => $anggota,
            'riwayat' => ic_stempel::query()
                ->with('event')
                ->where('member_id', $anggota->getKey())
                ->orderByDesc('created_at')
                ->orderByDesc('id_stempel')
                ->paginate(self::PER_HALAMAN_ANGGOTA)
                ->withQueryString(),
            'klaimAnggota' => ic_claim::query()->with('reward')->where('member_id', $anggota->getKey())->orderByDesc('id_claim')->get(),
        ]);
    }

    /** Kurangi stempel (koreksi salah input). Ditulis sebagai baris negatif di riwayat, tidak menghapus baris lama. */
    public function kurangiStempel(int $id, kurangiStempelRequest $request, StempelService $stempel): RedirectResponse
    {
        $anggota = member::query()->findOrFail($id);
        $jumlah = (int) $request->validated('jumlah');

        $stempel->kurangi($anggota, $jumlah, $request->validated('catatan'), session('user'));

        $pesan = "{$jumlah} stempel dikurangi dari {$anggota->nama}. Sekarang {$anggota->stempel_total} stempel.";

        // Klaim yang sudah diajukan tidak otomatis dibatalkan; admin diingatkan supaya bisa menindaklanjuti sendiri.
        $melebihi = ic_claim::query()
            ->where('member_id', $anggota->getKey())
            ->where('status', '!=', 'ditolak')
            ->whereHas('reward', fn($r) => $r->where('target_stempel', '>', $anggota->stempel_total))
            ->count();
        if ($melebihi > 0) {
            $pesan .= " Perhatian: {$melebihi} klaim hadiah anggota ini butuh stempel lebih banyak dari total sekarang. Klaim tidak otomatis dibatalkan; periksa di tab Klaim.";
        }

        return back()->with('success', $pesan);
    }

    /** Nonaktifkan akun: tidak bisa masuk dan sesinya berakhir. Data anggota tidak dihapus. */
    public function nonaktifkanAnggota(int $id, blokirAnggotaRequest $request, AkunAnggotaService $akun): RedirectResponse
    {
        $anggota = member::query()->findOrFail($id);

        if ($anggota->diblokir()) {
            return back()->with('success', "Akun {$anggota->nama} sudah dinonaktifkan.");
        }

        $akun->nonaktifkan($anggota, $request->validated('alasan'), session('user'));

        return back()->with('success', "Akun {$anggota->nama} dinonaktifkan. Sesi yang sedang berjalan berakhir pada permintaan berikutnya.");
    }

    public function aktifkanAnggota(int $id, AkunAnggotaService $akun): RedirectResponse
    {
        $anggota = member::query()->findOrFail($id);
        $akun->aktifkan($anggota);

        return back()->with('success', "Akun {$anggota->nama} diaktifkan kembali. Anggota bisa masuk lagi.");
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

        $bulanPilihan = ic_event::pilihanBulan();

        $filter = [
            'bulan' => $bulanPilihan->has($bulan) ? $bulan : 'all',
            'tipe' => array_key_exists($tipe, ic_tipe_event()) ? $tipe : 'all',
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
            $jadwal->diBulan($filter['bulan']);
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
            'bulanPilihan' => $bulanPilihan,
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

        return redirect(route('ic.admin.acara', ['acara' => $event->kode]) . '#lokasi')
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

        return redirect(route('ic.admin.acara', ['acara' => $event->kode]) . '#lokasi')
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

        // Anggota diberi kabar saat status berubah (lanjut, tolak, atau buka kembali). Mengubah catatan saja tidak mengirim email.
        if (in_array($request->validated('aksi'), ['lanjut', 'tolak', 'buka_kembali'], true)) {
            $email->kirimStatusKlaim($hasil);
        }

        $nama = $hasil->nama_member;
        $pesan = match ($request->validated('aksi')) {
            'lanjut' => "Pengajuan {$nama} sekarang berstatus " . config('inner_circle.status_klaim.' . $hasil->status . '.label') . '.',
            'tolak' => "Pengajuan {$nama} ditolak. Alasannya tampil di profil anggota.",
            'buka_kembali' => "Pengajuan {$nama} dibuka kembali dan berstatus Diajukan.",
            default => "Catatan untuk {$nama} disimpan.",
        };

        return back()->with('success', $pesan);
    }

    /** Pilih tahap yang dibuka di form lewat ?tahap=10|25|50|100|250. */
    public function hadiah(Request $request)
    {
        $rewards = ic_reward::query()
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
     * Tampilkan atau sembunyikan satu tahap hadiah dari anggota. Tahap yang disembunyikan tidak tampil di halaman
     * Hadiah dan tidak bisa diklaim baru; pengajuan yang sudah masuk tetap diproses. Minimal satu tahap harus aktif.
     */
    public function aktifkanHadiah(int $target, Request $request): RedirectResponse
    {
        $aktif = (bool) $request->validate(['aktif' => ['required', 'boolean']])['aktif'];
        $reward = ic_reward::query()->where('target_stempel', $target)->firstOrFail();

        if (! $aktif && ! ic_reward::query()->where('aktif', true)->where('id_reward', '!=', $reward->getKey())->exists()) {
            return redirect(route('ic.admin.hadiah', ['tahap' => $target]))
                ->with('error', 'Minimal satu tahap hadiah harus tetap aktif.');
        }

        $reward->update(['aktif' => $aktif]);

        return redirect(route('ic.admin.hadiah', ['tahap' => $target]))->with('success', $aktif
            ? "Tahap {$target} stempel aktif lagi dan tampil ke anggota."
            : "Tahap {$target} stempel disembunyikan dari anggota. Pengajuan yang sudah masuk tetap diproses.");
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

    /** Sembunyikan pengumuman dari anggota tanpa menghapusnya (atau tampilkan lagi). */
    public function aktifkanPengumuman(int $id, Request $request): RedirectResponse
    {
        $aktif = (bool) $request->validate(['aktif' => ['required', 'boolean']])['aktif'];
        $p = ic_broadcast::query()->findOrFail($id);
        $p->update(['aktif' => $aktif]);

        return back()->with('success', $aktif
            ? "Pengumuman “{$p->judul}” ditampilkan lagi ke anggota."
            : "Pengumuman “{$p->judul}” disembunyikan dari anggota (tidak dihapus).");
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
        $kategori = array_key_exists($kategori, ic_kategori_kenangan(true)) ? $kategori : 'all';

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