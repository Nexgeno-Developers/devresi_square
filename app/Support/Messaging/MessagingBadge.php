<?php

namespace App\Support\Messaging;

use App\Models\AccountUser;
use App\Models\MessageRequest;
use App\Services\Messaging\ConversationService;
use App\Services\Messaging\InboxFeed;
use App\Support\AccountMembership;

/**
 * Which inbox the signed-in user has on the current page, and its unread
 * count, for menu badges. Computed once per request.
 */
final class MessagingBadge
{
    private static ?array $resolved = null;

    /** @return array{area: string, party: Party, unread: int, requests: int}|null */
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
            $unread = app(InboxFeed::class)->unreadTotal($party, $user, $area, app(ConversationService::class));
            $requests = MessageRequest::query()->where('to_type', $party->type)->where('to_id', $party->id)->where('status', MessageRequest::PENDING)->count();
        } catch (\Throwable) {
            // Never break a page because the badge failed (e.g. before migrations run).
            return null;
        }

        return self::$resolved = ['area' => $area, 'party' => $party, 'unread' => $unread, 'requests' => $requests];
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

        return $badge ? $badge['unread'] + $badge['requests'] : 0;
    }

    /** For tests: forget the per-request cache. */
    public static function flush(): void
    {
        self::$resolved = null;
    }
}
