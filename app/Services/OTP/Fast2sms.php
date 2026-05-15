<?php

namespace App\Services\OTP;

use App\Contracts\SendSms;
use Illuminate\Support\Facades\Log;

class Fast2sms implements SendSms
{
    public function send(string $to, string $from, string $text, ?string $template_id = null): void
    {
        // Fast2SMS needs 10-digit number — strip +91
        $mobile = preg_replace('/[^\d]/', '', $to);
        if (strlen($mobile) === 12 && str_starts_with($mobile, '91')) {
            $mobile = substr($mobile, 2);
        }

        $route    = env('ROUTE', 'q');
        $authKey  = env('AUTH_KEY', env('FAST2SMS_API_KEY'));
        $senderId = env('SENDER_ID', '');
        $language = env('LANGUAGE', 'english');

        $payload = [
            'authorization' => $authKey,
            'route'         => $route,
            'numbers'       => $mobile,
            'flash'         => '0',
        ];

        if ($route === 'dlt_manual' || $route === 'dlt') {
            // DLT route — uses template_id
            $payload['sender_id']   = $senderId;
            $payload['message']     = $text;
            $payload['entity_id']   = env('ENTITY_ID', '');
            $payload['template_id'] = $template_id ?? '';
        } else {
            // Quick SMS route
            $payload['message']  = $text;
            $payload['language'] = $language;
        }

        try {
            $ch = curl_init('https://www.fast2sms.com/dev/bulkV2?' . http_build_query($payload));
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPGET        => true,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_HTTPHEADER     => ['Cache-Control: no-cache'],
            ]);
            $response = curl_exec($ch);
            $err      = curl_error($ch);
            curl_close($ch);

            if ($err) {
                Log::error("Fast2SMS cURL error: {$err}");
                return;
            }

            $decoded = json_decode($response, true);
            if (!empty($decoded['return'])) {
                Log::info("Fast2SMS sent to {$mobile}");
            } else {
                Log::error("Fast2SMS failed for {$mobile}: " . json_encode($decoded));
            }
        } catch (\Exception $e) {
            Log::error("Fast2SMS exception: {$e->getMessage()}");
        }
    }
}
