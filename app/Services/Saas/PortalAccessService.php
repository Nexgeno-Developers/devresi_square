<?php

namespace App\Services\Saas;

use App\Models\Account;
use App\Models\AccountUser;
use App\Models\Property;
use App\Models\PropertyParticipant;
use App\Models\Tenancy;
use App\Models\TenantMember;
use App\Models\User;
use App\Support\AccountMembership;
use App\Support\AccountType;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class PortalAccessService
{
    private const ACCESS_LEVELS = [
        'view' => 1,
        'edit' => 2,
        'full' => 3,
    ];

    public function isPortalUser(User $user, ?int $accountId = null): bool
    {
        if ($user->isSuperAdmin()) {
            return false;
        }

        $accountId = $accountId ?: current_account_id();

        if (! $accountId) {
            return false;
        }

        $membership = $this->activeMembership($user, $accountId);

        if (! $membership) {
            return false;
        }

        if (AccountMembership::isWorkspaceAdmin($membership->member_type)) {
            return false;
        }

        $account = Account::query()->find($accountId);

        if (
            $account
            && AccountType::isLandlord($account->account_type)
            && (int) $account->owner_user_id === (int) $user->id
            && ! AccountMembership::isTenant($membership->member_type)
            && $membership->member_type !== AccountMembership::CONTRACTOR
        ) {
            return false;
        }

        return AccountMembership::isPortalType($membership->member_type);
    }

    public function accessiblePropertyIds(User $user, int $accountId): array
    {
        if ($user->isSuperAdmin() || $this->isAccountOwnerOrAdmin($user, $accountId)) {
            return Property::query()
                ->where('account_id', $accountId)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return PropertyParticipant::query()
            ->active()
            ->forAccount($accountId)
            ->forUser($user->id)
            ->pluck('property_id')
            ->unique()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public function canAccessProperty(User $user, Property $property, string $permission = 'view'): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $accountId = (int) ($property->account_id ?: current_account_id());

        if (! $accountId || (int) $property->account_id !== $accountId) {
            return false;
        }

        $membership = $this->activeMembership($user, $accountId);

        if (! $membership) {
            return false;
        }

        if (AccountMembership::isWorkspaceAdmin($membership->member_type)) {
            return true;
        }

        if ($membership->member_type === AccountMembership::STAFF || $user->isStaffAccount()) {
            return true;
        }

        if (! $this->isPortalUser($user, $accountId)) {
            return true;
        }

        $participant = $this->getParticipant($user, $property);

        if (! $participant) {
            return false;
        }

        return $this->accessRank($participant->access_level) >= $this->accessRank($permission);
    }

    public function canEditProperty(User $user, Property $property): bool
    {
        return $this->canAccessProperty($user, $property, 'edit');
    }

    public function canViewFinance(User $user, Property $property): bool
    {
        return $this->allowedByParticipantFlag($user, $property, 'can_view_finance');
    }

    public function canViewDocuments(User $user, Property $property): bool
    {
        return $this->allowedByParticipantFlag($user, $property, 'can_view_documents');
    }

    public function canUploadDocuments(User $user, Property $property): bool
    {
        return $this->allowedByParticipantFlag($user, $property, 'can_upload_documents');
    }

    public function getParticipant(User $user, Property $property): ?PropertyParticipant
    {
        $accountId = (int) ($property->account_id ?: current_account_id());

        if (! $accountId) {
            return null;
        }

        return PropertyParticipant::query()
            ->active()
            ->forAccount($accountId)
            ->forProperty($property->id)
            ->forUser($user->id)
            ->orderByRaw("CASE access_level WHEN 'full' THEN 0 WHEN 'edit' THEN 1 ELSE 2 END")
            ->first();
    }

    public function grantPropertyAccess(
        Account $account,
        Property $property,
        User $user,
        string $participantType,
        string $accessLevel = 'view',
        bool $canViewFinance = false,
        bool $canViewDocuments = false,
        bool $canUploadDocuments = false,
        ?User $createdBy = null
    ): PropertyParticipant {
        $this->validateParticipantType($participantType);
        $this->validateAccessLevel($accessLevel);

        if ((int) $property->account_id !== (int) $account->id) {
            throw new InvalidArgumentException('Property does not belong to this account.');
        }

        if (AccountMembership::isPortalType($participantType)
            && ! app(AccountLimitService::class)->canAddPortalUser($account, $user)
        ) {
            throw ValidationException::withMessages([
                'user' => 'Your current plan has reached the tenant portal user limit.',
            ]);
        }

        AccountUser::updateOrCreate(
            [
                'account_id' => $account->id,
                'user_id' => $user->id,
            ],
            [
                'member_type' => $participantType,
                'access_level' => $accessLevel,
                'can_login' => true,
                'status' => 'active',
                'created_by' => $createdBy?->id,
            ]
        );

        return PropertyParticipant::updateOrCreate(
            [
                'account_id' => $account->id,
                'property_id' => $property->id,
                'user_id' => $user->id,
                'participant_type' => $participantType,
            ],
            [
                'access_level' => $accessLevel,
                'can_view_finance' => $canViewFinance,
                'can_view_documents' => $canViewDocuments,
                'can_upload_documents' => $canUploadDocuments,
                'status' => 'active',
                'created_by' => $createdBy?->id,
            ]
        );
    }

    public function inviteTenant(
        Account $account,
        Property $property,
        User $user,
        ?Tenancy $tenancy = null,
        ?User $createdBy = null
    ): void {
        DB::transaction(function () use ($account, $property, $user, $tenancy, $createdBy) {
            $this->grantPropertyAccess(
                $account,
                $property,
                $user,
                AccountMembership::TENANT,
                'view',
                false,
                true,
                false,
                $createdBy
            );

            if ($tenancy) {
                if ((int) $tenancy->account_id !== (int) $account->id
                    || (int) $tenancy->property_id !== (int) $property->id
                ) {
                    throw new InvalidArgumentException('Tenancy does not belong to this property.');
                }

                TenantMember::updateOrCreate(
                    [
                        'account_id' => $account->id,
                        'tenancy_id' => $tenancy->id,
                        'user_id' => $user->id,
                    ],
                    [
                        'is_main_person' => false,
                        'can_login' => true,
                    ]
                );
            }
        });
    }

    public function revokePortalAccess(Account $account, User $user): void
    {
        DB::transaction(function () use ($account, $user) {
            $membership = AccountUser::query()
                ->where('account_id', $account->id)
                ->where('user_id', $user->id)
                ->first();

            if (! $membership || ! AccountMembership::isPortalType($membership->member_type)) {
                throw new InvalidArgumentException('That person is not a portal user on this account.');
            }

            $membership->forceFill([
                'status' => 'disabled',
                'can_login' => false,
            ])->save();

            PropertyParticipant::query()
                ->where('account_id', $account->id)
                ->where('user_id', $user->id)
                ->update(['status' => 'inactive']);

            TenantMember::query()
                ->where('account_id', $account->id)
                ->where('user_id', $user->id)
                ->update(['can_login' => false]);
        });
    }

    public function isAccountOwnerOrAdmin(User $user, int $accountId): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $membership = $this->activeMembership($user, $accountId);

        return $membership && AccountMembership::isWorkspaceAdmin($membership->member_type);
    }

    private function activeMembership(User $user, int $accountId): ?AccountUser
    {
        return $user->accountUsers()
            ->where('account_id', $accountId)
            ->where('status', 'active')
            ->first();
    }

    private function allowedByParticipantFlag(User $user, Property $property, string $flag): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $accountId = (int) ($property->account_id ?: current_account_id());

        if (! $accountId || (int) $property->account_id !== $accountId) {
            return false;
        }

        $membership = $this->activeMembership($user, $accountId);

        if (! $membership) {
            return false;
        }

        if (AccountMembership::isWorkspaceAdmin($membership->member_type)) {
            return true;
        }

        if ($membership->member_type === AccountMembership::STAFF || $user->isStaffAccount()) {
            return true;
        }

        $participant = $this->getParticipant($user, $property);

        return $participant && (bool) $participant->{$flag};
    }

    private function accessRank(string $accessLevel): int
    {
        return self::ACCESS_LEVELS[$accessLevel] ?? 0;
    }

    private function validateParticipantType(string $participantType): void
    {
        $valid = [
            AccountMembership::LANDLORD_CONTACT,
            AccountMembership::OWNER,
            AccountMembership::TENANT,
            AccountMembership::CONTRACTOR,
            AccountMembership::PROPERTY_MANAGER,
            AccountMembership::STAFF,
        ];

        if (! in_array($participantType, $valid, true)) {
            throw new InvalidArgumentException('Invalid participant type.');
        }
    }

    private function validateAccessLevel(string $accessLevel): void
    {
        if (! array_key_exists($accessLevel, self::ACCESS_LEVELS)) {
            throw new InvalidArgumentException('Invalid access level.');
        }
    }
}
