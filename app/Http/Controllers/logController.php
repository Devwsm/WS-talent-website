<?php

namespace App\Http\Controllers;

use App\Models\log_aktivitas;
use App\Models\log_error;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Halaman Log di dashboard: error aplikasi (LogErrorService) dan jejak aktivitas admin (AktivitasService).
 * Hanya bisa dibuka admin yang sudah login (grup /dashboard).
 */
class logController extends Controller
{
    public function index(Request $request)
    {
        $tab = $this->teks($request, 'tab') === 'aktivitas' ? 'aktivitas' : 'error';
        $perHalaman = (int) config('log_admin.per_halaman', 20);

        $jumlahError = [
            'terbuka' => log_error::query()->where('selesai', false)->count(),
            'selesai' => log_error::query()->where('selesai', true)->count(),
        ];
        $jumlahError['semua'] = $jumlahError['terbuka'] + $jumlahError['selesai'];

        $data = ['tab' => $tab, 'jumlahError' => $jumlahError];

        if ($tab === 'error') {
            $status = $this->teks($request, 'status');
            $status = in_array($status, ['selesai', 'semua'], true) ? $status : 'terbuka';

            $query = log_error::query()->orderByDesc('terakhir_pada')->orderByDesc('id_log_error');
            if ($status !== 'semua') {
                $query->where('selesai', $status === 'selesai');
            }

            $data += ['daftar' => $query->paginate($perHalaman)->withQueryString(), 'filterStatus' => $status];
        } else {
            $kategori = $this->teks($request, 'kategori');
            $kategori = array_key_exists($kategori, config('log_admin.kategori')) ? $kategori : 'semua';
            $hanyaGagal = $this->teks($request, 'status') === 'gagal';

            $query = log_aktivitas::query()->orderByDesc('created_at')->orderByDesc('id_log_aktivitas');
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

        $email = trim((string) config('site.peringatan_email'));
        $data['emailPeringatan'] = $email === '' ? null : $email;

        return view('pages.dashboard-pages.log', $data);
    }

    /** Tandai satu error selesai, atau buka lagi. */
    public function ubahError(int $id, Request $request): RedirectResponse
    {
        $data = $request->validate(['aksi' => ['required', 'in:selesai,buka']]);

        log_error::query()->findOrFail($id)->update(['selesai' => $data['aksi'] === 'selesai']);

        return back()->with('success', $data['aksi'] === 'selesai' ? 'Error ditandai selesai.' : 'Error dibuka lagi.');
    }

    /** Hapus semua error yang sudah ditandai selesai. */
    public function bersihkanSelesai(): RedirectResponse
    {
        $jumlah = log_error::query()->where('selesai', true)->delete();

        return redirect()->route('log.index')->with('success', "{$jumlah} catatan error yang selesai dihapus.");
    }

    private function teks(Request $request, string $kunci): string
    {
        $nilai = $request->query($kunci);

        return is_string($nilai) ? $nilai : '';
    }
}