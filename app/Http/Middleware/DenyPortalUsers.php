<?php

namespace App\Http\Middleware;

use App\Services\Saas\PortalAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DenyPortalUsers
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $accountId = current_account_id();

        if (! $user || $user->isSuperAdmin() || ! $accountId) {
            return $next($request);
        }

        $portalAccessService = app(PortalAccessService::class);

        if ($portalAccessService->isAccountOwnerOrAdmin($user, $accountId)) {
            return $next($request);
        }

        abort_if($portalAccessService->isPortalUser($user, $accountId), 403, 'Portal users cannot access this module.');

        return $next($request);
    }
}
