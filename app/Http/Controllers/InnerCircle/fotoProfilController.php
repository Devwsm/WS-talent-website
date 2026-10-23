<?php

namespace App\Http\Controllers\InnerCircle;

use App\Http\Controllers\Controller;
use App\Http\Requests\InnerCircle\simpanFotoProfilRequest;
use App\Services\InnerCircle\AvatarService;
use App\Support\GambarGagal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/** Foto profil anggota (route di bawah `auth:member`). Halaman Profil sendiri ada di memberController. */
class fotoProfilController extends Controller
{
    public function simpan(simpanFotoProfilRequest $request, AvatarService $avatar): RedirectResponse
    {
        try {
            $avatar->simpan(Auth::guard('member')->user(), $request->file('foto'));
        } catch (GambarGagal $e) {
            return redirect()->route('ic.profil')->withErrors(['foto' => $e->getMessage()]);
        }

        return redirect()->route('ic.profil')->with('ic_toast', 'Foto profil diperbarui.');
    }

    public function hapus(AvatarService $avatar): RedirectResponse
    {
        $avatar->hapus(Auth::guard('member')->user());

        return redirect()->route('ic.profil')->with('ic_toast', 'Foto profil dihapus.');
    }
}