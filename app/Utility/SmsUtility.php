<?php

namespace App\Utility;

use App\Models\SmsTemplate;
use App\Services\SendSmsService;
use Illuminate\Support\Facades\Log;

class SmsUtility
{
    /**
     * Replace [[placeholder]] tokens in a template body.
     */
    private static function render(string $body, array $replacements): string
    {
        foreach ($replacements as $key => $value) {
            $body = str_replace('[[' . $key . ']]', $value, $body);
        }
        return $body;
    }

    /**
     * Core dispatcher — fetches template, renders, sends.
     */
    private static function dispatch(string $identifier, string $to, array $replacements): void
    {
        $template = SmsTemplate::getByIdentifier($identifier);

        if (!$template) {
            Log::warning("SMS template not found or disabled: {$identifier}");
            return;
        }

        $appName = config('app.name');
        $body    = self::render($template->sms_body, array_merge(
            ['site_name' => $appName],
            $replacements
        ));

        (new SendSmsService)->sendSMS(
            $to,
            $appName,
            $body,
            $template->template_id
        );
    }

    /**
     * Send phone number verification OTP.
     * Template: phone_number_verification — uses [[code]], [[site_name]]
     */
    public static function phone_number_verification(string $phone, string $otp): void
    {
        self::dispatch('phone_number_verification', $phone, ['code' => $otp]);
    }

    /**
     * Send password reset OTP.
     * Template: password_reset — uses [[code]]
     */
    public static function password_reset(string $phone, string $otp): void
    {
        self::dispatch('password_reset', $phone, ['code' => $otp]);
    }

    /**
     * Send account opening SMS.
     * Template: account_opening — uses [[site_name]], [[code]], [[password]]
     */
    public static function account_opening(string $phone, string $otp, string $password = ''): void
    {
        self::dispatch('account_opening', $phone, ['code' => $otp, 'password' => $password]);
    }
}
