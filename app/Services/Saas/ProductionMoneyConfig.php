<?php

namespace App\Services\Saas;

/**
 * Launch Step 45 — production mail + dual Stripe readiness checks.
 * Run via `php artisan launch:assert-money-config` before staging dress-rehearsal / GA.
 */
class ProductionMoneyConfig
{
    /**
     * @return list<string> Human-readable problems (empty = OK for current env)
     */
    public function problems(?string $env = null): array
    {
        $env = $env ?? (string) app()->environment();
        $problems = [];

        $mailer = strtolower((string) config('mail.default'));
        $from = strtolower((string) config('mail.from.address'));

        if (in_array($env, ['production', 'staging'], true)) {
            if (in_array($mailer, ['', 'log', 'array'], true)) {
                $problems[] = "MAIL_MAILER={$mailer} will not deliver OTP/invite mail in {$env}; use smtp, ses, postmark, or mailgun.";
            }

            if ($from === '' || str_contains($from, 'example.com') || $from === 'hello@example.com') {
                $problems[] = 'MAIL_FROM_ADDRESS must be a real address on a domain with SPF/DKIM/DMARC.';
            }

            $opSecret = (string) config('services.stripe.secret');
            $opWebhook = (string) config('services.stripe.webhook_secret');
            if ($opSecret === '' || $opWebhook === '') {
                $problems[] = 'Operating Stripe STRIPE_SECRET and STRIPE_WEBHOOK_SECRET are required.';
            }

            $rentSecret = (string) config('services.stripe.rent.secret');
            $rentWebhook = (string) config('services.stripe.rent.webhook_secret');
            if ($rentSecret === '' || $rentWebhook === '') {
                $problems[] = 'Client-money Stripe STRIPE_RENT_SECRET and STRIPE_RENT_WEBHOOK_SECRET are required in '.$env.'.';
            }

            if ($env === 'production' && $opSecret !== '' && $rentSecret !== '' && hash_equals($opSecret, $rentSecret)) {
                $problems[] = 'Production must use separate Stripe accounts: STRIPE_RENT_SECRET must not equal STRIPE_SECRET (client money vs operating).';
            }

            if ($env === 'production' && $opWebhook !== '' && $rentWebhook !== '' && hash_equals($opWebhook, $rentWebhook)) {
                $problems[] = 'Production must use separate webhook secrets: STRIPE_RENT_WEBHOOK_SECRET must not equal STRIPE_WEBHOOK_SECRET.';
            }
        }

        return $problems;
    }

    public function isReady(?string $env = null): bool
    {
        return $this->problems($env) === [];
    }
}
