<?php

namespace App\Services\Contractors;

use App\Mail\MailManager;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/** Mail for contractors and admins, who sit outside any landlord account. */
class ContractorMail
{
    /**
     * @param  array<int, array<string, mixed>>  $attachments
     */
    public function toUser(?User $user, string $subject, string $html, array $attachments = []): void
    {
        if (! $user?->email) {
            return;
        }
        $this->send($user->email, $subject, $html, $attachments);
    }

    public function toEmail(string $email, string $subject, string $html): void
    {
        $this->send($email, $subject, $html, []);
    }

    /** Super Admin alerts. A throttle key sends at most one alert per key per day. */
    public function toAdmins(string $subject, string $html, ?string $throttleKey = null): void
    {
        if ($throttleKey && ! Cache::add('contractor-admin-alert:'.$throttleKey.':'.now()->toDateString(), 1, now()->addDay())) {
            return;
        }
        $admins = User::role('Super Admin')->whereNotNull('email')->get();
        foreach ($admins as $admin) {
            $this->send($admin->email, $subject, $html, []);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $attachments
     */
    private function send(string $email, string $subject, string $html, array $attachments): void
    {
        try {
            Mail::to($email)->send(new MailManager([
                'subject' => $subject,
                'content' => $html,
                'attachments' => $attachments,
            ]));
        } catch (\Throwable $exception) {
            Log::error('Contractor email failed', ['email' => $email, 'subject' => $subject, 'error' => $exception->getMessage()]);
        }
    }
}
