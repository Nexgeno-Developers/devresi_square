<?php

namespace App\Services;

use App\Models\OtpConfiguration;
use Illuminate\Support\Facades\Log;

class SendSmsService
{
    /**
     * Send an SMS via the currently active provider.
     *
     * @param  string  $to          Recipient phone with country code e.g. +919876543210
     * @param  string  $from        Sender name / app name
     * @param  string  $text        Message body
     * @param  string|null  $template_id  DLT template ID (India only)
     */
    public function sendSMS(string $to, string $from, string $text, ?string $template_id = null): void
    {
        $active = OtpConfiguration::activeProvider();

        if (!$active) {
            Log::warning("No active SMS provider configured. OTP for {$to}: {$text}");
            return;
        }

        // Convert provider key to class name:
        // fast2sms → Fast2sms | ssl_wireless → Ssl_wireless | twillo → Twillo
        $providerKey = $active->type;
        $className   = str_replace(' ', '', ucwords(str_replace('_', ' ', $providerKey)));
        $fqcn        = "App\\Services\\OTP\\{$className}";

        if (!class_exists($fqcn)) {
            Log::error("SMS provider class not found: {$fqcn}");
            return;
        }

        try {
            (new $fqcn)->send($to, $from, $text, $template_id);
        } catch (\Exception $e) {
            Log::error("SMS dispatch exception [{$fqcn}]: {$e->getMessage()}");
        }
    }
}
