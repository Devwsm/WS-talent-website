<?php

namespace App\Http\Controllers\InnerCircle;

use App\Http\Controllers\Controller;
use App\Http\Requests\InnerCircle\ubahPengaduanRequest;
use App\Models\ic_pengaduan;
use App\Services\InnerCircle\EmailService;
use App\Services\InnerCircle\PengaduanGagal;
use App\Services\InnerCircle\PengaduanService;
use Illuminate\Http\RedirectResponse;

/** Tindak lanjut pengaduan oleh admin (CMS Inner Circle). Daftarnya tampil di detail anggota dan pintasan Ringkasan. */
class pengaduanAdminController extends Controller
{
    public function proses(int $id, ubahPengaduanRequest $request, PengaduanService $pengaduan, EmailService $email): RedirectResponse
    {
        $baris = ic_pengaduan::query()->with('member')->findOrFail($id);

        try {
            [$hasil, $berubah] = $pengaduan->proses(
                $baris,
                $request->validated('status'),
                $request->validated('dari'),
                $request->validated('catatan'),
                session('user'),
            );
        } catch (PengaduanGagal $e) {
            return back()->with('error', $e->getMessage());
        }

        // Anggota dikabari hanya saat statusnya berubah; mengubah catatan saja tidak mengirim email.
        if ($berubah) {
            $email->kirimStatusPengaduan($hasil);
        }

        return back()->with('success', 'Pengaduan ' . ($baris->member?->nama ?? 'anggota') . ' sekarang berstatus ' . $hasil->labelStatus() . '.');
    }
}