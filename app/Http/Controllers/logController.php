<?php

namespace App\Http\Controllers;

use App\Models\log_aktivitas;
use App\Models\log_error;
use App\Support\AreaLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Halaman Log: error aplikasi (LogErrorService) dan jejak aktivitas admin (AktivitasService).
 * Ada dua halaman dengan isi berbeda: CMS Artis (/dashboard/log) dan CMS Inner Circle (/dashboard/inner-circle/log).
 * Area ditentukan dari route (ic.admin.* = Inner Circle), dan setiap halaman hanya menampilkan catatan areanya sendiri.
 * Hanya bisa dibuka admin yang sudah login (grup /dashboard).
 */
class logController extends Controller
{
    public function index(Request $request)
    {
        $area = $this->area($request);
        $tab = $this->teks($request, 'tab') === 'aktivitas' ? 'aktivitas' : 'error';
        $perHalaman = (int) config('log_admin.per_halaman', 20);

        $jumlahError = [
            'terbuka' => $this->queryError($area)->where('selesai', false)->count(),
            'selesai' => $this->queryError($area)->where('selesai', true)->count(),
        ];
        $jumlahError['semua'] = $jumlahError['terbuka'] + $jumlahError['selesai'];

        $kategoriArea = (array) config('log_admin.kategori_area.' . $area, []);
        $data = [
            'area' => $area,
            'tab' => $tab,
            'jumlahError' => $jumlahError,
            'kategoriArea' => $kategoriArea,
            'ruteLog' => $area === AreaLog::INNER_CIRCLE ? 'ic.admin.log.index' : 'log.index',
            'ruteUbah' => $area === AreaLog::INNER_CIRCLE ? 'ic.admin.log.error.ubah' : 'log.error.ubah',
            'ruteBersih' => $area === AreaLog::INNER_CIRCLE ? 'ic.admin.log.error.bersihkan' : 'log.error.bersihkan',
        ];

        if ($tab === 'error') {
            $status = $this->teks($request, 'status');
            $status = in_array($status, ['selesai', 'semua'], true) ? $status : 'terbuka';

            $query = $this->queryError($area)->orderByDesc('terakhir_pada')->orderByDesc('id_log_error');
            if ($status !== 'semua') {
                $query->where('selesai', $status === 'selesai');
            }

            $data += ['daftar' => $query->paginate($perHalaman)->withQueryString(), 'filterStatus' => $status];
        } else {
            $kategori = $this->teks($request, 'kategori');
            $kategori = in_array($kategori, $kategoriArea, true) ? $kategori : 'semua';
            $hanyaGagal = $this->teks($request, 'status') === 'gagal';

            $query = log_aktivitas::query()
                ->whereIn('kategori', $kategoriArea)
                ->orderByDesc('created_at')
                ->orderByDesc('id_log_aktivitas');
            if ($kategori !== 'semua') {
                $query->where('kategori', $kategori);
            }
            if ($hanyaGagal) {
                $query->where('status', 'gagal');
            }

            $data += [
                'daftar' => $query->paginate($perHalaman)->withQueryString(),
                'filterKategori' => $kategori,
                'hanyaGagal' => $hanyaGagal,
            ];
        }

        // Tanda cron hidup (tampil di kedua CMS).
        $data['statusJadwal'] = \App\Services\InnerCircle\PenjadwalService::ringkasan();

        $email = trim((string) config('site.peringatan_email'));
        $data['emailPeringatan'] = $email === '' ? null : $email;

        return view('pages.dashboard-pages.log', $data);
    }

    /** Tandai satu error selesai, atau buka lagi. Hanya error milik area halaman ini. */
    public function ubahError(int $id, Request $request): RedirectResponse
    {
        $data = $request->validate(['aksi' => ['required', 'in:selesai,buka']]);

        $this->queryError($this->area($request))->findOrFail($id)->update(['selesai' => $data['aksi'] === 'selesai']);

        return back()->with('success', $data['aksi'] === 'selesai' ? 'Error ditandai selesai.' : 'Error dibuka lagi.');
    }

    /** Hapus semua error yang sudah ditandai selesai (hanya milik area halaman ini). */
    public function bersihkanSelesai(Request $request): RedirectResponse
    {
        $area = $this->area($request);
        $jumlah = $this->queryError($area)->where('selesai', true)->delete();

        return redirect()
            ->route($area === AreaLog::INNER_CIRCLE ? 'ic.admin.log.index' : 'log.index')
            ->with('success', "{$jumlah} catatan error yang selesai dihapus.");
    }

    private function area(Request $request): string
    {
        return $request->routeIs('ic.admin.*') ? AreaLog::INNER_CIRCLE : AreaLog::ARTIS;
    }

    /**
     * Error milik satu area. Bila fitur Inner Circle dimatikan, CMS Inner Circle tidak bisa dibuka, jadi CMS Artis
     * menampilkan semuanya supaya tidak ada catatan yang tak terlihat.
     */
    private function queryError(string $area): Builder
    {
        $query = log_error::query();

        if ($area === AreaLog::ARTIS && config('inner_circle.aktif')) {
            $query->where('area', AreaLog::ARTIS);
        } elseif ($area === AreaLog::INNER_CIRCLE) {
            $query->where('area', AreaLog::INNER_CIRCLE);
        }

        return $query;
    }

    private function teks(Request $request, string $kunci): string
    {
        $nilai = $request->query($kunci);

        return is_string($nilai) ? $nilai : '';
    }
}