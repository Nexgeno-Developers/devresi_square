<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictTenancyManagement
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $staffRoles = [
            'Owner',
            'Property Manager',
            'Landlord',
            'Estate Agent',
            'Agent',
            'Staff',
            'Test',
        ];

        if ($user->hasAnyRole($staffRoles)) {
            return $next($request);
        }

        if ($user->hasAnyRole(['Tenant', 'Contractor'])) {
            abort(403, 'You cannot manage tenancies.');
        }

        return $next($request);
    }
}
