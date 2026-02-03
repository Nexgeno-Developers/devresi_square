<?php

namespace App\Http\Controllers\Backend;

use App\Models\User;
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
            $user = User::find(Auth::id());

            // Backend‑eligible roles
            $backendRoles = [
                'Super Admin',
                'Owner',
                'Property Manager',
                'Landlord',
                'Staff'
            ];

            if ($user->hasAnyRole($backendRoles)) {
                return redirect()->route('backend.dashboard');
            }

            return redirect()->route('home');
        }

        return view('backend.login');
    }

    /**
     * Handle login submission.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($request->only('email','password'), $request->boolean('remember'))) {
            $user = User::find(Auth::id());

            $backendRoles = [
                'Super Admin',
                'Owner',
                'Property Manager',
                'Landlord',
                'Staff'
            ];

            if ($user->hasAnyRole($backendRoles)) {
                return redirect()->route('backend.dashboard');
            }

            return redirect()->route('home');
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
        return redirect()->route('backend.login');
    }
}

