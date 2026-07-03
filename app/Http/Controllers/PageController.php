<?php

namespace App\Http\Controllers;

class PageController extends Controller
{
    public function home()
    {
        return view('home');
    }

    public function infos()
    {
        return view('infos');
    }

    public function nouveautes()
    {
        return view('nouveautes');
    }

    public function contact()
    {
        return view('contact');
    }
}
