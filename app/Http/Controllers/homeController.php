<?php

namespace App\Http\Controllers;

use App\Models\albums;
use App\Models\banner;
use App\Models\header;
use App\Models\heroSection;
use App\Models\merchandise;
use App\Models\news;
use App\Models\schedule;
use Illuminate\Http\Request;

class homeController extends Controller
{
    //
    public function index()
    {
        $albums = albums::all();
        $banner = banner::all();
        $headers = header::all();
        $schedule = schedule::all();
        $news = news::all();
        $merchandise = merchandise::all();
        return view(
            'pages/home',
            compact(
                'schedule',
                'albums',
                'news',
                'merchandise',
                'banner',
                'headers'
            )
        );
    }

    public function profile()
    {
        return view('components.profile.profile-full');
    }
}