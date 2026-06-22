<?php

namespace App\Http\Controllers;

use App\Models\account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class loginController extends Controller
{
    //
    public function login(){
        return view('pages.login');
    }
    
    public function prosesLogin(Request $request)
    {
        $user = account::where('username', $request->username)->first();
        if ($user && Hash::check($request->password, $user->password)) {
            Session::put('login', true);
            Session::put('user', $user->username);
            return redirect()->route('dashboard');
        }
        return back()->with('error', 'Username atau Password salah');
    }

    public function logout()
    {
        Session::forget('login');
        return redirect()->route('home');
    }
}