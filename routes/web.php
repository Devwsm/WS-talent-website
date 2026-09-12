<?php

use App\Http\Controllers\dashboardController;
use App\Http\Controllers\homeController;
use App\Http\Controllers\loginController;
use App\Http\Controllers\profileController;
use App\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

Route::prefix('/')->group(function () {
    Route::get('/', [homeController::class, 'index'])->name('home');
    Route::get('/profile', [homeController::class, 'profile'])->name('profile');
});

// SEO — robots.txt & sitemap.xml digenerate dinamis (lihat SeoController)
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');

Route::prefix('/')->group(function () {
    Route::get('/login', [loginController::class, 'login'])->name('login');
    Route::post('/login', [loginController::class, 'prosesLogin'])->name('login.proses')->middleware('throttle:5,1');
    Route::get('/logout', [loginController::class, 'logout'])->name('logout');
});

Route::prefix('/dashboard')->middleware('cekLogin')->group(function () {
    Route::get('/', [dashboardController::class, 'dashboard'])->name('dashboard');

    Route::prefix('/color_pages')->group(function () {
        Route::get('/', [dashboardController::class, 'colorPages'])->name('color_pages.index');
        Route::post('/', [dashboardController::class, 'updateColorPages'])->name('color_pages.update')->middleware('throttle:10,1');
    });

    Route::prefix('/profile')->group(function () {
        Route::get('/', [profileController::class, 'profile'])->name('dashboard.profile');

        Route::post('/tambahStatistik', [profileController::class, 'tambahStatistik'])->name('statistik.tambah')->middleware('throttle:10,1');
        Route::delete('/hapusStatistik/{id}', [profileController::class, 'hapusStatistik'])->name('statistik.hapus')->middleware('throttle:10,1');
        Route::put('/updateStatistik/{id}', [profileController::class, 'updateStatistik'])->name('statistik.update')->middleware('throttle:10,1');

        Route::post('/tambahHighlight', [profileController::class, 'tambahHighlight'])->name('highlight.tambah')->middleware('throttle:10,1');
        Route::delete('/hapusHighlight/{id}', [profileController::class, 'hapusHighlight'])->name('highlight.hapus')->middleware('throttle:10,1');
        Route::put('/updateHighlight/{id}', [profileController::class, 'updateHighlight'])->name('highlight.update')->middleware('throttle:10,1');

        // Fase 6 — Profile (backend). UI form kiri/preview kanan menyusul di langkah 2.
        Route::post('/hero', [profileController::class, 'simpanHero'])->name('hero.simpan')->middleware('throttle:10,1');

        Route::post('/bio', [profileController::class, 'simpanBio'])->name('bio.simpan')->middleware('throttle:10,1');

        Route::post('/tambahGenre', [profileController::class, 'tambahGenre'])->name('genre.tambah')->middleware('throttle:10,1');
        Route::delete('/hapusGenre/{id}', [profileController::class, 'hapusGenre'])->name('genre.hapus')->middleware('throttle:10,1');
        Route::put('/updateGenre/{id}', [profileController::class, 'updateGenre'])->name('genre.update')->middleware('throttle:10,1');

        Route::post('/tambahCollab', [profileController::class, 'tambahCollab'])->name('collab.tambah')->middleware('throttle:10,1');
        Route::delete('/hapusCollab/{id}', [profileController::class, 'hapusCollab'])->name('collab.hapus')->middleware('throttle:10,1');
        Route::put('/updateCollab/{id}', [profileController::class, 'updateCollab'])->name('collab.update')->middleware('throttle:10,1');

        Route::post('/tambahMediaCoverage', [profileController::class, 'tambahMediaCoverage'])->name('media_coverage.tambah')->middleware('throttle:10,1');
        Route::delete('/hapusMediaCoverage/{id}', [profileController::class, 'hapusMediaCoverage'])->name('media_coverage.hapus')->middleware('throttle:10,1');
        Route::put('/updateMediaCoverage/{id}', [profileController::class, 'updateMediaCoverage'])->name('media_coverage.update')->middleware('throttle:10,1');

        Route::post('/tambahBooking', [profileController::class, 'tambahBooking'])->name('booking.tambah')->middleware('throttle:10,1');
        Route::delete('/hapusBooking/{id}', [profileController::class, 'hapusBooking'])->name('booking.hapus')->middleware('throttle:10,1');
        Route::put('/updateBooking/{id}', [profileController::class, 'updateBooking'])->name('booking.update')->middleware('throttle:10,1');

        Route::post('/tambahMediaSosial', [profileController::class, 'tambahMediaSosial'])->name('media_sosial.tambah')->middleware('throttle:10,1');
        Route::delete('/hapusMediaSosial/{id}', [profileController::class, 'hapusMediaSosial'])->name('media_sosial.hapus')->middleware('throttle:10,1');
        Route::put('/updateMediaSosial/{id}', [profileController::class, 'updateMediaSosial'])->name('media_sosial.update')->middleware('throttle:10,1');
    });

    Route::prefix('/banner')->group(function () {
        Route::get('/', [dashboardController::class, 'banner'])->name('banner');
        Route::post('/tambahBanner', [dashboardController::class, 'tambahBanner'])->name('banner.tambah')->middleware('throttle:10,1');
        Route::delete('/hapusBanner/{id}', [dashboardController::class, 'hapusBanner'])->name('banner.hapus')->middleware('throttle:10,1');
        Route::put('/updateBanner/{id}', [dashboardController::class, 'updateBanner'])->name('banner.update')->middleware('throttle:10,1');
    });

    Route::prefix('/headers')->group(function () {
        Route::get('/', [dashboardController::class, 'headers'])->name('headers');
        Route::post('/tambahHeaders', [dashboardController::class, 'tambahHeaders'])->name('headers.tambah')->middleware('throttle:10,1');
        Route::delete('/hapusHeaders/{id}', [dashboardController::class, 'hapusHeaders'])->name('headers.hapus')->middleware('throttle:10,1');
        Route::put('/updateHeaders/{id}', [dashboardController::class, 'updateHeaders'])->name('headers.update')->middleware('throttle:10,1');
    });

    Route::prefix('/albums')->group(function () {
        Route::get('/', [dashboardController::class, 'albums'])->name('albums');
        Route::post('/tambahAlbums', [dashboardController::class, 'tambahAlbums'])->name('albums.tambah')->middleware('throttle:10,1');
        Route::delete('/hapusAlbums/{id}', [dashboardController::class, 'hapusAlbums'])->name('albums.hapus')->middleware('throttle:10,1');
        Route::put('/updateAlbums/{id}', [dashboardController::class, 'updateAlbums'])->name('albums.update')->middleware('throttle:10,1');
    });

    Route::prefix('/news')->group(function () {
        Route::get('/', [dashboardController::class, 'news'])->name('news');
        Route::post('/tambahnews', [dashboardController::class, 'tambahnews'])->name('news.tambah')->middleware('throttle:10,1');
        Route::delete('/hapusnews/{id}', [dashboardController::class, 'hapusnews'])->name('news.hapus')->middleware('throttle:10,1');
        Route::put('/updatenews/{id}', [dashboardController::class, 'updatenews'])->name('news.update')->middleware('throttle:10,1');
    });

    Route::prefix('/merchandise')->group(function () {
        Route::get('/', [dashboardController::class, 'merchandise'])->name('merchandise');
        Route::post('/tambahMerchandise', [dashboardController::class, 'tambahMerchandise'])->name('merchandise.tambah')->middleware('throttle:10,1');
        Route::delete('/hapusMerchandise/{id}', [dashboardController::class, 'hapusMerchandise'])->name('merchandise.hapus')->middleware('throttle:10,1');
        Route::put('/updateMerchandise/{id}', [dashboardController::class, 'updateMerchandise'])->name('merchandise.update')->middleware('throttle:10,1');
    });
});