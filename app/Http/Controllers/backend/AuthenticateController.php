<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticateController
{
    public function index()
    {
        return view('backend.login'); // Return the login view
    }

    public function login(Request $request)
    {
        // Validate the request
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Attempt to log the user in
        if (Auth::attempt($request->only('email', 'password'))) {
            $user = Auth::user();

            // Redirect based on user role
            if (in_array($user->role_id, [1, 2, 3])) { // Assuming role_ids 1, 2, 3 are for admin roles
                return redirect()->route('backend.dashboard');
            } else {
                return redirect()->route('home'); // Redirect non-admin roles to home
            }
        }

        // Redirect back with an error message if login fails
        return redirect()->route('backend.login')->with('error', 'The provided credentials do not match our records.')->withInput();
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('backend.login');
    }
}

