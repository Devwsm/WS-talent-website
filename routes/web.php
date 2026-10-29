<?php

use App\Http\Controllers\dashboardController;
use App\Http\Controllers\homeController;
use App\Http\Controllers\InnerCircle\adminController;
use App\Http\Controllers\InnerCircle\akunController;
use App\Http\Controllers\InnerCircle\emailAdminController;
use App\Http\Controllers\InnerCircle\authController;
use App\Http\Controllers\InnerCircle\fotoProfilController;
use App\Http\Controllers\InnerCircle\googleController;
use App\Http\Controllers\InnerCircle\kenanganController;
use App\Http\Controllers\InnerCircle\lupaSandiController;
use App\Http\Controllers\InnerCircle\memberController;
use App\Http\Controllers\InnerCircle\tentangController;
use App\Http\Controllers\logController;
use App\Http\Controllers\loginController;
use App\Http\Controllers\profileController;
use App\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

Route::prefix('/')->group(function () {
    Route::get('/', [homeController::class, 'index'])->name('home');
    Route::get('/profile', [homeController::class, 'profile'])->name('profile');
    // Kebijakan privasi: publik dan selalu aktif (tidak ikut flag Inner Circle), dipakai juga oleh layar izin Google.
    Route::view('/privasi', 'pages.privasi')->name('privasi');
});

// SEO — robots.txt & sitemap.xml digenerate dinamis (lihat SeoController)
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');

Route::prefix('/')->group(function () {
    Route::get('/login', [loginController::class, 'login'])->name('login');
    Route::post('/login', [loginController::class, 'prosesLogin'])->name('login.proses')->middleware('throttle:5,1');
    // 2FA email (ADMIN_2FA_AKTIF): form kode, verifikasi, dan kirim ulang. Hanya bisa dibuka setelah sandi benar.
    Route::get('/login/otp', [loginController::class, 'formOtp'])->name('login.otp');
    Route::post('/login/otp', [loginController::class, 'prosesOtp'])->name('login.otp.proses')->middleware('throttle:admin-otp');
    Route::post('/login/otp/kirim-ulang', [loginController::class, 'kirimUlangOtp'])->name('login.otp.ulang')->middleware('throttle:admin-otp-ulang');
    // Logout wajib POST (+ CSRF) supaya nggak bisa dipicu dari link/gambar situs lain.
    Route::post('/logout', [loginController::class, 'logout'])->name('logout');
    // Tombol/link lama (GET) nggak error 405 — cukup dialihkan.
    Route::get('/logout', fn() => redirect()->route('dashboard'));
});

