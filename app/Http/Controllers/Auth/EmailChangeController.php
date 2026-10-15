<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\EmailChangeService;

class EmailChangeController extends Controller
{
    public function confirm(string $token, EmailChangeService $changes)
    {
        $changes->confirm($token);

        return redirect()->route('login')->with('success', 'Email confirmed. Sign in with your new address.');
    }
}
