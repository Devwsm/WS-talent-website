<?php

namespace App\Http\Controllers;

use App\Models\account;
use App\Services\AdminOtpService;
use App\Services\AktivitasService;
use App\Services\LoginPembatas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class loginController extends Controller
{
    public function login(Request $request)
    {
        // Membuka halaman login membatalkan proses kode OTP yang belum selesai.
        $request->session()->forget('otp_akun');

        return view('pages.login');
    }

    public function prosesLogin(Request $request, AdminOtpService $otp, AktivitasService $aktivitas, LoginPembatas $pembatas)
    {
        // Perangkat yang sedang dikunci (terlalu banyak gagal) ditolak lebih dulu, bahkan sebelum sandinya diperiksa.
        if (($sisa = $pembatas->sisaDetik($request)) > 0) {
            return back()
                ->withErrors(['login' => 'Terlalu banyak percobaan masuk yang gagal. Coba lagi dalam ' . LoginPembatas::teksDurasi($sisa) . '.'])
                ->onlyInput('username');
        }

        $request->validate([
            'username' => 'required|string|max:255',
            'password' => 'required|string|min:6',
        ], [
            'username.required' => 'Username harus diisi.',
            'password.required' => 'Password harus diisi.',
            'password.min'      => 'Password minimal 6 karakter.',
        ]);

        $user = account::where('username', $request->username)->first();

        // Hash::check selalu dijalankan (pada hash palsu bila username tidak ada) supaya waktu respons
        // tidak membedakan "username salah" dari "sandi salah" — cegah penebakan username lewat selisih waktu.
        // (hash bcrypt cost 12 dari string acak yang dibuang, jadi tidak cocok dengan sandi apa pun)
        $hashPalsu = '$2y$12$BKZsD4zPLZE2LpybB3R/x.3Blu9aFV7LJN1R2YBPpp7mxvRVoUEZy';
        $sandiCocok = Hash::check($request->password, $user->password ?? $hashPalsu);

        if (!$user || !$sandiCocok) {
            // Username yang diketik TIDAK dicatat: kadang yang terketik di kolom itu justru kata sandinya.
            $aktivitas->catatAkses('login_gagal');

            // Percobaan ini membuat perangkat melewati ambang: kunci sementara (dicatat sekali di Log, bukan tiap penolakan).
            if ($kunci = $pembatas->catatGagal($request)) {
                $aktivitas->catatAkses('login_dikunci');

                return back()
                    ->withErrors(['login' => 'Terlalu banyak percobaan masuk yang gagal. Coba lagi dalam ' . LoginPembatas::teksDurasi($kunci) . '.'])
                    ->onlyInput('username');
            }

            // Pesan sengaja dibuat generik — tidak bocorkan mana yang salah
            return back()->withErrors(['login' => 'Username atau password salah.'])->onlyInput('username');
        }

        // Sandi benar: hitungan gagal perangkat ini dibersihkan (hitungan IP tetap).
        $pembatas->bersihkan($request);

        // 2FA email (opsional, ADMIN_2FA_AKTIF). Sandi benar belum cukup: kode dikirim dulu ke email admin.
        if ($otp->aktif()) {
            if (blank($user->email)) {
                // Salah konfigurasi (email admin belum diisi) tidak boleh mengunci pemilik dari dashboard-nya sendiri.
                $aktivitas->catatAkses('tanpa_2fa', $user->username);

                return $this->selesaikanLogin($request, $user);
            }

            return $this->mulaiOtp($request, $user, $otp);
        }

        return $this->selesaikanLogin($request, $user);
    }

    // ---------- 2FA: kode OTP lewat email ----------

    public function formOtp(Request $request, AdminOtpService $otp)
    {
        $akun = $this->akunMenunggu($request);
        if (!$akun) {
            return redirect()->route('login');
        }

        return view('pages.login-otp', ['emailSamar' => $otp->samarkan((string) $akun->email)]);
    }

    public function prosesOtp(Request $request, AdminOtpService $otp, AktivitasService $aktivitas): RedirectResponse
    {
        $akun = $this->akunMenunggu($request);
        if (!$akun) {
            return redirect()->route('login');
        }

        $request->merge(['kode' => preg_replace('/\D+/', '', (string) $request->input('kode'))]);
        $request->validate(['kode' => ['required', 'digits:6']], [
            'kode.required' => 'Kode harus diisi.',
            'kode.digits' => 'Kode terdiri dari 6 angka.',
        ]);

        switch ($otp->periksa($akun, $request->input('kode'))) {
            case 'ok':
                return $this->selesaikanLogin($request, $akun);

            case 'habis':
                $request->session()->forget('otp_akun');
                $aktivitas->catatAkses('otp_habis', $akun->username);

                return redirect()->route('login')
                    ->withErrors(['login' => 'Terlalu banyak kode yang salah. Masuk ulang untuk meminta kode baru.']);

            case 'kedaluwarsa':
                return back()->withErrors(['kode' => 'Kode sudah kedaluwarsa. Tekan "Kirim ulang kode".']);

            default:
                $aktivitas->catatAkses('otp_salah', $akun->username);

                return back()->withErrors(['kode' => 'Kode salah. Periksa lagi kode di email.']);
        }
    }

    public function kirimUlangOtp(Request $request, AdminOtpService $otp): RedirectResponse
    {
        $akun = $this->akunMenunggu($request);
        if (!$akun) {
            return redirect()->route('login');
        }

        return $this->kirimKode($akun, $otp)
            ?? redirect()->route('login.otp')->with('success', 'Kode baru sudah dikirim.');
    }

    public function logout(Request $request)
    {
        // Logout tidak dicatat: Log hanya berisi perubahan konten dan login gagal.

        // Invalidate seluruh session + regenerate token — bukan hanya forget satu key
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    // ---------- internal ----------

    private function mulaiOtp(Request $request, account $user, AdminOtpService $otp): RedirectResponse
    {
        // Sesi baru untuk proses OTP (cegah session fixation); belum ada `login` sampai kodenya benar.
        $request->session()->regenerate();
        $request->session()->put('otp_akun', $user->getKey());

        $galat = $this->kirimKode($user, $otp);

        // Pengiriman gagal: kembali ke form login (bukan form OTP yang tidak akan pernah menerima kode).
        if ($galat) {
            $request->session()->forget('otp_akun');

            return $galat;
        }

        return redirect()->route('login.otp')
            ->with('success', 'Kode masuk sudah dikirim ke ' . $otp->samarkan((string) $user->email) . '.');
    }

    /** Kirim kode OTP. Mengembalikan null bila terkirim, atau respons galat siap tampil bila gagal. */
    private function kirimKode(account $akun, AdminOtpService $otp): ?RedirectResponse
    {
        try {
            $hasil = $otp->kirim($akun);
        } catch (\Throwable $e) {
            // Sudah masuk tab Error lewat report(); tidak dicatat lagi di Aktivitas supaya tidak ganda.
            report($e);

            return redirect()->route('login')->withErrors([
                'login' => 'Kode belum bisa dikirim ke email. Coba lagi sebentar, atau hubungi pengelola website.',
            ]);
        }

        if ($hasil === 'terlalu_sering') {
            return back()->withErrors(['kode' => 'Kode sudah terlalu sering diminta. Tunggu beberapa menit lalu coba lagi.']);
        }

        return null;
    }

    private function selesaikanLogin(Request $request, account $user): RedirectResponse
    {
        // Regenerate session ID — cegah session fixation attack
        $request->session()->regenerate();

        $request->session()->forget('otp_akun');
        $request->session()->put('login', true);
        $request->session()->put('user', $user->username);
        // Dipakai CekLogin untuk mencabut sesi ini bila akun dihapus atau sandinya diganti.
        $request->session()->put('admin_id', $user->getKey());
        $request->session()->put('admin_sidik', $user->sidikSesi());

        // Login berhasil tidak dicatat: Log hanya berisi perubahan konten dan login gagal.

        return redirect()->intended(route('dashboard'));
    }

    /** Akun yang sedang menunggu kode OTP (sandinya sudah benar di langkah sebelumnya), atau null. */
    private function akunMenunggu(Request $request): ?account
    {
        $id = $request->session()->get('otp_akun');

        return $id ? account::find($id) : null;
    }
}