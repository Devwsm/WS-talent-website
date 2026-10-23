<?php

namespace App\Http\Controllers\InnerCircle;

use App\Http\Controllers\Controller;
use App\Http\Requests\InnerCircle\simpanKenanganRequest;
use App\Models\ic_memory;
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
}