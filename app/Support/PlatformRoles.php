<?php

namespace App\Support;

/**
 * Launch Step 10: roles that must not be created, renamed, edited, or deleted
 * by customer workspace admins. Platform-owned only.
 */
final class PlatformRoles
{
    public const PROTECTED_NAMES = [
        'Super Admin',
        'Landlord',
        'Estate Agent',
        'Property Manager',
        'Tenant',
        'Contractor',
        'Agent',
    ];

    public static function isProtected(?string $name): bool
    {
        if ($name === null || $name === '') {
            return false;
        }

        return in_array($name, self::PROTECTED_NAMES, true);
    }

    public static function assertMutable(?string $name): void
    {
        abort_if(self::isProtected($name), 403, 'Platform roles cannot be modified.');
    }
}
