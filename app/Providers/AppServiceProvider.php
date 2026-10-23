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
        View::composer(
            ['template.layout', 'components.navbar', 'components.footer'],
            ThemeColorComposer::class
        );

        View::composer('components.footer', FooterProfileComposer::class);

        // Nama model huruf kecil (ic_memory) tidak ditemukan penemuan otomatis policy, jadi didaftarkan manual.
        Gate::policy(ic_memory::class, MemoryPolicy::class);

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