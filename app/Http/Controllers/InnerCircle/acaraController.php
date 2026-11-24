<?php

namespace App\Http\Controllers\InnerCircle;

use App\Http\Controllers\Concerns\PembantuHalamanAdminIc;
use App\Http\Controllers\Controller;
use App\Http\Requests\InnerCircle\aturAcaraRequest;
use App\Http\Requests\InnerCircle\imporEventRequest;
use App\Http\Requests\InnerCircle\simpanEventRequest;
use App\Models\ic_event;
use App\Services\InnerCircle\AcaraService;
use App\Services\InnerCircle\BandsintownService;
use App\Services\InnerCircle\SinkronGagal;
use App\Support\GambarGagal;
use App\Support\ImageProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * CMS Inner Circle, menu Acara bagian 1: Pengaturan acara (tambah satuan, impor CSV, sinkron Bandsintown, ubah,
 * hapus, dan daftar jadwal). QR dan titik lokasi ada di acaraHariHController.
 *
 * Alamat lama /acara?acara=KODE (panel QR sebelum menu Acara dibagi dua) dialihkan ke halaman Hari H.
 */
class acaraController extends Controller
{
    use PembantuHalamanAdminIc;

    private const PER_HALAMAN_ACARA = 10;

    /**
     * Filter jadwal lewat GET (?bulan=&tipe=&q=) seperti di area anggota. Jadwal dipaginasi (?page=).
     */
    public function index(Request $request)
    {
        $kodeLama = $this->teks($request, 'acara');
        if ($kodeLama !== '') {
            return redirect(route('ic.admin.acara.harih', ['acara' => $kodeLama]) . '#qr');
        }

        $bulan = $this->teks($request, 'bulan');
        $tipe = $this->teks($request, 'tipe');

        $bulanPilihan = ic_event::pilihanBulan();

        $filter = [
            'bulan' => $bulanPilihan->has($bulan) ? $bulan : 'all',
            'tipe' => array_key_exists($tipe, ic_tipe_event()) ? $tipe : 'all',
            'q' => mb_substr(trim($this->teks($request, 'q')), 0, 60),
        ];

        $jadwal = ic_event::query()
            ->withCount(['poin as jumlah_checkin' => fn($s) => $s->where('sumber', 'checkin')])
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

        return $this->tampil('acara.pengaturan', [
            'jadwal' => $jadwal->paginate(self::PER_HALAMAN_ACARA)->withQueryString()->fragment('jadwal'),
            'totalAcara' => ic_event::query()->count(),
            'filter' => $filter,
            'bulanPilihan' => $bulanPilihan,
            'bitTersedia' => BandsintownService::tersedia(),
            'bitTerakhir' => ($terakhir = ic_event::query()->max('disinkron_pada')) ? \Carbon\Carbon::parse($terakhir) : null,
            'ikonPeta' => $this->ikonPeta(),
        ]);
    }

    public function simpanAcara(simpanEventRequest $request, AcaraService $acara): RedirectResponse
    {
        $event = $acara->buat($request->validated());

        // Lanjut ke halaman Atur acara supaya GL, poster, jendela, dan lokasi bisa langsung diisi.
        return redirect(route('ic.admin.acara.atur', $event->kode))
            ->with('success', 'Acara ' . ($event->venue ?? $event->kode) . ' ditambahkan. Lengkapi pengaturannya di bawah.');
    }

    /** Halaman Atur acara: semua pengaturan satu acara (dasar, GL, check-in, poster) dan titik lokasi di peta. */
    public function atur(string $kode)
    {
        $event = ic_event::query()->where('kode', $kode)->firstOrFail();

        return $this->tampil('acara.atur', [
            'event' => $event,
            'posterUrl' => $this->urlPoster($event),
        ]);
    }

