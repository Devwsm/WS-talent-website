<?php

use App\Http\Controllers\dashboardController;
use App\Http\Controllers\homeController;
use App\Http\Controllers\loginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('/')->group(function(){
    Route::get('/', [homeController::class, 'index'])->name('home');
});

Route::prefix('/')->group(function(){
    Route::get('/login', [loginController::class, 'login'])->name('login');
    Route::post('/login', [loginController::class, 'prosesLogin'])->name('login.proses')->middleware('throttle:5,1');
    Route::get('/logout', [loginController::class, 'logout'])->name('logout');
});

Route::prefix('/dashboard')->middleware('cekLogin')->group(function(){
    Route::get('/', [dashboardController::class, 'dashboard'])->name('dashboard');
    
    Route::get('/albums', [dashboardController::class, 'albums'])->name('albums');
    Route::post('/tambahAlbums', [dashboardController::class, 'tambahAlbums'])->name('albums.tambah')->middleware('throttle:10,1');
    Route::delete('/hapusAlbums/{id}', [dashboardController::class, 'hapusAlbums'])->name('albums.hapus');
    Route::put('/updateAlbums/{id}', [dashboardController::class, 'updateAlbums'])->name('albums.update');
    
    Route::get('/schedule', [dashboardController::class, 'schedule'])->name('schedule');
    Route::post('/tambahSchedule', [dashboardController::class, 'tambahSchedule'])->name('schedule.tambah')->middleware('throttle:10,1');
    Route::delete('/hapusSchedule/{id}', [dashboardController::class, 'hapusSchedule'])->name('schedule.hapus');
    
    Route::get('/news', [dashboardController::class, 'news'])->name('news');
    Route::post('/tambahnews', [dashboardController::class, 'tambahnews'])->name('news.tambah')->middleware('throttle:10,1');
    Route::delete('/hapusnews/{id}', [dashboardController::class, 'hapusnews'])->name('news.hapus');
    Route::put('/updatenews/{id}', [dashboardController::class, 'updatenews'])->name('news.update');

    Route::get('/merchandise', [dashboardController::class, 'merchandise'])->name('merchandise');
    Route::post('/tambahMerchandise', [dashboardController::class, 'tambahMerchandise'])->name('merchandise.tambah')->middleware('throttle:10,1');
    Route::delete('/hapusMerchandise/{id}', [dashboardController::class, 'hapusMerchandise'])->name('merchandise.hapus');
    Route::put('/updateMerchandise/{id}', [dashboardController::class, 'updateMerchandise'])->name('merchandise.update');
});