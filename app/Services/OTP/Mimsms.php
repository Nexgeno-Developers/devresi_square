<?php

namespace App\Services\OTP;

use App\Contracts\SendSms;
use Illuminate\Support\Facades\Log;

class Mimsms implements SendSms
{
    public function send(string $to, string $from, string $text, ?string $template_id = null): void
    {
        $username = env('MIM_USER_NAME');
        $apiKey   = env('MIM_API_KEY');
        $senderId = env('MIM_SENDER_ID', $from);

        if (!$username || !$apiKey) {
            Log::warning("MimSMS not configured. OTP for {$to}");
            return;
        }

        try {
            $ch = curl_init('https://api.mimsms.com/api/SmsSending/SMS');
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode([
                    'UserName'  => $username,
                    'ApiKey'    => $apiKey,
                    'SenderId'  => $senderId,
                    'Message'   => $text,
                    'MobileNo'  => $to,
                ]),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT        => 15,
            ]);
            $response = curl_exec($ch);
            $err      = curl_error($ch);
            curl_close($ch);

            if ($err) { Log::error("MimSMS cURL error: {$err}"); return; }
            Log::info("MimSMS sent to {$to}: " . $response);
        } catch (\Exception $e) {
            Log::error("MimSMS exception: {$e->getMessage()}");
        }
    }
}
