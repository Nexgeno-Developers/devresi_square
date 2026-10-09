<?php

namespace App\Services\Contractors;

use App\Models\User;
use App\Services\SendSmsService;
use App\Support\ContractorSettings;
use Illuminate\Support\Facades\Log;

/** Text messages are reserved for emergency jobs. */
class ContractorSms
{
    public function __construct(private readonly SendSmsService $sms) {}

    public function enabled(): bool
    {
        return (bool) ContractorSettings::get('emergency_sms');
    }

    public function send(?User $user, string $text, ?string $phone = null): bool
    {
        if (! $this->enabled()) {
            return false;
        }
        $to = self::normalise($phone ?: $user?->contractorProfile?->phone ?: $user?->phone);
        if (! $to) {
            return false;
        }
        try {
            $this->sms->sendSMS($to, (string) config('app.name'), mb_substr($text, 0, 300));

            return true;
        } catch (\Throwable $exception) {
            Log::error('Contractor SMS failed', ['user_id' => $user?->id, 'error' => $exception->getMessage()]);

            return false;
        }
    }

    /** UK numbers to E.164, e.g. 07123 456789 → +447123456789. */
    public static function normalise(?string $phone): ?string
    {
        $digits = preg_replace('/[^\d+]/', '', (string) $phone);
        if ($digits === '' || $digits === null) {
            return null;
        }
        if (str_starts_with($digits, '+')) {
            return strlen($digits) >= 11 ? $digits : null;
        }
        if (str_starts_with($digits, '00')) {
            return '+'.substr($digits, 2);
        }
        if (str_starts_with($digits, '44')) {
            return '+'.$digits;
        }
        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            return '+44'.substr($digits, 1);
        }

        return null;
    }
}
