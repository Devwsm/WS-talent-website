<?php

namespace App\Http\Controllers\InnerCircle;

use App\Http\Controllers\Concerns\PembantuHalamanAdminIc;
use App\Http\Controllers\Controller;
use App\Models\ic_event;
use App\Models\ic_gl_antrean;
use App\Services\AktivitasService;
use App\Services\InnerCircle\GlGagal;
use App\Services\InnerCircle\GlService;
use App\Services\InnerCircle\UndianService;
use App\Support\CsvEkspor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * CMS Inner Circle, panel undian GL satu acara (Fase 6B): antrean, pemenang, undi sekarang, tunda/lanjutkan,
 * ekspor CSV, daftar untuk WhatsApp, dan tanda "diterima tim Show".
 *
 * Username dan sandi acara TIDAK pernah tampil di halaman ini, di CSV, maupun di daftar WhatsApp (keputusan 7):
 * sandi hanya terlihat lewat tombol di Atur acara (tercatat di Log) dan di kartu pemenang.
 * Catatan Log untuk aksi undian ditulis UndianService (dengan alasan), jadi route-nya dikecualikan dari pencatat
 * otomatis (`ic.admin.acara.gl.*` di config/log_admin.php) supaya tidak tercatat dua kali.
 */
class acaraGlController extends Controller
{
    use PembantuHalamanAdminIc;

    private const STATUS_UNDIAN = [
        'belum' => 'Belum diundi',
        'ditunda' => 'Ditunda admin',
        'tertunda' => 'Tertunda (data GL belum lengkap)',
        'selesai' => 'Selesai',
    ];

    public function index(string $kode, Request $request)
    {
        $event = $this->acara($kode);
        $selesai = $event->status_undian === ic_event::UNDIAN_SELESAI;

        $tab = $this->teks($request, 'tab');
        $tab = in_array($tab, ['antrean', 'pemenang'], true) ? $tab : ($selesai ? 'pemenang' : 'antrean');

        $dasar = ic_gl_antrean::query()->where('event_id', $event->getKey());
        $hitung = fn(?string $status) => (clone $dasar)->when($status, fn($q) => $q->where('status', $status))->count();

        $daftar = (clone $dasar)
            ->with('anggota')
            ->when($tab === 'pemenang', fn($q) => $q->where('status', ic_gl_antrean::MENANG)->orderBy('urutan_undi'))
            ->when($tab === 'antrean', fn($q) => $q->orderBy('created_at')->orderBy('id_antrean'))
            ->paginate(25)
            ->withQueryString()
            ->fragment('daftar');

        $pemenang = (clone $dasar)->with('anggota')->where('status', ic_gl_antrean::MENANG)->orderBy('urutan_undi')->get();

        $kurang = array_keys(array_filter([
            'kuota GL' => ! $event->punya_gl,
            'username acara' => ! filled($event->username_acara),
            'sandi acara' => ! filled($event->sandi_acara),
        ]));

        return $this->tampil('acara.gl', [
            'event' => $event,
            'namaAcara' => GlService::namaAcara($event),
            'statusLabel' => self::STATUS_UNDIAN[$event->status_undian] ?? $event->status_undian,
            'selesai' => $selesai,
            'tab' => $tab,
            'daftar' => $daftar,
            'jumlahSemua' => $hitung(null),
            'jumlahAntri' => $hitung(ic_gl_antrean::ANTRI),
            'jumlahMenang' => $hitung(ic_gl_antrean::MENANG),
            'jumlahKalah' => $hitung(ic_gl_antrean::TIDAK_TERPILIH),
            'jumlahDiterima' => $pemenang->whereNotNull('diterima_show_pada')->count(),
            'jumlahKartuTerkirim' => $pemenang->whereNotNull('kartu_dikirim_pada')->count(),
            'dataKurang' => $kurang,
            'bisaUndi' => $event->punya_gl && ! $selesai,
            'bisaTunda' => in_array($event->status_undian, [ic_event::UNDIAN_BELUM, ic_event::UNDIAN_TERTUNDA], true),
            'bisaLanjutkan' => in_array($event->status_undian, [ic_event::UNDIAN_DITUNDA, ic_event::UNDIAN_TERTUNDA], true),
            'teksWhatsapp' => $selesai ? $this->teksWhatsapp($event, $pemenang) : null,
        ]);
    }

    /** Undi sekarang: wajib alasan (dicatat di Log oleh UndianService). */
    public function undi(string $kode, Request $request, UndianService $undian): RedirectResponse
    {
        $event = $this->acara($kode);
        $data = $request->validate(['alasan' => ['required', 'string', 'min:5', 'max:200']], [
            'alasan.required' => 'Isi alasan undian, supaya tercatat di Log.',
            'alasan.min' => 'Alasan undian minimal 5 huruf.',
            'alasan.max' => 'Alasan undian maksimal 200 karakter.',
        ]);

        try {
            $hasil = $undian->undi($event, null, $data['alasan']);
        } catch (GlGagal $e) {
            return $this->kembali($event)->withErrors(['gl' => $e->getMessage()]);
        }

        return $this->kembali($event)->with('success', "Undian selesai: {$hasil['peserta']} peserta, {$hasil['pemenang']} pemenang. Email kartu GL dikirim ke pemenang.");
    }

