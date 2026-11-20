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

        // One sign-in page for everyone: the redesigned /login uses the same
        // credential check and post-login redirect. Keep any error or old
        // input flashed by a failed POST to this route for the next hop.
        session()->reflash();

        return redirect()->route('login');
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
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    }
}

