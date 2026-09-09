<?php

namespace App\Support;

final class AccountType
{
    public const LANDLORD = 'landlord';

    public const ESTATE_AGENT_FREELANCE = 'estate_agent_freelance';

    public const ESTATE_AGENT_COMPANY = 'estate_agent_company';

    public static function isLandlord(?string $accountType): bool
    {
        return $accountType === self::LANDLORD;
    }

    public static function isEstateAgent(?string $accountType): bool
    {
        return in_array($accountType, [self::ESTATE_AGENT_FREELANCE, self::ESTATE_AGENT_COMPANY], true);
    }
}
