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
use Illuminate\Http\Request;

class homeController extends Controller
{
    //
    public function index()
    {
        $albums = albums::all();
        $banner = banner::all();
        $headers = header::all();
        $statistik = statistik::all();
        // News ditampilin sebagai grid statis (bukan carousel) di homepage,
        // jadi dibatasi ke 8 terbaru aja biar nggak makin berat seiring
        // jumlah berita nambah. List lengkap tetep ada di dashboard admin.
        $news = news::latest()->take(8)->get();
        $merchandise = merchandise::all();

        // Fase 6 langkah 3 — data Profile buat teaser di homepage
        $hero = profile_hero::first();
        $genre = genre::all();
        $bio = bio::first();

        // dipakai buat link tombol "follow" di CTA homepage
        $mediaSosial = media_sosial::all();

        return view(
            'pages/home',
            compact(
                'albums',
                'banner',
                'headers',
                'statistik',
                'news',
                'merchandise',
                'hero',
                'genre',
                'bio',
                'mediaSosial',
            )
        );
    }

    public function profile()
    {
        $statistik = statistik::all();
        $highlight = highlight::all();

        // Fase 6 langkah 3 — data Profile lengkap buat halaman /profile
        $hero = profile_hero::first();
        $genre = genre::all();
        $bio = bio::first();
        $collab = collab::all();
        $mediaCoverage = media_coverage::all();
        $booking = booking::all();
        $mediaSosial = media_sosial::all();

        return view('components.profile.profile-full', compact(
            'statistik',
            'highlight',
            'hero',
            'genre',
            'bio',
            'collab',
            'mediaCoverage',
            'booking',
            'mediaSosial',
        ));
    }
}