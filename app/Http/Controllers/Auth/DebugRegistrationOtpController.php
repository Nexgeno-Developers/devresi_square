<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DebugRegistrationOtpController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        abort_unless($this->debugOtpEnabled(), 404);

        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $registration = Registration::query()
            ->where('email', $validated['email'])
            ->latest('id')
            ->first();

        if (! $registration) {
            return response()->json([
                'found' => false,
                'message' => 'No registration found for that email.',
            ], 404);
        }

        return response()->json([
            'found' => true,
            'id' => $registration->id,
            'email' => $registration->email,
            'otp' => $registration->otp_code,
            'expires_at' => optional($registration->otp_expires_at)?->toIso8601String(),
            'expired' => $registration->otp_expires_at
                ? now()->isAfter($registration->otp_expires_at)
                : null,
            'status' => $registration->status,
            'verify_via' => $registration->verify_via,
        ]);
    }

    public static function debugOtpEnabled(): bool
    {
        return app()->environment('local')
            && (bool) config('app.debug')
            && filter_var(env('REGISTRATION_OTP_DEBUG', false), FILTER_VALIDATE_BOOLEAN);
    }
}
