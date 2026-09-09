<?php

namespace Tests\Feature;

use App\Mail\MailManager;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationOtpMailTest extends TestCase
{
    public function test_signup_email_otp_is_sent_through_the_configured_mailer(): void
    {
        Mail::fake();

        $planId = \DB::table('plans')
            ->where('is_active', 1)
            ->where('target_account_type', 'landlord')
            ->value('id');

        if (! $planId) {
            $this->markTestSkipped('No active landlord plan to start registration.');
        }

        $email = 'otp.mail.'.uniqid().'@gmail.com';

        $this->postJson(route('register.post'), [
            'first_name' => 'Otp',
            'last_name' => 'Tester',
            'email' => $email,
            'phone' => '',
            'type' => 'landlord',
            'verify_via' => 'email',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'plan_id' => $planId,
            'billing_cycle' => 'monthly',
            'account_type' => 'landlord',
        ])->assertOk()->assertJson(['success' => true]);

        Mail::assertSent(MailManager::class, function (MailManager $mail) use ($email) {
            return $mail->hasTo($email)
                && str_contains((string) $mail->array['subject'], 'verification code');
        });
    }
}
