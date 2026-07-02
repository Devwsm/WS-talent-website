<?php

namespace App\Http\Controllers;

use App\Models\profileCards;
use Illuminate\Http\Request;

class profileController extends Controller
{
    //
    public function profile()
    {
        $profileCards = profileCards::all();
        return view('pages.dashboard-pages.profile', compact('profileCards'));
    }
}