<?php

namespace App\Services\OTP;

use App\Contracts\SendSms;
use Illuminate\Support\Facades\Log;

class Ssl_wireless implements SendSms
{
    public function send(string $to, string $from, string $text, ?string $template_id = null): void
    {
        $token  = env('SSL_SMS_API_TOKEN');
        $sid    = env('SSL_SMS_SID', $from);
        $url    = env('SSL_SMS_URL', 'https://sms.sslwireless.com/pushapi/dynamic/server.php');

        if (!$token) {
            Log::warning("SSL Wireless not configured. OTP for {$to}");
            return;
        }

        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => http_build_query([
                    'api_token' => $token,
                    'sid'       => $sid,
                    'msisdn'    => $to,
                    'sms'       => $text,
                    'csmsid'    => uniqid(),
                ]),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 15,
            ]);
            $response = curl_exec($ch);
            $err      = curl_error($ch);
            curl_close($ch);

            if ($err) { Log::error("SSL Wireless cURL error: {$err}"); return; }
            Log::info("SSL Wireless sent to {$to}: " . $response);
        } catch (\Exception $e) {
            Log::error("SSL Wireless exception: {$e->getMessage()}");
        }
    }
}
