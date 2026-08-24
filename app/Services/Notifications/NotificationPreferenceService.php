<?php

namespace App\Services\Notifications;

use App\Models\NotificationPreference;
use App\Models\User;

class NotificationPreferenceService
{
    public function channels(int $accountId, User $user, string $eventKey, array $definition): array
    {
        $channels = collect($definition['channels'] ?? ['system']);
        $account = NotificationPreference::query()
            ->where('account_id', $accountId)->whereNull('user_id')->where('event_key', $eventKey)->first();
        $personal = NotificationPreference::query()
            ->where('account_id', $accountId)->where('user_id', $user->id)->where('event_key', $eventKey)->first();

        $enabled = function (string $channel) use ($account, $personal, $channels): bool {
            $field = $channel === 'email' ? 'email_enabled' : 'in_app_enabled';
            $value = $channels->contains($channel);
            if ($account && $account->{$field} !== null) {
                $value = (bool) $account->{$field};
            }
            if ($personal && $personal->{$field} !== null) {
                $value = (bool) $personal->{$field};
            }
            return $value;
        };

        $resolved = collect(['email', 'system'])->filter(fn (string $channel) => $enabled($channel));
        $resolved = $resolved->merge($definition['locked_channels'] ?? [])->unique();

        if ($resolved->contains('email') && blank($user->email)) {
            $resolved = $resolved->reject(fn ($channel) => $channel === 'email');
        }

        if ($resolved->contains('system') && ! $this->canUsePortal($user, $accountId)) {
            $resolved = $resolved->reject(fn ($channel) => $channel === 'system');
        }

        return $resolved->values()->all();
    }

    private function canUsePortal(User $user, int $accountId): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->accountUsers()
            ->where('account_id', $accountId)
            ->where('status', 'active')
            ->where('can_login', true)
            ->exists();
    }
}
