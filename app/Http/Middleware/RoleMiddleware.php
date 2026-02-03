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
    /*public function handle($request, Closure $next, ...$roles)
    {
        if (!Auth::check() || !in_array(Auth::user()->role_id, $roles)) {
            return redirect()->route('login'); // Redirect to login if not authorized
        }

        return $next($request);
    }*/

    /**
     * Handle an incoming request and ensure the user has at least one of the given roles.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  mixed  ...$roles  List of role names
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // If not logged in or not in any of the required roles, deny access
        if (! Auth::check() || ! $request->user()->hasAnyRole($roles)) {
            abort(403, 'You do not have permission to access this resource.');
        }

        return $next($request);
    }
    
}
