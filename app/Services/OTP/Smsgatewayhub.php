<?php

namespace App\Services\OTP;

use App\Contracts\SendSms;
use Illuminate\Support\Facades\Log;

class Smsgatewayhub implements SendSms
{
    public function send(string $to, string $from, string $text, ?string $template_id = null): void
    {
        $apiKey   = env('SMSGHUB_API_KEY');
        $sender   = env('SMSGHUB_SENDER', $from);

        if (!$apiKey) {
            Log::warning("SMSGatewayHub not configured. OTP for {$to}");
            return;
        }

        // Strip leading +
        $mobile = ltrim($to, '+');

        try {
            $params = http_build_query([
                'APIKey'      => $apiKey,
                'senderid'    => $sender,
                'channel'     => 'Trans',
                'DCS'         => '0',
                'flashsms'    => '0',
                'number'      => $mobile,
                'text'        => $text,
                'route'       => 'Transactional',
            ]);

            $ch = curl_init('https://www.smsgatewayhub.com/api/mt/SendSMS?' . $params);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPGET        => true,
                CURLOPT_TIMEOUT        => 15,
            ]);
            $response = curl_exec($ch);
            $err      = curl_error($ch);
            curl_close($ch);

            if ($err) { Log::error("SMSGatewayHub cURL error: {$err}"); return; }
            Log::info("SMSGatewayHub sent to {$mobile}: " . $response);
        } catch (\Exception $e) {
            Log::error("SMSGatewayHub exception: {$e->getMessage()}");
        }
    }
}
