<?php

namespace App\Http\Controllers\InnerCircle;

use App\Http\Controllers\Controller;
use App\Http\Requests\InnerCircle\simpanKenanganRequest;
use App\Http\Requests\InnerCircle\simpanKomentarRequest;
use App\Models\ic_memory;
use App\Models\ic_memory_comment;
use App\Services\InnerCircle\KenanganService;
use App\Support\GambarGagal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Aksi anggota atas galeri kenangan (semua route di sini dilindungi `auth:member`):
 * unggah, hapus milik sendiri, dan suka. Halaman Komunitas sendiri ada di memberController.
 */
class kenanganController extends Controller
{
    public function simpan(simpanKenanganRequest $request, KenanganService $kenangan): RedirectResponse
    {
        try {
            $kenangan->terbit(Auth::guard('member')->user(), $request->validated(), $request->file('foto'));
        } catch (GambarGagal $e) {
            // Kembali ke halaman asal dengan isian tadi; modal unggah dibuka lagi oleh view karena ada galat.
            return back()->withInput()->withErrors(['foto' => $e->getMessage()]);
        }

        return redirect()->route('ic.komunitas', ['tab' => 'galeri'])
            ->with('ic_toast', 'Kenanganmu sudah tayang di galeri!');
    }

    /** Halaman detail satu kenangan: foto penuh, suka, dan komentar anggota. Kenangan yang disembunyikan = 404. */
    public function detail(int $id, KenanganService $kenangan)
    {
        $anggota = Auth::guard('member')->user();
        $dipilih = ic_memory::query()->findOrFail($id);
        abort_if(Gate::forUser($anggota)->denies('komentar', $dipilih), 404);

        $komentar = $dipilih->komentar()->with('member')->paginate((int) config('inner_circle.komentar_per_halaman', 50))->withQueryString();

        return view('pages.inner-circle.kenangan-detail', [
            'member' => $anggota,
            'kenangan' => $kenangan->detail($anggota, $dipilih),
            'komentar' => $komentar,
            'maksKomentar' => (int) config('inner_circle.komentar_maks', 300),
        ]);
    }

    /** Tulis komentar (butuh email terverifikasi, seperti unggah dan suka). */
    public function komentar(int $id, simpanKomentarRequest $request, KenanganService $kenangan): RedirectResponse
    {
        $anggota = Auth::guard('member')->user();
        $dipilih = ic_memory::query()->findOrFail($id);
        abort_if(Gate::forUser($anggota)->denies('komentar', $dipilih), 404);

        $kenangan->komentar($anggota, $dipilih, $request->validated('isi'));

        // Halaman terakhir daftar komentar (komentar terbaru ada di bawah).
        $terakhir = (int) ceil(max(1, $dipilih->komentar()->count()) / max(1, (int) config('inner_circle.komentar_per_halaman', 50)));

        return redirect(route('ic.kenangan.detail', array_filter(['id' => $id, 'page' => $terakhir > 1 ? $terakhir : null])) . '#komentar')
            ->with('ic_toast', 'Komentarmu terkirim.');
    }

    /** Hapus komentar: penulisnya atau pemilik kenangan. */
    public function hapusKomentar(int $id, KenanganService $kenangan): RedirectResponse
    {
        $anggota = Auth::guard('member')->user();
        $komentar = ic_memory_comment::query()->with('kenangan')->findOrFail($id);

        Gate::forUser($anggota)->authorize('hapusKomentar', $komentar);
        $idKenangan = $komentar->memory_id;
        $kenangan->hapusKomentar($komentar);

        return redirect(route('ic.kenangan.detail', $idKenangan) . '#komentar')->with('ic_toast', 'Komentar dihapus.');
    }

    /** Hanya pemilik (MemoryPolicy). Kenangan orang lain: 403. */
    public function hapus(int $id, KenanganService $kenangan): RedirectResponse
    {
        $anggota = Auth::guard('member')->user();
        $kenanganDipilih = ic_memory::query()->findOrFail($id);

        Gate::forUser($anggota)->authorize('hapus', $kenanganDipilih);
        $kenangan->hapus($kenanganDipilih);

        return back()->with('ic_toast', 'Kenangan dihapus.');
    }

    /** Suka atau batal suka; jawabannya JSON `{disukai, jumlah}` yang dipakai JavaScript untuk memperbarui tombol. */
    public function suka(int $id, KenanganService $kenangan): JsonResponse
    {
        $anggota = Auth::guard('member')->user();
        $kenanganDipilih = ic_memory::query()->findOrFail($id);

        // Kenangan yang disembunyikan admin diperlakukan seperti tidak ada.
        abort_if(Gate::forUser($anggota)->denies('suka', $kenanganDipilih), 404);

        return response()->json($kenangan->toggleSuka($anggota, $kenanganDipilih));
    }

    /** Daftar penyuka (JSON) untuk popup "Disukai oleh" dan penyegaran tumpukan foto di kartu. */
    public function penyuka(int $id, KenanganService $kenangan): JsonResponse
    {
        $anggota = Auth::guard('member')->user();
        $kenanganDipilih = ic_memory::query()->findOrFail($id);

        // Sama seperti suka dan komentar: kenangan yang disembunyikan admin seolah tidak ada.
        abort_if(Gate::forUser($anggota)->denies('komentar', $kenanganDipilih), 404);

        return response()->json($kenangan->daftarPenyuka($kenanganDipilih))->header('Cache-Control', 'no-store');
    }
}