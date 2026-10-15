<?php

namespace App\Support;

class PortalNoticeLink
{
    /**
     * Where a notification card should open. Tenants never land on a landlord screen.
     */
    public static function open(?string $url): string
    {
        if (function_exists('is_tenant_portal_user') && is_tenant_portal_user()) {
            return self::forTenant($url);
        }

        return filled($url) ? $url : route('backend.events.calendar');
    }

    public static function forTenant(?string $url): string
    {
        $url = trim((string) $url);
        $path = strtolower((string) (parse_url($url, PHP_URL_PATH) ?: $url));

        if ($path !== '' && self::isPortalPath($path)) {
            return $url;
        }
        if (str_contains($path, 'finance') || str_contains($path, 'rent')) {
            return route('tenant.rent');
        }
        if (str_contains($path, 'repair') || str_contains($path, 'maintenance')) {
            return route('tenant.maintenance');
        }
        if (str_contains($path, 'calendar') || str_contains($path, 'event') || str_contains($path, 'visit')) {
            return route('tenant.calendar');
        }
        if (str_contains($path, 'document') || str_contains($path, 'compliance')) {
            return route('tenant.documents');
        }
        if (str_contains($path, 'tenanc')) {
            return route('tenant.tenancy');
        }

        return route('tenant.notifications');
    }

    private static function isPortalPath(string $path): bool
    {
        foreach (['/portal/rent', '/portal/maintenance', '/portal/calendar', '/portal/documents', '/portal/tenancy', '/portal/notifications', '/portal/profile', '/email/confirm', '/repair-visit'] as $needle) {
            if (str_contains($path, $needle)) {
                return true;
            }
        }

        return false;
    }
}
