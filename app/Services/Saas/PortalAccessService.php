<?php

namespace App\Services\Saas;

use App\Models\Account;
use App\Models\AccountUser;
use App\Models\Property;
use App\Models\PropertyParticipant;
use App\Models\User;
use InvalidArgumentException;

class PortalAccessService
{
    private const PORTAL_MEMBER_TYPES = [
        'contact',
        'landlord',
        'owner_contact',
        'tenant',
        'contractor',
        'property_manager',
    ];

    private const ACCOUNT_ADMIN_TYPES = ['owner', 'admin'];

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

        $query = $user->accountUsers()
            ->where('status', 'active')
            ->whereIn('member_type', self::PORTAL_MEMBER_TYPES);

        if ($accountId) {
            $query->where('account_id', $accountId);
        }

        return $query->exists();
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

        if (in_array($membership->member_type, self::ACCOUNT_ADMIN_TYPES, true)) {
            return true;
        }

        if ($membership->member_type === 'staff' || $user->isStaffAccount()) {
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

    public function isAccountOwnerOrAdmin(User $user, int $accountId): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $membership = $this->activeMembership($user, $accountId);

        return $membership && in_array($membership->member_type, self::ACCOUNT_ADMIN_TYPES, true);
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

        if (in_array($membership->member_type, self::ACCOUNT_ADMIN_TYPES, true)) {
            return true;
        }

        if ($membership->member_type === 'staff' || $user->isStaffAccount()) {
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
        $valid = ['landlord', 'owner', 'tenant', 'contractor', 'property_manager', 'staff'];

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
