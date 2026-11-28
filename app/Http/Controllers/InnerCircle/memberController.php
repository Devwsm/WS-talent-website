<?php

namespace App\Http\Controllers\InnerCircle;

use App\Http\Controllers\Controller;
use App\Http\Requests\InnerCircle\ajukanKlaimRequest;
use App\Http\Requests\InnerCircle\checkinMandiriRequest;
use App\Http\Requests\InnerCircle\checkinRequest;
use App\Models\ic_broadcast;
use App\Models\ic_event;
use App\Models\ic_gl_antrean;
use App\Models\ic_memory;
use App\Models\ic_poin;
use App\Models\member;
use App\Services\InnerCircle\CheckinGagal;
use App\Services\InnerCircle\CheckinService;
use App\Services\InnerCircle\PemeriksaanService;
use App\Support\SidikPerangkat;
use App\Services\InnerCircle\ClaimService;
use App\Services\InnerCircle\GameService;
use App\Services\InnerCircle\IcGame;
use App\Services\InnerCircle\KenanganService;
use App\Services\InnerCircle\KlaimGagal;
use App\Services\InnerCircle\UndianService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Layar anggota WS Inner Circle (semua route di sini dilindungi `auth:member`).
 *
 * Fase 4-6: semua layar sudah dari database (anggota, Beranda, Check-in, Hadiah, Komunitas, Profil).
 * Nama variabel ke view tidak berubah dari Fase 2. Aksi tulis Komunitas ada di kenanganController
 * (unggah, hapus, suka) dan foto profil di fotoProfilController.
 */
class memberController extends Controller
{
    public function beranda(ClaimService $klaim)
    {
        $anggota = $this->anggota();

        return view('pages.inner-circle.beranda', [
            'member' => $anggota,
            'berikutnya' => $klaim->berikutnya($anggota->poin_total),
            'aktivitas' => $this->riwayatAktivitas($anggota, 5),
            'glSaya' => $this->glSaya($anggota),
        ]);
    }

    /**
     * GL gratis anggota yang masih relevan: antrean atau kemenangan di acara hari ini dan yang akan datang
     * (acara kemarin ikut sampai tengah hari, karena check-in club bisa lewat tengah malam).
     */
    private function glSaya(member $anggota)
    {
        return ic_gl_antrean::query()
            ->with('event')
            ->where('member_id', $anggota->getKey())
            ->whereIn('status', [ic_gl_antrean::ANTRI, ic_gl_antrean::MENANG])
            ->whereHas('event', fn($q) => $q->whereDate('tanggal', '>=', now()->subHours(12)->toDateString()))
            ->get()
            ->sortBy(fn($b) => $b->event->tanggal)
            ->values();
    }

    public function hadiah(ClaimService $klaim)
    {
        $anggota = $this->anggota();

        return view('pages.inner-circle.hadiah', [
            'member' => $anggota,
            'rewards' => $klaim->hadiahUntuk($anggota)->all(),
            'dompet' => $klaim->dompet($anggota),
            'penukaran' => $klaim->klaimAnggota($anggota)->take(20)->all(),
        ]);
    }

    /** Tukar poin dengan hadiah (id hadiah dari URL). Aturannya ada di ClaimService. */
    public function ajukanKlaim(int $id, ajukanKlaimRequest $request, ClaimService $klaim): RedirectResponse
    {
        try {
            $pengajuan = $klaim->ajukan($this->anggota(), $id, $request->validated('opsi'));
        } catch (KlaimGagal $e) {
            return redirect(route('ic.hadiah') . '#klaim')->with('ic_toast_galat', $e->getMessage());
        }

        return redirect(route('ic.hadiah') . '#klaim')
            ->with('ic_toast', 'Penukaran terkirim! ' . ic_angka($pengajuan->poin_ditahan) . ' poin ditahan sampai tim WSM selesai memeriksa.');
    }

    /** Batalkan penukaran sendiri selama masih "Diajukan": poin dan stok kembali. */
    public function batalkanKlaim(int $id, ClaimService $klaim): RedirectResponse
    {
        try {
            $pengajuan = $klaim->batalkan($this->anggota(), $id);
        } catch (KlaimGagal $e) {
            return redirect(route('ic.hadiah') . '#klaim')->with('ic_toast_galat', $e->getMessage());
        }

        return redirect(route('ic.hadiah') . '#klaim')
            ->with('ic_toast', 'Penukaran dibatalkan. ' . ic_angka($pengajuan->poin_ditahan) . ' poin sudah kembali ke saldomu.');
    }

