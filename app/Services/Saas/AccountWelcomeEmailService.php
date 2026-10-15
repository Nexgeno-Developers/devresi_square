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
        $trialDays = (int) ($account->subscriptions()->latest('id')->first()?->plan?->trial_days ?: 7);
        $onTrial = $account->status === 'trialing' || ($account->trial_ends_at && $account->trial_ends_at->isFuture());
        $trialLine = $onTrial
            ? 'Your workspace is set up and your <strong>'.$trialDays.'-day Pro trial</strong> is active.'
            : 'Your workspace is set up.';
        $addProperty = e(route('admin.properties.create'));
        $tenancies = e(route('admin.tenancies.create'));
        $dashboard = e(route('backend.dashboard'));
        $walkthrough = e(config('services.calendly.url') ?: route('home'));

        $content = "
            <p>Hi {$greetingName},</p>
            <p>Welcome to ResiSquare. {$trialLine}</p>
            <p>ResiSquare brings your entire property operation into one connected workspace—giving you a single source of truth for every property, tenancy, rent ledger, and repair.</p>
            <h3>Get started in 3 quick steps:</h3>
            <ol>
                <li><strong>Add your first property:</strong> Create a property record manually or import your portfolio via CSV.<br>
                    <a href=\"{$addProperty}\">Add Property</a></li>
                <li><strong>Attach a tenancy:</strong> <a href=\"{$tenancies}\">Connect tenant details</a>, contract terms, rent schedules, and deposits directly to the property.</li>
                <li><strong>Check your dashboard:</strong> <a href=\"{$dashboard}\">Monitor occupancy, rent collections, and maintenance alerts</a> in one clear view.</li>
            </ol>
            <p>Need help getting started? <a href=\"{$walkthrough}\">Book a live 15-minute walkthrough</a> or reply to this email.</p>
            <p>Welcome aboard,<br>The ResiSquare Team</p>
        ";

        try {
            Mail::to($recipient)->send(new MailManager([
                'subject' => 'Welcome to ResiSquare',
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
