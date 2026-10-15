<?php

namespace App\Services\Auth;

use App\Models\EmailChangeToken;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Services\Notifications\SecurityNotice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EmailChangeService
{
    public function __construct(private readonly SecurityNotice $notices)
    {
    }

    /**
     * @return 'unchanged'|'sent'|'failed'
     */
    public function request(User $user, string $email): string
    {
        $next = strtolower(trim($email));
        $current = strtolower(trim((string) $user->email));
        if ($next === '' || $next === $current) {
            return 'unchanged';
        }

        $user->forceFill(['pending_email' => $next])->save();

        EmailChangeToken::query()->where('user_id', $user->id)->delete();
        $plain = Str::random(64);
        EmailChangeToken::query()->create([
            'user_id' => $user->id,
            'email' => $next,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addHours(72),
        ]);

        $link = route('email.confirm', ['token' => $plain]);
        $sent = $this->notices->emailTo($next, 'Confirm your email', $this->body($user, $link));

        return $sent ? 'sent' : 'failed';
    }

    public function confirm(string $plain): User
    {
        $hash = hash('sha256', $plain);

        return DB::transaction(function () use ($hash) {
            $token = EmailChangeToken::query()->where('token_hash', $hash)->lockForUpdate()->first();
            abort_unless($token && hash_equals($token->token_hash, $hash), 404);
            abort_if($token->expires_at->isPast(), 410);

            $user = User::query()->whereKey($token->user_id)->lockForUpdate()->firstOrFail();
            $pending = strtolower(trim((string) $user->pending_email));
            abort_unless(
                $pending !== '' && hash_equals(hash('sha256', $pending), hash('sha256', strtolower($token->email))),
                404
            );
            abort_if(
                User::query()->where('email', $token->email)->where('id', '!=', $user->id)->exists(),
                409
            );

            $user->forceFill([
                'email' => $token->email,
                'pending_email' => null,
                'email_verified_at' => now(),
            ])->save();
            EmailChangeToken::query()->where('user_id', $user->id)->delete();
            $this->notices->endOtherSessions($user);

            return $user;
        });
    }

    private function body(User $user, string $link): string
    {
        $template = EmailTemplate::getByIdentifier('email_change_confirmation');
        $placeholders = [
            'tenant_name' => $user->name ?: $user->email,
            'confirm_link' => $link,
            'crm_name' => config('app.name'),
        ];
        $html = $template
            ? $template->replace($placeholders, ['confirm_link'])
            : '<p>Hi '.e($placeholders['tenant_name']).',</p>'
                .'<p>Confirm this email address to use it for your Resisquare login.</p>'
                .'<p>The link works for 72 hours and only once. Until you open it, sign in with your current email.</p>';
        if (! str_contains($html, $link)) {
            $html .= '<p><a href="'.e($link).'">Confirm email</a></p>';
        }

        return $html;
    }
}