    /** Masuk daftar tunggu hadiah yang stoknya habis. */
    public function ikutTunggu(int $id, ClaimService $klaim): RedirectResponse
    {
        try {
            $klaim->ikutTunggu($this->anggota(), $id);
        } catch (KlaimGagal $e) {
            return redirect(route('ic.hadiah') . '#katalog')->with('ic_toast_galat', $e->getMessage());
        }

        return redirect(route('ic.hadiah') . '#katalog')->with('ic_toast', 'Kamu masuk daftar tunggu. Saat stoknya tersedia lagi, hadiah ini akan bertanda di halaman Hadiah.');
    }

    public function keluarTunggu(int $id, ClaimService $klaim): RedirectResponse
    {
        $klaim->keluarTunggu($this->anggota(), $id);

        return redirect(route('ic.hadiah') . '#katalog')->with('ic_toast', 'Kamu keluar dari daftar tunggu.');
    }

    /**
     * Filter jadwal lewat GET (?bulan=&tipe=&q=) dan acara terpilih lewat ?acara=KODE, jadi bisa
     * dibagikan dan tetap jalan tanpa JavaScript. ?kode=WS-XXXXXXXXXXXX berasal dari QR yang
     * dipindai kamera bawaan HP: kodenya mengisi form dan acaranya dicari dari token.
     */
    public function checkin(Request $request, CheckinService $checkin)
    {
        $anggota = $this->anggota();

        // Pengaman undian GL bila cron belum terpasang (paling sering sekali per menit untuk seluruh situs).
        app(UndianService::class)->pengamanKunjungan();

        $bulan = $this->teks($request, 'bulan');
        $tipe = $this->teks($request, 'tipe');

        // Pilihan bulan dari tanggal acara yang ada (format 2026-10). Nilai lain, termasuk ?bulan=10 dari tautan lama, dianggap "semua".
        // Acara yang disembunyikan (manual, atau otomatis karena bulannya sudah lewat) tidak ikut jadi pilihan.
        $bulanPilihan = ic_event::pilihanBulan(true);

        $filter = [
            'bulan' => $bulanPilihan->has($bulan) ? $bulan : 'all',
            'tipe' => array_key_exists($tipe, ic_tipe_event()) ? $tipe : 'all',
            'q' => mb_substr(trim($this->teks($request, 'q')), 0, 60),
        ];

        // Parameter filter yang bukan nilai bawaan, dipakai ulang di tautan tiap kartu acara.
        $parameterFilter = array_filter([
            'bulan' => $filter['bulan'] !== 'all' ? $filter['bulan'] : null,
            'tipe' => $filter['tipe'] !== 'all' ? $filter['tipe'] : null,
            'q' => $filter['q'] !== '' ? $filter['q'] : null,
        ]);

        $query = ic_event::query()->tampilDiAnggota()->orderBy('tanggal')->orderBy('id_event');
        if ($filter['bulan'] !== 'all') {
            $query->diBulan($filter['bulan']);
        }
        if ($filter['tipe'] !== 'all') {
            $query->where('tipe', $filter['tipe']);
        }
        if ($filter['q'] !== '') {
            $like = '%' . addcslashes($filter['q'], '%_\\') . '%';
            $query->where(function ($w) use ($like) {
                $w->where('kota', 'like', $like)
                    ->orWhere('venue', 'like', $like)
                    ->orWhere('tipe', 'like', $like);
            });
        }

        $events = $query->get();

        $sudahCheckin = ic_poin::query()
            ->where('member_id', $anggota->getKey())
            ->whereNotNull('event_id')
            ->pluck('event_id')
            ->all();
        foreach ($events as $e) {
            $e->setAttribute('sudah_checkin', in_array($e->id_event, $sudahCheckin, true));
        }

        // Acara terpilih: dari ?acara=KODE, atau (QR dipindai di luar halaman ini) dari token di ?kode=.
        $kodeAcara = $this->teks($request, 'acara');
        $eventDipilih = $kodeAcara !== ''
            ? ic_event::query()->where('kode', $kodeAcara)->first()
            : null;
        if ($eventDipilih && ! $eventDipilih->bisa_checkin) {
            $eventDipilih = null;
        }

        $tokenQr = CheckinService::normalisasiToken($this->teks($request, 'kode'));
        $galatKode = null;
        if ($tokenQr !== null && ! $eventDipilih) {
            $eventDipilih = $checkin->cariEventDariToken($tokenQr);
            if (! $eventDipilih) {
                $galatKode = 'QR ini tidak cocok dengan acara yang sedang dibuka untuk check-in. Mungkin sudah diganti panitia atau acaranya belum dimulai.';
            }
        } elseif ($this->teks($request, 'kode') !== '' && $tokenQr === null) {
            $galatKode = 'Kode check-in tidak valid. Periksa lagi kode dari panitia atau pindai ulang QR.';
        }
        if ($eventDipilih) {
            $eventDipilih->setAttribute('sudah_checkin', in_array($eventDipilih->id_event, $sudahCheckin, true));
        }

        return view('pages.inner-circle.checkin', [
            'member' => $anggota,
            'events' => $events,
            'totalEvents' => ic_event::query()->tampilDiAnggota()->count(),
            'filter' => $filter,
            'bulanPilihan' => $bulanPilihan,
            'parameterFilter' => $parameterFilter,
            'eventDipilih' => $eventDipilih,
            'kodeIsian' => $tokenQr ?? '',
            'galatKode' => $galatKode,
            'bolehMandiri' => $eventDipilih !== null && ! $checkin->pemenangGl($anggota, $eventDipilih),
            'ajukan' => app(PemeriksaanService::class)->tawaran($anggota, $eventDipilih),
        ]);
    }

