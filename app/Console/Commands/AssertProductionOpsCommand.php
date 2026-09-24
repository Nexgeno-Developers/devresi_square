<?php

namespace App\Console\Commands;

use App\Services\Ops\ProductionOpsConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AssertProductionOpsCommand extends Command
{
    protected $signature = 'launch:assert-ops
        {--env= : Override environment name (default: app.env)}
        {--strict : Always fail on any problem even in local}';

    protected $description = 'Assert queue/scheduler/ops readiness before GA (Step 46).';

    public function handle(ProductionOpsConfig $config): int
    {
        $env = $this->option('env') ?: app()->environment();
        $problems = $config->problems($env);

        $this->line('Expected scheduler entries:');
        foreach ($config->expectedScheduleSignatures() as $signature) {
            $this->line(' - '.$signature);
        }

        if (Schema::hasTable('failed_jobs')) {
            $failed = (int) DB::table('failed_jobs')->count();
            $this->line("failed_jobs count: {$failed}");
            if ($failed > 50 && in_array($env, ['production', 'staging'], true)) {
                $problems[] = "failed_jobs has {$failed} rows — investigate before GA (queue:failed / retry).";
            }
        } else {
            $problems[] = 'failed_jobs table missing — run queue failed-table migrations.';
        }

        if (Schema::hasTable('jobs') && config('queue.default') === 'database') {
            $pending = (int) DB::table('jobs')->count();
            $this->line("jobs pending: {$pending}");
            if ($pending > 500 && in_array($env, ['production', 'staging'], true)) {
                $problems[] = "jobs table depth {$pending} — workers may be down or overloaded.";
            }
        }

        Artisan::call('schedule:list');
        $scheduleOut = Artisan::output();
        foreach ($config->expectedScheduleSignatures() as $signature) {
            if (! str_contains($scheduleOut, $signature)) {
                $problems[] = "Scheduler missing expected command: {$signature}";
            }
        }

        if ($problems === []) {
            $this->info("Ops config OK for env={$env}.");
            $this->line('See docs/launch/OPS_RUNBOOK.md for workers, cron, backups, alerts, Super Admin.');

            return self::SUCCESS;
        }

        $this->error("Ops problems for env={$env}:");
        foreach ($problems as $problem) {
            $this->line(' - '.$problem);
        }

        $strict = (bool) $this->option('strict');
        $mustFail = $strict || in_array($env, ['production', 'staging'], true);

        return $mustFail ? self::FAILURE : self::SUCCESS;
    }
}
