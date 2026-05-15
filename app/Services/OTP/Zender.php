<?php

namespace App\Services\OTP;

use App\Contracts\SendSms;
use Illuminate\Support\Facades\Log;

class Zender implements SendSms
{
    public function send(string $to, string $from, string $text, ?string $template_id = null): void
    {
        $apiKey   = env('ZENDER_APIKEY');
        $siteUrl  = rtrim(env('ZENDER_SITEURL', ''), '/');
        $service  = (int) env('ZENDER_SERVICE', 0); // 0/1 = SMS, 2 = WhatsApp
        $device   = env('ZENDER_DEVICE', '');
        $sim      = env('ZENDER_SIM', '1');
        $gateway  = env('ZENDER_GATEWAY', '');
        $whatsapp = env('ZENDER_WHATSAPP', '');

        if (!$apiKey || !$siteUrl) {
            Log::warning("Zender not configured. OTP for {$to}");
            return;
        }

        try {
            if ($service == 2) {
                // WhatsApp
                $url     = $siteUrl . '/api/send/whatsapp';
                $payload = ['secret' => $apiKey, 'account' => $whatsapp, 'recipient' => $to, 'type' => 'text', 'message' => $text];
            } elseif ($service == 1) {
                // Credits-based SMS
                $url     = $siteUrl . '/api/send/sms';
                $payload = ['secret' => $apiKey, 'mode' => 'credits', 'gateway' => $gateway, 'phone' => $to, 'message' => $text];
            } else {
                // Device-based SMS
                $url     = $siteUrl . '/api/send/sms';
                $payload = ['secret' => $apiKey, 'mode' => 'devices', 'device' => $device, 'sim' => $sim, 'phone' => $to, 'message' => $text];
            }

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($payload),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT        => 15,
            ]);
            $response = curl_exec($ch);
            $err      = curl_error($ch);
            curl_close($ch);

            if ($err) { Log::error("Zender cURL error: {$err}"); return; }
            Log::info("Zender sent to {$to}: " . $response);
        } catch (\Exception $e) {
            Log::error("Zender exception: {$e->getMessage()}");
        }
    }
}
