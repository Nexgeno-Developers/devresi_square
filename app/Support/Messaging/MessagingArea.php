<?php

namespace App\Support\Messaging;

use App\Models\ConversationParticipant;

/**
 * The three places messaging lives. The area comes from the route group,
 * never from request input, and decides which party the user acts as.
 */
final class MessagingArea
{
    /** Landlord / agency workspace inbox (owner, admin, staff). */
    public const ADMIN = 'admin';

    /** Tenants and other portal contacts. */
    public const PORTAL = 'portal';

    public const CONTRACTOR = 'contractor';

    public const ALL = [self::ADMIN, self::PORTAL, self::CONTRACTOR];

    public static function route(string $area, string $name, mixed $parameters = []): string
    {
        return route($area.'.messages.'.$name, $parameters);
    }

    /** Where a participant reads the chat, used for notification links. */
    public static function forParticipant(ConversationParticipant $participant): string
    {
        return match ($participant->role) {
            ConversationParticipant::LANDLORD => self::ADMIN,
            ConversationParticipant::CONTRACTOR => self::CONTRACTOR,
            default => self::PORTAL,
        };
    }

    public static function layoutFlags(string $area): array
    {
        return [
            'area' => $area,
            'isPortal' => $area === self::PORTAL,
            'isContractor' => $area === self::CONTRACTOR,
        ];
    }
}