// WS Inner Circle — dibungkus flag INNER_CIRCLE_AKTIF (404 bila mati, lihat middleware IcAktif).
// Fase 4-6: akun anggota (guard `member`), check-in, stempel, hadiah, klaim, Komunitas (pengumuman, kenangan),
// dan foto profil sudah tersambung ke database.
Route::prefix('/inner-circle')->middleware('icAktif')->name('ic.')->group(function () {
    // Halaman publik yang menjelaskan program untuk calon anggota (tamu yang membuka /inner-circle dialihkan ke sini).
    Route::get('/tentang', [tentangController::class, 'index'])->name('tentang');

    Route::get('/masuk', [authController::class, 'masuk'])->name('masuk');
    Route::post('/masuk', [authController::class, 'prosesMasuk'])->name('masuk.proses')->middleware('throttle:ic-masuk');
    Route::get('/daftar', [authController::class, 'daftar'])->name('daftar');
    Route::post('/daftar', [authController::class, 'prosesDaftar'])->name('daftar.proses')->middleware('throttle:ic-daftar');

    // Lupa kata sandi (Fase 9): jawaban selalu sama untuk semua email, lihat lupaSandiController.
    Route::get('/lupa-sandi', [lupaSandiController::class, 'form'])->name('sandi.lupa');
    Route::post('/lupa-sandi', [lupaSandiController::class, 'kirim'])->name('sandi.kirim')->middleware('throttle:ic-lupa');
    Route::get('/atur-ulang/{token}', [lupaSandiController::class, 'formAtur'])->whereAlphaNumeric('token')->name('sandi.atur');
    Route::post('/atur-ulang', [lupaSandiController::class, 'proses'])->name('sandi.atur.proses')->middleware('throttle:ic-atur');

    // Masuk/daftar dengan Google (Fase 8): 404 bila belum diaktifkan (flag + kredensial), lihat googleController.
    Route::get('/google/masuk', [googleController::class, 'mulai'])->name('google.masuk')->middleware('throttle:ic-google');
    Route::get('/google/callback', [googleController::class, 'callback'])->name('google.callback')->middleware('throttle:ic-google');

    Route::middleware(['auth:member', 'icSesi'])->group(function () {
        Route::post('/keluar', [authController::class, 'keluar'])->name('keluar');

        Route::get('/', [memberController::class, 'beranda'])->name('beranda');
        Route::get('/hadiah', [memberController::class, 'hadiah'])->name('hadiah');
        Route::post('/hadiah/{target}/klaim', [memberController::class, 'ajukanKlaim'])->whereNumber('target')->name('hadiah.klaim')->middleware('throttle:ic-klaim');
        Route::get('/check-in', [memberController::class, 'checkin'])->name('checkin');
        Route::post('/check-in', [memberController::class, 'prosesCheckin'])->name('checkin.proses')->middleware('throttle:ic-checkin');
        Route::get('/komunitas', [memberController::class, 'komunitas'])->name('komunitas');
        Route::post('/kenangan', [kenanganController::class, 'simpan'])->name('kenangan.simpan')->middleware('throttle:ic-kenangan');
        Route::delete('/kenangan/{id}', [kenanganController::class, 'hapus'])->whereNumber('id')->name('kenangan.hapus')->middleware('throttle:30,1');
        Route::post('/kenangan/{id}/suka', [kenanganController::class, 'suka'])->whereNumber('id')->name('kenangan.suka')->middleware('throttle:ic-suka');
        Route::get('/profil', [memberController::class, 'profil'])->name('profil');
        Route::post('/profil/foto', [fotoProfilController::class, 'simpan'])->name('profil.foto')->middleware('throttle:ic-foto');
        Route::delete('/profil/foto', [fotoProfilController::class, 'hapus'])->name('profil.foto.hapus')->middleware('throttle:ic-foto');
        Route::delete('/profil/akun', [akunController::class, 'hapus'])->name('akun.hapus')->middleware('throttle:ic-akun');
    });
});

