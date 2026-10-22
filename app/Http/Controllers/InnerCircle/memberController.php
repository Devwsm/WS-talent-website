<?php

namespace App\Http\Controllers\InnerCircle;

use App\Http\Controllers\Controller;
use App\Http\Requests\InnerCircle\checkinRequest;
use App\Models\ic_event;
use App\Models\ic_stempel;
use App\Models\member;
use App\Services\InnerCircle\CheckinGagal;
use App\Services\InnerCircle\CheckinService;
use App\Support\InnerCircleDummy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Layar anggota WS Inner Circle (semua route di sini dilindungi `auth:member`).
 *
 * Fase 4: anggota, Beranda, Check-in, dan riwayat stempel di Profil sudah dari database.
 * Hadiah dan Komunitas masih memakai InnerCircleDummy untuk isinya (Fase 5-6), tetapi anggotanya
 * sudah anggota yang sedang login. Nama variabel ke view tidak berubah dari Fase 2.
 */
class memberController extends Controller
{
    public function beranda()
    {
        $anggota = $this->anggota();

        return view('pages.inner-circle.beranda', [
            'member' => $anggota,
            'berikutnya' => InnerCircleDummy::berikutnya($anggota->stempel_total),
            'aktivitas' => $this->riwayatStempel($anggota, 5),
        ]);
    }

    public function hadiah()
    {
        $anggota = $this->anggota();

        return view('pages.inner-circle.hadiah', [
            'member' => $anggota,
            'rewards' => InnerCircleDummy::rewards($anggota->stempel_total),
        ]);
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

        try {
            $stempel = $checkin->checkin($anggota, $request->validated('kode'), $kodeAcara);
        } catch (CheckinGagal $e) {
            return redirect(route('ic.checkin', array_filter(['acara' => $kodeAcara])) . '#kode-kehadiran')
                ->withErrors(['kode' => $e->getMessage()])
                ->withInput(['kode' => $request->validated('kode')]);
        }

        return redirect()->route('ic.beranda')
            ->with('ic_toast', 'Check-in berhasil! +' . $stempel->jumlah . ' stempel. ' . $stempel->judul . '.');
    }

    /** Tab lewat ?tab=pengumuman|galeri dan kategori galeri lewat ?kategori=festival|club|personal. */
    public function komunitas(Request $request)
    {
        $tab = $this->teks($request, 'tab') === 'galeri' ? 'galeri' : 'pengumuman';

        $kategori = $this->teks($request, 'kategori');
        $kategori = array_key_exists($kategori, config('inner_circle.kategori_kenangan')) ? $kategori : 'all';

        return view('pages.inner-circle.komunitas', [
            'member' => $this->anggota(),
            'tab' => $tab,
            'kategori' => $kategori,
            'unggulan' => InnerCircleDummy::pengumumanUnggulan(),
            'pengumuman' => InnerCircleDummy::pengumuman(),
            'kenangan' => InnerCircleDummy::kenangan($kategori === 'all' ? null : $kategori),
            'jumlahKenangan' => count(InnerCircleDummy::kenangan()),
        ]);
    }

    public function profil()
    {
        $anggota = $this->anggota();
        $rewards = InnerCircleDummy::rewards($anggota->stempel_total);

        return view('pages.inner-circle.profil', [
            'member' => $anggota,
            'metrik' => (object) [
                'total_stempel' => $anggota->stempel_total,
                'tahap_terbuka' => count(array_filter($rewards, fn($r) => $r->status_member !== 'terkunci')),
                // Pengajuan hadiah baru tersambung ke database di Fase 5.
                'jumlah_klaim' => 0,
            ],
            'riwayat' => $this->riwayatStempel($anggota, 100),
            'klaim' => [],
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