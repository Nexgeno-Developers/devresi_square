<?php

namespace App\Http\Middleware;

use App\Support\AccountMembership;
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

        $membership = current_account_membership($user);

        if (
            $user->hasAnyRole(['Tenant', 'Contractor'])
            || ($membership && AccountMembership::isTenant($membership->member_type))
            || ($membership && $membership->member_type === AccountMembership::CONTRACTOR)
        ) {
            abort(403, 'You cannot manage tenancies.');
        }

        if (is_landlord_plan_user($user)) {
            return $next($request);
        }

        $staffRoles = [
            'Owner',
            'Property Manager',
            'Estate Agent',
            'Agent',
            'Staff',
            'Test',
        ];

        if ($user->hasAnyRole($staffRoles)) {
            return $next($request);
        }

        abort(403, 'You cannot manage tenancies.');
    }
}
