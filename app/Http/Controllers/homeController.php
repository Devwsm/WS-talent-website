<?php

namespace App\Http\Controllers;

use App\Models\albums;
use App\Models\heroSection;
use App\Models\merchandise;
use App\Models\schedule;
use Illuminate\Http\Request;

class homeController extends Controller
{
    //
    public function index(){
        $hero = heroSection::all();
        $schedule = schedule::all();
        $albums = albums::all();
        $merchandise = merchandise::all();
        return view('pages/home', compact('hero', 'schedule', 'albums', 'merchandise'));
    }
}