    /** Check-in mandiri (tombol, tanpa QR). Ditutup untuk pemenang GL gratis; lihat CheckinService::checkinMandiri. */
    public function prosesCheckinMandiri(checkinMandiriRequest $request, CheckinService $checkin): RedirectResponse
    {
        $anggota = $this->anggota();
        $kodeAcara = $request->validated('acara');

        $lokasi = $request->filled('lat') && $request->filled('lng')
            ? ['lat' => $request->validated('lat'), 'lng' => $request->validated('lng'), 'akurasi' => $request->validated('akurasi')]
            : null;

        try {
            $poin = $checkin->checkinMandiri($anggota, $kodeAcara, null, $lokasi, SidikPerangkat::untuk($request));
        } catch (CheckinGagal $e) {
            app(PemeriksaanService::class)->simpanGagalSesi($e);

            return redirect($this->tujuanGalat($request, $kodeAcara, ['acara' => $kodeAcara]))->withErrors(['kode' => $e->getMessage()]);
        }

        return redirect()->route('ic.beranda')
            ->with('ic_toast', 'Check-in berhasil! +' . ic_angka($poin->jumlah) . ' poin. ' . $poin->judul . '.');
    }

    public function prosesCheckin(checkinRequest $request, CheckinService $checkin): RedirectResponse
    {
        $anggota = $this->anggota();
        $kodeAcara = $request->validated('acara');

        // Posisi HP hanya dikirim bila browser berhasil membacanya; acara dengan titik lokasi yang tidak menerimanya akan menolak.
        $lokasi = $request->filled('lat') && $request->filled('lng')
            ? ['lat' => $request->validated('lat'), 'lng' => $request->validated('lng'), 'akurasi' => $request->validated('akurasi')]
            : null;

        try {
            $poin = $checkin->checkin($anggota, $request->validated('kode'), $kodeAcara, null, $lokasi, SidikPerangkat::untuk($request));
        } catch (CheckinGagal $e) {
            app(PemeriksaanService::class)->simpanGagalSesi($e);

            // Kembali ke halaman yang sama dengan acaranya terpilih: lembar check-in terbuka lagi dan menampilkan galatnya.
            return redirect($this->tujuanGalat($request, $kodeAcara, array_filter(['acara' => $kodeAcara])))
                ->withErrors(['kode' => $e->getMessage()])
                ->withInput(['kode' => $request->validated('kode')]);
        }

        return redirect()->route('ic.beranda')
            ->with('ic_toast', 'Check-in berhasil! +' . ic_angka($poin->jumlah) . ' poin. ' . $poin->judul . '.');
    }

