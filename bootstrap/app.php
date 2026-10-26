<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
        // Header keamanan untuk semua halaman web (lihat App\Http\Middleware\SecurityHeaders).
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->alias([
            'cekLogin' => \App\Http\Middleware\CekLogin::class,
            'icAktif' => \App\Http\Middleware\IcAktif::class,
            'icSesi' => \App\Http\Middleware\IcSesiVersi::class,
        ]);

        // Pengunjung yang belum login: area anggota ke halaman Masuk Inner Circle,
        // selain itu tetap ke halaman login admin seperti sebelumnya.
        $middleware->redirectGuestsTo(
            fn(Request $request) => $request->is('inner-circle', 'inner-circle/*')
                ? route('ic.masuk')
                : route('login')
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();