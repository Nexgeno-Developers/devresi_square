<?php

namespace App\Services\Portal;

use App\Mail\MailManager;
use App\Models\AccountContractor;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ContractorInviteMailer
{
    /**
     * @return array{sent: bool, error: ?string}
     */
    public function sendSignup(string $email, ?string $name, User $landlord, AccountContractor $link): array
    {
        $signupUrl = route('register.contractor', ['invite' => $link->token]);
        $landlordName = $landlord->name ?: 'A landlord';
        $placeholders = [
            'contractor_name' => $name ?: 'there',
            'contractor_email' => $email,
            'landlord_name' => $landlordName,
            'signup_url' => $signupUrl,
            'login_url' => url('/login'),
            'crm_name' => config('app.name'),
            'admin_email' => config('mail.from.address'),
        ];
        $subject = $landlordName.' invited you to join '.config('app.name');
        $fallback = '<p>Hi '.e($placeholders['contractor_name']).',</p>'
            .'<p>'.e($landlordName).' invited you to join '.e(config('app.name')).' as a contractor so they can send you repair jobs.</p>'
            .'<p>Email: '.e($email).'</p>'
            .'<p><a href="'.e($signupUrl).'">Sign up and join</a></p>'
            .'<p>Create your account, add your documents, and once approved you will be tagged as their contractor.</p>';

        return $this->deliver($email, 'contractor_invite', $subject, $fallback, $placeholders, ['signup_url', 'login_url']);
    }

    /**
     * @return array{sent: bool, error: ?string}
     */
    public function sendExisting(User $user, User $landlord, int $accountId): array
    {
        $loginUrl = url('/login');
        $landlordName = $landlord->name ?: 'A landlord';
        $placeholders = [
            'contractor_name' => $user->name ?: $user->email,
            'contractor_email' => $user->email,
            'landlord_name' => $landlordName,
            'signup_url' => $loginUrl,
            'login_url' => $loginUrl,
            'crm_name' => config('app.name'),
            'admin_email' => config('mail.from.address'),
        ];
        $subject = $landlordName.' added you as their contractor';
        $fallback = '<p>Hi '.e($placeholders['contractor_name']).',</p>'
            .'<p>'.e($landlordName).' added you to their contractor list on '.e(config('app.name')).'.</p>'
            .'<p><a href="'.e($loginUrl).'">Log in</a> to see their jobs once you are approved for the trade.</p>';

        $result = $this->deliver((string) $user->email, 'contractor_added', $subject, $fallback, $placeholders, ['login_url', 'signup_url']);
        $this->inApp($accountId, $user, $landlordName);

        return $result;
    }

    /**
     * @param  array<string, string>  $placeholders
     * @param  array<int, string>  $rawKeys
     * @return array{sent: bool, error: ?string}
     */
    private function deliver(string $email, string $identifier, string $subject, string $fallback, array $placeholders, array $rawKeys): array
    {
        try {
            $template = EmailTemplate::getByIdentifier($identifier);
            if ($template) {
                $renderedHtml = $template->replace($placeholders, $rawKeys);
                $subject = render_template($template->subject, $placeholders);
            } else {
                $renderedHtml = $fallback;
            }

            Mail::to($email)->send(new MailManager([
                'subject' => $subject,
                'content' => $renderedHtml,
                'attachments' => [],
            ]));

            return ['sent' => true, 'error' => null];
        } catch (\Throwable $exception) {
            Log::error('Contractor invite email failed: '.$exception->getMessage(), [
                'email' => $email,
                'identifier' => $identifier,
            ]);

            return ['sent' => false, 'error' => $exception->getMessage()];
        }
    }

    private function inApp(int $accountId, User $user, string $landlordName): void
    {
        if ($accountId <= 0) {
            return;
        }

        try {
            DB::table('notifications')->insert([
                'id' => (string) Str::uuid(),
                'account_id' => $accountId,
                'type' => 'crm_event',
                'event_key' => 'contractor.invited',
                'category' => 'repairs',
                'priority' => 'high',
                'action_url' => route('contractor.jobs.index'),
                'notifiable_type' => $user->getMorphClass(),
                'notifiable_id' => $user->getKey(),
                'data' => json_encode([
                    'event_key' => 'contractor.invited',
                    'title' => 'A landlord added you',
                    'message' => $landlordName.' added you to their contractor list.',
                    'url' => route('contractor.jobs.index'),
                    'category' => 'repairs',
                    'priority' => 'high',
                ], JSON_THROW_ON_ERROR),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            Log::error('Contractor invite in-app notice failed: '.$exception->getMessage(), [
                'user_id' => $user->id,
                'account_id' => $accountId,
            ]);
        }
    }
}