    /**
     * Tab lewat ?tab=pengumuman|galeri, kategori galeri lewat ?kategori=festival|club|personal, halaman galeri
     * lewat ?page=. Pengumuman hanya yang aktif dan sudah tiba tanggal terbitnya; galeri hanya yang tayang.
     */
    public function komunitas(Request $request, KenanganService $kenangan, GameService $game, IcGame $pengaturanGame)
    {
        $anggota = $this->anggota();
        $gameAktif = $pengaturanGame->aktif();
        $tabDiminta = $this->teks($request, 'tab');
        $tab = match (true) {
            $tabDiminta === 'galeri' => 'galeri',
            $tabDiminta === 'game' && $gameAktif => 'game',
            default => 'pengumuman',
        };

        $kategori = $this->teks($request, 'kategori');
        $kategori = array_key_exists($kategori, ic_kategori_kenangan()) ? $kategori : 'all';

        // Lencana angka di tab: isi yang belum dibaca sejak tab itu terakhir dibuka. Tab yang sedang dibuka dianggap
        // dibaca sekarang, jadi lencananya hilang dan waktu bacanya diperbarui.
        $belumDibaca = $this->belumDibaca($anggota, $tab === 'game' ? '' : $tab);

        $data = [
            'member' => $anggota,
            'tab' => $tab,
            'kategori' => $kategori,
            'jumlahPengumuman' => $belumDibaca['pengumuman'],
            'jumlahKenangan' => $belumDibaca['galeri'],
            'gameAktif' => $gameAktif,
            'maksFotoMb' => config('inner_circle.foto_kenangan_maks_kb') / 1024,
        ];

        if ($tab === 'pengumuman') {
            $data['unggulan'] = ic_broadcast::query()->tayang()->where('unggulan', true)
                ->orderByDesc('terbit_pada')->orderByDesc('id_broadcast')->first();
            $data['pengumuman'] = ic_broadcast::query()->tayang()->where('unggulan', false)
                ->orderByDesc('terbit_pada')->orderByDesc('id_broadcast')
                ->limit((int) config('inner_circle.pengumuman_maks_anggota', 20))->get();
        } elseif ($tab === 'game') {
            $data['ringkasanGame'] = $game->ringkasan($anggota);
            $data['teksGame'] = $pengaturanGame->untukTeks();
            $data['papan'] = $game->papan($anggota);
        } else {
            $data['kenangan'] = $kenangan->galeri($anggota, $kategori === 'all' ? null : $kategori);
        }

        return view('pages.inner-circle.komunitas', $data);
    }

    public function profil(ClaimService $klaim)
    {
        $anggota = $this->anggota();
        $rewards = $klaim->hadiahUntuk($anggota);
        $pengajuan = $klaim->klaimAnggota($anggota);

        return view('pages.inner-circle.profil', [
            'member' => $anggota,
            'maksFotoMb' => config('inner_circle.foto_profil_maks_kb') / 1024,
            'metrik' => (object) [
                'total_poin' => $anggota->poin_total,
                'tahap_terbuka' => $rewards->filter(fn($r) => $r->status_member === 'terbuka')->count(),
                'poin_tertahan' => $klaim->dompet($anggota)->tertahan,
                'jumlah_klaim' => $pengajuan->count(),
            ],
            'riwayat' => $this->riwayatAktivitas($anggota, 100),
            'klaim' => $pengajuan->all(),
        ]);
    }

    /**
     * Tujuan setelah check-in gagal: halaman detail acara bila form dikirim dari sana (field `kembali=detail`),
     * selain itu halaman Check-in dengan acaranya terpilih (lembar terbuka lagi dan menampilkan galatnya).
     */
    private function tujuanGalat(Request $request, ?string $kodeAcara, array $parameter): string
    {
        if ($request->input('kembali') === 'detail' && filled($kodeAcara)) {
            return route('ic.acara.detail', $kodeAcara);
        }

        return route('ic.checkin', $parameter);
    }

    private function anggota(): member
    {
        return Auth::guard('member')->user();
    }

