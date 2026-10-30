<?php

namespace App\Providers;

use App\Models\ic_memory;
use App\Policies\MemoryPolicy;
use App\View\Composers\FooterProfileComposer;
use App\View\Composers\ThemeColorComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($proxies = config('app.trusted_proxies')) {
            TrustProxies::at($proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }

        View::composer(
            ['template.layout', 'components.navbar', 'components.footer'],
            ThemeColorComposer::class
        );

        View::composer('components.footer', FooterProfileComposer::class);

        // Nama model huruf kecil (ic_memory) tidak ditemukan penemuan otomatis policy, jadi didaftarkan manual.
        Gate::policy(ic_memory::class, MemoryPolicy::class);

        // Masuk anggota: dibatasi per EMAIL+IP (tebak sandi satu akun) dan per IP yang longgar, karena penonton satu
        // venue sering berbagi IP (Wi-Fi/CGNAT) dan tidak boleh saling mengunci.
        RateLimiter::for('ic-masuk', function (Request $request) {
            $email = mb_strtolower(trim((string) $request->input('email')));
            $galat = fn() => back()
                ->withErrors(['email' => 'Terlalu banyak percobaan masuk. Tunggu semenit lalu coba lagi.'])
                ->onlyInput('email');

            return [
                Limit::perMinute(5)->by('masuk:' . sha1($email . '|' . $request->ip()))->response($galat),
                Limit::perMinute(60)->by('masuk-ip:' . $request->ip())->response($galat),
            ];
        });

        // Login admin CMS: dibatasi per USERNAME+IP (menebak sandi satu akun) dan per IP (mencoba banyak username).
        // Dipisah dari `throttle:N,M` bawaan, yang berbagi satu penghitung per IP dengan semua rute lain.
        RateLimiter::for('admin-login', function (Request $request) {
            $username = mb_strtolower(trim((string) $request->input('username')));
            $galat = fn() => back()
                ->withErrors(['login' => 'Terlalu banyak percobaan masuk. Tunggu semenit lalu coba lagi.'])
                ->onlyInput('username');

            return [
                Limit::perMinute(5)->by('admin-login:' . sha1($username . '|' . $request->ip()))->response($galat),
                Limit::perMinute(10)->by('admin-login-ip:' . $request->ip())->response($galat),
            ];
        });

        // Kirim ulang email verifikasi per ANGGOTA, per menit dan per hari. Kunci kedua batas sengaja berbeda: dua batas
        // dengan kunci sama dalam satu pembatas berbagi satu penghitung, sehingga batas hariannya tidak pernah berlaku.
        RateLimiter::for('ic-verifikasi', function (Request $request) {
            $id = Auth::guard('member')->id();
            $kunci = $id ? 'member:' . $id : 'ip:' . $request->ip();
            $galat = fn() => redirect()
                ->route('ic.hadiah')
                ->with('ic_toast_galat', 'Terlalu banyak permintaan email verifikasi. Tunggu sebentar lalu coba lagi.');

            return [
                Limit::perMinute((int) config('inner_circle.batas_verifikasi_per_menit', 2))->by('menit:' . $kunci)->response($galat),
                Limit::perDay((int) config('inner_circle.batas_verifikasi_per_hari', 10))->by('hari:' . $kunci)->response($galat),
            ];
        });

        // Hapus akun per ANGGOTA: pemeriksaan kata sandi di sini tidak boleh dipakai untuk menebak sandi.
        RateLimiter::for('ic-akun', fn(Request $request) => Limit::perMinute(5)->by('akun:' . (Auth::guard('member')->id() ?? $request->ip())));

        // Login admin dengan kode OTP email. Dipisah dari throttle bawaan (yang berbagi satu penghitung per IP untuk semua
        // rute) supaya memasukkan kode, mengirim ulang, dan login tidak saling menghabiskan jatah.
        RateLimiter::for('admin-otp', fn(Request $request) => Limit::perMinute(10)->by('admin-otp:' . $request->ip()));
        RateLimiter::for('admin-otp-ulang', fn(Request $request) => Limit::perMinute(3)->by('admin-otp-ulang:' . $request->ip()));

        // Aksi kecil di dashboard (tampil/sembunyikan, tandai error selesai): jatah sendiri per IP.
        RateLimiter::for('admin-aksi-kecil', fn(Request $request) => Limit::perMinute(30)->by('admin-aksi-kecil:' . $request->ip()));

        // Lupa kata sandi: per EMAIL (cegah membanjiri kotak masuk orang lain) dan per IP. Hitungannya sama untuk email
        // terdaftar maupun tidak, jadi pembatasan tidak membocorkan apakah sebuah email punya akun.
        // Ditambah batas HARIAN GLOBAL (semua pengunjung digabung): penyerang yang berganti-ganti email dan IP tidak
        // lagi bisa menghabiskan kuota kirim email hosting atau merusak reputasi pengirim. Diletakkan paling akhir
        // supaya permintaan yang sudah ditolak batas per email/IP tidak ikut menghabiskan kuota global.
        RateLimiter::for('ic-lupa', function (Request $request) {
            $email = mb_strtolower(trim((string) $request->input('email')));
            $galat = fn() => back()
                ->withErrors(['email' => 'Terlalu banyak permintaan. Tunggu beberapa saat lalu coba lagi.'])
                ->onlyInput('email');
            $galatHarian = fn() => back()
                ->withErrors(['email' => 'Layanan atur ulang kata sandi sedang padat hari ini. Coba lagi besok, atau hubungi admin.'])
                ->onlyInput('email');

            return [
                Limit::perMinute(3)->by('lupa:' . sha1($email . '|' . $request->ip()))->response($galat),
                Limit::perHour(5)->by('lupa-email:' . sha1($email))->response($galat),
                Limit::perMinute(30)->by('lupa-ip:' . $request->ip())->response($galat),
                Limit::perDay(100)->by('lupa-global')->response($galatHarian),
            ];
        });

        // Menyimpan kata sandi baru dari tautan email, per IP.
        RateLimiter::for('ic-atur', fn(Request $request) => Limit::perMinute(10)->by('atur:' . $request->ip()));

        // Alur Google (mulai + callback) per IP, longgar karena penonton satu venue berbagi IP.
        RateLimiter::for('ic-google', fn(Request $request) => Limit::perMinute(60)->by('google:' . $request->ip()));

        // Daftar anggota per IP, longgar untuk keramaian satu venue tapi tetap menahan skrip pembuat akun massal.
        RateLimiter::for('ic-daftar', function (Request $request) {
            return Limit::perMinute(30)->by('daftar:' . $request->ip())->response(
                fn() => back()
                    ->withErrors(['email' => 'Terlalu banyak pendaftaran dari jaringan ini. Tunggu semenit lalu coba lagi.'])
                    ->withInput($request->except(['sandi', 'sandi_confirmation']))
            );
        });

        // Check-in Inner Circle dibatasi per ANGGOTA, bukan per IP: penonton satu venue sering berbagi IP.
        RateLimiter::for('ic-checkin', function (Request $request) {
            $id = Auth::guard('member')->id();

            return Limit::perMinute((int) config('inner_circle.batas_checkin_per_menit', 8))
                ->by($id ? 'member:' . $id : 'ip:' . $request->ip())
                ->response(fn() => redirect()
                    ->route('ic.checkin')
                    ->withErrors(['kode' => 'Terlalu banyak percobaan. Tunggu sebentar lalu coba lagi.']));
        });

        // Pengajuan hadiah juga per ANGGOTA: cukup longgar untuk salah klik, cukup ketat untuk skrip.
        RateLimiter::for('ic-klaim', function (Request $request) {
            $id = Auth::guard('member')->id();

            return Limit::perMinute((int) config('inner_circle.batas_klaim_per_menit', 6))
                ->by($id ? 'member:' . $id : 'ip:' . $request->ip())
                ->response(fn() => redirect()
                    ->route('ic.hadiah')
                    ->with('ic_toast_galat', 'Terlalu banyak percobaan. Tunggu sebentar lalu coba lagi.'));
        });

        // Unggah kenangan: per menit (salah klik, skrip) dan per hari (spam galeri). Per ANGGOTA, bukan per IP.
        RateLimiter::for('ic-kenangan', function (Request $request) {
            $id = Auth::guard('member')->id();
            $kunci = $id ? 'member:' . $id : 'ip:' . $request->ip();
            $galat = fn() => back()->with('ic_toast_galat', 'Terlalu banyak unggahan. Tunggu sebentar lalu coba lagi.');

            return [
                Limit::perMinute((int) config('inner_circle.batas_kenangan_per_menit', 4))->by($kunci)->response($galat),
                Limit::perDay((int) config('inner_circle.batas_kenangan_per_hari', 20))->by($kunci)
                    ->response(fn() => back()->with('ic_toast_galat', 'Batas unggahan hari ini sudah tercapai. Coba lagi besok.')),
            ];
        });

        // Suka dipakai lewat fetch (JSON): batas yang longgar, cukup untuk mencegah skrip. Lewat batas = 429 JSON.
        RateLimiter::for('ic-suka', function (Request $request) {
            $id = Auth::guard('member')->id();

            return Limit::perMinute((int) config('inner_circle.batas_suka_per_menit', 60))
                ->by($id ? 'member:' . $id : 'ip:' . $request->ip());
        });

        // Ganti atau hapus foto profil: pemrosesan gambar paling berat di area anggota.
        RateLimiter::for('ic-foto', function (Request $request) {
            $id = Auth::guard('member')->id();

            return Limit::perMinute((int) config('inner_circle.batas_foto_per_menit', 6))
                ->by($id ? 'member:' . $id : 'ip:' . $request->ip())
                ->response(fn() => redirect()
                    ->route('ic.profil')
                    ->with('ic_toast_galat', 'Terlalu banyak percobaan. Tunggu sebentar lalu coba lagi.'));
        });
    }
}