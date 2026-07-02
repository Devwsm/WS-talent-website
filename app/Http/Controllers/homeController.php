<?php

namespace App\Http\Controllers;

use App\Models\albums;
use App\Models\banner;
use App\Models\header;
use App\Models\heroSection;
use App\Models\highlight;
use App\Models\merchandise;
use App\Models\news;
use App\Models\schedule;
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
        $schedule = schedule::all();
        $news = news::all();
        $merchandise = merchandise::all();
        return view(
            'pages/home',
            compact(
                'albums',
                'banner',
                'headers',
                'statistik',
                'schedule',
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