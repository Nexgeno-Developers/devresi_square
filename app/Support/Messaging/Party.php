<?php

namespace App\Support\Messaging;

use App\Models\Account;
use App\Models\AccountUser;
use App\Models\User;
use App\Support\AccountMembership;
use App\Support\AccountType;
use InvalidArgumentException;

/**
 * Who sends or receives a message: a SaaS account (shared inbox for its
 * owner, admins and staff) or one person (tenant, contractor, portal contact).
 */
final class Party
{
    public const ACCOUNT = 'account';

    public const USER = 'user';

    /** Placeholder target for "message by email" requests that matched nobody. */
    public const EMAIL = 'email';

    private ?string $cachedName = null;

    public function __construct(
        public readonly string $type,
        public readonly int $id,
    ) {
        if (! in_array($type, [self::ACCOUNT, self::USER], true)) {
            throw new InvalidArgumentException("Unknown party type [{$type}].");
        }
    }

    public static function account(int $id): self
    {
        return new self(self::ACCOUNT, $id);
    }

    public static function user(int $id): self
    {
        return new self(self::USER, $id);
    }

    public static function make(string $type, int $id): self
    {
        return new self($type, $id);
    }

    public function key(): string
    {
        return $this->type.':'.$this->id;
    }

    public function is(?self $other): bool
    {
        return $other !== null && $other->type === $this->type && $other->id === $this->id;
    }

    public function isAccount(): bool
    {
        return $this->type === self::ACCOUNT;
    }

    public static function pairKey(self $a, self $b): string
    {
        $keys = [$a->key(), $b->key()];
        sort($keys);

        return implode('|', $keys);
    }

    /** Pair key for a request sent to an email address that matched nobody. */
    public static function emailPairKey(self $from, string $email): string
    {
        return $from->key().'|email:'.substr(hash('sha256', mb_strtolower(trim($email))), 0, 40);
    }

    /** User ids allowed to read and write for this party right now. */
    public function userIds(): array
    {
        if ($this->type === self::USER) {
            return [$this->id];
        }

        return AccountUser::query()
            ->where('account_id', $this->id)
            ->where('status', 'active')
            ->whereIn('member_type', AccountMembership::WORKSPACE_OPERATOR_TYPES)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function actsFor(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return false;
        }

        return in_array((int) $user->id, $this->userIds(), true);
    }

    /** Whether this party can currently log in and take part. */
    public function isActive(): bool
    {
        if ($this->type === self::ACCOUNT) {
            $account = Account::query()->find($this->id);

            return $account !== null && ! in_array($account->status, ['suspended', 'cancelled'], true);
        }

        $user = User::query()->find($this->id);
        if (! $user || $user->isSuperAdmin() || (isset($user->status) && (string) $user->status === '0')) {
            return false;
        }

        if ($user->hasRole('Contractor')) {
            return true;
        }

        return AccountUser::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->where('can_login', true)
                    ->orWhereIn('member_type', AccountMembership::WORKSPACE_OPERATOR_TYPES);
            })
            ->exists();
    }

    public function displayName(): string
    {
        if ($this->cachedName !== null) {
            return $this->cachedName;
        }

        if ($this->type === self::ACCOUNT) {
            return $this->cachedName = (string) (Account::query()->whereKey($this->id)->value('account_name') ?: 'Workspace');
        }

        $user = User::query()->with('contractorProfile')->find($this->id);
        if (! $user) {
            return $this->cachedName = 'Former user';
        }
        if ($user->hasRole('Contractor') && $user->contractorProfile) {
            return $this->cachedName = $user->contractorProfile->displayName();
        }

        return $this->cachedName = (string) ($user->name ?: trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'User');
    }

    /** Short role label shown next to the name, e.g. "Letting agent" or "Tenant". */
    public function roleLabel(): string
    {
        if ($this->type === self::ACCOUNT) {
            $type = Account::query()->whereKey($this->id)->value('account_type');

            return AccountType::isEstateAgent($type) ? 'Letting agent' : 'Landlord';
        }

        $user = User::query()->find($this->id);
        if ($user?->hasRole('Contractor')) {
            return 'Contractor';
        }

        $types = AccountUser::query()->where('user_id', $this->id)->where('status', 'active')->pluck('member_type')->all();
        if (in_array(AccountMembership::TENANT, $types, true)) {
            return 'Tenant';
        }
        if (in_array(AccountMembership::PROPERTY_MANAGER, $types, true)) {
            return 'Property manager';
        }
        if (array_intersect([AccountMembership::OWNER_CONTACT, AccountMembership::LANDLORD_CONTACT], $types)) {
            return 'Landlord';
        }

        return 'Contact';
    }
}
