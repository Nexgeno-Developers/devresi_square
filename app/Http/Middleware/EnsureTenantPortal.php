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

        $accountId = current_account_id();
        $tenantMembership = $accountId
            ? AccountUser::query()
                ->where('account_id', $accountId)
                ->where('user_id', $user->id)
                ->where('member_type', AccountMembership::TENANT)
                ->first()
            : null;

        if ($tenantMembership && ($tenantMembership->status !== 'active' || ! $tenantMembership->can_login)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        }

        if (! $tenantMembership || $tenantMembership->status !== 'active' || ! $tenantMembership->can_login) {
            abort(403, 'This area is only available to tenants.');
        }

        return $next($request);
    }
}
