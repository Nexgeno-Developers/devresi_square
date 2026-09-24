<?php

namespace App\Console\Commands;

use App\Services\Saas\ProductionMoneyConfig;
use Illuminate\Console\Command;

class AssertProductionMoneyConfigCommand extends Command
{
    protected $signature = 'launch:assert-money-config
        {--env= : Override environment name (default: app.env)}
        {--strict : Always fail on any problem even in local}';

    protected $description = 'Assert production mail + dual Stripe config before Money Path dress-rehearsal (Step 45).';

    public function handle(ProductionMoneyConfig $config): int
    {
        $env = $this->option('env') ?: app()->environment();
        $problems = $config->problems($env);

        if ($problems === []) {
            $this->info("Money/mail config OK for env={$env}.");
            $this->line('See docs/launch/PRODUCTION_MAIL_AND_STRIPE.md for DNS + webhook + failed-payment runbook.');

            return self::SUCCESS;
        }

        $this->error("Money/mail config problems for env={$env}:");
        foreach ($problems as $problem) {
            $this->line(' - '.$problem);
        }

        $strict = (bool) $this->option('strict');
        $mustFail = $strict || in_array($env, ['production', 'staging'], true);

        return $mustFail ? self::FAILURE : self::SUCCESS;
    }
}
