<?php

namespace App\Services\OTP;

use App\Contracts\SendSms;
use Illuminate\Support\Facades\Log;

class Mimo implements SendSms
{
    public function send(string $to, string $from, string $text, ?string $template_id = null): void
    {
        $username = env('MIMO_USERNAME');
        $password = env('MIMO_PASSWORD');
        $senderId = env('MIMO_SENDER_ID', $from);

        if (!$username || !$password) {
            Log::warning("Mimo not configured. OTP for {$to}");
            return;
        }

        $base = 'http://52.30.114.86:8080/mimosms/v1/';

        try {
            // Step 1: Login
            $ch = curl_init($base . 'login?username=' . urlencode($username) . '&password=' . urlencode($password));
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
            $loginResp = json_decode(curl_exec($ch), true);
            curl_close($ch);

            $token = $loginResp['token'] ?? null;
            if (!$token) { Log::error("Mimo login failed"); return; }

            // Step 2: Send
            $ch = curl_init($base . 'message/send');
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode([
                    'token'    => $token,
                    'from'     => $senderId,
                    'to'       => $to,
                    'message'  => $text,
                ]),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT        => 15,
            ]);
            $sendResp = curl_exec($ch);
            curl_close($ch);
            Log::info("Mimo sent to {$to}: " . $sendResp);

            // Step 3: Logout
            $ch = curl_init($base . 'logout?token=' . urlencode($token));
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
            curl_exec($ch);
            curl_close($ch);
        } catch (\Exception $e) {
            Log::error("Mimo exception: {$e->getMessage()}");
        }
    }
}
