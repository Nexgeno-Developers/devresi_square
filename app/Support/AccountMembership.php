<?php

namespace App\Support;

final class AccountMembership
{
    public const OWNER = 'owner';

    public const ADMIN = 'admin';

    public const STAFF = 'staff';

    public const CONTACT = 'contact';

    /** Agency workspace contact. Not a paying landlord workspace operator. */
    public const LANDLORD_CONTACT = 'landlord';

    public const OWNER_CONTACT = 'owner_contact';

    public const TENANT = 'tenant';

    public const CONTRACTOR = 'contractor';

    public const PROPERTY_MANAGER = 'property_manager';

    public const WORKSPACE_ADMIN_TYPES = [
        self::OWNER,
        self::ADMIN,
    ];

    public const WORKSPACE_OPERATOR_TYPES = [
        self::OWNER,
        self::ADMIN,
        self::STAFF,
    ];

    public const PORTAL_TYPES = [
        self::CONTACT,
        self::LANDLORD_CONTACT,
        self::OWNER_CONTACT,
        self::TENANT,
        self::CONTRACTOR,
        self::PROPERTY_MANAGER,
    ];

    public static function isWorkspaceAdmin(?string $memberType): bool
    {
        return in_array($memberType, self::WORKSPACE_ADMIN_TYPES, true);
    }

    public static function isWorkspaceOperator(?string $memberType): bool
    {
        return in_array($memberType, self::WORKSPACE_OPERATOR_TYPES, true);
    }

    public static function isPortalType(?string $memberType): bool
    {
        return in_array($memberType, self::PORTAL_TYPES, true);
    }

    public static function isTenant(?string $memberType): bool
    {
        return $memberType === self::TENANT;
    }

    public static function isLandlordWorkspaceOperator(?string $memberType, ?string $accountType): bool
    {
        return AccountType::isLandlord($accountType) && self::isWorkspaceAdmin($memberType);
    }
}
