<?php

namespace App\Services\OTP;

use App\Contracts\SendSms;
use Illuminate\Support\Facades\Log;

class Twillo implements SendSms
{
    public function send(string $to, string $from, string $text, ?string $template_id = null): void
    {
        $sid   = env('TWILIO_SID');
        $token = env('TWILIO_AUTH_TOKEN', env('TWILIO_TOKEN'));
        $type  = env('TWILLO_TYPE', '1');
        $fromNumber = env('VALID_TWILLO_NUMBER', env('TWILIO_FROM'));

        if (!$sid || !$token || !$fromNumber) {
            Log::warning("Twilio not configured. OTP for {$to}: {$text}");
            return;
        }

        try {
            if ($type == '1') {
                // SMS
                $url  = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";
                $data = http_build_query(['From' => $fromNumber, 'To' => $to, 'Body' => $text]);
            } else {
                // WhatsApp
                $url  = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";
                $data = http_build_query([
                    'From' => 'whatsapp:' . $fromNumber,
                    'To'   => 'whatsapp:' . $to,
                    'Body' => $text,
                ]);
            }

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $data,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_USERPWD        => "{$sid}:{$token}",
                CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
                CURLOPT_TIMEOUT        => 15,
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err      = curl_error($ch);
            curl_close($ch);

            if ($err) { Log::error("Twilio cURL error: {$err}"); return; }

            $decoded = json_decode($response, true);
            if ($httpCode >= 200 && $httpCode < 300) {
                Log::info("Twilio SMS sent to {$to}");
            } else {
                Log::error("Twilio failed [{$httpCode}] for {$to}: " . json_encode($decoded));
            }
        } catch (\Exception $e) {
            Log::error("Twilio exception: {$e->getMessage()}");
        }
    }
}
