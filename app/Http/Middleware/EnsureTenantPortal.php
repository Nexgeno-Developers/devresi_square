<?php

namespace App\Http\Middleware;

use App\Models\AccountUser;
use App\Support\AccountMembership;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantPortal
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user, 403, 'This area is only available to tenants.');

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $membership = current_account_membership($user);
        $isTenant = ($membership && AccountMembership::isTenant($membership->member_type))
            || $user->hasRole('Tenant');

        if (! $isTenant) {
            abort(403, 'This area is only available to tenants.');
        }

        $accountId = current_account_id();
        if ($accountId) {
            $tenantMembership = AccountUser::query()
                ->where('account_id', $accountId)
                ->where('user_id', $user->id)
                ->where('member_type', AccountMembership::TENANT)
                ->first();

            if ($tenantMembership && ($tenantMembership->status !== 'active' || ! $tenantMembership->can_login)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login');
            }
        }

        return $next($request);
    }
}
