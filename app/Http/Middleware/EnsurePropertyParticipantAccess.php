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

        $property = $this->resolveProperty($request, $accountId);

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

    private function resolveProperty(Request $request, int $accountId): ?Property
    {
        foreach (['property', 'property_id', 'propertyId', 'id'] as $key) {
            $value = $request->route($key);

            if ($value instanceof Property) {
                abort_unless((int) $value->account_id === $accountId, 403);

                return $value;
            }

            if (is_numeric($value)) {
                return Property::query()
                    ->forAccount($accountId)
                    ->find((int) $value);
            }
        }

        $queryId = $request->query('property_id');

        return is_numeric($queryId)
            ? Property::query()->forAccount($accountId)->find((int) $queryId)
            : null;
    }
}
