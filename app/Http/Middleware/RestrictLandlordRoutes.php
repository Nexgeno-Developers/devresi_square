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

        if (! $user || $user->isSuperAdmin()) {
            return $next($request);
        }

        if ($user->hasAnyRole(['Property Manager', 'Estate Agent'])) {
            return $next($request);
        }

        if (! is_landlord_plan_user($user)) {
            return $next($request);
        }

        $routeName = $request->route()?->getName() ?? '';

        if ($routeName === '' || ! $this->isAllowed($routeName)) {
            abort(403, 'This area is not available on the landlord plan.');
        }

        return $next($request);
    }

    private function isAllowed(string $routeName): bool
    {
        foreach (config('landlord_mvp.blocked_route_name_prefixes', []) as $prefix) {
            if ($this->matchesPrefix($routeName, $prefix)) {
                return false;
            }
        }

        foreach (config('landlord_mvp.enabled_route_name_prefixes', []) as $prefix) {
            if ($this->matchesPrefix($routeName, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function matchesPrefix(string $routeName, string $prefix): bool
    {
        if ($prefix === '') {
            return false;
        }

        if ($routeName === $prefix) {
            return true;
        }

        if (str_ends_with($prefix, '.')) {
            return str_starts_with($routeName, $prefix);
        }

        return str_starts_with($routeName, $prefix.'.');
    }
}
