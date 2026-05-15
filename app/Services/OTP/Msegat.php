<?php

namespace App\Services\OTP;

use App\Contracts\SendSms;
use Illuminate\Support\Facades\Log;

class Msegat implements SendSms
{
    public function send(string $to, string $from, string $text, ?string $template_id = null): void
    {
        $apiKey   = env('MSEGAT_API_KEY');
        $username = env('MSEGAT_USERNAME');
        $sender   = env('MSEGAT_USER_SENDER', $from);

        if (!$apiKey || !$username) {
            Log::warning("Msegat not configured. OTP for {$to}");
            return;
        }

        try {
            $ch = curl_init('https://www.msegat.com/gw/sendsms.php');
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode([
                    'userName'   => $username,
                    'numbers'    => $to,
                    'userSender' => $sender,
                    'apiKey'     => $apiKey,
                    'msg'        => $text,
                ]),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT        => 15,
            ]);
            $response = curl_exec($ch);
            $err      = curl_error($ch);
            curl_close($ch);

            if ($err) { Log::error("Msegat cURL error: {$err}"); return; }
            Log::info("Msegat sent to {$to}: " . $response);
        } catch (\Exception $e) {
            Log::error("Msegat exception: {$e->getMessage()}");
        }
    }
}
