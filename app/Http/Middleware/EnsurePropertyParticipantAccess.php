<?php

namespace App\Http\Middleware;

use App\Models\Property;
use App\Services\Saas\PortalAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePropertyParticipantAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isSuperAdmin()) {
            return $next($request);
        }

        $accountId = current_account_id();

        if (! $accountId) {
            return $next($request);
        }

        $portalAccess = app(PortalAccessService::class);

        if ($portalAccess->isAccountOwnerOrAdmin($user, $accountId)) {
            return $next($request);
        }

        $property = $this->resolveProperty($request);

        if (! $property) {
            return $next($request);
        }

        abort_unless(
            $portalAccess->canAccessProperty($user, $property),
            403,
            'You do not have access to this property.'
        );

        return $next($request);
    }

    private function resolveProperty(Request $request): ?Property
    {
        foreach (['property', 'property_id', 'propertyId', 'id'] as $key) {
            $value = $request->route($key);

            if ($value instanceof Property) {
                return $value;
            }

            if (is_numeric($value)) {
                return Property::query()->find((int) $value);
            }
        }

        $queryId = $request->query('property_id');

        return is_numeric($queryId) ? Property::query()->find((int) $queryId) : null;
    }
}
