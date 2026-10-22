<?php

namespace App\Providers;

use App\View\Composers\FooterProfileComposer;
use App\View\Composers\ThemeColorComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        // Check-in Inner Circle dibatasi per ANGGOTA, bukan per IP: penonton satu venue sering berbagi IP.
        RateLimiter::for('ic-checkin', function (Request $request) {
            $id = Auth::guard('member')->id();

            return Limit::perMinute((int) config('inner_circle.batas_checkin_per_menit', 8))
                ->by($id ? 'member:' . $id : 'ip:' . $request->ip())
                ->response(fn() => redirect()
                    ->route('ic.checkin')
                    ->withErrors(['kode' => 'Terlalu banyak percobaan. Tunggu sebentar lalu coba lagi.']));
        });
    }
}