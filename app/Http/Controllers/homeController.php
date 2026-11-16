<?php

namespace App\Http\Controllers;

use App\Models\albums;
use App\Models\banner;
use App\Models\bio;
use App\Models\booking;
use App\Models\collab;
use App\Models\genre;
use App\Models\header;
use App\Models\highlight;
use App\Models\media_coverage;
use App\Models\media_sosial;
use App\Models\merchandise;
use App\Models\news;
use App\Models\profile_hero;
use App\Models\statistik;
use App\Support\CachePublik;
use App\Support\HtmlSanitizer;

class homeController extends Controller
{
    //
    public function index()
    {
        // Seluruh data Home disimpan sementara (CachePublik) dan dibuang otomatis saat CMS menyimpan sesuatu.
        // Teks berita dan bio disaring SEKALI di sini (saat cache dibuat), jadi view tidak perlu menyaring ulang
        // di setiap kunjungan. View `components/news` hanya dipakai dari halaman ini.
        $polos = CachePublik::ingat('home', function () {
            // News ditampilin sebagai grid statis (bukan carousel) di homepage,
            // jadi dibatasi ke 8 terbaru aja biar nggak makin berat seiring
            // jumlah berita nambah. List lengkap tetep ada di dashboard admin.
            // Berita yang masih terjadwal ikut disimpan di cache (terpisah) dan disaring ulang tiap kunjungan,
            // supaya muncul tepat pada waktunya tanpa menunggu cache habis (lihat trait Terjadwal).
            $news = news::tayang()->terbaruTayang()->take(8)->get();
            $newsTerjadwal = news::belumTayang()->get();
            foreach ($news->concat($newsTerjadwal) as $item) {
                $item->news_description = HtmlSanitizer::clean($item->news_description);
            }

            $bio = bio::first();
            if ($bio) {
                $bio->konten = HtmlSanitizer::clean($bio->konten);
            }

            return [
                // Album, banner, header, dan merchandise tampil menurut urutan yang diatur di CMS.
                'albums' => CachePublik::polos(albums::urut()->get()),
                'banner' => CachePublik::polos(banner::urut()->get()),
                'headers' => CachePublik::polos(header::urut()->get()),
                'statistik' => CachePublik::polos(statistik::all()),
                'news' => CachePublik::polos($news),
                'newsTerjadwal' => CachePublik::polos($newsTerjadwal),
                'merchandise' => CachePublik::polos(merchandise::urut()->get()),
                // Fase 6 langkah 3 — data Profile buat teaser di homepage
                'hero' => CachePublik::polos(profile_hero::first()),
                'genre' => CachePublik::polos(genre::all()),
                'bio' => CachePublik::polos($bio),
            ];
        });

        // Yang masih terjadwal disaring di sini (bukan di cache): berita yang jadwalnya sudah tiba ikut naik ke
        // daftar dan diurutkan ulang, lalu dipotong 8.
        $news = news::hanyaTayang(
            CachePublik::daftar(news::class, $polos['news'])
                // `?? []`: cache lama (sebelum fitur jadwal) belum punya kunci ini; tidak boleh membuat halaman error.
                ->concat(CachePublik::daftar(news::class, $polos['newsTerjadwal'] ?? []))
        )
            ->sortByDesc(fn(news $n) => [($n->terbit_pada ?? $n->created_at)?->getTimestamp(), $n->id_news])
            ->take(8)
            ->values();

        return view('pages/home', [
            'albums' => albums::hanyaTayang(CachePublik::daftar(albums::class, $polos['albums'])),
            'banner' => CachePublik::daftar(banner::class, $polos['banner']),
            'headers' => CachePublik::daftar(header::class, $polos['headers']),
            'statistik' => CachePublik::daftar(statistik::class, $polos['statistik']),
            'news' => $news,
            'merchandise' => CachePublik::daftar(merchandise::class, $polos['merchandise']),
            'hero' => CachePublik::satu(profile_hero::class, $polos['hero']),
            'genre' => CachePublik::daftar(genre::class, $polos['genre']),
            'bio' => CachePublik::satu(bio::class, $polos['bio']),
        ]);
    }

    public function profile()
    {
        // Sama seperti Home: disimpan sementara, dan bio disaring sekali saat cache dibuat.
        $polos = CachePublik::ingat('profile', function () {
            $bio = bio::first();
            if ($bio) {
                $bio->konten = HtmlSanitizer::clean($bio->konten);
            }

            return [
                'statistik' => CachePublik::polos(statistik::all()),
                'highlight' => CachePublik::polos(highlight::all()),
                // Fase 6 langkah 3 — data Profile lengkap buat halaman /profile
                'hero' => CachePublik::polos(profile_hero::first()),
                'genre' => CachePublik::polos(genre::all()),
                'bio' => CachePublik::polos($bio),
                'collab' => CachePublik::polos(collab::all()),
                'mediaCoverage' => CachePublik::polos(media_coverage::all()),
                'booking' => CachePublik::polos(booking::all()),
                'mediaSosial' => CachePublik::polos(media_sosial::all()),
            ];
        });

        return view('components.profile.profile-full', [
            'statistik' => CachePublik::daftar(statistik::class, $polos['statistik']),
            'highlight' => CachePublik::daftar(highlight::class, $polos['highlight']),
            'hero' => CachePublik::satu(profile_hero::class, $polos['hero']),
            'genre' => CachePublik::daftar(genre::class, $polos['genre']),
            'bio' => CachePublik::satu(bio::class, $polos['bio']),
            'collab' => CachePublik::daftar(collab::class, $polos['collab']),
            'mediaCoverage' => CachePublik::daftar(media_coverage::class, $polos['mediaCoverage']),
            'booking' => CachePublik::daftar(booking::class, $polos['booking']),
            'mediaSosial' => CachePublik::daftar(media_sosial::class, $polos['mediaSosial']),
        ]);
    }
}