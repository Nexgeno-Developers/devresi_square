<?php

namespace App\Http\Controllers\Backend;

use App\Models\OtpConfiguration;
use App\Models\SmsTemplate;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class OTPController extends Controller
{
    // ─── List all providers ───────────────────────────────────────────────────
    public function configure_index()
    {
        $otpConfigurations = OtpConfiguration::all();
        $smsTemplates      = SmsTemplate::all();
        return view('backend.otp.index', compact('otpConfigurations', 'smsTemplates'));
    }

    // ─── Switch active provider ───────────────────────────────────────────────
    public function updateActivationSettings(Request $request)
    {
        $request->validate([
            'otp_type' => 'required|string|exists:otp_configurations,type',
        ]);

        // Deactivate all, activate selected
        OtpConfiguration::query()->update(['value' => 0]);
        OtpConfiguration::where('type', $request->otp_type)->update(['value' => 1]);

        flash('SMS provider switched to ' . $request->otp_type)->success();
        return back();
    }

    // ─── Update .env credentials ──────────────────────────────────────────────
    public function update_credentials(Request $request)
    {
        $keys = [
            // Fast2SMS
            'AUTH_KEY', 'SENDER_ID', 'LANGUAGE', 'ROUTE', 'ENTITY_ID',
            // Twilio
            'TWILIO_SID', 'TWILIO_AUTH_TOKEN', 'TWILLO_TYPE', 'VALID_TWILLO_NUMBER',
            // Nexmo
            'NEXMO_KEY', 'NEXMO_SECRET', 'NEXMO_SENDER_ID',
            // MimSMS
            'MIM_USER_NAME', 'MIM_API_KEY', 'MIM_SENDER_ID',
            // Mimo
            'MIMO_USERNAME', 'MIMO_PASSWORD', 'MIMO_SENDER_ID',
            // Msegat
            'MSEGAT_API_KEY', 'MSEGAT_USERNAME', 'MSEGAT_USER_SENDER',
            // SMSGatewayHub
            'SMSGHUB_API_KEY', 'SMSGHUB_SENDER',
            // Sparrow
            'SPARROW_TOKEN', 'MESSGAE_FROM',
            // SSL Wireless
            'SSL_SMS_API_TOKEN', 'SSL_SMS_SID', 'SSL_SMS_URL',
            // Zender
            'ZENDER_APIKEY', 'ZENDER_SITEURL', 'ZENDER_SERVICE',
            'ZENDER_DEVICE', 'ZENDER_SIM', 'ZENDER_GATEWAY', 'ZENDER_WHATSAPP',
        ];

        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);

        foreach ($keys as $key) {
            if ($request->has($key)) {
                $value = $request->input($key);
                $escaped = preg_match('/\s/', $value) ? '"' . $value . '"' : $value;

                if (preg_match("/^{$key}=/m", $envContent)) {
                    $envContent = preg_replace("/^{$key}=.*/m", "{$key}={$escaped}", $envContent);
                } else {
                    $envContent .= "\n{$key}={$escaped}";
                }
            }
        }

        file_put_contents($envPath, $envContent);

        flash('SMS credentials updated successfully.')->success();
        return back();
    }
}
