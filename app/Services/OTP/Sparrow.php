<?php

namespace App\Services\OTP;

use App\Contracts\SendSms;
use Illuminate\Support\Facades\Log;

class Sparrow implements SendSms
{
    public function send(string $to, string $from, string $text, ?string $template_id = null): void
    {
        $token  = env('SPARROW_TOKEN');
        $sender = env('MESSGAE_FROM', $from); // note: typo kept intentionally to match motiwala

        if (!$token) {
            Log::warning("Sparrow SMS not configured. OTP for {$to}");
            return;
        }

        // Strip +977 prefix for Nepal
        $mobile = preg_replace('/[^\d]/', '', $to);
        if (str_starts_with($mobile, '977')) {
            $mobile = substr($mobile, 3);
        }

        try {
            $ch = curl_init('http://api.sparrowsms.com/v2/sms/');
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => http_build_query([
                    'token'  => $token,
                    'from'   => $sender,
                    'to'     => $mobile,
                    'text'   => $text,
                ]),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 15,
            ]);
            $response = curl_exec($ch);
            $err      = curl_error($ch);
            curl_close($ch);

            if ($err) { Log::error("Sparrow cURL error: {$err}"); return; }
            Log::info("Sparrow SMS sent to {$mobile}: " . $response);
        } catch (\Exception $e) {
            Log::error("Sparrow exception: {$e->getMessage()}");
        }
    }
}
