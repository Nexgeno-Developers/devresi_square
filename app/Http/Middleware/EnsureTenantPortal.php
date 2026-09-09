<?php

namespace App\Http\Middleware;

use App\Support\AccountMembership;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantPortal
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user, 403, 'This area is only available to tenants.');

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $membership = current_account_membership($user);

        if (
            ($membership && AccountMembership::isTenant($membership->member_type))
            || $user->hasRole('Tenant')
        ) {
            return $next($request);
        }

        abort(403, 'This area is only available to tenants.');
    }
}
