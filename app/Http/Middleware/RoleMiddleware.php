<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    /*
    public function handle($request, Closure $next, $role)
    {
        if (auth()->check() && auth()->user()->role->name == $role) {
            return $next($request);
        }
        return redirect('/');
    }*/
    public function handle($request, Closure $next, ...$roles)
    {
        if (!Auth::check() || !in_array(Auth::user()->role_id, $roles)) {
            return redirect()->route('login'); // Redirect to login if not authorized
        }

        return $next($request);
    }
    
}