    /**
     * Jumlah isi Komunitas yang belum dibaca anggota, per tab.
     *
     * - Pengumuman: yang sudah tayang dan dibuat setelah tab terakhir dibuka, atau yang tanggal terbitnya (jadwal)
     *   jatuh sesudah hari itu.
     * - Galeri: kenangan tayang milik anggota lain yang dibuat setelah tab terakhir dibuka.
     * - Belum pernah membuka tab: pembandingnya waktu anggota bergabung.
     *
     * Membuka sebuah tab menandainya dibaca (hanya untuk anggota sungguhan; pratinjau CMS memakai anggota contoh
     * yang tidak tersimpan, jadi tidak menulis apa pun).
     *
     * @return array{pengumuman: int, galeri: int}
     */
    private function belumDibaca(member $anggota, string $tabDibuka): array
    {
        $sekarang = now();
        $bergabung = $anggota->created_at ?? $sekarang;

        $sejakPengumuman = $anggota->pengumuman_dibaca_pada ?? $bergabung;
        $sejakGaleri = $anggota->galeri_dibaca_pada ?? $bergabung;

        $jumlah = [
            'pengumuman' => ic_broadcast::query()->tayang()
                ->where(fn($q) => $q->where('created_at', '>', $sejakPengumuman)
                    ->orWhereDate('terbit_pada', '>', $sejakPengumuman->toDateString()))
                ->count(),
            'galeri' => ic_memory::query()->tayang()
                ->where('member_id', '!=', $anggota->getKey())
                ->where('created_at', '>', $sejakGaleri)
                ->count(),
        ];

        // Tab lain (mis. Game) tidak punya lencana: tidak ada yang ditandai dibaca.
        if ($tabDibuka === '') {
            return $jumlah;
        }

        $kolom = $tabDibuka === 'galeri' ? 'galeri_dibaca_pada' : 'pengumuman_dibaca_pada';
        $kunci = $tabDibuka === 'galeri' ? 'galeri' : 'pengumuman';

        if ($anggota->exists) {
            // Hanya ditulis bila ada yang berubah (ada lencana, atau belum pernah dibuka sama sekali).
            if ($jumlah[$kunci] > 0 || $anggota->{$kolom} === null) {
                $anggota->forceFill([$kolom => $sekarang])->saveQuietly();
            }
        }
        $jumlah[$kunci] = 0;

        return $jumlah;
    }

    /**
     * Riwayat aktivitas: poin (check-in, poin manual, penukaran, koreksi) dan GL gratis (antri, menang, tidak terpilih)
     * dalam satu daftar, terbaru di atas. Baris GL berupa objek sederhana dengan `jenis = gl` (lihat komponen
     * baris-aktivitas); baris poin tetap model ic_poin.
     *
     * @return array<int, object>
     */
    private function riwayatAktivitas(member $anggota, int $batas): array
    {
        $poin = ic_poin::query()
            ->where('member_id', $anggota->getKey())
            ->orderByDesc('created_at')
            ->orderByDesc('id_poin')
            ->limit($batas)
            ->get();

        $gl = ic_gl_antrean::query()
            ->with('event')
            ->where('member_id', $anggota->getKey())
            ->orderByDesc('updated_at')
            ->orderByDesc('id_antrean')
            ->limit($batas)
            ->get()
            ->filter(fn($b) => $b->event !== null)
            ->map(function ($b) {
                $status = match ($b->status) {
                    ic_gl_antrean::MENANG => 'Menang GL',
                    ic_gl_antrean::TIDAK_TERPILIH => 'Tidak terpilih',
                    default => 'Dalam antrean',
                };

                return (object) [
                    'jenis' => 'gl',
                    'ikon' => 'ticket',
                    'judul' => 'GL gratis · ' . ($b->event->venue ?? $b->event->kode),
                    'tanggal' => $b->updated_at ?? $b->created_at,
                    'keterangan' => $b->event->tanggal->locale('id')->translatedFormat('D, j M Y') . ' · ' . ($b->status === ic_gl_antrean::MENANG ? 'lihat kartu GL' : 'lihat acara'),
                    'status' => $status,
                    'menang' => $b->status === ic_gl_antrean::MENANG,
                    // Pemenang langsung dibawa ke Kartu GL (popup terbuka otomatis di halaman acara).
                    'tautan' => $b->status === ic_gl_antrean::MENANG
                        ? route('ic.acara.detail', ['kode' => $b->event->kode, 'kartu' => 1])
                        : route('ic.acara.detail', $b->event->kode),
                ];
            });

        return $poin->concat($gl)
            ->sortByDesc(fn($a) => $a->tanggal->getTimestamp())
            ->take($batas)
            ->values()
            ->all();
    }

    /** Ambil parameter query sebagai teks; array atau nilai aneh dianggap kosong. */
    private function teks(Request $request, string $kunci): string
    {
        $nilai = $request->query($kunci);

        return is_string($nilai) ? $nilai : '';
    }
}