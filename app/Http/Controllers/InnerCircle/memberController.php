<?php

namespace App\Http\Controllers\InnerCircle;

use App\Http\Controllers\Controller;
use App\Http\Requests\InnerCircle\ajukanKlaimRequest;
use App\Http\Requests\InnerCircle\checkinRequest;
use App\Models\ic_broadcast;
use App\Models\ic_event;
use App\Models\ic_memory;
use App\Models\ic_stempel;
use App\Models\member;
use App\Services\InnerCircle\CheckinGagal;
use App\Services\InnerCircle\CheckinService;
use App\Services\InnerCircle\ClaimService;
use App\Services\InnerCircle\KenanganService;
use App\Services\InnerCircle\KlaimGagal;
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
            'berikutnya' => $klaim->berikutnya($anggota->stempel_total),
            'aktivitas' => $this->riwayatStempel($anggota, 5),
        ]);
    }

    public function hadiah(ClaimService $klaim)
    {
        $anggota = $this->anggota();

        return view('pages.inner-circle.hadiah', [
            'member' => $anggota,
            'rewards' => $klaim->hadiahUntuk($anggota)->all(),
        ]);
    }

    /** Ajukan hadiah satu tahap (target stempel dari URL). Aturannya ada di ClaimService. */
    public function ajukanKlaim(int $target, ajukanKlaimRequest $request, ClaimService $klaim): RedirectResponse
    {
        try {
            $pengajuan = $klaim->ajukan($this->anggota(), $target, $request->validated('opsi'));
        } catch (KlaimGagal $e) {
            return redirect(route('ic.hadiah') . '#klaim')->with('ic_toast_galat', $e->getMessage());
        }

        return redirect(route('ic.hadiah') . '#klaim')
            ->with('ic_toast', 'Pengajuan terkirim! Tim WSM akan meninjau hadiah ' . $pengajuan->target_stempel . ' stempel.');
    }

    /**
     * Filter jadwal lewat GET (?bulan=&tipe=&q=) dan acara terpilih lewat ?acara=KODE, jadi bisa
     * dibagikan dan tetap jalan tanpa JavaScript. ?kode=WS-XXXXXXXXXXXX berasal dari QR yang
     * dipindai kamera bawaan HP: kodenya mengisi form dan acaranya dicari dari token.
     */
    public function checkin(Request $request, CheckinService $checkin)
    {
        $anggota = $this->anggota();

        $bulan = $this->teks($request, 'bulan');
        $tipe = $this->teks($request, 'tipe');

        $filter = [
            'bulan' => array_key_exists((int) $bulan, config('inner_circle.bulan_event')) ? (string) (int) $bulan : 'all',
            'tipe' => array_key_exists($tipe, config('inner_circle.tipe_event')) ? $tipe : 'all',
            'q' => mb_substr(trim($this->teks($request, 'q')), 0, 60),
        ];

        // Parameter filter yang bukan nilai bawaan, dipakai ulang di tautan tiap kartu acara.
        $parameterFilter = array_filter([
            'bulan' => $filter['bulan'] !== 'all' ? $filter['bulan'] : null,
            'tipe' => $filter['tipe'] !== 'all' ? $filter['tipe'] : null,
            'q' => $filter['q'] !== '' ? $filter['q'] : null,
        ]);

        $query = ic_event::query()->orderBy('tanggal')->orderBy('id_event');
        if ($filter['bulan'] !== 'all') {
            $query->whereMonth('tanggal', (int) $filter['bulan']);
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

        $sudahCheckin = ic_stempel::query()
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
            'totalEvents' => ic_event::query()->count(),
            'filter' => $filter,
            'parameterFilter' => $parameterFilter,
            'eventDipilih' => $eventDipilih,
            'kodeIsian' => $tokenQr ?? '',
            'galatKode' => $galatKode,
        ]);
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
            $stempel = $checkin->checkin($anggota, $request->validated('kode'), $kodeAcara, null, $lokasi);
        } catch (CheckinGagal $e) {
            // Kembali ke halaman yang sama dengan acaranya terpilih: lembar check-in terbuka lagi dan menampilkan galatnya.
            return redirect(route('ic.checkin', array_filter(['acara' => $kodeAcara])))
                ->withErrors(['kode' => $e->getMessage()])
                ->withInput(['kode' => $request->validated('kode')]);
        }

        return redirect()->route('ic.beranda')
            ->with('ic_toast', 'Check-in berhasil! +' . $stempel->jumlah . ' stempel. ' . $stempel->judul . '.');
    }

    /**
     * Tab lewat ?tab=pengumuman|galeri, kategori galeri lewat ?kategori=festival|club|personal, halaman galeri
     * lewat ?page=. Pengumuman hanya yang aktif dan sudah tiba tanggal terbitnya; galeri hanya yang tayang.
     */
    public function komunitas(Request $request, KenanganService $kenangan)
    {
        $anggota = $this->anggota();
        $tab = $this->teks($request, 'tab') === 'galeri' ? 'galeri' : 'pengumuman';

        $kategori = $this->teks($request, 'kategori');
        $kategori = array_key_exists($kategori, config('inner_circle.kategori_kenangan')) ? $kategori : 'all';

        $data = [
            'member' => $anggota,
            'tab' => $tab,
            'kategori' => $kategori,
            'jumlahPengumuman' => ic_broadcast::query()->tayang()->count(),
            'jumlahKenangan' => ic_memory::query()->tayang()->count(),
            'maksFotoMb' => config('inner_circle.foto_kenangan_maks_kb') / 1024,
        ];

        if ($tab === 'pengumuman') {
            $data['unggulan'] = ic_broadcast::query()->tayang()->where('unggulan', true)
                ->orderByDesc('terbit_pada')->orderByDesc('id_broadcast')->first();
            $data['pengumuman'] = ic_broadcast::query()->tayang()->where('unggulan', false)
                ->orderByDesc('terbit_pada')->orderByDesc('id_broadcast')
                ->limit((int) config('inner_circle.pengumuman_maks_anggota', 20))->get();
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
                'total_stempel' => $anggota->stempel_total,
                'tahap_terbuka' => $rewards->filter(fn($r) => $r->status_member !== 'terkunci')->count(),
                'jumlah_klaim' => $pengajuan->count(),
            ],
            'riwayat' => $this->riwayatStempel($anggota, 100),
            'klaim' => $pengajuan->all(),
        ]);
    }

    private function anggota(): member
    {
        return Auth::guard('member')->user();
    }

    /** Stempel terbaru anggota (check-in dan stempel manual), terbaru di atas. */
    private function riwayatStempel(member $anggota, int $batas): array
    {
        return ic_stempel::query()
            ->where('member_id', $anggota->getKey())
            ->orderByDesc('created_at')
            ->orderByDesc('id_stempel')
            ->limit($batas)
            ->get()
            ->all();
    }

    /** Ambil parameter query sebagai teks; array atau nilai aneh dianggap kosong. */
    private function teks(Request $request, string $kunci): string
    {
        $nilai = $request->query($kunci);

        return is_string($nilai) ? $nilai : '';
    }
}