<?php

namespace App\Support;

use App\Models\BusinessSetting;
use Illuminate\Support\Facades\Cache;

class ContractorSettings
{
    private const PREFIX = 'contractor_marketplace.';

    public static function get(string $key): mixed
    {
        $default = config('contractor_marketplace.defaults.'.$key);
        $stored = get_setting(self::PREFIX.$key);
        if ($stored === null || $stored === '') {
            return $default;
        }
        if (is_array($default)) {
            $decoded = json_decode((string) $stored, true);

            if (! is_array($decoded)) {
                return $default;
            }

            return array_is_list($default) ? $decoded : array_replace($default, $decoded);
        }

        return is_numeric($stored) ? $stored + 0 : $stored;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            BusinessSetting::query()->updateOrCreate(
                ['type' => self::PREFIX.$key],
                ['value' => is_array($value) ? json_encode($value) : (string) $value]
            );
        }
        Cache::forget('business_settings');
    }

    public static function plMinimum(): float
    {
        return (float) self::get('public_liability_minimum');
    }

    public static function elMinimum(): float
    {
        return (float) self::get('employers_liability_minimum');
    }

    public static function maxPerSend(): int
    {
        return max(1, (int) self::get('max_contractors_per_send'));
    }

    public static function maxPerEmergencySend(): int
    {
        return max(1, (int) self::get('max_contractors_per_emergency_send'));
    }

    public static function inviteHours(string $urgency): int
    {
        return max(1, (int) (self::get('invite_hours')[$urgency] ?? 72));
    }

    public static function offerValidHours(string $urgency): int
    {
        return max(1, (int) (self::get('offer_valid_hours')[$urgency] ?? 48));
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function tradeDocuments(): array
    {
        $map = self::get('trade_documents');

        return is_array($map) ? array_map(fn ($docs) => array_values(array_filter((array) $docs)), $map) : [];
    }
}