    public function tunda(string $kode, UndianService $undian): RedirectResponse
    {
        $event = $this->acara($kode);

        try {
            $undian->tunda($event, null);
        } catch (GlGagal $e) {
            return $this->kembali($event)->withErrors(['gl' => $e->getMessage()]);
        }

        return $this->kembali($event)->with('success', 'Undian ditunda. Antrean tetap terbuka dan undian otomatis tidak jalan sampai dilanjutkan.');
    }

    public function lanjutkan(string $kode, UndianService $undian): RedirectResponse
    {
        $event = $this->acara($kode);

        try {
            $undian->lanjutkan($event, null);
        } catch (GlGagal $e) {
            return $this->kembali($event)->withErrors(['gl' => $e->getMessage()]);
        }

        return $this->kembali($event)->with('success', 'Undian dilanjutkan sesuai jadwal. Bila waktunya sudah lewat, undian jalan dalam semenit.');
    }

    /** Tandai semua pemenang yang belum bertanda sebagai "daftar sudah diterima tim Show". */
    public function diterima(string $kode, AktivitasService $aktivitas): RedirectResponse
    {
        $event = $this->acara($kode);

        if ($event->status_undian !== ic_event::UNDIAN_SELESAI) {
            return $this->kembali($event)->withErrors(['gl' => 'Daftar pemenang baru ada setelah undian selesai.']);
        }

        $jumlah = ic_gl_antrean::query()
            ->where('event_id', $event->getKey())
            ->where('status', ic_gl_antrean::MENANG)
            ->whereNull('diterima_show_pada')
            ->update(['diterima_show_pada' => now()]);

        if ($jumlah > 0) {
            $aktivitas->catat('Daftar GL diterima tim Show', GlService::namaAcara($event) . " ({$event->kode}): {$jumlah} pemenang ditandai sudah diterima tim Show.", 'berhasil', 'inner-circle', null, 'ic.admin.acara.gl.diterima');
        }

        return $this->kembali($event)->with('success', $jumlah > 0 ? "{$jumlah} pemenang ditandai sudah diterima tim Show." : 'Semua pemenang sudah bertanda diterima.');
    }

    /** CSV ?jenis=pemenang (bawaan setelah selesai) atau ?jenis=antrean (semua pendaftar). Tanpa username dan sandi acara. */
    public function ekspor(string $kode, Request $request, AktivitasService $aktivitas)
    {
        $event = $this->acara($kode);
        $jenis = $this->teks($request, 'jenis') === 'antrean' ? 'antrean' : 'pemenang';

        $query = ic_gl_antrean::query()
            ->with('anggota')
            ->where('event_id', $event->getKey())
            ->when($jenis === 'pemenang', fn($q) => $q->where('status', ic_gl_antrean::MENANG)->orderBy('urutan_undi'))
            ->when($jenis === 'antrean', fn($q) => $q->orderBy('created_at')->orderBy('id_antrean'));

        $aktivitas->catat('Daftar GL diunduh', 'Ekspor CSV ' . ($jenis === 'pemenang' ? 'pemenang' : 'antrean') . ' GL ' . GlService::namaAcara($event) . " ({$event->kode}): " . (clone $query)->count() . ' baris.', 'berhasil', 'inner-circle', null, 'ic.admin.acara.gl.ekspor');

        $status = ['antri' => 'Antre', 'menang' => 'Menang', 'tidak_terpilih' => 'Tidak terpilih'];
        $baris = (function () use ($query, $status) {
            $no = 0;
            foreach ($query->cursor() as $b) {
                yield [
                    ++$no,
                    $b->anggota?->kode_member,
                    $b->anggota?->nama,
                    $b->anggota?->email,
                    $status[$b->status] ?? $b->status,
                    $b->urutan_undi,
                    $b->created_at?->format('Y-m-d H:i:s'),
                    $b->kartu_dikirim_pada?->format('Y-m-d H:i:s'),
                    $b->diterima_show_pada?->format('Y-m-d H:i:s'),
                ];
            }
        })();

        return CsvEkspor::unduh(
            'gl-' . $jenis . '-' . $event->kode . '-' . $event->tanggal->toDateString() . '.csv',
            ['No', 'Kode anggota', 'Nama', 'Email', 'Status', 'Urutan undi', 'Daftar pada', 'Kartu GL dikirim', 'Diterima tim Show'],
            $baris,
        );
    }

    /** Teks siap tempel ke WhatsApp. Hanya nama dan Member ID: tanpa username/sandi acara dan tanpa email. */
    private function teksWhatsapp(ic_event $event, $pemenang): string
    {
        $baris = $pemenang->values()->map(fn(ic_gl_antrean $b, int $i) => ($i + 1) . '. ' . ($b->anggota?->nama ?? 'Anggota dihapus') . ($b->anggota?->kode_member ? ' (' . $b->anggota->kode_member . ')' : ''));

        return implode("\n", [
            '*GL gratis: ' . GlService::namaAcara($event) . '*',
            $event->tanggal->locale('id')->translatedFormat('l, j F Y'),
            '',
            'Pemenang (' . $pemenang->count() . ' dari kuota ' . (int) $event->kuota_gl . '):',
            ...($baris->isEmpty() ? ['(belum ada pemenang)'] : $baris->all()),
        ]);
    }

    private function acara(string $kode): ic_event
    {
        $event = ic_event::query()->where('kode', $kode)->firstOrFail();
        abort_unless($event->bisa_checkin, 404);

        return $event;
    }

    private function kembali(ic_event $event): RedirectResponse
    {
        return redirect(route('ic.admin.acara.gl', $event->kode) . '#undian');
    }
}