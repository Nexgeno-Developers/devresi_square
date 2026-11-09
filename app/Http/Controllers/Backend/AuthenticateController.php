<?php

namespace App\Http\Controllers\Backend;

use App\Models\User;
use App\Support\PostLoginRedirect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticateController
{
   /**
     * Show login form or redirect authenticated users.
     */
    public function index()
    {
        if (Auth::check()) {
            return PostLoginRedirect::to(User::find(Auth::id()));
        }

        return view('backend.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($request->only('email','password'), $request->boolean('remember'))) {
            $user = User::find(Auth::id());

            if (PostLoginRedirect::accessRemoved($user)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()
                    ->with('error', PostLoginRedirect::ACCESS_REMOVED)
                    ->withInput($request->only('email'));
            }

            return PostLoginRedirect::to($user);
        }

        return back()
            ->with('error', 'The provided credentials do not match our records.')
            ->withInput();
    }

    /**
     * Log the user out.
     */
    public function logout()
    {
        Auth::logout();

        return redirect()->route('login');
    }
}