Route::prefix('/dashboard')->middleware(['cekLogin', 'catatAktivitas'])->group(function () {
    Route::get('/', [dashboardController::class, 'dashboard'])->name('dashboard');

    // Log: error aplikasi dan jejak aktivitas admin (tersedia terlepas dari flag Inner Circle).
    Route::prefix('/log')->name('log.')->group(function () {
        Route::get('/', [logController::class, 'index'])->name('index');
        Route::patch('/error/{id}', [logController::class, 'ubahError'])->whereNumber('id')->name('error.ubah')->middleware('throttle:admin-aksi-kecil');
        Route::delete('/error', [logController::class, 'bersihkanSelesai'])->name('error.bersihkan')->middleware('throttle:admin-aksi-kecil');
    });

    // WS Inner Circle — panel admin di dashboard CMS (7 tab), ikut flag INNER_CIRCLE_AKTIF (404 bila mati).
    // Fase 4-6: semua tab sudah memakai database.
    Route::prefix('/inner-circle')->middleware('icAktif')->name('ic.admin.')->group(function () {
        Route::get('/', [adminController::class, 'ringkasan'])->name('ringkasan');
        Route::get('/anggota', [adminController::class, 'anggota'])->name('anggota');
        Route::post('/anggota/{id}/stempel', [adminController::class, 'tambahStempel'])->whereNumber('id')->name('anggota.stempel')->middleware('throttle:10,1');

        Route::get('/acara', [adminController::class, 'acara'])->name('acara');
        Route::post('/acara', [adminController::class, 'simpanAcara'])->name('acara.simpan')->middleware('throttle:10,1');
        Route::post('/acara/impor', [adminController::class, 'imporAcara'])->name('acara.impor')->middleware('throttle:10,1');
        // Fase 9: jadwal dari Bandsintown, dan perbaikan token QR yang tidak terbaca (APP_KEY berbeda).
        Route::post('/acara/sinkron-bandsintown', [adminController::class, 'sinkronBandsintown'])->name('acara.bandsintown')->middleware('throttle:3,1');
        Route::post('/acara/perbaiki-token', [adminController::class, 'perbaikiToken'])->name('acara.perbaiki')->middleware('throttle:5,1');
        Route::put('/acara/{kode}', [adminController::class, 'ubahAcara'])->name('acara.ubah')->middleware('throttle:10,1');
        Route::delete('/acara/{kode}', [adminController::class, 'hapusAcara'])->name('acara.hapus')->middleware('throttle:10,1');
        Route::post('/acara/{kode}/token', [adminController::class, 'tokenBaru'])->name('acara.token')->middleware('throttle:10,1');
        Route::get('/acara/{kode}/qr', [adminController::class, 'unduhQr'])->name('acara.qr');
        Route::get('/acara/{kode}/cetak', [adminController::class, 'cetakQr'])->name('acara.cetak');
        Route::get('/klaim', [adminController::class, 'klaim'])->name('klaim');
        Route::patch('/klaim/{id}', [adminController::class, 'ubahKlaim'])->whereNumber('id')->name('klaim.proses')->middleware('throttle:30,1');
        Route::get('/hadiah', [adminController::class, 'hadiah'])->name('hadiah');
        Route::put('/hadiah/{target}', [adminController::class, 'ubahHadiah'])->whereNumber('target')->name('hadiah.ubah')->middleware('throttle:10,1');
        Route::patch('/hadiah/{target}/aktif', [adminController::class, 'aktifkanHadiah'])->whereNumber('target')->name('hadiah.aktif')->middleware('throttle:admin-aksi-kecil');
        Route::get('/pengumuman', [adminController::class, 'pengumuman'])->name('pengumuman');
        Route::post('/pengumuman', [adminController::class, 'simpanPengumuman'])->name('pengumuman.simpan')->middleware('throttle:10,1');
        Route::put('/pengumuman/{id}', [adminController::class, 'ubahPengumuman'])->whereNumber('id')->name('pengumuman.ubah')->middleware('throttle:10,1');
        Route::patch('/pengumuman/{id}/aktif', [adminController::class, 'aktifkanPengumuman'])->whereNumber('id')->name('pengumuman.aktif')->middleware('throttle:admin-aksi-kecil');
        Route::delete('/pengumuman/{id}', [adminController::class, 'hapusPengumuman'])->whereNumber('id')->name('pengumuman.hapus')->middleware('throttle:10,1');
        Route::get('/kenangan', [adminController::class, 'kenangan'])->name('kenangan');
        Route::patch('/kenangan/{id}', [adminController::class, 'moderasiKenangan'])->whereNumber('id')->name('kenangan.moderasi')->middleware('throttle:30,1');

        // Fase 9: format email notifikasi, pratinjau, dan email tes.
        Route::get('/email', [emailAdminController::class, 'index'])->name('email');
        Route::get('/email/pratinjau/{jenis}', [emailAdminController::class, 'pratinjau'])->where('jenis', '[a-z-]+')->name('email.pratinjau');
        Route::post('/email/tes', [emailAdminController::class, 'tes'])->name('email.tes')->middleware('throttle:5,1');
    });

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