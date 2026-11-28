<?php

namespace App\Http\Controllers\InnerCircle;

use App\Http\Controllers\Controller;
use App\Http\Requests\InnerCircle\gameSelesaiRequest;
use App\Models\member;
use App\Services\InnerCircle\GameGagal;
use App\Services\InnerCircle\GameService;
use App\Services\InnerCircle\IcGame;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Mini game Mambo Jambo Pineapple Catcher untuk anggota. Halaman (`index`) dan dua aksi JSON (`mulai`, `selesai`).
 * Semua aturannya ada di GameService; controller hanya menerjemahkan hasilnya ke JSON.
 */
class gameController extends Controller
{
    public function index(GameService $game)
    {
        $this->pastikanAktif();
        $anggota = $this->anggota();

        return view('pages.inner-circle.game', [
            'member' => $anggota,
            'ringkasan' => $game->ringkasan($anggota),
            'menitRonde' => app(IcGame::class)->menitRonde(),
            'bisaMain' => $anggota->email_verified_at !== null && ! $anggota->diblokir(),
        ]);
    }

    public function mulai(GameService $game): JsonResponse
    {
        $this->pastikanAktif();

        try {
            $ronde = $game->mulai($this->anggota());
        } catch (GameGagal $e) {
            return response()->json(['pesan' => $e->getMessage()], $e->status);
        }

        return response()->json(['kode' => $ronde->kode, 'seed' => $ronde->seed])->header('Cache-Control', 'no-store');
    }

    public function selesai(gameSelesaiRequest $request, GameService $game): JsonResponse
    {
        $this->pastikanAktif();

        try {
            $hasil = $game->selesai(
                $this->anggota(),
                $request->validated('kode'),
                (int) $request->validated('langkah'),
                (string) ($request->validated('log') ?? ''),
                $request->validated('skor') !== null ? (int) $request->validated('skor') : null,
            );
        } catch (GameGagal $e) {
            return response()->json(['pesan' => $e->getMessage()], $e->status);
        }

        return response()->json($hasil)->header('Cache-Control', 'no-store');
    }

    /** Game bisa dimatikan dari CMS (Konten > Aturan game) tanpa deploy. */
    private function pastikanAktif(): void
    {
        abort_unless(app(IcGame::class)->aktif(), 404);
    }

    private function anggota(): member
    {
        return Auth::guard('member')->user();
    }
}