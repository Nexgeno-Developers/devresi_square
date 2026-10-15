<?php

namespace App\Http\Middleware;

use App\Support\AccountMembership;
use App\Support\AccountType;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictTenancyManagement
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $membership = current_account_membership($user);

        if (
            $user->hasAnyRole(['Tenant', 'Contractor'])
            || ($membership && AccountMembership::isTenant($membership->member_type))
            || ($membership && $membership->member_type === AccountMembership::CONTRACTOR)
        ) {
            abort(403, 'You cannot manage tenancies.');
        }

        if (is_landlord_plan_user($user) || $this->landlordWorkspaceMember($membership)) {
            return $next($request);
        }

        $staffRoles = [
            'Owner',
            'Property Manager',
            'Estate Agent',
            'Agent',
            'Staff',
            'Test',
        ];

        if ($user->hasAnyRole($staffRoles)) {
            return $next($request);
        }

        abort(403, 'You cannot manage tenancies.');
    }

    private function landlordWorkspaceMember($membership): bool
    {
        $account = function_exists('current_account') ? current_account() : null;

        if (! $account || ! $membership || ! AccountType::isLandlord($account->account_type)) {
            return false;
        }

        return in_array($membership->member_type, [
            AccountMembership::STAFF,
            AccountMembership::PROPERTY_MANAGER,
            AccountMembership::LANDLORD_CONTACT,
            AccountMembership::CONTACT,
            AccountMembership::OWNER_CONTACT,
        ], true);
    }
}
