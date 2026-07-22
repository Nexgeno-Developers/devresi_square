<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\MailManager;
use App\Models\Plan;
use App\Models\Registration;
use App\Models\User;
use App\Services\Saas\AccountProvisioningService;
use App\Services\Saas\AccountWelcomeEmailService;
use App\Services\Saas\CurrentAccountService;
use App\Services\Saas\StripeCheckoutService;
use App\Utility\SmsUtility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class RegistrationController extends Controller
{
    private const ACCOUNT_TYPES = [
        'landlord',
        'estate_agent_freelance',
        'estate_agent_company',
    ];

    private const BILLING_CYCLES = [
        'monthly',
        'annual',
    ];

    private const ACCOUNT_TYPE_TO_REGISTRATION_TYPE = [
        'landlord' => 'landlord',
        'estate_agent_freelance' => 'estate_agent',
        'estate_agent_company' => 'estate_agent',
    ];

    // ─── Step 1: Show registration form ──────────────────────────────────────
    public function showForm(Request $request)
    {
        $selectedPlan = null;
        $selectedBillingCycle = null;
        $selectedAccountType = null;
        $suggestedRegistrationType = null;

        if (! $request->filled('plan_id')) {
            return redirect()
                ->route('pricing')
                ->with('info', 'Choose a plan to create your account.');
        }

        if ($request->filled('plan_id')) {
            $selectedPlan = Plan::query()
                ->where('is_active', 1)
                ->find($request->plan_id);

            $selectedBillingCycle = $request->input('billing_cycle');
            $selectedAccountType = $request->input('account_type');

            if (
                ! $selectedPlan
                || ! in_array($selectedBillingCycle, self::BILLING_CYCLES, true)
                || $selectedAccountType !== $selectedPlan->target_account_type
            ) {
                return redirect()
                    ->route('pricing')
                    ->with('error', 'Selected plan is not available.');
            }

            $suggestedRegistrationType = self::ACCOUNT_TYPE_TO_REGISTRATION_TYPE[$selectedAccountType] ?? null;
        }

        return view('frontend.register', compact(
            'selectedPlan',
            'selectedBillingCycle',
            'selectedAccountType',
            'suggestedRegistrationType'
        ));
    }

    // ─── Step 2: Submit form → save, send OTP, return JSON for AJAX ──────────
    public function submit(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'email'      => [
                'required',
                'email:rfc,dns',
                // Allow reapply if previous registration was rejected
                \Illuminate\Validation\Rule::unique('registrations', 'email')->where(fn ($q) =>
                    $q->whereNotIn('status', ['rejected'])
                ),
                'unique:users,email',
            ],
            'phone'      => [
                'nullable',
                'string',
                'regex:/^\+?[0-9\s\-\(\)]{7,20}$/',
            ],
            'type'       => 'required|in:landlord,owner,estate_agent,contractor',
            'verify_via' => 'required|in:email,phone',
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'plan_id' => ['required', 'integer'],
            'billing_cycle' => ['required', Rule::in(self::BILLING_CYCLES)],
            'account_type' => ['required', Rule::in(self::ACCOUNT_TYPES)],
        ], [
            'email.email'           => 'Please enter a valid email address.',
            'email.unique'          => 'This email is already registered.',
            'phone.regex'           => 'Please enter a valid phone number.',
            'type.in'               => 'Please select a valid type.',
            'verify_via.required'   => 'Please choose how to verify your identity.',
        ]);

        $selectedPlan = null;

        // Extra: phone required when verify_via = phone
        $validator->after(function ($v) use ($request, &$selectedPlan) {
            if ($request->verify_via === 'phone' && empty(trim($request->phone ?? ''))) {
                $v->errors()->add('phone', 'Phone number is required when verifying via phone.');
            }

            if (! $request->filled('plan_id')) {
                return;
            }

            $selectedPlan = Plan::query()
                ->where('is_active', 1)
                ->find($request->plan_id);

            if (! $selectedPlan) {
                $v->errors()->add('plan_id', 'Selected plan is not available.');
                return;
            }

            if (! in_array($request->billing_cycle, self::BILLING_CYCLES, true)) {
                $v->errors()->add('billing_cycle', 'Please choose monthly or annual billing.');
            }

            if ($request->account_type !== $selectedPlan->target_account_type) {
                $v->errors()->add('account_type', 'Selected account type does not match the selected plan.');
            }

            $expectedType = self::ACCOUNT_TYPE_TO_REGISTRATION_TYPE[$selectedPlan->target_account_type] ?? null;
            if ($expectedType && $request->type !== $expectedType) {
                $v->errors()->add('type', 'Please choose the registration type that matches the selected plan.');
            }
        });

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $registration = Registration::create([
            'first_name'     => $request->first_name,
            'last_name'      => $request->last_name,
            'email'          => $request->email,
            'phone'          => $request->phone,
            'type'           => $request->type,
            'verify_via'     => $request->verify_via,
            'password_hash'  => Hash::make($request->password),
            'otp_code'       => $otp,
            'otp_expires_at' => now()->addMinutes(2), // 2-minute expiry
            'status'         => 'pending',
            'ip'             => $request->ip(),
            'ref_url'        => $request->headers->get('referer'),
            'plan_id'        => $selectedPlan?->id,
            'billing_cycle'  => $selectedPlan ? $request->billing_cycle : null,
            'account_type'   => $selectedPlan?->target_account_type,
        ]);

        // Send OTP
        if ($request->verify_via === 'email') {
            $this->sendEmailOtp($registration, $otp);
        } else {
            $this->sendPhoneOtp($registration, $otp);
        }

        // Store in session for OTP step
        session(['reg_id' => $registration->id]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('register.verify.otp');
    }

    // ─── Step 3: Show OTP entry page ─────────────────────────────────────────
    public function showOtpForm()
    {
        $regId = session('reg_id');
        if (!$regId) {
            return redirect()->route('register');
        }

        $registration = Registration::with('plan')->findOrFail($regId);

        if ($registration->isVerified()) {
            return view('frontend.register-pending-approval');
        }

        return view('frontend.register-verify-otp', compact('registration'));
    }

    // ─── Step 4: Verify OTP ───────────────────────────────────────────────────
    public function verifyOtp(
        Request $request,
        AccountProvisioningService $accountProvisioningService,
        AccountWelcomeEmailService $welcomeEmailService,
        StripeCheckoutService $stripeCheckoutService,
        CurrentAccountService $currentAccountService
    )
    {
        $request->validate([
            'otp' => 'required|digits:6',
        ]);

        $regId = session('reg_id');
        if (!$regId) {
            return redirect()->route('register');
        }

        $registration = Registration::with(['plan', 'account', 'user'])->findOrFail($regId);

        if ($registration->status === 'approved' && $registration->user && $registration->account) {
            Auth::login($registration->user);
            $currentAccountService->setCurrentAccount($registration->user, $registration->account->id);
            $welcomeEmailService->send($registration->account);

            return redirect()->route('backend.billing.index');
        }

        // Check expiry
        if ($registration->otp_expires_at && now()->isAfter($registration->otp_expires_at)) {
            return back()->withErrors(['otp' => 'OTP has expired. Please request a new one.']);
        }

        // Check code
        if ($registration->otp_code !== $request->otp) {
            return back()->withErrors(['otp' => 'Invalid OTP. Please try again.']);
        }

        $now = now();
        DB::beginTransaction();

        try {
            $registration->update([
                'otp_verified_at' => $now,
                'otp_code' => null,
                'email_verified_at' => $registration->verify_via === 'email' ? $now : $registration->email_verified_at,
                'phone_verified_at' => $registration->verify_via === 'phone' ? $now : $registration->phone_verified_at,
                'status' => 'verified',
            ]);

            $user = User::create([
                'first_name' => $registration->first_name,
                'last_name' => $registration->last_name,
                'email' => $registration->email,
                'phone' => $registration->phone,
                'user_type' => $registration->type,
                'status' => 1,
                'can_login' => 1,
                'password' => $registration->password_hash,
                'email_verified_at' => $registration->email_verified_at,
            ]);

            $roleName = $registration->account_type === 'landlord' ? 'Landlord' : 'Estate Agent';
            $role = Role::findByName($roleName);
            $user->assignRole($role);

            $account = $accountProvisioningService->provisionFromRegistration($registration, $user, $user);

            // Access remains restricted until Stripe confirms the subscription via webhook.
            $account->forceFill(['status' => 'suspended'])->save();

            $registration->update([
                'status' => 'approved',
                'approved_at' => $now,
                'user_id' => $user->id,
                'password_hash' => null,
            ]);

            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();
            Log::error('Self-service registration provisioning failed', [
                'registration_id' => $registration->id,
                'error' => $exception->getMessage(),
            ]);

            return back()->withErrors(['otp' => 'We could not create your account. Please try again or contact support.']);
        }

        session()->forget('reg_id');
        Auth::login($user);
        $currentAccountService->setCurrentAccount($user, $account->id);
        $welcomeEmailService->send($account);

        try {
            $checkoutUrl = $stripeCheckoutService->createPlanCheckoutSession(
                $account,
                $account->currentSubscription()->with('plan')->firstOrFail()
            );

            return redirect()->away($checkoutUrl);
        } catch (\Throwable $exception) {
            Log::error('Self-service Stripe checkout creation failed', [
                'registration_id' => $registration->id,
                'account_id' => $account->id,
                'error' => $exception->getMessage(),
            ]);

            return redirect()
                ->route('backend.billing.index')
                ->with('error', 'Your account was created, but checkout could not start. Please retry from Billing & Plan.');
        }
    }

    // ─── Resend OTP ───────────────────────────────────────────────────────────
    public function resendOtp(Request $request)
    {
        $regId = session('reg_id');
        if (!$regId) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Session expired. Please register again.'], 400);
            }
            return redirect()->route('register');
        }

        $registration = Registration::findOrFail($regId);

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $registration->update([
            'otp_code'       => $otp,
            'otp_expires_at' => now()->addMinutes(2),
        ]);

        if ($registration->verify_via === 'email') {
            $this->sendEmailOtp($registration, $otp);
            $msg = 'A new OTP has been sent to your email.';
        } else {
            $this->sendPhoneOtp($registration, $otp);
            $msg = 'A new OTP has been sent to your phone.';
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        flash($msg)->success();
        return back();
    }

    // ─── Send OTP via email ───────────────────────────────────────────────────
    private function sendEmailOtp(Registration $registration, string $otp): void
    {
        try {
            $name    = $registration->first_name;
            $appName = config('app.name');

            $subject = "Your {$appName} verification code: {$otp}";
            $content = "
                <p>Hi {$name},</p>
                <p>Your verification code for <strong>{$appName}</strong> is:</p>
                <p style='text-align:center; margin:24px 0;'>
                    <span style='font-size:36px; font-weight:bold; letter-spacing:8px;
                                 background:#f0f4ff; padding:12px 24px; border-radius:8px;
                                 display:inline-block; font-family:monospace;'>
                        {$otp}
                    </span>
                </p>
                <p>This code expires in <strong>2 minutes</strong>.</p>
                <p style='color:#6c757d; font-size:13px;'>
                    If you did not request this, please ignore this email.
                </p>
            ";

            Mail::to($registration->email)
                ->send(new MailManager([
                    'subject'     => $subject,
                    'content'     => $content,
                    'attachments' => [],
                ]));

            Log::info("Email OTP sent to {$registration->email}");
        } catch (\Exception $e) {
            Log::error("Failed to send email OTP: {$e->getMessage()}");
        }
    }

    // ─── Send OTP via SMS — delegates to active provider via SmsUtility ──────
    private function sendPhoneOtp(Registration $registration, string $otp): void
    {
        $phone = $this->normalizePhone($registration->phone ?? '');
        SmsUtility::phone_number_verification($phone, $otp);
    }

    // ─── Normalize phone to E.164 ─────────────────────────────────────────────
    private function normalizePhone(string $phone): string
    {
        $cleaned = preg_replace('/[^\d+]/', '', $phone);
        if (str_starts_with($cleaned, '+')) return $cleaned;
        if (strlen($cleaned) === 10)        return '+91' . $cleaned;
        if (strlen($cleaned) === 12 && str_starts_with($cleaned, '91')) return '+' . $cleaned;
        return '+' . $cleaned;
    }
}
