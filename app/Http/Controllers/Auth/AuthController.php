<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\User;

class AuthController
{
    public function showLoginForm()
    {
        return view('frontend.login');
    }

    public function showRegisterForm()
    {
        return view('frontend.register');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($request->only('email', 'password'), $remember)) {
            $user = Auth::user();

            // Add Landlord (and any others) here:
            if (
                $user->hasAnyRole([
                    'Super Admin',
                    'Owner',
                    'Property Manager',
                    'Landlord',
                    'Estate Agent',
                    'Agent',
                    'Staff',
                    'Test',
                ])
            ) {
                return redirect()->route('backend.dashboard');
            }

            return redirect()->route('home');
        }

        return back()
            ->withError('The provided credentials do not match our records.')
            ->withInput();
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // assign the default role
            $user->assignRole('User');

            Auth::login($user, $request->boolean('remember'));

            return redirect()
                ->route('backend.dashboard')
                ->with('success', 'Registration successful!');
        } catch (\Exception $e) {
            return back()
                ->withError('Registration failed. Please try again.')
                ->withInput();
        }
    }

    /*
        public function login(Request $request)
        {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            if (Auth::attempt($request->only('email', 'password'))) {
                return redirect()->route('admin.dashboard');
            }

            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ]);
        }
    */
    /*public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Attempt to log the user in with 'remember' option
        $remember = $request->has('remember'); // Check if 'remember' checkbox is checked

        if (Auth::attempt($request->only('email', 'password'), $remember)) {
            $user = Auth::user();

            // Check if user has one of the specified role IDs
            if (in_array($user->role_id, [1, 2, 3])) {
                return redirect()->route('backend.dashboard');
            } else {
                return redirect()->route('home'); // Redirect to home for other roles
            }
        }

        // Redirect back with a flash message for failed login
        return redirect()->route('login')->with('error', 'The provided credentials do not match our records.')->withInput();
    }


        public function register(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput(); // Redirect back with input data and errors
        }

        try {
            // Create the user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role_id' => 6, // Default to 'user' role
            ]);

            // Log the user in and remember if checked
            $remember = $request->has('remember'); // Check if 'remember' checkbox is checked
            Auth::login($user, $remember); // Pass $remember to remember the user

            // Redirect to the backend dashboard
            return redirect()->route('backend.dashboard')->with('success', 'Registration successful!'); // Flash success message

        } catch (\Exception $e) {
            // Handle any exceptions
            return redirect()->back()
                ->with('error', 'Registration failed. Please try again.') // Flash error message
                ->withInput(); // Redirect back with input data
        }
    }*/
    /*
        public function register(Request $request)
        {
            // Validate the request data
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ]);

            // Create a new user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role_id' => 6, // Default to 'user' role
            ]);

            // Log in the user
            Auth::login($user);

            // Flash a success message to the session
            session()->flash('success', 'Registration successful! You are now logged in.');

            // Redirect to the admin dashboard
            return redirect()->route('admin.dashboard');
        }
            */


    public function logout(Request $request)
    {
        Auth::logout();
        return redirect()->route('login');
    }
}
