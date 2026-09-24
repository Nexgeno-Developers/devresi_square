<?php

namespace App\Http\Middleware;

use App\Models\AccountUser;
use App\Services\Saas\CurrentAccountService;
use App\Support\AccountMembership;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AccountStatusGuard
{
    private const BLOCKED_STATUSES = ['suspended', 'cancelled'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isSuperAdmin()) {
            return $next($request);
        }

        $account = app(CurrentAccountService::class)->current($user);

        if (! $account || ! in_array($account->status, self::BLOCKED_STATUSES, true)) {
            return $next($request);
        }

        if ($this->isAllowedWhileBlocked($request, $account->id, $user->id)) {
            return $next($request);
        }

        if ($this->tenantMayUsePortalWhileBlocked($request, $account->id, $user->id)) {
            if ($request->routeIs('tenant.rent.pay')) {
                return $this->refuseCardPay($request, $account->status);
            }

            return $next($request);
        }

        $message = $account->status === 'cancelled'
            ? 'This account is cancelled. Please visit Billing & Plan to reactivate access.'
            : 'This account is suspended. Please visit Billing & Plan to restore access.';

        if ($request->expectsJson()) {
            abort(403, $message);
        }

        if (function_exists('flash')) {
            flash($message)->error();
        }

        return redirect()->route('backend.billing.index');
    }

    private function isAllowedWhileBlocked(Request $request, int $accountId, int $userId): bool
    {
        if ($request->routeIs('backend.billing.*', 'backend.accounts.switch', 'backend.logout', 'admin.users.profile.*')) {
            return true;
        }

        $membership = AccountUser::query()
            ->where('account_id', $accountId)
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->first();

        return $membership
            && $membership->can_login
            && AccountMembership::isWorkspaceAdmin($membership->member_type)
            && $request->routeIs('backend.billing.*');
    }

    private function tenantMayUsePortalWhileBlocked(Request $request, int $accountId, int $userId): bool
    {
        if (! $request->routeIs('backend.home', 'tenant.*')) {
            return false;
        }

        $membership = AccountUser::query()
            ->where('account_id', $accountId)
            ->where('user_id', $userId)
            ->where('member_type', AccountMembership::TENANT)
            ->where('status', 'active')
            ->where('can_login', true)
            ->first();

        return $membership !== null;
    }

    private function refuseCardPay(Request $request, string $status): Response
    {
        $message = $status === 'cancelled'
            ? 'Card payments are paused because this account is cancelled. Bank transfer is still available.'
            : 'Card payments are paused because this account is suspended. Bank transfer is still available.';

        if ($request->expectsJson()) {
            abort(403, $message);
        }

        if (function_exists('flash')) {
            flash($message)->error();
        }

        return redirect()->route('tenant.rent');
    }
}
