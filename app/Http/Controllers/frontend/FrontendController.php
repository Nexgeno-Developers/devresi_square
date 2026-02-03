<?php

namespace App\Http\Controllers\Frontend;
use Illuminate\Http\Request;

class FrontendController
{
    public function index()
    {
        return view('frontend.index');
    }
    
}
