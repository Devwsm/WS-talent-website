<?php

namespace App\Http\Controllers\InnerCircle;

use App\Http\Controllers\Concerns\PembantuHalamanAdminIc;
use App\Http\Controllers\Controller;
use App\Http\Requests\InnerCircle\blokirAnggotaRequest;
use App\Http\Requests\InnerCircle\imporEventRequest;
use App\Http\Requests\InnerCircle\kurangiPoinRequest;
use App\Http\Requests\InnerCircle\moderasiKenanganRequest;
use App\Http\Requests\InnerCircle\simpanEventRequest;
use App\Http\Requests\InnerCircle\simpanHadiahRequest;
use App\Http\Requests\InnerCircle\simpanLokasiRequest;
use App\Http\Requests\InnerCircle\simpanPengumumanRequest;
use App\Http\Requests\InnerCircle\poinManualRequest;
use App\Http\Requests\InnerCircle\ubahKlaimRequest;
use App\Models\ic_broadcast;
use App\Models\ic_claim;
use App\Models\ic_event;
use App\Models\ic_memory;
use App\Models\ic_pengaduan;
use App\Models\ic_reward_tunggu;
use App\Models\ic_reward;
use App\Models\ic_poin;
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
use App\Services\InnerCircle\PoinService;
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
    use PembantuHalamanAdminIc;

    private const PER_HALAMAN_ANGGOTA = 15;
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
                'kehadiran_terverifikasi' => ic_poin::query()->where('sumber', 'checkin')->count(),
                'klaim_menunggu' => ic_claim::query()->whereIn('status', ic_claim::statusMenunggu())->count(),
                'pengaduan_terbuka' => ic_pengaduan::query()->whereIn('status', ic_pengaduan::statusTerbuka())->count(),
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
                ->orderByDesc('aktif')
                ->orderBy('urutan')
                ->orderBy('harga_poin')
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
            ->withMax(['poin as terakhir_checkin' => fn($s) => $s->where('sumber', 'checkin')], 'created_at')
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
        // Pintasan dari Ringkasan: hanya anggota yang punya pengaduan belum selesai (?pengaduan=terbuka).
        if ($this->teks($request, 'pengaduan') === 'terbuka') {
            $query->whereHas('pengaduan', fn($p) => $p->whereIn('status', ic_pengaduan::statusTerbuka()));
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

        $query->withCount(['poin as total_checkin' => fn($s) => $s->where('sumber', 'checkin')])->reorder('id_member');
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
                    $m->poin_total,
                    $m->total_checkin,
                    $m->terakhir_checkin?->format('Y-m-d H:i'),
                    $m->diblokir() ? 'Dinonaktifkan' : 'Aktif',
                ];
            }
        })();

        return CsvEkspor::unduh(
            'anggota-inner-circle-' . now()->format('Ymd-His') . '.csv',
            ['Kode', 'Nama', 'Email', 'Email terverifikasi', 'Daftar via', 'Google terhubung', 'Bergabung', 'Total poin', 'Jumlah check-in', 'Check-in terakhir', 'Status akun'],
            $baris,
        );
    }

    /** Profil satu anggota: data akun, tindakan admin, riwayat poin, dan klaim hadiah. */
    public function detailAnggota(int $id)
    {
        $anggota = member::query()
            ->withCount(['poin as total_checkin' => fn($s) => $s->where('sumber', 'checkin')])
            ->withMax(['poin as terakhir_checkin' => fn($s) => $s->where('sumber', 'checkin')], 'created_at')
            ->findOrFail($id);

        return $this->tampil('anggota-detail', [
            'anggota' => $anggota,
            'riwayat' => ic_poin::query()
                ->with('event')
                ->where('member_id', $anggota->getKey())
                ->orderByDesc('created_at')
                ->orderByDesc('id_poin')
                ->paginate(self::PER_HALAMAN_ANGGOTA)
                ->withQueryString(),
            'klaimAnggota' => ic_claim::query()->with('reward')->where('member_id', $anggota->getKey())->orderByDesc('id_claim')->get(),
            'dompet' => app(ClaimService::class)->dompet($anggota),
            'pengaduanAnggota' => ic_pengaduan::query()->with('event')->where('member_id', $anggota->getKey())->orderByDesc('id_pengaduan')->get(),
        ]);
    }

    /** Kurangi poin (koreksi salah input). Ditulis sebagai baris negatif di riwayat, tidak menghapus baris lama. */
    public function kurangiPoin(int $id, kurangiPoinRequest $request, PoinService $poin): RedirectResponse
    {
        $anggota = member::query()->findOrFail($id);
        $jumlah = (int) $request->validated('jumlah');

        $poin->kurangi($anggota, $jumlah, $request->validated('catatan'), session('user'));

        $pesan = ic_angka($jumlah) . " poin dikurangi dari {$anggota->nama}. Sekarang " . ic_angka($anggota->poin_total) . ' poin.';

        // Klaim yang sudah diajukan tidak otomatis dibatalkan; admin diingatkan supaya bisa menindaklanjuti sendiri.
        $melebihi = ic_claim::query()
            ->where('member_id', $anggota->getKey())
            ->where('status', '!=', 'ditolak')
            ->whereHas('reward', fn($r) => $r->where('harga_poin', '>', $anggota->poin_total))
            ->count();
        if ($melebihi > 0) {
            $pesan .= " Perhatian: {$melebihi} klaim hadiah anggota ini butuh poin lebih banyak dari total sekarang. Klaim tidak otomatis dibatalkan; periksa di tab Klaim.";
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

    /** Tambah poin manual (mis. kehadiran di acara privat yang diverifikasi tim WSM). */
    public function tambahPoin(int $id, poinManualRequest $request, PoinService $poin): RedirectResponse
    {
        $anggota = member::query()->findOrFail($id);
        $jumlah = (int) $request->validated('jumlah');

        $poin->tambahManual($anggota, $jumlah, $request->validated('catatan'), session('user'));

        return back()->with('success', ic_angka($jumlah) . " poin ditambahkan untuk {$anggota->nama}.");
    }

    /**
     * Antrean klaim. Filter lewat ?status=menunggu (bawaan, yang tertua dulu) | semua | selesai | ditolak.
     * Dipaginasi (?page=).
     */
    public function klaim(Request $request)
    {
        $status = $this->teks($request, 'status');
        $status = in_array($status, ['semua', 'selesai', 'ditolak', 'dibatalkan'], true) ? $status : 'menunggu';

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
                'dibatalkan' => (int) ($perStatus['dibatalkan'] ?? 0),
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

    /**
     * Katalog hadiah. Hadiah yang dibuka di form dipilih lewat ?hadiah=ID, atau ?baru=1 untuk menambah (bisa ditautkan dan
     * tetap jalan tanpa JavaScript). Stok, penukaran yang menunggu, dan daftar tunggu ikut dihitung.
     */
    public function hadiah(Request $request)
    {
        $rewards = ic_reward::query()
            ->withCount([
                'klaim as klaim_menunggu' => fn($q) => $q->whereIn('status', ic_claim::statusMenunggu()),
                'tunggu as jumlah_tunggu',
            ])
            ->orderByDesc('aktif')
            ->orderBy('urutan')
            ->orderBy('harga_poin')
            ->get();

        $baru = $this->teks($request, 'baru') === '1';
        $dipilih = $baru ? null : ($rewards->firstWhere('id_reward', (int) $this->teks($request, 'hadiah')) ?? $rewards->first());

        return $this->tampil('hadiah', [
            'rewards' => $rewards->all(),
            'dipilih' => $dipilih,
            'mode' => $dipilih ? 'ubah' : 'baru',
            'penunggu' => $dipilih
                ? ic_reward_tunggu::query()->with('member')->where('reward_id', $dipilih->getKey())->orderBy('created_at')->limit(50)->get()
                : collect(),
            'ikonPeta' => $this->ikonPeta(),
        ]);
    }

    /** Tambah hadiah baru ke katalog. */
    public function simpanHadiah(simpanHadiahRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['opsi'] = $data['opsi'] ?: null;
        $data['urutan'] = (int) ic_reward::query()->max('urutan') + 1;
        $data['aktif'] = true;
        $reward = ic_reward::create($data);

        return redirect(route('ic.admin.hadiah', ['hadiah' => $reward->getKey()]))
            ->with('success', "Hadiah \"{$reward->judul}\" ditambahkan ke katalog.");
    }

    /** Ubah teks, tampilan, harga, dan stok satu hadiah. Penukaran yang sudah masuk menyimpan harganya sendiri. */
    public function ubahHadiah(int $id, simpanHadiahRequest $request): RedirectResponse
    {
        $reward = ic_reward::query()->findOrFail($id);

        $data = $request->validated();
        $data['opsi'] = $data['opsi'] ?: null;
        $reward->update($data);

        return redirect(route('ic.admin.hadiah', ['hadiah' => $reward->getKey()]))
            ->with('success', "Hadiah \"{$reward->judul}\" diperbarui.");
    }

    /**
     * Tampilkan atau sembunyikan satu hadiah dari anggota. Hadiah yang disembunyikan tidak tampil di halaman
     * Hadiah dan tidak bisa ditukar baru; penukaran yang sudah masuk tetap diproses. Minimal satu hadiah harus aktif.
     */
    public function aktifkanHadiah(int $id, Request $request): RedirectResponse
    {
        $aktif = (bool) $request->validate(['aktif' => ['required', 'boolean']])['aktif'];
        $reward = ic_reward::query()->findOrFail($id);

        if (! $aktif && ! ic_reward::query()->where('aktif', true)->where('id_reward', '!=', $reward->getKey())->exists()) {
            return redirect(route('ic.admin.hadiah', ['hadiah' => $reward->getKey()]))
                ->with('error', 'Minimal satu hadiah harus tetap aktif.');
        }

        $reward->update(['aktif' => $aktif]);

        return redirect(route('ic.admin.hadiah', ['hadiah' => $reward->getKey()]))->with('success', $aktif
            ? "Hadiah \"{$reward->judul}\" aktif lagi dan tampil ke anggota."
            : "Hadiah \"{$reward->judul}\" disembunyikan dari anggota. Penukaran yang sudah masuk tetap diproses.");
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
            ->withCount(['penyuka as jumlah_like', 'komentar as jumlah_komentar'])
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

    /** Hapus satu komentar yang melanggar aturan (moderasi admin). Kenangan dan anggota lain tidak terpengaruh. */
    public function hapusKomentar(int $id, KenanganService $kenangan): RedirectResponse
    {
        $komentar = \App\Models\ic_memory_comment::query()->with('member')->findOrFail($id);
        $nama = $komentar->member->nama ?? 'anggota';
        $kenangan->hapusKomentar($komentar);

        return back()->with('success', "Komentar dari {$nama} dihapus.");
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
}