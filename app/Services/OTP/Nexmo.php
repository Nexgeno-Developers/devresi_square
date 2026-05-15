<?php

namespace App\Services\OTP;

use App\Contracts\SendSms;
use Illuminate\Support\Facades\Log;

class Nexmo implements SendSms
{
    public function send(string $to, string $from, string $text, ?string $template_id = null): void
    {
        $key      = env('NEXMO_KEY');
        $secret   = env('NEXMO_SECRET');
        $senderId = env('NEXMO_SENDER_ID', $from);

        if (!$key || !$secret) {
            Log::warning("Nexmo not configured. OTP for {$to}");
            return;
        }

        try {
            $data = http_build_query([
                'api_key'    => $key,
                'api_secret' => $secret,
                'to'         => $to,
                'from'       => $senderId,
                'text'       => $text,
            ]);

            $ch = curl_init('https://rest.nexmo.com/sms/json');
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $data,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 15,
            ]);
            $response = curl_exec($ch);
            $err      = curl_error($ch);
            curl_close($ch);

            if ($err) { Log::error("Nexmo cURL error: {$err}"); return; }

            $decoded = json_decode($response, true);
            $status  = $decoded['messages'][0]['status'] ?? '1';
            if ($status === '0') {
                Log::info("Nexmo SMS sent to {$to}");
            } else {
                Log::error("Nexmo failed for {$to}: " . json_encode($decoded));
            }
        } catch (\Exception $e) {
            Log::error("Nexmo exception: {$e->getMessage()}");
        }
    }
}
