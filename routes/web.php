<?php

use App\Http\Controllers\dashboardController;
use App\Http\Controllers\homeController;
use App\Http\Controllers\loginController;
use App\Http\Controllers\profileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('/')->group(function () {
    Route::get('/', [homeController::class, 'index'])->name('home');
    Route::get('/profile', [homeController::class, 'profile'])->name('profile');
});

Route::prefix('/')->group(function () {
    Route::get('/login', [loginController::class, 'login'])->name('login');
    Route::post('/login', [loginController::class, 'prosesLogin'])->name('login.proses')->middleware('throttle:5,1');
    Route::get('/logout', [loginController::class, 'logout'])->name('logout');
});

Route::prefix('/dashboard')->middleware('cekLogin')->group(function () {
    Route::get('/', [dashboardController::class, 'dashboard'])->name('dashboard');

    Route::prefix('/profile')->group(function () {
        Route::get('/', [profileController::class, 'profile'])->name('dashboard.profile');
        
        Route::post('/tambahStatistik', [profileController::class, 'tambahStatistik'])->name('statistik.tambah')->middleware('throttle:10,1');
        Route::delete('/hapusStatistik/{id}', [profileController::class, 'hapusStatistik'])->name('statistik.hapus');
        Route::put('/updateStatistik/{id}', [profileController::class, 'updateStatistik'])->name('statistik.update');
    });

    Route::prefix('/banner')->group(function () {
        Route::get('/', [dashboardController::class, 'banner'])->name('banner');
        Route::post('/tambahBanner', [dashboardController::class, 'tambahBanner'])->name('banner.tambah')->middleware('throttle:10,1');
        Route::delete('/hapusBanner/{id}', [dashboardController::class, 'hapusBanner'])->name('banner.hapus');
    });

    Route::prefix('/headers')->group(function () {
        Route::get('/', [dashboardController::class, 'headers'])->name('headers');
        Route::post('/tambahHeaders', [dashboardController::class, 'tambahHeaders'])->name('headers.tambah')->middleware('throttle:10,1');
        Route::delete('/hapusHeaders/{id}', [dashboardController::class, 'hapusHeaders'])->name('headers.hapus');
        Route::put('/updateHeaders/{id}', [dashboardController::class, 'updateHeaders'])->name('headers.update');
    });

    Route::prefix('/albums')->group(function () {
        Route::get('/', [dashboardController::class, 'albums'])->name('albums');
        Route::post('/tambahAlbums', [dashboardController::class, 'tambahAlbums'])->name('albums.tambah')->middleware('throttle:10,1');
        Route::delete('/hapusAlbums/{id}', [dashboardController::class, 'hapusAlbums'])->name('albums.hapus');
        Route::put('/updateAlbums/{id}', [dashboardController::class, 'updateAlbums'])->name('albums.update');
    });

    Route::prefix('/schedule')->group(function () {
        Route::get('/', [dashboardController::class, 'schedule'])->name('schedule');
        Route::post('/tambahSchedule', [dashboardController::class, 'tambahSchedule'])->name('schedule.tambah')->middleware('throttle:10,1');
        Route::delete('/hapusSchedule/{id}', [dashboardController::class, 'hapusSchedule'])->name('schedule.hapus');
    });

    Route::prefix('/news')->group(function () {
        Route::get('/', [dashboardController::class, 'news'])->name('news');
        Route::post('/tambahnews', [dashboardController::class, 'tambahnews'])->name('news.tambah')->middleware('throttle:10,1');
        Route::delete('/hapusnews/{id}', [dashboardController::class, 'hapusnews'])->name('news.hapus');
        Route::put('/updatenews/{id}', [dashboardController::class, 'updatenews'])->name('news.update');
    });

    Route::prefix('/merchandise')->group(function () {
        Route::get('/', [dashboardController::class, 'merchandise'])->name('merchandise');
        Route::post('/tambahMerchandise', [dashboardController::class, 'tambahMerchandise'])->name('merchandise.tambah')->middleware('throttle:10,1');
        Route::delete('/hapusMerchandise/{id}', [dashboardController::class, 'hapusMerchandise'])->name('merchandise.hapus');
        Route::put('/updateMerchandise/{id}', [dashboardController::class, 'updateMerchandise'])->name('merchandise.update');
    });
});