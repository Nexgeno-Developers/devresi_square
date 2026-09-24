<?php

namespace App\Services\Ops;

/**
 * Launch Step 46 — queue / scheduler / health readiness checks.
 */
class ProductionOpsConfig
{
    /**
     * @return list<string>
     */
    public function problems(?string $env = null): array
    {
        $env = $env ?? (string) app()->environment();
        $problems = [];

        if (! in_array($env, ['production', 'staging'], true)) {
            return $problems;
        }

        $queue = strtolower((string) config('queue.default'));
        if (in_array($queue, ['', 'sync', 'null'], true)) {
            $problems[] = "QUEUE_CONNECTION={$queue} will not run background mail/notifications in {$env}; use database, redis, or sqs with supervised workers.";
        }

        if (config('app.debug') === true) {
            $problems[] = 'APP_DEBUG must be false in '.$env.'.';
        }

        $url = (string) config('app.url');
        if ($url === '' || str_contains($url, 'localhost') || str_contains($url, '127.0.0.1')) {
            $problems[] = 'APP_URL must be the public https origin in '.$env.' (needed for signed links, Stripe returns, invites).';
        }

        if (! str_starts_with($url, 'https://') && $env === 'production') {
            $problems[] = 'APP_URL must use https:// in production.';
        }

        return $problems;
    }

    public function isReady(?string $env = null): bool
    {
        return $this->problems($env) === [];
    }

    /**
     * @return list<string> Scheduled command signatures from bootstrap/app.php
     */
    public function expectedScheduleSignatures(): array
    {
        return [
            'sale-invoices:generate-recurring',
            'rent-invoices:generate-recurring',
            'crm-notifications:send-due',
            'notifications:retry',
            'events:send-reminders',
            'sale-invoices:apply-penalties',
        ];
    }
}
