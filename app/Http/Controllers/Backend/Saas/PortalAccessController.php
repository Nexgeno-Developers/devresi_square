<?php

namespace App\Http\Controllers\Backend\Saas;

use App\Models\Account;
use App\Models\AccountUser;
use App\Models\PropertyParticipant;
use App\Models\Tenancy;
use App\Models\User;
use App\Services\Portal\TenantInviteMailer;
use App\Services\Saas\PortalAccessService;
use App\Support\AccountMembership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;

class PortalAccessController
{
    public function index(Request $request)
    {
        $user = $request->user();
        $account = current_account();

        abort_unless($account, 403);

        $portalAccessService = app(PortalAccessService::class);
        $this->ensureCanManagePortal($user, $account, $portalAccessService);

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
            ->whereIn('member_type', AccountMembership::PORTAL_TYPES)
            ->orderBy('member_type')
            ->orderByDesc('id')
            ->paginate(25);

        $tenancies = Tenancy::query()
            ->with('property')
            ->forAccount($account->id)
            ->whereHas('property')
            ->orderByDesc('id')
            ->get();

        return view('backend.saas.portal_access.index', compact('account', 'portalUsers', 'tenancies'));
    }

    public function invite(Request $request)
    {
        $actor = $request->user();
        $account = current_account();

        abort_unless($account, 403);

        $portalAccessService = app(PortalAccessService::class);
        $this->ensureCanManagePortal($actor, $account, $portalAccessService);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'tenancy_id' => ['required', 'integer'],
        ]);

        $tenancy = Tenancy::query()
            ->with('property')
            ->forAccount($account->id)
            ->findOrFail($validated['tenancy_id']);

        if (! $tenancy->property) {
            throw ValidationException::withMessages([
                'tenancy_id' => 'That tenancy has no property attached.',
            ]);
        }

        [$invitee, $wasNew] = $this->resolveInvitee($validated['name'], $validated['email'], $actor, $account);

        try {
            $portalAccessService->inviteTenant(
                $account,
                $tenancy->property,
                $invitee,
                $tenancy,
                $actor
            );
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'tenancy_id' => $e->getMessage(),
            ]);
        }

        $mail = app(TenantInviteMailer::class)->send($invitee, $tenancy->property, $wasNew);

        if ($mail['sent']) {
            flash($wasNew
                ? 'Tenant portal access has been granted. We emailed them a link to set a password.'
                : 'Tenant portal access has been granted. We emailed them a login link.'
            )->success();
        } else {
            flash('Tenant portal access has been granted, but the invite email could not be sent.')->warning();
        }

        return redirect()->route('admin.portal-access.index');
    }

    public function revoke(Request $request, User $user)
    {
        $actor = $request->user();
        $account = current_account();

        abort_unless($account, 403);

        $portalAccessService = app(PortalAccessService::class);
        $this->ensureCanManagePortal($actor, $account, $portalAccessService);

        abort_unless((int) $user->id !== (int) $actor->id, 403, 'You cannot revoke your own access.');

        $portalAccessService->revokePortalAccess($account, $user);

        flash('Portal access has been revoked.')->success();

        return redirect()->route('admin.portal-access.index');
    }

    private function ensureCanManagePortal($user, Account $account, PortalAccessService $portalAccessService): void
    {
        abort_unless(
            $user->isSuperAdmin() || $portalAccessService->isAccountOwnerOrAdmin($user, $account->id),
            403
        );
    }

    /**
     * @return array{0: User, 1: bool}
     */
    private function resolveInvitee(string $name, string $email, User $actor, Account $account): array
    {
        $email = strtolower($email);
        $invitee = User::query()->where('email', $email)->first();
        $wasNew = false;

        if ($invitee && $invitee->isSuperAdmin()) {
            throw ValidationException::withMessages([
                'email' => 'That email cannot be invited as a tenant.',
            ]);
        }

        if ($invitee && (int) $invitee->id === (int) $actor->id) {
            throw ValidationException::withMessages([
                'email' => 'You cannot invite yourself as a tenant.',
            ]);
        }

        if ($invitee) {
            $existing = AccountUser::query()
                ->where('account_id', $account->id)
                ->where('user_id', $invitee->id)
                ->first();

            if ($existing && AccountMembership::isWorkspaceOperator($existing->member_type)) {
                throw ValidationException::withMessages([
                    'email' => 'That person already manages this account.',
                ]);
            }
        } else {
            $parts = preg_split('/\s+/', $name, 2) ?: [$name];
            $invitee = User::create([
                'name' => $name,
                'first_name' => $parts[0] ?? $name,
                'last_name' => $parts[1] ?? '',
                'email' => $email,
                'password' => Hash::make(Str::random(32)),
                'status' => 1,
                'can_login' => true,
                'created_by' => $actor->id,
            ]);
            $wasNew = true;
        }

        Role::findOrCreate('Tenant', 'web');

        if (! $invitee->hasRole('Tenant')) {
            $invitee->assignRole('Tenant');
        }

        return [$invitee, $wasNew];
    }
}
