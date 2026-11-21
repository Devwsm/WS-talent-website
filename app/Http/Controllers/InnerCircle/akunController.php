<?php

namespace App\Http\Controllers\InnerCircle;

use App\Http\Controllers\Controller;
use App\Http\Requests\InnerCircle\hapusAkunRequest;
use App\Http\Requests\InnerCircle\ubahNamaRequest;
use App\Services\InnerCircle\AkunGagal;
use App\Services\InnerCircle\HapusAkunService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/** Hapus akun anggota (route di bawah `auth:member`). Sesi admin CMS di browser yang sama tidak disentuh. */
class akunController extends Controller
{
    /** Ganti nama tampilan (boleh berspasi). Email dan Member ID tidak berubah. */
    public function ubahNama(ubahNamaRequest $request): RedirectResponse
    {
        $anggota = Auth::guard('member')->user();
        $anggota->forceFill(['nama' => $request->validated('nama')])->save();

        return redirect()->route('ic.profil')->with('ic_toast', 'Namamu sudah diganti.');
    }

    public function hapus(hapusAkunRequest $request, HapusAkunService $layanan): RedirectResponse
    {
        $anggota = Auth::guard('member')->user();

        try {
            $layanan->hapus($anggota);
        } catch (AkunGagal $e) {
            return redirect()->route('ic.profil')->withErrors(['akun' => $e->getMessage()]);
        }

        Auth::guard('member')->logout();
        $request->session()->forget('ic_sesi_versi');
        $request->session()->regenerateToken();

        return redirect()->route('ic.masuk')->with('ic_toast', 'Akunmu sudah dihapus. Terima kasih sudah bergabung.');
    }
}