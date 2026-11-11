<?php

namespace App\Http\Middleware;

use App\Models\AccountUser;
use App\Support\AccountMembership;
use App\Support\Messaging\MessagingArea;
use App\Support\Messaging\Party;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Works out which party the signed-in user acts as, from the route group
 * alone (never from request input), and stores it on the request.
 *
 * Usage: messaging.as:admin | messaging.as:portal | messaging.as:contractor
 */
class EnsureMessagingIdentity
{
    public function handle(Request $request, Closure $next, string $area): Response
    {
        abort_unless(config('messaging.enabled', true), 404);
        $user = $request->user();
        abort_unless($user && ! $user->isSuperAdmin(), 403);
        // Super Admin "log in as account" must not open private conversations.
        // Super Admin only ever sees reported messages, in the moderation queue.
        abort_if($request->hasSession() && $request->session()->has('impersonator_user_id'), 403, 'Messages are private, so they are hidden while you are logged in as this account.');

        $party = match ($area) {
            MessagingArea::ADMIN => $this->workspace($user),
            MessagingArea::PORTAL => $this->portal($user),
            MessagingArea::CONTRACTOR => $user->hasRole('Contractor') ? Party::user($user->id) : null,
            default => null,
        };
        abort_unless($party !== null, 403, 'Messages are not available here.');

        $request->attributes->set('messaging.party', $party);
        $request->attributes->set('messaging.area', $area);

        return $next($request);
    }

    private function workspace($user): ?Party
    {
        $accountId = current_account_id();
        if (! $accountId) {
            return null;
        }
        $isOperator = AccountUser::query()
            ->where('account_id', $accountId)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->whereIn('member_type', AccountMembership::WORKSPACE_OPERATOR_TYPES)
            ->exists();

        return $isOperator ? Party::account((int) $accountId) : null;
    }

    private function portal($user): ?Party
    {
        $accountId = current_account_id();
        if (! $accountId) {
            return null;
        }
        $portalTypes = array_values(array_diff(AccountMembership::PORTAL_TYPES, [AccountMembership::CONTRACTOR]));
        $isPortal = AccountUser::query()
            ->where('account_id', $accountId)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where('can_login', true)
            ->whereIn('member_type', $portalTypes)
            ->exists();

        return $isPortal ? Party::user($user->id) : null;
    }
}
