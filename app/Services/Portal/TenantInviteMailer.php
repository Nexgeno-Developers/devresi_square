<?php

namespace App\Services\Portal;

use App\Mail\MailManager;
use App\Models\EmailTemplate;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TenantInviteMailer
{
    /**
     * @return array{sent: bool, error: ?string}
     */
    public function send(User $user, Property $property, bool $wasNew): array
    {
        try {
            $address = trim(implode(', ', array_filter([
                $property->line_1,
                $property->city,
                $property->postcode,
            ])));
            $resetLink = $wasNew ? $user->createResetLink() : '';
            $loginUrl = url('/login');

            $placeholders = [
                'tenant_name' => $user->name ?? $user->email,
                'tenant_email' => $user->email,
                'tenant_password' => 'Set via the link below',
                'property_name' => $property->prop_name ?: $property->line_1,
                'property_address' => $address !== '' ? $address : '—',
                'move_in_date' => now()->toFormattedDateString(),
                'rent' => '—',
                'reset_link' => $resetLink,
                'login_url' => $loginUrl,
                'crm_name' => config('app.name'),
                'admin_email' => config('mail.from.address'),
            ];

            if ($wasNew) {
                $template = EmailTemplate::getByIdentifier('tenant_account_created');
                $subject = 'Welcome to '.config('app.name').' — set your password';
                $fallback = '<p>Hi '.e($placeholders['tenant_name']).',</p>'
                    .'<p>Your landlord has invited you to the tenant portal for '.e($placeholders['property_address']).'.</p>'
                    .'<p>Email: '.e($placeholders['tenant_email']).'</p>'
                    .'<p><a href="'.e($resetLink).'">Set your password</a> to access your account.</p>';
                $rawKeys = ['reset_link', 'login_url'];
            } else {
                $template = EmailTemplate::getByIdentifier('tenant_welcome');
                $subject = 'Your tenancy at '.$placeholders['property_name'];
                $fallback = '<p>Hi '.e($placeholders['tenant_name']).',</p>'
                    .'<p>You have been added as a tenant at '.e($placeholders['property_address']).'.</p>'
                    .'<p><a href="'.e($loginUrl).'">Log in</a> to view your tenancy.</p>';
                $rawKeys = ['login_url'];
            }

            if ($template) {
                $renderedHtml = $template->replace($placeholders, $rawKeys);
                $subject = render_template($template->subject, $placeholders);
            } else {
                $renderedHtml = $fallback;
            }

            Mail::to($user->email)->send(new MailManager([
                'subject' => $subject,
                'content' => $renderedHtml,
                'attachments' => [],
            ]));

            return ['sent' => true, 'error' => null];
        } catch (\Throwable $exception) {
            Log::error('Tenant invite email failed: '.$exception->getMessage(), [
                'email' => $user->email,
                'user_id' => $user->id,
            ]);

            return ['sent' => false, 'error' => $exception->getMessage()];
        }
    }
}
