<?php

namespace App\Http\Controllers\Backend\Saas;

use App\Models\AccountUser;
use App\Models\PropertyParticipant;
use App\Services\Saas\PortalAccessService;
use Illuminate\Http\Request;

class PortalAccessController
{
    public function index(Request $request)
    {
        $user = $request->user();
        $account = current_account();

        abort_unless($account, 403);

        $portalAccessService = app(PortalAccessService::class);

        abort_unless(
            $user->isSuperAdmin() || $portalAccessService->isAccountOwnerOrAdmin($user, $account->id),
            403
        );

        $portalUsers = AccountUser::query()
            ->with('user')
            ->addSelect([
                'assigned_properties_count' => PropertyParticipant::query()
                    ->selectRaw('COUNT(DISTINCT property_id)')
                    ->whereColumn('property_participants.account_id', 'account_users.account_id')
                    ->whereColumn('property_participants.user_id', 'account_users.user_id')
                    ->where('property_participants.status', 'active'),
            ])
            ->where('account_id', $account->id)
            ->whereIn('member_type', ['contact', 'landlord', 'owner_contact', 'tenant', 'contractor', 'property_manager'])
            ->orderBy('member_type')
            ->orderByDesc('id')
            ->paginate(25);

        return view('backend.saas.portal_access.index', compact('account', 'portalUsers'));
    }
}
