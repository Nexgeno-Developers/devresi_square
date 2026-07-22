<?php

namespace App\Services\Saas;

use App\Mail\MailManager;
use App\Models\Account;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AccountWelcomeEmailService
{
    public function send(Account $account): bool
    {
        $account->loadMissing('owner');

        if ($account->registration_welcome_email_sent_at) {
            return true;
        }

        $recipient = $account->owner?->email ?: $account->billing_email;
        if (! $recipient) {
            Log::warning('Registration welcome email has no recipient', [
                'account_id' => $account->id,
            ]);

            return false;
        }

        $ownerName = trim((string) ($account->owner?->first_name ?: $account->owner?->name));
        $greetingName = e($ownerName ?: 'there');
        $appName = e((string) config('app.name'));
        $loginId = e((string) $recipient);
        $loginUrl = e(url('/login'));
        $passwordResetUrl = e(url('/password/forgot'));

        $content = "
            <p>Hi {$greetingName},</p>
            <p>Welcome to <strong>{$appName}</strong>. Your account has been created.</p>
            <p>Your login details are:</p>
            <table style='border-collapse:collapse; margin:16px 0;'>
                <tr>
                    <td style='padding:6px 12px; font-weight:bold; background:#f8f9fa; border:1px solid #dee2e6;'>Login ID</td>
                    <td style='padding:6px 12px; border:1px solid #dee2e6;'>{$loginId}</td>
                </tr>
                <tr>
                    <td style='padding:6px 12px; font-weight:bold; background:#f8f9fa; border:1px solid #dee2e6;'>Password</td>
                    <td style='padding:6px 12px; border:1px solid #dee2e6;'>Use the password you created during registration</td>
                </tr>
            </table>
            <p style='text-align:center; margin:24px 0;'>
                <a href='{$loginUrl}' style='background:#0b60bd; color:#fff; padding:12px 28px; border-radius:4px; text-decoration:none; font-size:15px;'>
                    Login to Your Account
                </a>
            </p>
            <p style='color:#6c757d; font-size:13px;'>
                Forgotten your password? <a href='{$passwordResetUrl}'>Reset it securely</a>.
            </p>
            <p>&mdash; The {$appName} Team</p>
        ";

        try {
            Mail::to($recipient)->send(new MailManager([
                'subject' => 'Welcome to '.config('app.name').' — Your Account is Ready',
                'content' => $content,
                'attachments' => [],
            ]));

            $account->forceFill([
                'registration_welcome_email_sent_at' => now(),
            ])->save();

            Log::info('Registration welcome email sent', [
                'account_id' => $account->id,
                'recipient' => $recipient,
            ]);

            return true;
        } catch (\Throwable $exception) {
            Log::error('Registration welcome email failed', [
                'account_id' => $account->id,
                'recipient' => $recipient,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
