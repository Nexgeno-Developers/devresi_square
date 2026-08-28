<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictLandlordRoutes
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('Landlord')) {
            return $next($request);
        }

        if ($user->hasAnyRole(['Super Admin', 'Property Manager', 'Estate Agent'])) {
            return $next($request);
        }

        $routeName = $request->route()?->getName() ?? '';

        foreach (config('landlord_mvp.blocked_route_name_prefixes', []) as $prefix) {
            if ($routeName !== '' && str_starts_with($routeName, $prefix)) {
                abort(403, 'This area is not available on the landlord plan.');
            }
        }

        return $next($request);
    }
}
