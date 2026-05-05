<?php

namespace App\Console\Commands;

use App\Jobs\SendNotificationJob;
use App\Models\NotificationLog;
use Illuminate\Console\Command;

class RetryNotificationsCommand extends Command
{
    protected $signature = 'notifications:retry {--limit=500}';

    protected $description = 'Retry failed notification logs that have remaining attempts.';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        if ($limit <= 0) {
            $limit = 500;
        }

        $logs = NotificationLog::query()
            ->where('status', 'failed')
            ->whereColumn('attempt', '<', 'max_attempts')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($logs as $log) {
            SendNotificationJob::dispatch($log);
        }

        $this->info("Dispatched {$logs->count()} notification(s) for retry.");

        return self::SUCCESS;
    }
}

