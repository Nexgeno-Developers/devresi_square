<?php

namespace App\Support\Messaging;

use App\Models\AccountUser;
use App\Services\Messaging\ConversationService;
use App\Support\AccountMembership;

/**
 * Which inbox the signed-in user has on the current page, and its unread
 * count, for menu badges. Computed once per request.
 */
final class MessagingBadge
{
    private static ?array $resolved = null;

    /** @return array{area: string, party: Party, unread: int}|null */
    public static function current(): ?array
    {
        if (self::$resolved !== null) {
            return self::$resolved ?: null;
        }
        self::$resolved = [];

        $user = auth()->user();
        if (! $user || ! config('messaging.enabled', true) || $user->isSuperAdmin() || session()->has('impersonator_user_id')) {
            return null;
        }

        $area = null;
        $party = null;
        $accountId = current_account_id();
        $membership = $accountId
            ? AccountUser::query()->where('account_id', $accountId)->where('user_id', $user->id)->where('status', 'active')->first()
            : null;

        if ($membership && AccountMembership::isWorkspaceOperator($membership->member_type)) {
            $area = MessagingArea::ADMIN;
            $party = Party::account((int) $accountId);
        } elseif ($membership && $membership->can_login && AccountMembership::isPortalType($membership->member_type) && $membership->member_type !== AccountMembership::CONTRACTOR) {
            $area = MessagingArea::PORTAL;
            $party = Party::user($user->id);
        } elseif ($user->hasRole('Contractor')) {
            $area = MessagingArea::CONTRACTOR;
            $party = Party::user($user->id);
        }
        if (! $area) {
            return null;
        }

        try {
            $unread = app(ConversationService::class)->unreadCount($party, $user);
        } catch (\Throwable) {
            // Never break a page because the badge failed (e.g. before migrations run).
            return null;
        }

        return self::$resolved = ['area' => $area, 'party' => $party, 'unread' => $unread];
    }

    public static function area(): ?string
    {
        return self::current()['area'] ?? null;
    }

    public static function url(): ?string
    {
        $badge = self::current();

        return $badge ? MessagingArea::route($badge['area'], 'index') : null;
    }

    public static function total(): int
    {
        $badge = self::current();

        return $badge ? $badge['unread'] : 0;
    }

    /** For tests: forget the per-request cache. */
    public static function flush(): void
    {
        self::$resolved = null;
    }
}
