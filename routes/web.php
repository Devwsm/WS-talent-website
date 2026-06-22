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
    Route::post('/login', [loginController::class, 'prosesLogin'])->name('login.proses');
    Route::get('/logout', [loginController::class, 'logout'])->name('logout');
});

Route::prefix('/dashboard')->middleware('cekLogin')->group(function(){
    Route::get('/', [dashboardController::class, 'dashboard'])->name('dashboard');
    
    Route::get('/schedule', [dashboardController::class, 'schedule'])->name('schedule');
    Route::post('/tambahSchedule', [dashboardController::class, 'tambahSchedule'])->name('schedule.tambah');
    Route::delete('/hapusSchedule/{id}', [dashboardController::class, 'hapusSchedule'])->name('schedule.hapus');
    
    Route::get('/albums', [dashboardController::class, 'albums'])->name('albums');
    Route::post('/tambahAlbums', [dashboardController::class, 'tambahAlbums'])->name('albums.tambah');
    Route::delete('/hapusAlbums/{id}', [dashboardController::class, 'hapusAlbums'])->name('albums.hapus');
    Route::put('/updateAlbums/{id}', [dashboardController::class, 'updateAlbums'])->name('albums.update');
    
    
    Route::get('/merchandise', [dashboardController::class, 'merchandise'])->name('merchandise');
    Route::post('/tambahMerchandise', [dashboardController::class, 'tambahMerchandise'])->name('merchandise.tambah');
    Route::delete('/hapusMerchandise/{id}', [dashboardController::class, 'hapusMerchandise'])->name('merchandise.hapus');
    Route::put('/updateMerchandise/{id}', [dashboardController::class, 'updateMerchandise'])->name('merchandise.update');
});