<?php

namespace App\Http\Controllers\InnerCircle;

use App\Http\Controllers\Controller;
use App\Support\InnerCircleDummy;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Panel admin WS Inner Circle di dashboard CMS (7 tab).
 * Fase 3: tampilan saja, datanya dari InnerCircleDummy dan tombol simpan belum menyimpan.
 * Fase 4-6 mengganti InnerCircleDummy dengan Eloquent dan menambah route tulis; nama variabel
 * ke view tidak berubah.
 */
class adminController extends Controller
{
    private const PER_HALAMAN_ACARA = 10;

    public function ringkasan()
    {
        return $this->tampil('ringkasan', [
            'ringkasan' => InnerCircleDummy::ringkasan(),
            'klaimMenunggu' => array_slice(InnerCircleDummy::klaimAdmin(true), 0, 3),
        ]);
    }

    /** Cari lewat ?q= (nama, email, atau kode anggota). */
    public function anggota(Request $request)
    {
        $q = mb_substr(trim($this->teks($request, 'q')), 0, 60);

        return $this->tampil('anggota', [
            'anggota' => InnerCircleDummy::anggota($q),
            'totalAnggota' => count(InnerCircleDummy::anggota()),
            'q' => $q,
        ]);
    }

    /**
     * Filter jadwal lewat GET (?bulan=&tipe=&q=) seperti di area anggota, acara untuk panel QR
     * lewat ?acara=KODE. Jadwal dipaginasi (?page=).
     */
    public function acara(Request $request)
    {
        $bulan = $this->teks($request, 'bulan');
        $tipe = $this->teks($request, 'tipe');

        $filter = [
            'bulan' => array_key_exists((int) $bulan, config('inner_circle.bulan_event')) ? (string) (int) $bulan : 'all',
            'tipe' => array_key_exists($tipe, config('inner_circle.tipe_event')) ? $tipe : 'all',
            'q' => mb_substr(trim($this->teks($request, 'q')), 0, 60),
        ];

        // Hanya acara yang bisa check-in (bukan privat, ada venue) yang punya QR.
        $acaraQr = array_values(array_filter(
            InnerCircleDummy::eventsAdmin(),
            fn($e) => $e->bisa_checkin
        ));
        $kode = $this->teks($request, 'acara');
        $acaraDipilih = collect($acaraQr)->first(fn($e) => $e->kode === $kode)
            ?? collect($acaraQr)->first(fn($e) => $e->status_waktu !== 'lewat')
            ?? ($acaraQr[0] ?? null);

        $jadwal = InnerCircleDummy::eventsAdmin($filter);
        $halaman = max(1, (int) $request->query('page', 1));
        $paginator = (new LengthAwarePaginator(
            array_slice($jadwal, ($halaman - 1) * self::PER_HALAMAN_ACARA, self::PER_HALAMAN_ACARA),
            count($jadwal),
            self::PER_HALAMAN_ACARA,
            $halaman,
            ['path' => url()->current(), 'query' => $request->query()]
        ))->fragment('jadwal');

        return $this->tampil('acara', [
            'jadwal' => $paginator,
            'totalAcara' => InnerCircleDummy::totalEvents(),
            'filter' => $filter,
            'acaraQr' => $acaraQr,
            'acaraDipilih' => $acaraDipilih,
            'ikonPeta' => $this->ikonPeta(),
        ]);
    }

    public function klaim()
    {
        return $this->tampil('klaim', [
            'klaim' => InnerCircleDummy::klaimAdmin(),
        ]);
    }

    public function hadiah()
    {
        return $this->tampil('hadiah', [
            'rewards' => InnerCircleDummy::rewards(),
            'ikonPeta' => $this->ikonPeta(),
        ]);
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

    /** Render satu tab; angka lencana di bar tab selalu ikut dikirim. */
    private function tampil(string $tab, array $data)
    {
        return view('pages.dashboard-pages.inner-circle.' . $tab, $data + [
            'jumlahKlaimMenunggu' => count(InnerCircleDummy::klaimAdmin(true)),
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