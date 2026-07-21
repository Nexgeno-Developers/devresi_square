<?php

namespace App\Http\Middleware;

use App\Models\AccountUser;
use App\Services\Saas\CurrentAccountService;
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
            && in_array($membership->member_type, ['owner', 'admin'], true)
            && $request->routeIs('backend.billing.*');
    }
}
