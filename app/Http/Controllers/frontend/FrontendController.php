<?php

namespace App\Http\Controllers\Frontend;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FrontendController
{
    public function index()
    {
        // Check if the user is already authenticated
        if (Auth::check()) {
            // Redirect based on user role
            $user = Auth::user();

            // If the user is already on the home page, don't redirect again
            if (request()->routeIs('home') && !in_array($user->role_id, [1, 2, 3])) {
                return view('frontend.index');
            }

            if (in_array($user->role_id, [1, 2, 3])) { // Admin roles
                return redirect()->route('backend.dashboard');
            } else {
                return redirect()->route('home');
            }
        }
        return view('frontend.index');
    }

    public function pricing()
    {
        
        return view('frontend.pricing');
    }
    

}