    public function simpanAtur(string $kode, aturAcaraRequest $request, ImageProcessor $gambar): RedirectResponse
    {
        $event = ic_event::query()->where('kode', $kode)->firstOrFail();
        $d = $request->validated();

        $data = [
            'tanggal' => $d['tanggal'],
            'tipe' => $d['tipe'],
            'venue' => $d['venue'] ?? null,
            'kota' => $d['kota'] ?? null,
            'deskripsi' => $d['deskripsi'] ?? null,
            'petunjuk_masuk' => $d['petunjuk_masuk'] ?? null,
            'username_acara' => $d['username_acara'] ?? null,
            'poin_gl' => $d['poin_gl'] ?? null,
            'poin_mandiri' => $d['poin_mandiri'] ?? null,
            'jendela_mulai' => $d['jendela_mulai'] ?? null,
            'jendela_selesai' => $d['jendela_selesai'] ?? null,
        ];

        // Setelah undian selesai hasilnya terkunci: kuota dan waktu undian tidak lagi diubah dari sini.
        if ($event->status_undian !== ic_event::UNDIAN_SELESAI) {
            $data['kuota_gl'] = (int) ($d['kuota_gl'] ?? 0) > 0 ? (int) $d['kuota_gl'] : null;
            $data['undian_pada'] = $d['undian_pada'] ?? null;
        }

        // Sandi tampil apa adanya di form (kode yang bisa dibaca tim): kosong = tidak berubah, centang hapus = dikosongkan.
        if (! empty($d['hapus_sandi'])) {
            $data['sandi_acara'] = null;
        } elseif (filled($d['sandi_acara'] ?? null)) {
            $data['sandi_acara'] = $d['sandi_acara'];
        }

        // Begitu admin mengubah tanggal, kota, atau venue acara hasil Bandsintown, sinkron berikutnya tidak menimpanya lagi.
        if (
            $event->sumber === 'bandsintown'
            && ($event->tanggal->toDateString() !== $data['tanggal'] || $event->kota !== $data['kota'] || $event->venue !== $data['venue'])
        ) {
            $data['sumber'] = 'manual';
        }

        $posterBaru = null;
        $posterLama = $event->poster;
        if ($request->hasFile('poster')) {
            try {
                // Poster acara selalu 9:16 (dipotong dari tengah); sisi terpanjang 1600 px = 900 x 1600.
                $posterBaru = $gambar->simpanWebp($request->file('poster'), 'inner-circle/acara', 1600, 82, 9 / 16);
            } catch (GambarGagal $e) {
                return back()->withInput()->withErrors(['poster' => $e->getMessage()]);
            }
            $data['poster'] = $posterBaru;
        } elseif (! empty($d['hapus_poster'])) {
            $data['poster'] = null;
        }

        $event->update($data);

        // Data GL yang tadinya kurang kini lengkap: antrean tidak lagi berstatus tertunda.
        $event->refresh();
        if ($event->status_undian === ic_event::UNDIAN_TERTUNDA && $event->glLengkap()) {
            $event->update(['status_undian' => ic_event::UNDIAN_BELUM]);
        }

        if ($posterLama && ($posterBaru || ! empty($d['hapus_poster']))) {
            Storage::disk('public')->delete($posterLama);
        }

        return redirect(route('ic.admin.acara.atur', $event->kode))
            ->with('success', 'Pengaturan acara ' . ($event->venue ?? $event->kode) . ' disimpan.');
    }

    private function urlPoster(ic_event $event): ?string
    {
        return $event->poster && Storage::disk('public')->exists($event->poster) ? Storage::url($event->poster) : null;
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

        if ($event->poin()->exists()) {
            return back()->with('error', 'Acara ini sudah punya check-in anggota, jadi tidak bisa dihapus. Ubah datanya saja bila ada yang salah.');
        }

        $event->delete();

        return back()->with('success', 'Acara dihapus.');
    }

    /** Sembunyikan atau tampilkan acara di daftar anggota (bukan menghapus; QR dan check-in tidak berubah). */
    public function ubahTampil(string $kode): RedirectResponse
    {
        $event = ic_event::query()->where('kode', $kode)->firstOrFail();
        $event->balikTampilAnggota();

        $nama = $event->venue ?? $event->kode;

        return back()->with('success', $event->tampil_di_anggota
            ? "Acara {$nama} tampil lagi di daftar anggota."
            : "Acara {$nama} disembunyikan dari daftar anggota.");
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
}