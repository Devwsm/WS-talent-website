<?php

namespace App\Http\Controllers;

use App\Models\albums;
use App\Models\banner;
use App\Models\color_pages;
use App\Models\header;
use App\Models\highlight;
use App\Models\merchandise;
use App\Models\news;
use App\Models\statistik;
use Illuminate\Http\Request;

class homeController extends Controller
{
    //
    public function index()
    {
        $color_pages = color_pages::first();
        $albums = albums::all();
        $banner = banner::all();
        $headers = header::all();
        $statistik = statistik::all();
        $news = news::all();
        $merchandise = merchandise::all();
        return view(
            'pages/home',
            compact(
                'color_pages',
                'albums',
                'banner',
                'headers',
                'statistik',
                'news',
                'merchandise',
            )
        );
    }

    public function profile()
    {
        $statistik = statistik::all();
        $highlight = highlight::all();
        return view('components.profile.profile-full', compact('statistik', 'highlight'));
    }
}